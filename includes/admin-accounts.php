<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/users.php';

/**
 * Données et actions du tableau de bord admin, sourcées depuis MySQL.
 * Remplace includes/notion-admin.php.
 */

const ADMIN_BILLING_STATUSES = ['Facturé', 'Payé', 'En cours', 'En attente', 'Dispensé'];

function listAllAccounts(): array
{
    $rows = db()->query(
        'SELECT c.id, c.nom_complet, c.nom_entreprise, c.type_client, c.telephone,
                c.location, c.job_domaine, c.linkedin, c.commandes_libres, a.email, a.derniere_connexion
         FROM clients c
         JOIN comptes a ON a.id = c.compte_id
         WHERE a.mot_de_passe_hash IS NOT NULL AND a.mot_de_passe_hash != \'\'
         ORDER BY c.id ASC'
    )->fetchAll();

    if (!$rows) return [];

    $ids = array_column($rows, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Prestations par client (toutes les lignes, dans l'ordre de création) :
    // la dernière sert au résumé de la table (service/prix/facturation), la
    // liste complète alimente la gestion détaillée (ajout/édition/suppression).
    $latestByClient = [];
    $prestationsByClient = [];
    $pStmt = db()->prepare(
        "SELECT id, client_id, type_service, prix, statut_facturation, description, date_debut, date_fin
         FROM prestations WHERE client_id IN ($placeholders) ORDER BY id ASC"
    );
    $pStmt->execute($ids);
    $ordersByClient = [];
    foreach ($pStmt->fetchAll() as $p) {
        $latestByClient[$p['client_id']] = $p; // overwritten going forward = keeps the latest (ids ascending)
        if ($p['description'] !== null) {
            $ordersByClient[$p['client_id']][] = $p['description'];
        }
        $prestationsByClient[$p['client_id']][] = [
            'id'                 => (int)$p['id'],
            'description'        => $p['description'] ?? '',
            'type_service'       => $p['type_service'] ?? '',
            'prix'               => $p['prix'] !== null ? (float)$p['prix'] : null,
            'statut_facturation' => $p['statut_facturation'] ?? '',
            'date_debut'         => $p['date_debut'],
            'date_fin'           => $p['date_fin'],
        ];
    }

    // Factures par client.
    $invByClient = [];
    $iStmt = db()->prepare(
        "SELECT id, client_id, nom_fichier FROM factures WHERE client_id IN ($placeholders) ORDER BY id ASC"
    );
    $iStmt->execute($ids);
    foreach ($iStmt->fetchAll() as $f) {
        $invByClient[$f['client_id']][] = [
            'name' => $f['nom_fichier'],
            'url'  => '/api/admin-view-invoice.php?id=' . (int)$f['id'],
        ];
    }

    $accounts = [];
    foreach ($rows as $c) {
        $id = $c['id'];
        $prestation = $latestByClient[$id] ?? [];
        $invoiceFiles = $invByClient[$id] ?? [];

        $orders = !empty($c['commandes_libres']) ? $c['commandes_libres'] : implode("\n", $ordersByClient[$id] ?? []);

        $accounts[] = [
            'id'           => $id,
            'name'         => $c['nom_complet'] ?: 'N.A',
            'email'        => $c['email'],
            'company'      => $c['nom_entreprise'] ?? '',
            'service'      => $prestation['type_service'] ?? '',
            'role'         => resolveUserRole($c['type_client']),
            'billing'      => $prestation['statut_facturation'] ?? '',
            'price'        => isset($prestation['prix']) ? (float)$prestation['prix'] : null,
            'lastLogin'    => $c['derniere_connexion'],
            'invoiceCount' => count($invoiceFiles),
            'invoiceFiles' => $invoiceFiles,
            'phone'        => $c['telephone'] ?? '',
            'location'     => $c['location'] ?? '',
            'jobDomaine'   => $c['job_domaine'] ?? '',
            'linkedin'     => $c['linkedin'] ?? '',
            'orders'       => $orders,
            'prestations'  => $prestationsByClient[$id] ?? [],
        ];
    }

    return $accounts;
}

function updateAccountRole(int $clientId, string $role): bool
{
    if (!in_array($role, ['particulier', 'entreprise', 'admin'], true)) {
        error_log('[SlapIA Admin] updateAccountRole rejected invalid role: ' . $role);
        return false;
    }

    try {
        $stmt = db()->prepare('UPDATE clients SET type_client = :v WHERE id = :id');
        $stmt->execute(['v' => roleToTypeClient($role), 'id' => $clientId]);
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Admin] updateAccountRole failed for client ' . $clientId . ': ' . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour le statut de facturation de la prestation la plus récente du
 * client ; en crée une (sans service/prix) s'il n'en a encore aucune.
 */
function updateAccountBilling(int $clientId, string $billing): bool
{
    if (!in_array($billing, ADMIN_BILLING_STATUSES, true)) {
        error_log('[SlapIA Admin] updateAccountBilling rejected invalid status: ' . $billing);
        return false;
    }

    try {
        $pdo = db();
        $latest = $pdo->prepare('SELECT id FROM prestations WHERE client_id = :id ORDER BY id DESC LIMIT 1');
        $latest->execute(['id' => $clientId]);
        $prestationId = $latest->fetchColumn();

        if ($prestationId) {
            $stmt = $pdo->prepare('UPDATE prestations SET statut_facturation = :b WHERE id = :id');
            $stmt->execute(['b' => $billing, 'id' => $prestationId]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO prestations (client_id, statut_facturation) VALUES (:client_id, :b)'
            );
            $stmt->execute(['client_id' => $clientId, 'b' => $billing]);
        }
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Admin] updateAccountBilling failed for client ' . $clientId . ': ' . $e->getMessage());
        return false;
    }
}

function listRssSubscribers(): array
{
    $rows = db()->query('SELECT email, date_creation FROM rss_subscriber ORDER BY date_creation DESC')->fetchAll();
    return array_map(fn($r) => ['email' => $r['email'], 'subscribedAt' => $r['date_creation']], $rows);
}

/** Réutilise la même logique de hash que le flux de reset public. */
function resetAccountPassword(int $clientId, string $newPassword): bool
{
    return updatePassword($clientId, $newPassword);
}

/**
 * Enregistre une facture PDF sur disque (storage/invoices/) et l'attache au
 * client via une ligne dans la table factures. Ne remplace jamais les
 * factures existantes (comportement identique à l'ancien uploadInvoiceFile).
 */
function uploadInvoiceFile(int $clientId, string $localFilePath, string $filename, string $mimeType): bool
{
    $dir = storagePath('invoices/' . $clientId);
    $storedName = uniqid('inv_', true) . '.pdf';
    $dest = $dir . '/' . $storedName;

    if (!@copy($localFilePath, $dest)) {
        error_log('[SlapIA Admin] uploadInvoiceFile: copy failed for client ' . $clientId);
        return false;
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO factures (client_id, nom_fichier, chemin_fichier, mime_type, taille_octets)
             VALUES (:client_id, :nom, :chemin, :mime, :taille)'
        );
        $stmt->execute([
            'client_id' => $clientId,
            'nom'       => $filename,
            'chemin'    => 'invoices/' . $clientId . '/' . $storedName,
            'mime'      => $mimeType,
            'taille'    => @filesize($dest) ?: null,
        ]);
        return true;
    } catch (Throwable $e) {
        @unlink($dest);
        error_log('[SlapIA Admin] uploadInvoiceFile DB insert failed for client ' . $clientId . ': ' . $e->getMessage());
        return false;
    }
}

/**
 * Ancienne version du formulaire "Détails" (phone/location/orders seulement).
 * Remplacée par updateClientProfile(), qui couvre la fiche complète — conservée
 * pour compatibilité mais plus appelée par admin.js.
 */
function updateAccountContactDetails(int $clientId, string $phone, string $location, string $orders): bool
{
    $phone = trim($phone);
    if ($phone !== '' && !preg_match('/^[0-9+\-.() ]{1,30}$/', $phone)) {
        error_log('[SlapIA Admin] updateAccountContactDetails rejected invalid phone for client ' . $clientId);
        return false;
    }

    $location = trim($location);
    $orders   = trim($orders);
    if (mb_strlen($location) > 500 || mb_strlen($orders) > 2000) {
        error_log('[SlapIA Admin] updateAccountContactDetails rejected oversized field for client ' . $clientId);
        return false;
    }

    try {
        $stmt = db()->prepare(
            'UPDATE clients SET telephone = :phone, location = :location, commandes_libres = :orders WHERE id = :id'
        );
        $stmt->execute([
            'phone'    => $phone !== '' ? $phone : null,
            'location' => $location !== '' ? $location : null,
            'orders'   => $orders !== '' ? $orders : null,
            'id'       => $clientId,
        ]);
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Admin] updateAccountContactDetails failed for client ' . $clientId . ': ' . $e->getMessage());
        return false;
    }
}

