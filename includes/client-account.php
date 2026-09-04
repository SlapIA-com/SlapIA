<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Données et actions du tableau de bord client, sourcées depuis MySQL.
 * Remplace includes/notion-client.php.
 */

/** Les 5 valeurs "étoiles" utilisées par le formulaire d'avis côté client. */
const OWN_REVIEW_SATISFACTION_VALUES = ['⭐', '⭐⭐', '⭐⭐⭐', '⭐⭐⭐⭐', '⭐⭐⭐⭐⭐'];

function satisfactionToInt(string $emojiStars): ?int
{
    $n = mb_strlen($emojiStars, 'UTF-8');
    return ($n >= 1 && $n <= 5) ? $n : null;
}

function satisfactionToEmoji(?int $n): string
{
    if ($n === null || $n < 1) return '';
    return OWN_REVIEW_SATISFACTION_VALUES[min($n, 5) - 1];
}

/**
 * Fetches the logged-in user's own account details for the client dashboard.
 * $clientId doit toujours venir de la session (currentUser()['id']), jamais
 * d'une entrée utilisateur directe.
 */
function getOwnAccountDetails(int $clientId): ?array
{
    $stmt = db()->prepare(
        'SELECT c.id, c.nom_complet, c.nom_entreprise, c.telephone, c.location,
                c.linkedin, c.commandes_libres, a.email, a.derniere_connexion
         FROM clients c
         JOIN comptes a ON a.id = c.compte_id
         WHERE c.id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $clientId]);
    $row = $stmt->fetch();
    if (!$row) {
        error_log('[SlapIA Client] getOwnAccountDetails: client ' . $clientId . ' introuvable.');
        return null;
    }

    $latestPrestation = db()->prepare(
        'SELECT type_service, prix, statut_facturation FROM prestations
         WHERE client_id = :id ORDER BY id DESC LIMIT 1'
    );
    $latestPrestation->execute(['id' => $clientId]);
    $prestation = $latestPrestation->fetch() ?: [];

    // "orders" (Différentes commandes) : le champ libre clients.commandes_libres
    // fait foi s'il a été renseigné (modifiable depuis l'admin) ; à défaut, on
    // reconstruit un résumé à partir des lignes de prestations détaillées.
    // (Distinct de clients.notes, qui reste réservé aux notes internes/admin.)
    if (!empty($row['commandes_libres'])) {
        $orders = $row['commandes_libres'];
    } else {
        $ordersStmt = db()->prepare(
            'SELECT description FROM prestations WHERE client_id = :id AND description IS NOT NULL ORDER BY id ASC'
        );
        $ordersStmt->execute(['id' => $clientId]);
        $orders = implode("\n", array_column($ordersStmt->fetchAll(), 'description'));
    }

    $reviewStmt = db()->prepare(
        'SELECT commentaire, satisfaction FROM avis_clients WHERE client_id = :id ORDER BY id DESC LIMIT 1'
    );
    $reviewStmt->execute(['id' => $clientId]);
    $review = $reviewStmt->fetch() ?: [];

    $invoicesStmt = db()->prepare(
        'SELECT id, nom_fichier FROM factures WHERE client_id = :id ORDER BY id ASC'
    );
    $invoicesStmt->execute(['id' => $clientId]);
    $invoices = array_map(
        fn($f) => ['name' => $f['nom_fichier'], 'id' => (int)$f['id']],
        $invoicesStmt->fetchAll()
    );

    return [
        'id'           => $row['id'],
        'name'         => $row['nom_complet'] ?: 'Utilisateur',
        'email'        => $row['email'],
        'company'      => $row['nom_entreprise'] ?? '',
        'service'      => $prestation['type_service'] ?? '',
        'billing'      => $prestation['statut_facturation'] ?? '',
        'price'        => isset($prestation['prix']) ? (float)$prestation['prix'] : null,
        'lastLogin'    => $row['derniere_connexion'],
        'invoices'     => $invoices,
        'linkedin'     => $row['linkedin'] ?? '',
        'review'       => $review['commentaire'] ?? '',
        'satisfaction' => satisfactionToEmoji($review['satisfaction'] ?? null),
        'phone'        => $row['telephone'] ?? '',
        'location'     => $row['location'] ?? '',
        'orders'       => $orders,
    ];
}

