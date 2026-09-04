<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Auth & comptes utilisateurs, sourcés depuis MySQL (tables comptes + clients).
 * Remplace includes/notion-users.php.
 *
 * Convention conservée depuis Notion : type_client NULL = compte admin
 * (les comptes internes n'ont pas de type Particulier/Entreprise).
 */

/** Maps clients.type_client to an internal role. */
function resolveUserRole(?string $typeClient): string
{
    if ($typeClient === null || $typeClient === '') return 'admin';
    if ($typeClient === 'Entreprise') return 'entreprise';
    return 'particulier';
}

/** Maps our internal role name back to the clients.type_client value. */
function roleToTypeClient(string $role): ?string
{
    if ($role === 'entreprise') return 'Entreprise';
    if ($role === 'particulier') return 'Particulier';
    return null; // admin
}

/**
 * Cherche un utilisateur par email (jointure comptes + clients). Ne retourne
 * que les comptes ayant un mot de passe défini (mêmes règles que l'ancien
 * système Notion).
 */
function findUserByEmail(string $email): ?array
{
    $stmt = db()->prepare(
        'SELECT c.id AS client_id, c.nom_complet, c.type_client, c.notes,
                a.id AS compte_id, a.email, a.mot_de_passe_hash, a.reset_token,
                a.reset_token_expiry, a.derniere_connexion
         FROM comptes a
         JOIN clients c ON c.compte_id = a.id
         WHERE a.email = :email
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch();

    if (!$row || $row['mot_de_passe_hash'] === null || $row['mot_de_passe_hash'] === '') {
        return null;
    }
    return $row;
}

/** Charge un utilisateur par son client_id (identifiant utilisé en session). */
function getUserById(int $clientId): ?array
{
    $stmt = db()->prepare(
        'SELECT c.id AS client_id, c.nom_complet, c.type_client, c.notes,
                a.id AS compte_id, a.email, a.mot_de_passe_hash, a.reset_token,
                a.reset_token_expiry, a.derniere_connexion
         FROM clients c
         JOIN comptes a ON a.id = c.compte_id
         WHERE c.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $clientId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function verifyPassword(array $userRow, string $password): bool
{
    $hash = $userRow['mot_de_passe_hash'] ?? '';
    if ($hash === '') return false;
    return password_verify($password, $hash);
}

/** Conservé pour compatibilité : toutes les entrées migrées sont déjà en bcrypt. */
function upgradePasswordHash(int $clientId, string $plainPassword): bool
{
    return updatePassword($clientId, $plainPassword);
}

function userDisplayName(array $userRow): string
{
    return $userRow['nom_complet'] ?: 'Utilisateur';
}

function userRole(array $userRow): string
{
    return resolveUserRole($userRow['type_client'] ?? null);
}

function setResetToken(int $clientId): ?string
{
    $token  = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', time() + 3600);

    try {
        $stmt = db()->prepare(
            'UPDATE comptes a JOIN clients c ON c.compte_id = a.id
             SET a.reset_token = :token, a.reset_token_expiry = :expiry
             WHERE c.id = :id'
        );
        $stmt->execute(['token' => $token, 'expiry' => $expiry, 'id' => $clientId]);
        return $token;
    } catch (Throwable $e) {
        error_log('[SlapIA Auth] setResetToken failed for client ' . $clientId . ': ' . $e->getMessage());
        return null;
    }
}

function validateResetToken(string $email, string $token): ?array
{
    $stmt = db()->prepare(
        'SELECT c.id AS client_id, c.nom_complet, c.type_client,
                a.id AS compte_id, a.email, a.mot_de_passe_hash,
                a.reset_token, a.reset_token_expiry
         FROM comptes a
         JOIN clients c ON c.compte_id = a.id
         WHERE a.email = :email LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch();

    if (!$row || empty($row['reset_token']) || !hash_equals($row['reset_token'], $token)) {
        return null;
    }
    if (empty($row['reset_token_expiry']) || strtotime($row['reset_token_expiry']) < time()) {
        return null;
    }
    return $row;
}

function clearResetToken(int $clientId): bool
{
    try {
        $stmt = db()->prepare(
            'UPDATE comptes a JOIN clients c ON c.compte_id = a.id
             SET a.reset_token = NULL, a.reset_token_expiry = NULL
             WHERE c.id = :id'
        );
        $stmt->execute(['id' => $clientId]);
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Auth] clearResetToken failed for client ' . $clientId . ': ' . $e->getMessage());
        return false;
    }
}

function updatePassword(int $clientId, string $plainPassword): bool
{
    try {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        $stmt = db()->prepare(
            'UPDATE comptes a JOIN clients c ON c.compte_id = a.id
             SET a.mot_de_passe_hash = :hash
             WHERE c.id = :id'
        );
        $stmt->execute(['hash' => $hash, 'id' => $clientId]);
        clearResetToken($clientId); // best-effort cleanup
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Auth] updatePassword failed for client ' . $clientId . ': ' . $e->getMessage());
        return false;
    }
}

function setLastLogin(int $clientId): bool
{
    try {
        $stmt = db()->prepare(
            'UPDATE comptes a JOIN clients c ON c.compte_id = a.id
             SET a.derniere_connexion = NOW()
             WHERE c.id = :id'
        );
        $stmt->execute(['id' => $clientId]);
        return true;
    } catch (Throwable $e) {
        error_log('[SlapIA Auth] setLastLogin failed for client ' . $clientId . ': ' . $e->getMessage());
        return false;
    }
}