// ─────────────────────────────────────────────────────────────────────────
//  Administration complète depuis le panneau admin (plus besoin de phpMyAdmin)
// ─────────────────────────────────────────────────────────────────────────

/** Mot de passe lisible (sans caractères ambigus) pour la création de compte. */
function generateRandomPassword(int $length = 12): string
{
    $charset = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $max = strlen($charset) - 1;
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $charset[random_int(0, $max)];
    }
    return $password;
}

/**
 * Crée un compte + fiche client en une transaction (onboarding depuis
 * l'admin, sans passer par phpMyAdmin). Génère un mot de passe si aucun
 * n'est fourni ; celui-ci n'est jamais stocké en clair ni journalisé — il
 * n'est retourné qu'une fois, à l'appelant, pour affichage immédiat.
 *
 * @return array{success:bool, client_id?:int, password?:string, error?:string}
 *         error ∈ invalid_fields|invalid_password|duplicate_email|server_error
 */
function createClient(array $fields): array
{
    $email = strtolower(trim($fields['email'] ?? ''));
    $name  = trim($fields['nom_complet'] ?? '');
    $role  = $fields['role'] ?? 'particulier';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || mb_strlen($name) > 255) {
        return ['success' => false, 'error' => 'invalid_fields'];
    }
    if (!in_array($role, ['particulier', 'entreprise', 'admin'], true)) {
        return ['success' => false, 'error' => 'invalid_fields'];
    }

    $company  = trim($fields['nom_entreprise'] ?? '');
    $phone    = trim($fields['telephone'] ?? '');
    $location = trim($fields['location'] ?? '');
    $job      = trim($fields['job_domaine'] ?? '');
    $linkedin = trim($fields['linkedin'] ?? '');

    if ($phone !== '' && !preg_match('/^[0-9+\-.() ]{1,30}$/', $phone)) {
        return ['success' => false, 'error' => 'invalid_fields'];
    }
    if ($linkedin !== '' && !preg_match('#^https?://#i', $linkedin)) {
        return ['success' => false, 'error' => 'invalid_fields'];
    }
    if (mb_strlen($company) > 255 || mb_strlen($location) > 500 || mb_strlen($job) > 255) {
        return ['success' => false, 'error' => 'invalid_fields'];
    }

    $password = trim($fields['password'] ?? '');
    if ($password === '') {
        $password = generateRandomPassword();
    } elseif (strlen($password) < 8) {
        return ['success' => false, 'error' => 'invalid_password'];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $existing = $pdo->prepare('SELECT id FROM comptes WHERE email = :email LIMIT 1');
        $existing->execute(['email' => $email]);
        if ($existing->fetchColumn()) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'duplicate_email'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $insertAccount = $pdo->prepare(
            'INSERT INTO comptes (email, mot_de_passe_hash, mail_avis) VALUES (:email, :hash, 1)'
        );
        $insertAccount->execute(['email' => $email, 'hash' => $hash]);
        $compteId = (int)$pdo->lastInsertId();

        $insertClient = $pdo->prepare(
            'INSERT INTO clients (compte_id, nom_complet, nom_entreprise, telephone, location, job_domaine, linkedin, type_client)
             VALUES (:compte_id, :nom, :company, :phone, :location, :job, :linkedin, :type_client)'
        );
        $insertClient->execute([
            'compte_id'   => $compteId,
            'nom'         => $name,
            'company'     => $company !== '' ? $company : null,
            'phone'       => $phone !== '' ? $phone : null,
            'location'    => $location !== '' ? $location : null,
            'job'         => $job !== '' ? $job : null,
            'linkedin'    => $linkedin !== '' ? $linkedin : null,
            'type_client' => roleToTypeClient($role),
        ]);
        $clientId = (int)$pdo->lastInsertId();

        $pdo->commit();
        return ['success' => true, 'client_id' => $clientId, 'password' => $password];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[SlapIA Admin] createClient failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'server_error'];
    }
}

