<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notion.php';

/**
 * Maps the Notion "Status" property to an internal role.
 * Empty Status = admin: this is an intentional Notion convention (internal
 * team accounts have no client type set), not a bug — kept centralized here
 * so it's never re-implemented ad hoc elsewhere.
 */
function resolveUserRole(string $statusValue): string
{
    if ($statusValue === '') return 'admin';
    if ($statusValue === 'Entreprise') return 'entreprise';
    return 'particulier';
}

function findUserByEmail(string $email): ?array
{
    $dbId = config('NOTION_SATISFACTION_DATABASE_ID');
    $result = notion()->queryDatabase($dbId, [
        'filter' => ['property' => 'Email', 'email' => ['equals' => $email]],
    ]);

    foreach ($result['results'] ?? [] as $page) {
        $hash = NotionAPI::richText($page['properties']['Mot de passe'] ?? []);
        if ($hash !== '') {
            return $page;
        }
    }
    return null;
}

function verifyPassword(array $userPage, string $password): bool
{
    $hash = NotionAPI::richText($userPage['properties']['Mot de passe'] ?? []);
    if ($hash === '') return false;

    if (strpos($hash, '$2y$') === 0) {
        return password_verify($password, $hash);
    }

    // Legacy plain-text password, upgraded by the caller after a successful check.
    return hash_equals($hash, $password);
}

function upgradePasswordHash(string $pageId, string $plainPassword): bool
{
    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Mot de passe' => [
                'rich_text' => [['text' => ['content' => password_hash($plainPassword, PASSWORD_BCRYPT)]]],
            ],
        ],
    ]);
    return empty($result['error']) && ($result['http_code'] ?? 0) < 300;
}

function userDisplayName(array $userPage): string
{
    return NotionAPI::title($userPage['properties']['Prenom NOM'] ?? []) ?: 'Utilisateur';
}

function userRole(array $userPage): string
{
    $prop = $userPage['properties']['Status'] ?? [];
    // Status has historically been either a select or a rich_text property in Notion.
    $status = NotionAPI::select($prop) ?: NotionAPI::richText($prop);
    return resolveUserRole($status);
}

function setResetToken(string $pageId): ?string
{
    $token  = bin2hex(random_bytes(32));
    $expiry = date('c', time() + 3600);

    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Reset Token'  => ['rich_text' => [['text' => ['content' => $token]]]],
            'Reset Expiry' => ['date' => ['start' => $expiry]],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Auth] setResetToken failed for page ' . $pageId . ': ' . json_encode($result));
        return null;
    }

    return $token;
}

function validateResetToken(string $email, string $token): ?array
{
    $dbId = config('NOTION_SATISFACTION_DATABASE_ID');
    $result = notion()->queryDatabase($dbId, [
        'filter' => ['property' => 'Email', 'email' => ['equals' => $email]],
    ]);

    foreach ($result['results'] ?? [] as $page) {
        $storedToken = NotionAPI::richText($page['properties']['Reset Token'] ?? []);
        $expiry      = $page['properties']['Reset Expiry']['date']['start'] ?? '';

        if ($storedToken !== '' && hash_equals($storedToken, $token) && strtotime($expiry) > time()) {
            return $page;
        }
    }
    return null;
}

function clearResetToken(string $pageId): bool
{
    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Reset Token'  => ['rich_text' => []],
            'Reset Expiry' => ['date' => null],
        ],
    ]);
    return empty($result['error']) && ($result['http_code'] ?? 0) < 300;
}

function updatePassword(string $pageId, string $plainPassword): bool
{
    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Mot de passe' => [
                'rich_text' => [['text' => ['content' => password_hash($plainPassword, PASSWORD_BCRYPT)]]],
            ],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Auth] updatePassword failed for page ' . $pageId . ': ' . json_encode($result));
        return false;
    }

    clearResetToken($pageId); // best-effort cleanup; password change already succeeded above
    return true;
}

/**
 * Best-effort: records the login timestamp on the user's Notion page.
 * The "Dernière connexion" property must be created manually in Notion
 * (type Date) — until then this always fails gracefully (logged, never
 * fatal, never blocks login).
 */
function setLastLogin(string $pageId): bool
{
    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Dernière connexion' => ['date' => ['start' => date('c')]],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Admin] setLastLogin failed for page ' . $pageId . ': ' . json_encode($result));
        return false;
    }

    return true;
}
