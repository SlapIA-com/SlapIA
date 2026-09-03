<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notion.php';
require_once __DIR__ . '/notion-users.php';

const ADMIN_BILLING_STATUSES = ['Facturé', 'Payé', 'En cours', 'En attente', 'Dispensé'];

/** Maps our internal role name back to the real Notion Status select value. */
function roleToNotionStatus(string $role): string
{
    if ($role === 'entreprise') return 'Entreprise';
    if ($role === 'particulier') return 'Particulier';
    return ''; // admin
}

function listAllAccounts(): array
{
    $dbId   = config('NOTION_SATISFACTION_DATABASE_ID');
    $result = notion()->queryDatabaseAll($dbId);
    if (!empty($result['error'])) {
        error_log('[SlapIA Admin] listAllAccounts failed: ' . json_encode($result));
        return [];
    }

    $accounts = [];
    foreach ($result['results'] ?? [] as $page) {
        $props = $page['properties'] ?? [];

        // Only real accounts have a password set (mirrors findUserByEmail's convention).
        $hash = NotionAPI::richText($props['Mot de passe'] ?? []);
        if ($hash === '') continue;

        $statusProp  = $props['Status'] ?? [];
        $statusValue = NotionAPI::select($statusProp) ?: NotionAPI::richText($statusProp);

        // "file" (Notion-hosted) exposes a presigned URL valid ~1h; "external"
        // exposes a permanent URL. Both are safe to hand to the browser as-is
        // for viewing/downloading right after this data was fetched.
        $invoiceFiles = NotionAPI::files($props['Factures'] ?? []);
        $files        = $props['Factures']['files'] ?? []; // kept for the invoiceCount line below

        $accounts[] = [
            'id'           => $page['id'],
            'name'         => NotionAPI::title($props['Prenom NOM'] ?? []) ?: 'N.A',
            'email'        => $props['Email']['email'] ?? '',
            'company'      => NotionAPI::richText($props["Nom d'entreprise"] ?? []),
            'service'      => NotionAPI::select($props['Type de service'] ?? []),
            'role'         => resolveUserRole($statusValue),
            'billing'      => NotionAPI::select($props['Facturation'] ?? []),
            'price'        => NotionAPI::number($props['Prix'] ?? []),
            'lastLogin'    => $props['Dernière connexion']['date']['start'] ?? null,
            'invoiceCount' => count($files),
            'invoiceFiles' => $invoiceFiles,
            'phone'        => $props['Téléphone']['phone_number'] ?? '',
            'location'     => NotionAPI::richText($props['Location'] ?? []),
            'orders'       => NotionAPI::richText($props['Différentes commandes'] ?? []),
        ];
    }

    return $accounts;
}

function updateAccountRole(string $pageId, string $role): bool
{
    if (!in_array($role, ['particulier', 'entreprise', 'admin'], true)) {
        error_log('[SlapIA Admin] updateAccountRole rejected invalid role: ' . $role);
        return false;
    }

    $notionValue = roleToNotionStatus($role);
    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Status' => $notionValue === ''
                ? ['select' => null]
                : ['select' => ['name' => $notionValue]],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Admin] updateAccountRole failed for page ' . $pageId . ': ' . json_encode($result));
        return false;
    }

    return true;
}

function updateAccountBilling(string $pageId, string $billing): bool
{
    if (!in_array($billing, ADMIN_BILLING_STATUSES, true)) {
        error_log('[SlapIA Admin] updateAccountBilling rejected invalid status: ' . $billing);
        return false;
    }

    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Facturation' => ['select' => ['name' => $billing]],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Admin] updateAccountBilling failed for page ' . $pageId . ': ' . json_encode($result));
        return false;
    }

    return true;
}

function listRssSubscribers(): array
{
    $dbId = config('NOTION_RSS_SUBSCRIBER_DATABASE_ID', '');
    if ($dbId === '') {
        error_log('[SlapIA Admin] listRssSubscribers: NOTION_RSS_SUBSCRIBER_DATABASE_ID is not configured, returning empty list.');
        return [];
    }
    $result = notion()->queryDatabaseAll($dbId);
    if (!empty($result['error'])) {
        error_log('[SlapIA Admin] listRssSubscribers failed: ' . json_encode($result));
        return [];
    }

    $subscribers = [];
    foreach ($result['results'] ?? [] as $page) {
        $subscribers[] = [
            'email'        => NotionAPI::title($page['properties']['Email'] ?? []),
            'subscribedAt' => $page['created_time'] ?? null,
        ];
    }

    return $subscribers;
}

/** Reuses the same password-hashing logic as the user-facing reset flow. */
function resetAccountPassword(string $pageId, string $newPassword): bool
{
    return updatePassword($pageId, $newPassword);
}

/**
 * Uploads a local file to Notion (2-step File Upload API) and attaches it
 * to the target account's "Factures" property, alongside any files already
 * there (never replaces existing invoices).
 */
