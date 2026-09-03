<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notion.php';

/** The 5 real Notion "Satisfaction" select values — no others are valid. */
const OWN_REVIEW_SATISFACTION_VALUES = ['⭐', '⭐⭐', '⭐⭐⭐', '⭐⭐⭐⭐', '⭐⭐⭐⭐⭐'];

/**
 * Fetches the logged-in user's own account details for the client dashboard.
 * Never accepts anything other than the caller's own page ID — callers must
 * derive $pageId from the session (currentUser()['id']), never from client input.
 */
function getOwnAccountDetails(string $pageId): ?array
{
    $page = notion()->getPage($pageId);
    if (!empty($page['error']) || ($page['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Client] getOwnAccountDetails failed for page ' . $pageId . ': ' . json_encode($page));
        return null;
    }

    $props = $page['properties'] ?? [];

    return [
        'id'           => $page['id'],
        'name'         => NotionAPI::title($props['Prenom NOM'] ?? []) ?: 'Utilisateur',
        'email'        => $props['Email']['email'] ?? '',
        'company'      => NotionAPI::richText($props["Nom d'entreprise"] ?? []),
        'service'      => NotionAPI::select($props['Type de service'] ?? []),
        'billing'      => NotionAPI::select($props['Facturation'] ?? []),
        'price'        => NotionAPI::number($props['Prix'] ?? []),
        'lastLogin'    => $props['Dernière connexion']['date']['start'] ?? null,
        'invoices'     => NotionAPI::files($props['Factures'] ?? []),
        'linkedin'     => $props['Linkedin']['url'] ?? '',
        'review'       => NotionAPI::richText($props['Avis clients'] ?? []),
        'satisfaction' => NotionAPI::select($props['Satisfaction'] ?? []),
        'phone'        => $props['Téléphone']['phone_number'] ?? '',
        'location'     => NotionAPI::richText($props['Location'] ?? []),
        'orders'       => NotionAPI::richText($props['Différentes commandes'] ?? []),
    ];
}

/**
 * Updates the caller's own LinkedIn URL. Pass an empty string to clear it.
 * Rejects (returns false) any non-empty value that isn't http(s) — never
 * writes an unvalidated scheme to Notion.
 */
function updateOwnLinkedin(string $pageId, string $linkedin): bool
{
    $linkedin = trim($linkedin);
    if ($linkedin !== '' && !preg_match('#^https?://#i', $linkedin)) {
        error_log('[SlapIA Client] updateOwnLinkedin rejected invalid URL for page ' . $pageId);
        return false;
    }

    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Linkedin' => ['url' => $linkedin !== '' ? $linkedin : null],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Client] updateOwnLinkedin failed for page ' . $pageId . ': ' . json_encode($result));
        return false;
    }

    return true;
}

/**
 * Updates the caller's own public testimonial (text + star rating).
 * Publishes immediately — no moderation step. Rejects empty text, text over
 * Notion's 2000-char rich_text limit, or any satisfaction value outside the
 * 5 real select options.
 */
function updateOwnReview(string $pageId, string $reviewText, string $satisfaction): bool
{
    $reviewText = trim($reviewText);
    if ($reviewText === '' || mb_strlen($reviewText) > 2000 || !in_array($satisfaction, OWN_REVIEW_SATISFACTION_VALUES, true)) {
        error_log('[SlapIA Client] updateOwnReview rejected invalid input for page ' . $pageId);
        return false;
    }

    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Avis clients' => ['rich_text' => [['text' => ['content' => $reviewText]]]],
            'Satisfaction' => ['select' => ['name' => $satisfaction]],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Client] updateOwnReview failed for page ' . $pageId . ': ' . json_encode($result));
        return false;
    }

    return true;
}

/**
 * Uploads a local image file to Notion (2-step File Upload API, mirrors
 * uploadInvoiceFile() in includes/notion-admin.php) and writes it as the
 * page's icon. Every avatar surface on the site (header, dashboards, public
 * testimonials via api/notion-avatar.php) already reads the page icon as a
 * fallback, so this single write makes the new photo appear everywhere.
 */
function uploadOwnPhoto(string $pageId, string $localFilePath, string $filename, string $mimeType): bool
{
    $create = notion()->createFileUpload($filename, $mimeType);
    if (empty($create['id']) || ($create['status'] ?? '') !== 'pending') {
        error_log('[SlapIA Client] uploadOwnPhoto create step failed: ' . json_encode($create));
        return false;
    }

    $send = notion()->sendFileUpload($create['upload_url'], $localFilePath, $filename, $mimeType);
    if (($send['status'] ?? '') !== 'uploaded') {
        error_log('[SlapIA Client] uploadOwnPhoto send step failed: ' . json_encode($send));
        return false;
    }

    $result = notion()->updatePage($pageId, [
        'icon' => ['type' => 'file_upload', 'file_upload' => ['id' => $create['id']]],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Client] uploadOwnPhoto attach step failed for page ' . $pageId . ': ' . json_encode($result));
        return false;
    }

    return true;
}

/**
 * Updates the caller's own phone number. Pass an empty string to clear it.
 * Rejects (returns false) any non-empty value outside a plain phone-number
 * charset (digits, spaces, +, -, ., parentheses), max 30 chars.
 */
function updateOwnPhone(string $pageId, string $phone): bool
{
    $phone = trim($phone);
    if ($phone !== '' && !preg_match('/^[0-9+\-.() ]{1,30}$/', $phone)) {
        error_log('[SlapIA Client] updateOwnPhone rejected invalid value for page ' . $pageId);
        return false;
    }

    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Téléphone' => ['phone_number' => $phone !== '' ? $phone : null],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Client] updateOwnPhone failed for page ' . $pageId . ': ' . json_encode($result));
        return false;
    }

    return true;
}

/**
 * Updates the caller's own address/location free-text field. Pass an empty
 * string to clear it. Rejects text over 500 chars.
 */
function updateOwnLocation(string $pageId, string $location): bool
{
    $location = trim($location);
    if (mb_strlen($location) > 500) {
        error_log('[SlapIA Client] updateOwnLocation rejected too-long value for page ' . $pageId);
        return false;
    }

    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Location' => ['rich_text' => $location !== '' ? [['text' => ['content' => $location]]] : []],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Client] updateOwnLocation failed for page ' . $pageId . ': ' . json_encode($result));
        return false;
    }

    return true;
}