/**
 * Met à jour la fiche client complète (identité, contact, commandes) ainsi
 * que l'email du compte lié, en une transaction. Remplace
 * updateAccountContactDetails() pour le nouveau panneau "Détails".
 *
 * @return array{success:bool, error?:string} error ∈ invalid_fields|not_found|duplicate_email|server_error
 */
function updateClientProfile(int $clientId, array $fields): array
{
    $name = trim($fields['nom_complet'] ?? '');
    if ($name === '' || mb_strlen($name) > 255) {
        return ['success' => false, 'error' => 'invalid_fields'];
    }

    $email = strtolower(trim($fields['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'invalid_fields'];
    }

    $company  = trim($fields['nom_entreprise'] ?? '');
    $phone    = trim($fields['telephone'] ?? '');
    $location = trim($fields['location'] ?? '');
    $job      = trim($fields['job_domaine'] ?? '');
    $linkedin = trim($fields['linkedin'] ?? '');
    $orders   = trim($fields['orders'] ?? '');

    if ($phone !== '' && !preg_match('/^[0-9+\-.() ]{1,30}$/', $phone)) {
        return ['success' => false, 'error' => 'invalid_fields'];
    }
    if ($linkedin !== '' && !preg_match('#^https?://#i', $linkedin)) {
        return ['success' => false, 'error' => 'invalid_fields'];
    }
    if (mb_strlen($company) > 255 || mb_strlen($location) > 500 || mb_strlen($job) > 255 || mb_strlen($orders) > 2000) {
        return ['success' => false, 'error' => 'invalid_fields'];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $compteStmt = $pdo->prepare('SELECT compte_id FROM clients WHERE id = :id LIMIT 1');
        $compteStmt->execute(['id' => $clientId]);
        $compteId = $compteStmt->fetchColumn();
        if (!$compteId) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'not_found'];
        }

        $dupe = $pdo->prepare('SELECT id FROM comptes WHERE email = :email AND id != :compte_id LIMIT 1');
        $dupe->execute(['email' => $email, 'compte_id' => $compteId]);
        if ($dupe->fetchColumn()) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'duplicate_email'];
        }

        $updateAccount = $pdo->prepare('UPDATE comptes SET email = :email WHERE id = :id');
        $updateAccount->execute(['email' => $email, 'id' => $compteId]);

        $updateClient = $pdo->prepare(
            'UPDATE clients SET nom_complet = :nom, nom_entreprise = :company, telephone = :phone,
                    location = :location, job_domaine = :job, linkedin = :linkedin, commandes_libres = :orders
             WHERE id = :id'
        );
        $updateClient->execute([
            'nom'      => $name,
            'company'  => $company !== '' ? $company : null,
            'phone'    => $phone !== '' ? $phone : null,
            'location' => $location !== '' ? $location : null,
            'job'      => $job !== '' ? $job : null,
            'linkedin' => $linkedin !== '' ? $linkedin : null,
            'orders'   => $orders !== '' ? $orders : null,
            'id'       => $clientId,
        ]);

        $pdo->commit();
        return ['success' => true];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[SlapIA Admin] updateClientProfile failed for client ' . $clientId . ': ' . $e->getMessage());
        return ['success' => false, 'error' => 'server_error'];
    }
}