function uploadInvoiceFile(string $pageId, string $localFilePath, string $filename, string $mimeType): bool
{
    $create = notion()->createFileUpload($filename, $mimeType);
    if (empty($create['id']) || ($create['status'] ?? '') !== 'pending') {
        error_log('[SlapIA Admin] uploadInvoiceFile create step failed: ' . json_encode($create));
        return false;
    }

    $send = notion()->sendFileUpload($create['upload_url'], $localFilePath, $filename, $mimeType);
    if (($send['status'] ?? '') !== 'uploaded') {
        error_log('[SlapIA Admin] uploadInvoiceFile send step failed: ' . json_encode($send));
        return false;
    }

    // Fetch the current page to preserve any invoices already attached.
    $page = notion()->getPage($pageId);
    if (!empty($page['error']) || ($page['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Admin] uploadInvoiceFile getPage step failed for page ' . $pageId . ': ' . json_encode($page));
        return false;
    }
    $existingFiles = $page['properties']['Factures']['files'] ?? [];

    $preservedFiles = [];
    foreach ($existingFiles as $f) {
        $type = $f['type'] ?? '';

        if ($type === 'external' || $type === 'file_upload') {
            // Already a write-valid shape — keep as-is.
            $preservedFiles[] = $f;
            continue;
        }

        if ($type === 'file') {
            // "file" is Notion's read-only, responses-only shape (presigned,
            // expiring URL) — not valid on write. Re-host through the File
            // Upload API so the reference stays durable.
            $sourceUrl = $f['file']['url'] ?? '';
            $name      = $f['name'] ?? 'facture.pdf';
            if ($sourceUrl === '') {
                error_log('[SlapIA Admin] uploadInvoiceFile: existing file "' . $name . '" on page ' . $pageId . ' has no URL to re-host, aborting to avoid silent loss.');
                return false;
            }

            $tmpFile = tempnam(sys_get_temp_dir(), 'slapia_inv_');
            $bytes   = @file_get_contents($sourceUrl);
            if ($bytes === false) {
                @unlink($tmpFile);
                error_log('[SlapIA Admin] uploadInvoiceFile: failed to re-fetch existing file "' . $name . '" for page ' . $pageId);
                return false;
            }
            $written = file_put_contents($tmpFile, $bytes);
            if ($written === false || $written !== strlen($bytes)) {
                @unlink($tmpFile);
                error_log('[SlapIA Admin] uploadInvoiceFile: failed to write temp file for existing invoice "' . $name . '" on page ' . $pageId);
                return false;
            }
            $reMime = mime_content_type($tmpFile) ?: 'application/octet-stream';

            $reCreate = notion()->createFileUpload($name, $reMime);
            if (empty($reCreate['id']) || ($reCreate['status'] ?? '') !== 'pending') {
                @unlink($tmpFile);
                error_log('[SlapIA Admin] uploadInvoiceFile: re-upload create failed for "' . $name . '": ' . json_encode($reCreate));
                return false;
            }

            $reSend = notion()->sendFileUpload($reCreate['upload_url'], $tmpFile, $name, $reMime);
            @unlink($tmpFile);
            if (($reSend['status'] ?? '') !== 'uploaded') {
                error_log('[SlapIA Admin] uploadInvoiceFile: re-upload send failed for "' . $name . '": ' . json_encode($reSend));
                return false;
            }

            $preservedFiles[] = ['type' => 'file_upload', 'file_upload' => ['id' => $reCreate['id']], 'name' => $name];
        } else {
            error_log('[SlapIA Admin] uploadInvoiceFile: existing entry on page ' . $pageId . ' has unrecognized type "' . $type . '", aborting to avoid silent loss.');
            return false;
        }
    }

    $preservedFiles[] = [
        'type'        => 'file_upload',
        'file_upload' => ['id' => $create['id']],
        'name'        => $filename,
    ];

    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Factures' => ['files' => $preservedFiles],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Admin] uploadInvoiceFile attach step failed for page ' . $pageId . ': ' . json_encode($result));
        return false;
    }

    return true;
}

/**
 * Admin write: updates a target account's phone/location/orders together
 * (the admin UI's "Détails" panel submits all three from one form).
 * Same validation rules as the client's own updateOwnPhone()/updateOwnLocation()
 * in includes/notion-client.php — kept duplicated here rather than shared,
 * matching this file's existing convention of not depending on notion-client.php.
 */
function updateAccountContactDetails(string $pageId, string $phone, string $location, string $orders): bool
{
    $phone = trim($phone);
    if ($phone !== '' && !preg_match('/^[0-9+\-.() ]{1,30}$/', $phone)) {
        error_log('[SlapIA Admin] updateAccountContactDetails rejected invalid phone for page ' . $pageId);
        return false;
    }

    $location = trim($location);
    $orders   = trim($orders);
    if (mb_strlen($location) > 500 || mb_strlen($orders) > 2000) {
        error_log('[SlapIA Admin] updateAccountContactDetails rejected oversized field for page ' . $pageId);
        return false;
    }

    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Téléphone'              => ['phone_number' => $phone !== '' ? $phone : null],
            'Location'               => ['rich_text' => $location !== '' ? [['text' => ['content' => $location]]] : []],
            'Différentes commandes'  => ['rich_text' => $orders !== '' ? [['text' => ['content' => $orders]]] : []],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Admin] updateAccountContactDetails failed for page ' . $pageId . ': ' . json_encode($result));
        return false;
    }

    return true;
}
