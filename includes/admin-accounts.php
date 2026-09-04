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
                c.location, c.commandes_libres, a.email, a.derniere_connexion
         FROM clients c
         JOIN comptes a ON a.id = c.compte_id
         WHERE a.mot_de_passe_hash IS NOT NULL AND a.mot_de_passe_hash != \'\'
         ORDER BY c.id ASC'
    )->fetchAll();

    if (!$rows) return [];

    $ids = array_column($rows, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Dernière prestation par client (service/prix/facturation affichés).
    $latestByClient = [];
    $pStmt = db()->prepare(
        "SELECT client_id, type_service, prix, statut_facturation, description
         FROM prestations WHERE client_id IN ($placeholders) ORDER BY id ASC"
    );
    $pStmt->execute($ids);
    $ordersByClient = [];
    foreach ($pStmt->fetchAll() as $p) {
        $latestByClient[$p['client_id']] = $p; // overwritten going forward = keeps the latest (ids ascending)
        if ($p['description'] !== null) {
            $ordersByClient[$p['client_id']][] = $p['description'];
        }
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
            'orders'       => $orders,
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
 * Admin write: updates a target account's phone/location/orders together
 * (the admin UI's "Détails" panel submits all three from one form).
 * "orders" est stocké dans clients.commandes_libres (champ libre, distinct de
 * clients.notes qui reste réservé aux notes internes) — voir getOwnAccountDetails().
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