// ── Prestations : lignes de service individuelles par client ──────────────

/** Valide et normalise les champs d'une prestation ; null si invalide. */
function validatePrestationFields(array $fields): ?array
{
    $type        = trim($fields['type_service'] ?? '');
    $description = trim($fields['description'] ?? '');
    $prix        = $fields['prix'] ?? null;
    $statut      = $fields['statut_facturation'] ?? null;
    $dateDebut   = trim((string)($fields['date_debut'] ?? ''));
    $dateFin     = trim((string)($fields['date_fin'] ?? ''));

    if ($prix !== null && $prix !== '') {
        if (!is_numeric($prix) || (float)$prix < 0) return null;
        $prix = round((float)$prix, 2);
    } else {
        $prix = null;
    }

    if ($statut !== null && $statut !== '' && !in_array($statut, ADMIN_BILLING_STATUSES, true)) {
        return null;
    }
    $statut = ($statut === '' ? null : $statut);

    foreach ([$dateDebut, $dateFin] as $d) {
        if ($d !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return null;
    }

    if (mb_strlen($type) > 255 || mb_strlen($description) > 500) return null;

    return [
        'type_service'       => $type !== '' ? $type : null,
        'description'        => $description !== '' ? $description : null,
        'prix'               => $prix,
        'statut_facturation' => $statut,
        'date_debut'         => $dateDebut !== '' ? $dateDebut : null,
        'date_fin'           => $dateFin !== '' ? $dateFin : null,
    ];
}

function addPrestation(int $clientId, array $fields): array
{
    $clean = validatePrestationFields($fields);
    if ($clean === null) return ['success' => false, 'error' => 'invalid_fields'];

    try {
        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO prestations (client_id, description, type_service, prix, statut_facturation, date_debut, date_fin)
             VALUES (:client_id, :description, :type_service, :prix, :statut, :date_debut, :date_fin)'
        );
        $stmt->execute([
            'client_id'    => $clientId,
            'description'  => $clean['description'],
            'type_service' => $clean['type_service'],
            'prix'         => $clean['prix'],
            'statut'       => $clean['statut_facturation'],
            'date_debut'   => $clean['date_debut'],
            'date_fin'     => $clean['date_fin'],
        ]);
        return ['success' => true, 'id' => (int)$pdo->lastInsertId()];
    } catch (Throwable $e) {
        error_log('[SlapIA Admin] addPrestation failed for client ' . $clientId . ': ' . $e->getMessage());
        return ['success' => false, 'error' => 'server_error'];
    }
}