function updateOwnLinkedin(int $clientId, string $linkedin): bool
{
    $linkedin = trim($linkedin);
    if ($linkedin !== '' && !preg_match('#^https?://#i', $linkedin)) {
        error_log('[SlapIA Client] updateOwnLinkedin rejected invalid URL for client ' . $clientId);
        return false;
    }

    try {
        $stmt = db()->prepare('UPDATE clients SET linkedin = :v WHERE id = :id');
        $stmt->execute(['v' => $linkedin !== '' ? $linkedin : null, 'id' => $clientId]);
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Client] updateOwnLinkedin failed for client ' . $clientId . ': ' . $e->getMessage());
        return false;
    }
}

/**
 * Updates the caller's own public testimonial (text + star rating).
 * Publie immédiatement (pas de modération). Une seule ligne par client dans
 * avis_clients : met à jour l'avis existant s'il y en a déjà un, sinon en crée un.
 */
function updateOwnReview(int $clientId, string $reviewText, string $satisfaction): bool
{
    $reviewText = trim($reviewText);
    $satInt     = satisfactionToInt($satisfaction);
    if ($reviewText === '' || mb_strlen($reviewText) > 2000 || $satInt === null) {
        error_log('[SlapIA Client] updateOwnReview rejected invalid input for client ' . $clientId);
        return false;
    }

    try {
        $pdo = db();
        $existing = $pdo->prepare('SELECT id FROM avis_clients WHERE client_id = :id ORDER BY id DESC LIMIT 1');
        $existing->execute(['id' => $clientId]);
        $existingId = $existing->fetchColumn();

        if ($existingId) {
            $stmt = $pdo->prepare('UPDATE avis_clients SET commentaire = :c, satisfaction = :s WHERE id = :id');
            $stmt->execute(['c' => $reviewText, 's' => $satInt, 'id' => $existingId]);
        } else {
            $nameStmt = $pdo->prepare('SELECT nom_complet FROM clients WHERE id = :id');
            $nameStmt->execute(['id' => $clientId]);
            $name = $nameStmt->fetchColumn() ?: 'Client';

            $stmt = $pdo->prepare(
                'INSERT INTO avis_clients (client_id, prenom_nom, satisfaction, commentaire, created_at)
                 VALUES (:client_id, :name, :s, :c, NOW())'
            );
            $stmt->execute(['client_id' => $clientId, 'name' => $name, 's' => $satInt, 'c' => $reviewText]);
        }
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Client] updateOwnReview failed for client ' . $clientId . ': ' . $e->getMessage());
        return false;
    }
}

/**
 * Enregistre la photo de profil sur disque (storage/avatars/) et met à jour
 * clients.photo_path / photo_mime. Remplace l'ancien upload vers l'icône Notion.
 */
function uploadOwnPhoto(int $clientId, string $localFilePath, string $filename, string $mimeType): bool
{
    $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $ext = $extMap[$mimeType] ?? pathinfo($filename, PATHINFO_EXTENSION) ?: 'jpg';

    $dir  = storagePath('avatars');
    $dest = $dir . '/' . $clientId . '.' . $ext;

    if (!@copy($localFilePath, $dest)) {
        error_log('[SlapIA Client] uploadOwnPhoto: copy failed for client ' . $clientId);
        return false;
    }

    try {
        $stmt = db()->prepare('UPDATE clients SET photo_path = :p, photo_mime = :m WHERE id = :id');
        $stmt->execute(['p' => 'avatars/' . $clientId . '.' . $ext, 'm' => $mimeType, 'id' => $clientId]);
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Client] uploadOwnPhoto DB update failed for client ' . $clientId . ': ' . $e->getMessage());
        return false;
    }
}

function updateOwnPhone(int $clientId, string $phone): bool
{
    $phone = trim($phone);
    if ($phone !== '' && !preg_match('/^[0-9+\-.() ]{1,30}$/', $phone)) {
        error_log('[SlapIA Client] updateOwnPhone rejected invalid value for client ' . $clientId);
        return false;
    }

    try {
        $stmt = db()->prepare('UPDATE clients SET telephone = :v WHERE id = :id');
        $stmt->execute(['v' => $phone !== '' ? $phone : null, 'id' => $clientId]);
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Client] updateOwnPhone failed for client ' . $clientId . ': ' . $e->getMessage());
        return false;
    }
}

function updateOwnLocation(int $clientId, string $location): bool
{
    $location = trim($location);
    if (mb_strlen($location) > 500) {
        error_log('[SlapIA Client] updateOwnLocation rejected too-long value for client ' . $clientId);
        return false;
    }

    try {
        $stmt = db()->prepare('UPDATE clients SET location = :v WHERE id = :id');
        $stmt->execute(['v' => $location !== '' ? $location : null, 'id' => $clientId]);
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Client] updateOwnLocation failed for client ' . $clientId . ': ' . $e->getMessage());
        return false;
    }
}
