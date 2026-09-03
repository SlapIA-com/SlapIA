<?php
/**
 * Audit & force-upgrade legacy plain-text passwords stored in Notion.
 *
 * Why this exists: api/auth-login.php already auto-upgrades a plain-text
 * password to bcrypt the next time that user logs in successfully. That
 * covers active accounts, but any account that never logs in again keeps
 * its password sitting in Notion in plain text indefinitely. This script
 * closes that gap by re-hashing every remaining plain-text password in one
 * pass, without waiting for a login.
 *
 * Usage (run on the NAS, where .env with NOTION_API_KEY actually lives):
 *   php scripts/audit-passwords.php            # dry run — lists what would change
 *   php scripts/audit-passwords.php --apply    # actually re-hashes them in Notion
 *
 * Safe to re-run: accounts already on a bcrypt hash ($2y$...) are always
 * skipped, so a second run finds nothing left to do.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is CLI-only.\n");
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/notion.php';

$apply = in_array('--apply', $argv, true);

$dbId = config('NOTION_SATISFACTION_DATABASE_ID');
if (!$dbId) {
    fwrite(STDERR, "NOTION_SATISFACTION_DATABASE_ID is not set (check .env).\n");
    exit(1);
}

echo $apply
    ? "Running in APPLY mode — plain-text passwords will be re-hashed in Notion.\n\n"
    : "Running in DRY-RUN mode — nothing will be changed. Pass --apply to actually fix accounts.\n\n";

$result = notion()->queryDatabaseAll($dbId, []);
if (!empty($result['error'])) {
    fwrite(STDERR, "Failed to query Notion database: " . ($result['message'] ?? 'unknown error') . "\n");
    exit(1);
}

$pages = $result['results'] ?? [];
echo "Scanned " . count($pages) . " account(s).\n\n";

$legacyCount = 0;
$upgradedCount = 0;
$failedCount = 0;
$noPasswordCount = 0;

foreach ($pages as $page) {
    $props = $page['properties'] ?? [];
    $hash  = NotionAPI::richText($props['Mot de passe'] ?? []);
    $email = $props['Email']['email'] ?? '(no email)';
    $name  = NotionAPI::title($props['Prenom NOM'] ?? []) ?: '(no name)';

    if ($hash === '') {
        $noPasswordCount++;
        continue;
    }

    if (strpos($hash, '$2y$') === 0) {
        continue; // already bcrypt, nothing to do
    }

    $legacyCount++;
    echo "  - {$email} ({$name}) — plain-text password found";

    if (!$apply) {
        echo " [would upgrade]\n";
        continue;
    }

    $update = notion()->updatePage($page['id'], [
        'properties' => [
            'Mot de passe' => [
                'rich_text' => [['text' => ['content' => password_hash($hash, PASSWORD_BCRYPT)]]],
            ],
        ],
    ]);

    if (empty($update['error']) && ($update['http_code'] ?? 0) < 300) {
        $upgradedCount++;
        echo " -> upgraded to bcrypt\n";
    } else {
        $failedCount++;
        echo " -> FAILED: " . json_encode($update) . "\n";
    }
}

echo "\n--- Summary ---\n";
echo "Accounts with no password set: {$noPasswordCount}\n";
echo "Legacy plain-text passwords found: {$legacyCount}\n";
if ($apply) {
    echo "Upgraded to bcrypt: {$upgradedCount}\n";
    echo "Failed: {$failedCount}\n";
} else {
    echo "Nothing changed (dry run). Re-run with --apply to fix them.\n";
}