function updatePrestation(int $prestationId, array $fields): array
{
    $clean = validatePrestationFields($fields);
    if ($clean === null) return ['success' => false, 'error' => 'invalid_fields'];

    try {
        $stmt = db()->prepare(
            'UPDATE prestations SET description = :description, type_service = :type_service, prix = :prix,
                    statut_facturation = :statut, date_debut = :date_debut, date_fin = :date_fin
             WHERE id = :id'
        );
        $stmt->execute([
            'description'  => $clean['description'],
            'type_service' => $clean['type_service'],
            'prix'         => $clean['prix'],
            'statut'       => $clean['statut_facturation'],
            'date_debut'   => $clean['date_debut'],
            'date_fin'     => $clean['date_fin'],
            'id'           => $prestationId,
        ]);
        return ['success' => true];
    } catch (Throwable $e) {
        error_log('[SlapIA Admin] updatePrestation failed for prestation ' . $prestationId . ': ' . $e->getMessage());
        return ['success' => false, 'error' => 'server_error'];
    }
}

function deletePrestation(int $prestationId): bool
{
    try {
        $stmt = db()->prepare('DELETE FROM prestations WHERE id = :id');
        $stmt->execute(['id' => $prestationId]);
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Admin] deletePrestation failed for prestation ' . $prestationId . ': ' . $e->getMessage());
        return false;
    }
}

// ── Avis clients : modération depuis l'admin ──────────────────────────────

function listAdminReviews(): array
{
    $rows = db()->query(
        'SELECT a.id, a.client_id, a.prenom_nom, a.satisfaction, a.commentaire, a.created_at,
                c.nom_complet AS client_name
         FROM avis_clients a
         LEFT JOIN clients c ON c.id = a.client_id
         ORDER BY a.created_at DESC, a.id DESC'
    )->fetchAll();

    return array_map(function ($r) {
        return [
            'id'           => (int)$r['id'],
            'clientId'     => $r['client_id'] !== null ? (int)$r['client_id'] : null,
            'clientName'   => $r['client_name'] ?? '',
            'name'         => $r['prenom_nom'],
            'satisfaction' => $r['satisfaction'] !== null ? (int)$r['satisfaction'] : null,
            'comment'      => $r['commentaire'] ?? '',
            'createdAt'    => $r['created_at'],
        ];
    }, $rows);
}

/** @return array{success:bool, error?:string} error ∈ invalid_fields|server_error */
function adminUpdateReview(int $reviewId, string $name, string $comment, ?int $satisfaction): array
{
    $name    = trim($name);
    $comment = trim($comment);
    if ($name === '' || mb_strlen($name) > 255 || mb_strlen($comment) > 2000) {
        return ['success' => false, 'error' => 'invalid_fields'];
    }
    if ($satisfaction !== null && ($satisfaction < 1 || $satisfaction > 5)) {
        return ['success' => false, 'error' => 'invalid_fields'];
    }

    try {
        $stmt = db()->prepare(
            'UPDATE avis_clients SET prenom_nom = :nom, commentaire = :comment, satisfaction = :sat WHERE id = :id'
        );
        $stmt->execute([
            'nom'     => $name,
            'comment' => $comment !== '' ? $comment : null,
            'sat'     => $satisfaction,
            'id'      => $reviewId,
        ]);
        return ['success' => true];
    } catch (Throwable $e) {
        error_log('[SlapIA Admin] adminUpdateReview failed for review ' . $reviewId . ': ' . $e->getMessage());
        return ['success' => false, 'error' => 'server_error'];
    }
}

function adminDeleteReview(int $reviewId): bool
{
    try {
        $stmt = db()->prepare('DELETE FROM avis_clients WHERE id = :id');
        $stmt->execute(['id' => $reviewId]);
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Admin] adminDeleteReview failed for review ' . $reviewId . ': ' . $e->getMessage());
        return false;
    }
}
