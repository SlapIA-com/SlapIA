# Dashboard Profile Features Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a logged-in client from `pages/dashboard.php` upload a profile photo, edit their LinkedIn link, add/edit their public testimonial (text + star rating) with a live preview, and open their invoice PDFs inline instead of forcing a download.

**Architecture:** Extend `includes/notion-client.php` with three new self-service Notion writes (all scoped to `currentUser()['id']`, never a client-supplied ID) plus richer read data. Add four small JSON/file API endpoints under `api/`, each guarded by `requireLogin()` + CSRF. Extend the existing dashboard page shell, stylesheet, and client-side script — no new pages.

**Tech Stack:** PHP 8 (no framework), Notion API as backend (`includes/notion.php`'s `NotionAPI` class + File Upload API), vanilla JS (no build step), existing `t()` i18n helper.

## Global Constraints

- Every new/modified endpoint under `api/` must call `requireLogin()` before touching any Notion data, and must verify CSRF on every write (`X-CSRF-Token` header for JSON bodies, `csrf_token` POST field for the multipart upload — this split is intentional and already established by `api/admin-upload-invoice-exec.php`).
- Every Notion write must target `currentUser()['id']` (or a value derived server-side from it) — never a page ID supplied by the client. This spec explicitly forbids any endpoint that accepts a page/account ID from the request body for the acting user's own data.
- The invoice-viewing endpoint accepts only an integer `index` into the caller's own invoice list — never a URL or file ID from the client (SSRF/open-proxy prevention, per the design spec).
- Every Notion write function returns `bool` and follows the existing pattern: check `!empty($result['error']) || ($result['http_code'] ?? 0) >= 300`, `error_log()` on failure with the page ID and encoded result, return `false` — never a silent success on a failed write.
- All user-visible strings go through `t('dashboard.xxx')` server-side or `window.DASHBOARD_I18N` client-side — no hardcoded French (or any language) literals in `assets/js/dashboard.js`, matching the fix already applied to the existing dashboard JS.
- Every Notion-sourced string rendered into `innerHTML` in `assets/js/dashboard.js` must go through the existing `escHtml()` helper; every Notion-sourced URL rendered into an `href`/`src` must additionally pass an `/^https?:\/\//i` scheme check before use.
- The real Notion `Satisfaction` select values are exactly: `⭐`, `⭐⭐`, `⭐⭐⭐`, `⭐⭐⭐⭐`, `⭐⭐⭐⭐⭐` (5 values, no others). Any write to this property must validate against this exact list.
- Do not exercise any mutating Notion write (photo upload, LinkedIn save, avis save) against the live database during automated task verification — guard checks (anonymous requests get redirected/blocked) and static/lint checks only. Full functional verification of writes is manual, done by the user.
- Never modify `includes/reviews.php` — confirmed unnecessary; the public homepage already resolves avatars via `api/notion-avatar.php?id=<page_id>`, which already falls back to the page icon.

---

### Task 1: Extend `includes/notion-client.php` with LinkedIn, avis, and photo writes

**Files:**
- Modify: `includes/notion-client.php`

**Interfaces:**
- Consumes: `notion()` singleton and `NotionAPI::title/richText/select/number/files` from `includes/notion.php` (existing); `notion()->createFileUpload()`/`sendFileUpload()` (existing, from sub-project 2).
- Produces (used by Tasks 2-5):
  - `getOwnAccountDetails(string $pageId): ?array` — now also returns `id`, `linkedin`, `review`, `satisfaction` keys.
  - `updateOwnLinkedin(string $pageId, string $linkedin): bool`
  - `updateOwnReview(string $pageId, string $reviewText, string $satisfaction): bool`
  - `uploadOwnPhoto(string $pageId, string $localFilePath, string $filename, string $mimeType): bool`
  - `const OWN_REVIEW_SATISFACTION_VALUES` — the 5 valid Notion select values, exported for reuse by Task 3's endpoint validation.

- [ ] **Step 1: Extend `getOwnAccountDetails()` and add the constant**

Replace the full contents of `includes/notion-client.php` with:

```php
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
```

- [ ] **Step 2: Lint the file**

Run: `php -l includes/notion-client.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add includes/notion-client.php
git commit -m "feat(dashboard): add LinkedIn, avis, and photo writes to notion-client"
```

---

### Task 2: New endpoint `api/dashboard-update-linkedin.php`

**Files:**
- Create: `api/dashboard-update-linkedin.php`

**Interfaces:**
- Consumes: `requireLogin()`, `currentUser()`, `verifyCSRFToken()` (from `includes/auth.php`/`includes/config.php`), `updateOwnLinkedin()` (Task 1), `t()` (from `includes/i18n.php`).
- Produces: `POST /api/dashboard-update-linkedin.php` — JSON body `{linkedin: string}`, header `X-CSRF-Token`. Response `{success: true}` or `{success: false, error: string}`.

- [ ] **Step 1: Write the endpoint**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-client.php';

requireLogin();

header('Content-Type: application/json');
ob_start();

try {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => t('auth.err_csrf')]);
        exit;
    }

    $input    = json_decode(file_get_contents('php://input'), true) ?: [];
    $linkedin = trim($input['linkedin'] ?? '');

    if ($linkedin !== '' && !preg_match('#^https?://#i', $linkedin)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_linkedin_invalid')]);
        exit;
    }

    $me = currentUser();
    if (!updateOwnLinkedin($me['id'], $linkedin)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_linkedin_failed')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Dashboard Update Linkedin] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/dashboard-update-linkedin.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Guard check — anonymous request must not reach Notion**

Run (server must be running locally, e.g. `http://slapia.local/`, with no session cookie):
```bash
curl -s -o /dev/null -w "%{http_code}" http://slapia.local/api/dashboard-update-linkedin.php -X POST -H "Content-Type: application/json" -d "{\"linkedin\":\"https://linkedin.com/in/test\"}"
```
Expected: `302` (redirect to `/login` from `requireLogin()`) — never a 200 or 500 from an unauthenticated request. Do not test the authenticated success path against live data; that is manual QA.

- [ ] **Step 4: Commit**

```bash
git add api/dashboard-update-linkedin.php
git commit -m "feat(dashboard): add LinkedIn update endpoint"
```

---

### Task 3: New endpoint `api/dashboard-update-review.php`

**Files:**
- Create: `api/dashboard-update-review.php`

**Interfaces:**
- Consumes: `requireLogin()`, `currentUser()`, `verifyCSRFToken()`, `updateOwnReview()` and `OWN_REVIEW_SATISFACTION_VALUES` (Task 1), `t()`.
- Produces: `POST /api/dashboard-update-review.php` — JSON body `{review: string, satisfaction: string}`, header `X-CSRF-Token`. Response `{success: true}` or `{success: false, error: string}`.

- [ ] **Step 1: Write the endpoint**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-client.php';

requireLogin();

header('Content-Type: application/json');
ob_start();

try {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => t('auth.err_csrf')]);
        exit;
    }

    $input        = json_decode(file_get_contents('php://input'), true) ?: [];
    $reviewText   = trim($input['review'] ?? '');
    $satisfaction = $input['satisfaction'] ?? '';

    if ($reviewText === '') {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_avis_empty')]);
        exit;
    }

    if (mb_strlen($reviewText) > 2000) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_avis_too_long')]);
        exit;
    }

    if (!in_array($satisfaction, OWN_REVIEW_SATISFACTION_VALUES, true)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_satisfaction_invalid')]);
        exit;
    }

    $me = currentUser();
    if (!updateOwnReview($me['id'], $reviewText, $satisfaction)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_avis_failed')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Dashboard Update Review] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/dashboard-update-review.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Guard check**

```bash
curl -s -o /dev/null -w "%{http_code}" http://slapia.local/api/dashboard-update-review.php -X POST -H "Content-Type: application/json" -d "{\"review\":\"test\",\"satisfaction\":\"⭐⭐⭐⭐⭐\"}"
```
Expected: `302`.

- [ ] **Step 4: Commit**

```bash
git add api/dashboard-update-review.php
git commit -m "feat(dashboard): add avis update endpoint"
```

---

### Task 4: New endpoint `api/dashboard-upload-photo.php`

**Files:**
- Create: `api/dashboard-upload-photo.php`

**Interfaces:**
- Consumes: `requireLogin()`, `currentUser()`, `verifyCSRFToken()`, `uploadOwnPhoto()` (Task 1), `t()`.
- Produces: `POST /api/dashboard-upload-photo.php` — multipart form, field `file` (image) + field `csrf_token`. Response `{success: true}` or `{success: false, error: string}`.
- Note: after a successful upload, this endpoint clears `api/notion-avatar.php`'s file cache for the caller's own page ID so the new photo is visible immediately instead of up to 1 hour later (that endpoint caches per page ID regardless of query string). Mirrors the cache file naming in `api/notion-avatar.php:19-20`.

- [ ] **Step 1: Write the endpoint**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-client.php';

requireLogin();

header('Content-Type: application/json');
ob_start();

try {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => t('auth.err_csrf')]);
        exit;
    }

    if (empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_photo_missing')]);
        exit;
    }

    $file     = $_FILES['file'];
    $mimeType = mime_content_type($file['tmp_name']);

    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowedTypes[$mimeType])) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_photo_type')]);
        exit;
    }

    $maxBytes = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxBytes) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_photo_size')]);
        exit;
    }

    $safeName = 'photo-profil.' . $allowedTypes[$mimeType];

    $me = currentUser();
    if (!uploadOwnPhoto($me['id'], $file['tmp_name'], $safeName, $mimeType)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_photo_failed')]);
        exit;
    }

    // Invalidate api/notion-avatar.php's cache for this page so the new
    // photo shows immediately instead of after its 1-hour TTL.
    $pageIdClean = preg_replace('/[^a-f0-9]/i', '', $me['id']);
    $cacheDir    = sys_get_temp_dir();
    @unlink($cacheDir . '/slapia_avatar_' . $pageIdClean . '.meta');
    @unlink($cacheDir . '/slapia_avatar_' . $pageIdClean . '.img');

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Dashboard Upload Photo] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/dashboard-upload-photo.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Guard check**

```bash
curl -s -o /dev/null -w "%{http_code}" http://slapia.local/api/dashboard-upload-photo.php -X POST -F "csrf_token=x"
```
Expected: `302`.

- [ ] **Step 4: Commit**

```bash
git add api/dashboard-upload-photo.php
git commit -m "feat(dashboard): add profile photo upload endpoint"
```

---

### Task 5: New endpoint `api/dashboard-view-invoice.php`

**Files:**
- Create: `api/dashboard-view-invoice.php`

**Interfaces:**
- Consumes: `requireLogin()`, `currentUser()`, `getOwnAccountDetails()` (Task 1).
- Produces: `GET /api/dashboard-view-invoice.php?index=N` — streams the caller's Nth own invoice PDF with `Content-Disposition: inline`. No JSON; either the raw PDF bytes or a bare HTTP error status. Accepts only an integer index, never a URL — the URL is always re-derived server-side from the caller's own Notion data.

- [ ] **Step 1: Write the endpoint**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-client.php';

requireLogin();

$index = filter_input(INPUT_GET, 'index', FILTER_VALIDATE_INT);
if ($index === null || $index === false || $index < 0) {
    http_response_code(400);
    exit;
}

$me      = currentUser();
$account = getOwnAccountDetails($me['id']);

if ($account === null || !isset($account['invoices'][$index])) {
    http_response_code(404);
    exit;
}

$invoice = $account['invoices'][$index];
$url     = $invoice['url'] ?? '';
if (!preg_match('#^https://#i', $url)) {
    http_response_code(502);
    exit;
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 3,
]);
$data     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
unset($ch);

if ($httpCode !== 200 || $data === false || $data === '') {
    http_response_code(502);
    exit;
}

$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($invoice['name'] ?: 'facture.pdf'));
if ($safeName === '') $safeName = 'facture.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $safeName . '"');
header('Content-Length: ' . strlen($data));
header('Cache-Control: private, max-age=0, no-cache');
echo $data;
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/dashboard-view-invoice.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Guard check**

```bash
curl -s -o /dev/null -w "%{http_code}" "http://slapia.local/api/dashboard-view-invoice.php?index=0"
```
Expected: `302`.

- [ ] **Step 4: Commit**

```bash
git add api/dashboard-view-invoice.php
git commit -m "feat(dashboard): add inline invoice viewing endpoint"
```

---

### Task 6: Extend `pages/dashboard.php` — new avis container and i18n keys

**Files:**
- Modify: `pages/dashboard.php`

**Interfaces:**
- Consumes: existing `t()`, `generateCSRFToken()`.
- Produces: a `#dashboard-avis` container that Task 8's JS renders into; `window.DASHBOARD_I18N` extended with every new key Task 8's JS references (values come from `t('dashboard.xxx')` — Task 9 supplies the actual translated strings; until then `t()` falls back to returning the raw key, which is fine for this task's guard-only verification).

- [ ] **Step 1: Add the new container and extend the i18n array**

In `pages/dashboard.php`, replace the `<div class="dash-grid">...</div>` block through the closing `</script>` tag (currently lines 28-61) with:

```php
    <div class="dash-grid">
      <div id="dashboard-profile" class="dash-card"></div>
      <div id="dashboard-billing" class="dash-card"></div>
    </div>

    <div id="dashboard-invoices" class="dash-card"></div>

    <div id="dashboard-avis" class="dash-card"></div>

    <div id="dashboard-password" class="dash-card"></div>
  </div>
</section>

<script>
window.DASHBOARD_CSRF_TOKEN = <?php echo json_encode($csrf); ?>;
window.DASHBOARD_I18N = <?php echo json_encode([
    'greeting' => t('dashboard.greeting'),
    'no_service' => t('dashboard.no_service'),
    'label_profile' => t('dashboard.label_profile'),
    'label_name' => t('dashboard.label_name'),
    'label_email' => t('dashboard.label_email'),
    'label_company' => t('dashboard.label_company'),
    'label_billing' => t('dashboard.label_billing'),
    'label_status' => t('dashboard.label_status'),
    'label_last_login' => t('dashboard.label_last_login'),
    'label_invoices' => t('dashboard.label_invoices'),
    'empty_invoices' => t('dashboard.empty_invoices'),
    'download' => t('dashboard.download'),
    'view' => t('dashboard.view'),
    'change_password_title' => t('dashboard.change_password_title'),
    'label_current_password' => t('dashboard.label_current_password'),
    'label_new_password' => t('dashboard.label_new_password'),
    'submit_update' => t('dashboard.submit_update'),
    'password_updated' => t('dashboard.password_updated'),
    'err_generic' => t('dashboard.err_generic'),
    'label_photo' => t('dashboard.label_photo'),
    'change_photo' => t('dashboard.change_photo'),
    'photo_updated' => t('dashboard.photo_updated'),
    'err_photo_type' => t('dashboard.err_photo_type'),
    'err_photo_size' => t('dashboard.err_photo_size'),
    'err_photo_failed' => t('dashboard.err_photo_failed'),
    'label_linkedin' => t('dashboard.label_linkedin'),
    'placeholder_linkedin' => t('dashboard.placeholder_linkedin'),
    'save' => t('dashboard.save'),
    'linkedin_updated' => t('dashboard.linkedin_updated'),
    'err_linkedin_invalid' => t('dashboard.err_linkedin_invalid'),
    'label_avis_title' => t('dashboard.label_avis_title'),
    'label_avis_text' => t('dashboard.label_avis_text'),
    'placeholder_avis' => t('dashboard.placeholder_avis'),
    'label_satisfaction' => t('dashboard.label_satisfaction'),
    'publish' => t('dashboard.publish'),
    'avis_updated' => t('dashboard.avis_updated'),
    'err_avis_empty' => t('dashboard.err_avis_empty'),
    'preview_label' => t('dashboard.preview_label'),
    'preview_empty' => t('dashboard.preview_empty'),
], JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="assets/js/dashboard.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
```

- [ ] **Step 2: Lint the file**

Run: `php -l pages/dashboard.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Guard check**

```bash
curl -s -o /dev/null -w "%{http_code}" http://slapia.local/dashboard
```
Expected: `302` (redirect to `/login`, anonymous request).

- [ ] **Step 4: Commit**

```bash
git add pages/dashboard.php
git commit -m "feat(dashboard): add avis container and extend dashboard i18n keys"
```

---

### Task 7: Extend `assets/css/dashboard.css` — photo widget, star selector, live preview

**Files:**
- Modify: `assets/css/dashboard.css`

**Interfaces:**
- Consumes: existing design tokens (`--paper`, `--white`, `--line`, `--ink`, `--ink-fade`, `--signal`, `--font-display`, `--font-mono`) already used throughout this file and `assets/css/style.css`.
- Produces: classes Task 8's JS will use — `.dash-photo`, `.dash-photo__img`, `.dash-photo__actions`, `.dash-field-edit`, `.dash-field-edit__row`, `.dash-stars-input`, `.dash-stars-input__star`, `.dash-avis-layout`, `.dash-avis-preview`, `.dash-avis-preview__label`, `.dash-avis-preview__empty`.

- [ ] **Step 1: Append the new styles**

Append to the end of `assets/css/dashboard.css`:

```css
.dash-photo { display: flex; align-items: center; gap: 18px; margin-bottom: 22px; }
.dash-photo__img {
  width: 64px; height: 64px; border-radius: 50%; object-fit: cover;
  border: 1px solid var(--line); background: var(--paper); flex-shrink: 0;
}
.dash-photo__actions { display: flex; flex-direction: column; gap: 6px; }
.dash-photo__hint { color: var(--ink-fade); font-size: 0.8rem; }

.dash-field-edit { padding: 10px 0; border-bottom: 1px solid var(--line); }
.dash-field-edit:last-child { border-bottom: none; }
.dash-field-edit label { display: block; color: var(--ink-fade); font-size: 0.85rem; margin-bottom: 8px; }
.dash-field-edit__row { display: flex; gap: 10px; }
.dash-field-edit__row input {
  flex: 1; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--line);
  background: var(--paper); color: var(--ink); font-size: 0.9rem;
}

.dash-stars-input { display: flex; gap: 6px; margin: 4px 0 16px; }
.dash-stars-input__star {
  background: none; border: none; cursor: pointer; font-size: 1.6rem; line-height: 1;
  color: var(--line); padding: 0;
}
.dash-stars-input__star--filled { color: var(--signal); }

.dash-avis-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; align-items: start; }
@media (max-width: 780px) { .dash-avis-layout { grid-template-columns: 1fr; } }

.dash-avis-form textarea {
  width: 100%; min-height: 120px; padding: 12px; border-radius: 8px; border: 1px solid var(--line);
  background: var(--paper); color: var(--ink); font-size: 0.9rem; font-family: inherit; resize: vertical;
  margin-bottom: 16px;
}

.dash-avis-preview__label {
  font-family: var(--font-mono); font-size: 0.72rem; letter-spacing: 0.05em; text-transform: uppercase;
  color: var(--ink-fade); margin-bottom: 10px;
}
.dash-avis-preview__empty { color: var(--ink-fade); font-size: 0.88rem; }
.dash-avis-preview .review-item { border: 1px solid var(--line); border-radius: 16px; background: var(--paper); }
```

- [ ] **Step 2: Commit**

```bash
git add assets/css/dashboard.css
git commit -m "feat(dashboard): add styles for photo upload, star selector, avis preview"
```

---

### Task 8: Rewrite `assets/js/dashboard.js` — photo, LinkedIn, avis + live preview, inline invoice links

**Files:**
- Modify: `assets/js/dashboard.js`

**Interfaces:**
- Consumes: `window.DASHBOARD_I18N` (Task 6), `window.DASHBOARD_CSRF_TOKEN` (existing), the four new endpoints from Tasks 2-5, `GET /api/dashboard-data.php` (existing, now also returns `id`, `linkedin`, `review`, `satisfaction`).
- Produces: renders into `#dashboard-profile` (now includes photo + LinkedIn), `#dashboard-avis` (new), and updates `#dashboard-invoices` links to use the inline-view endpoint.

- [ ] **Step 1: Replace the full contents of `assets/js/dashboard.js`**

```javascript
(function () {
  var I18N = window.DASHBOARD_I18N || {};
  var account = null;

  function escHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  var BILLING_CLASS = {
    'Payé': 'dash-badge--paid',
    'Dispensé': 'dash-badge--dispense',
    'En attente': 'dash-badge--pending',
    'En cours': 'dash-badge--cours',
    'Facturé': 'dash-badge--invoiced',
  };

  var SATISFACTION_VALUES = ['⭐', '⭐⭐', '⭐⭐⭐', '⭐⭐⭐⭐', '⭐⭐⭐⭐⭐'];

  fetch('/api/dashboard-data.php')
    .then(function (r) { return r.json(); })
    .then(function (json) {
      if (!json.success) throw new Error(json.error || I18N.err_generic || 'Error');
      account = json.account;
      renderSummary();
      renderProfile();
      renderBilling();
      renderInvoices();
      renderAvis();
      renderPasswordForm();
    })
    .catch(function (e) {
      document.getElementById('dashboard-alert').innerHTML =
        '<div class="alert alert--error"><span>!</span><span>' + escHtml(e.message) + '</span></div>';
    });

  function avatarUrl(bust) {
    var url = '/api/notion-avatar.php?id=' + encodeURIComponent(account.id);
    if (bust) url += '&v=' + Date.now();
    return url;
  }

  function renderSummary() {
    document.getElementById('dashboard-summary').innerHTML =
      '<div>' +
        '<div class="dash-summary__greeting">' + escHtml(I18N.greeting) + ' ' + escHtml(account.name) + '</div>' +
        '<div class="dash-summary__sub">' + escHtml(account.service || I18N.no_service) + '</div>' +
      '</div>' +
      '<span class="dash-badge ' + (BILLING_CLASS[account.billing] || 'dash-badge--invoiced') + '">' + escHtml(account.billing || '—') + '</span>';
  }

  function renderBilling() {
    document.getElementById('dashboard-billing').innerHTML =
      '<h2>' + escHtml(I18N.label_billing) + '</h2>' +
      (account.price ? '<div class="dash-price">' + account.price + ' €</div>' : '') +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_status) + '</span><span class="dash-field__value">' + escHtml(account.billing || '—') + '</span></div>' +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_last_login) + '</span><span class="dash-field__value">' + (account.lastLogin ? new Date(account.lastLogin).toLocaleString('fr-FR') : '—') + '</span></div>';
  }

  function renderInvoices() {
    var invoicesEl = document.getElementById('dashboard-invoices');
    var rows = (account.invoices || []).map(function (f, index) {
      return '<div class="dash-invoice-row">' +
        '<span class="dash-invoice-row__name">📄 ' + escHtml(f.name) + '</span>' +
        '<a href="/api/dashboard-view-invoice.php?index=' + index + '" target="_blank" rel="noopener noreferrer" class="btn btn--ghost">' + escHtml(I18N.view) + '</a>' +
      '</div>';
    }).join('');
    invoicesEl.innerHTML = '<h2>' + escHtml(I18N.label_invoices) + '</h2>' + (rows || '<div class="dash-invoice-empty">' + escHtml(I18N.empty_invoices) + '</div>');
  }

  function renderProfile() {
    var el = document.getElementById('dashboard-profile');
    el.innerHTML =
      '<h2>' + escHtml(I18N.label_profile) + '</h2>' +
      '<div class="dash-photo">' +
        '<img class="dash-photo__img" id="photo-preview" src="' + avatarUrl(false) + '" alt="" onerror="this.style.visibility=\'hidden\'">' +
        '<div class="dash-photo__actions">' +
          '<span class="dash-photo__hint">' + escHtml(I18N.label_photo) + '</span>' +
          '<button type="button" class="btn btn--ghost" id="photo-change-btn">' + escHtml(I18N.change_photo) + '</button>' +
          '<input type="file" id="photo-input" accept="image/jpeg,image/png,image/webp" hidden>' +
        '</div>' +
      '</div>' +
      '<div id="photo-alert"></div>' +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_name) + '</span><span class="dash-field__value">' + escHtml(account.name) + '</span></div>' +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_email) + '</span><span class="dash-field__value">' + escHtml(account.email) + '</span></div>' +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_company) + '</span><span class="dash-field__value">' + escHtml(account.company || '—') + '</span></div>' +
      '<div class="dash-field-edit">' +
        '<label for="linkedin-input">' + escHtml(I18N.label_linkedin) + '</label>' +
        '<div class="dash-field-edit__row">' +
          '<input type="url" id="linkedin-input" value="' + escHtml(account.linkedin || '') + '" placeholder="' + escHtml(I18N.placeholder_linkedin) + '">' +
          '<button type="button" class="btn btn--primary" id="linkedin-save-btn">' + escHtml(I18N.save) + '</button>' +
        '</div>' +
      '</div>' +
      '<div id="linkedin-alert"></div>';

    document.getElementById('photo-change-btn').addEventListener('click', function () {
      document.getElementById('photo-input').click();
    });
    document.getElementById('photo-input').addEventListener('change', onPhotoSelected);
    document.getElementById('linkedin-save-btn').addEventListener('click', onLinkedinSave);
  }

  function onPhotoSelected(e) {
    var file = e.target.files[0];
    if (!file) return;
    var alertBox = document.getElementById('photo-alert');
    alertBox.innerHTML = '';

    var formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', window.DASHBOARD_CSRF_TOKEN);

    fetch('/api/dashboard-upload-photo.php', { method: 'POST', body: formData })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json.success) {
          document.getElementById('photo-preview').src = avatarUrl(true);
          alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.photo_updated) + '</span></div>';
        } else {
          alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
        }
      });
  }

  function onLinkedinSave() {
    var alertBox = document.getElementById('linkedin-alert');
    alertBox.innerHTML = '';
    var value = document.getElementById('linkedin-input').value.trim();

    fetch('/api/dashboard-update-linkedin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.DASHBOARD_CSRF_TOKEN },
      body: JSON.stringify({ linkedin: value }),
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json.success) {
          account.linkedin = value;
          alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.linkedin_updated) + '</span></div>';
        } else {
          alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
        }
      });
  }

  function renderAvis() {
    var el = document.getElementById('dashboard-avis');
    var currentStars = SATISFACTION_VALUES.indexOf(account.satisfaction) + 1; // 0 if none set
    if (currentStars < 1) currentStars = 5;

    el.innerHTML =
      '<h2>' + escHtml(I18N.label_avis_title) + '</h2>' +
      '<div class="dash-avis-layout">' +
        '<div class="dash-avis-form">' +
          '<label for="avis-textarea">' + escHtml(I18N.label_avis_text) + '</label>' +
          '<div class="dash-stars-input" id="avis-stars"></div>' +
          '<textarea id="avis-textarea" placeholder="' + escHtml(I18N.placeholder_avis) + '">' + escHtml(account.review || '') + '</textarea>' +
          '<div id="avis-alert"></div>' +
          '<button type="button" class="btn btn--primary" id="avis-save-btn">' + escHtml(I18N.publish) + '</button>' +
        '</div>' +
        '<div class="dash-avis-preview">' +
          '<div class="dash-avis-preview__label">' + escHtml(I18N.preview_label) + '</div>' +
          '<div id="avis-preview-content"></div>' +
        '</div>' +
      '</div>';

    renderStarsInput(currentStars);
    updateAvisPreview();

    document.getElementById('avis-textarea').addEventListener('input', updateAvisPreview);
    document.getElementById('avis-save-btn').addEventListener('click', onAvisSave);
  }

  function renderStarsInput(selected) {
    var starsEl = document.getElementById('avis-stars');
    var html = '';
    for (var i = 1; i <= 5; i++) {
      html += '<button type="button" class="dash-stars-input__star' + (i <= selected ? ' dash-stars-input__star--filled' : '') + '" data-value="' + i + '">★</button>';
    }
    starsEl.innerHTML = html;
    starsEl.dataset.selected = selected;

    Array.prototype.forEach.call(starsEl.querySelectorAll('.dash-stars-input__star'), function (btn) {
      btn.addEventListener('click', function () {
        renderStarsInput(parseInt(btn.dataset.value, 10));
        updateAvisPreview();
      });
    });
  }

  function currentSelectedSatisfaction() {
    var selected = parseInt(document.getElementById('avis-stars').dataset.selected, 10) || 5;
    return SATISFACTION_VALUES[selected - 1];
  }

  function updateAvisPreview() {
    var text = document.getElementById('avis-textarea').value.trim();
    var previewEl = document.getElementById('avis-preview-content');

    if (text === '') {
      previewEl.innerHTML = '<div class="dash-avis-preview__empty">' + escHtml(I18N.preview_empty) + '</div>';
      return;
    }

    var selected = parseInt(document.getElementById('avis-stars').dataset.selected, 10) || 5;
    var starsHtml = '★'.repeat(selected) + '☆'.repeat(5 - selected);
    var initials = escHtml((account.name || '').split(' ').map(function (p) { return p[0] || ''; }).join('').toUpperCase());
    var avatarSrc = avatarUrl(false);

    previewEl.innerHTML =
      '<div class="review-item">' +
        '<div class="review-header">' +
          '<div class="review-avatar">' +
            '<img src="' + avatarSrc + '" alt="" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">' +
            '<span style="display:none">' + initials + '</span>' +
          '</div>' +
          '<div class="review-info">' +
            '<span class="review-name">' + escHtml(account.name) + '</span>' +
            (account.service ? '<div class="review-profession">' + escHtml(account.service) + (account.company ? ' <span class="company-name">· ' + escHtml(account.company) + '</span>' : '') + '</div>' : '') +
          '</div>' +
        '</div>' +
        '<div class="review-content-scroll"><p class="review-text">' + escHtml(text) + '</p></div>' +
        '<div class="review-stars">' + starsHtml + '</div>' +
      '</div>';
  }

  function onAvisSave() {
    var alertBox = document.getElementById('avis-alert');
    alertBox.innerHTML = '';
    var text = document.getElementById('avis-textarea').value.trim();
    var satisfaction = currentSelectedSatisfaction();

    if (text === '') {
      alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(I18N.err_avis_empty) + '</span></div>';
      return;
    }

    fetch('/api/dashboard-update-review.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.DASHBOARD_CSRF_TOKEN },
      body: JSON.stringify({ review: text, satisfaction: satisfaction }),
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json.success) {
          account.review = text;
          account.satisfaction = satisfaction;
          alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.avis_updated) + '</span></div>';
        } else {
          alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
        }
      });
  }

  function renderPasswordForm() {
    var el = document.getElementById('dashboard-password');
    el.innerHTML =
      '<h2>' + escHtml(I18N.change_password_title) + '</h2>' +
      '<div id="password-alert"></div>' +
      '<form id="password-form" class="dash-password-form" novalidate>' +
        '<div class="field">' +
          '<label for="current_password">' + escHtml(I18N.label_current_password) + '</label>' +
          '<input type="password" id="current_password" name="current_password" required>' +
        '</div>' +
        '<div class="field">' +
          '<label for="new_password">' + escHtml(I18N.label_new_password) + '</label>' +
          '<input type="password" id="new_password" name="new_password" minlength="8" required>' +
        '</div>' +
        '<button type="submit" class="btn btn--primary">' + escHtml(I18N.submit_update) + '</button>' +
      '</form>';

    document.getElementById('password-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var alertBox = document.getElementById('password-alert');
      alertBox.innerHTML = '';

      fetch('/api/dashboard-change-password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.DASHBOARD_CSRF_TOKEN },
        body: JSON.stringify({
          current_password: document.getElementById('current_password').value,
          new_password: document.getElementById('new_password').value,
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          if (json.success) {
            alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.password_updated) + '</span></div>';
            document.getElementById('password-form').reset();
          } else {
            alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
          }
        });
    });
  }
})();
```

- [ ] **Step 2: Browser verification**

Start the local vhost (`http://slapia.local/`), log in as a real client account, open `/dashboard`, and confirm:
- The profile card shows the current avatar, a "Changer la photo" button, and a LinkedIn field pre-filled from Notion.
- The avis card shows a 5-star clickable selector, a textarea pre-filled with any existing avis, and a live preview that updates on every keystroke and star click, matching the visual structure of a real testimonial on the homepage.
- Each invoice row's action button opens the PDF in a new tab (inline), not a download prompt.
- Do not click "Publier"/"Enregistrer"/upload a real photo against the live account during this task's verification — that is manual QA the user will do themselves, per the Global Constraints.

- [ ] **Step 3: Commit**

```bash
git add assets/js/dashboard.js
git commit -m "feat(dashboard): render photo upload, LinkedIn edit, avis editor with live preview"
```

---

### Task 9: Translations (fr/en/de)

**Files:**
- Modify: `lang/fr.php`
- Modify: `lang/en.php`
- Modify: `lang/de.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: every `dashboard.*` key referenced by Task 6/8 that doesn't already exist, in all 3 languages, so `window.DASHBOARD_I18N` never falls back to a raw key.

- [ ] **Step 1: Add the new keys to `lang/fr.php`**

In `lang/fr.php`, inside the existing `'dashboard' => [ ... ]` array (currently ending at `'err_password_update_failed' => 'La mise à jour a échoué. Réessayez.',`), add before the closing `],`:

```php
    'view' => 'Consulter',
    'label_photo' => 'Photo de profil',
    'change_photo' => 'Changer la photo',
    'photo_updated' => 'Photo mise à jour.',
    'err_photo_type' => 'Formats acceptés : JPEG, PNG, WebP.',
    'err_photo_size' => 'Fichier trop volumineux (5 Mo max).',
    'err_photo_failed' => 'L\'upload a échoué. Réessayez.',
    'err_photo_missing' => 'Aucune photo sélectionnée.',
    'label_linkedin' => 'LinkedIn',
    'placeholder_linkedin' => 'https://www.linkedin.com/in/...',
    'save' => 'Enregistrer',
    'linkedin_updated' => 'Lien LinkedIn mis à jour.',
    'err_linkedin_invalid' => 'Le lien LinkedIn doit commencer par http:// ou https://.',
    'err_linkedin_failed' => 'La mise à jour a échoué. Réessayez.',
    'label_avis_title' => 'Mon avis',
    'label_avis_text' => 'Votre avis',
    'placeholder_avis' => 'Partagez votre expérience avec SlapIA...',
    'label_satisfaction' => 'Votre note',
    'publish' => 'Publier',
    'avis_updated' => 'Avis publié.',
    'err_avis_empty' => 'Merci d\'écrire un avis avant de publier.',
    'err_avis_too_long' => 'Votre avis est trop long (2000 caractères max).',
    'err_avis_failed' => 'La publication a échoué. Réessayez.',
    'err_satisfaction_invalid' => 'Note invalide.',
    'preview_label' => 'Aperçu — tel qu\'il apparaîtra sur le site',
    'preview_empty' => 'Écrivez votre avis pour voir l\'aperçu.',
```

- [ ] **Step 2: Add the equivalent keys to `lang/en.php`**

In `lang/en.php`, inside the `'dashboard' => [ ... ]` array, add the same keys with English values:

```php
    'view' => 'View',
    'label_photo' => 'Profile photo',
    'change_photo' => 'Change photo',
    'photo_updated' => 'Photo updated.',
    'err_photo_type' => 'Accepted formats: JPEG, PNG, WebP.',
    'err_photo_size' => 'File too large (5 MB max).',
    'err_photo_failed' => 'Upload failed. Please try again.',
    'err_photo_missing' => 'No photo selected.',
    'label_linkedin' => 'LinkedIn',
    'placeholder_linkedin' => 'https://www.linkedin.com/in/...',
    'save' => 'Save',
    'linkedin_updated' => 'LinkedIn link updated.',
    'err_linkedin_invalid' => 'The LinkedIn link must start with http:// or https://.',
    'err_linkedin_failed' => 'Update failed. Please try again.',
    'label_avis_title' => 'My review',
    'label_avis_text' => 'Your review',
    'placeholder_avis' => 'Share your experience with SlapIA...',
    'label_satisfaction' => 'Your rating',
    'publish' => 'Publish',
    'avis_updated' => 'Review published.',
    'err_avis_empty' => 'Please write a review before publishing.',
    'err_avis_too_long' => 'Your review is too long (2000 characters max).',
    'err_avis_failed' => 'Publishing failed. Please try again.',
    'err_satisfaction_invalid' => 'Invalid rating.',
    'preview_label' => 'Preview — how it will appear on the site',
    'preview_empty' => 'Write your review to see the preview.',
```

- [ ] **Step 3: Add the equivalent keys to `lang/de.php`**

In `lang/de.php`, inside the `'dashboard' => [ ... ]` array, add the same keys with German values:

```php
    'view' => 'Ansehen',
    'label_photo' => 'Profilfoto',
    'change_photo' => 'Foto ändern',
    'photo_updated' => 'Foto aktualisiert.',
    'err_photo_type' => 'Zulässige Formate: JPEG, PNG, WebP.',
    'err_photo_size' => 'Datei zu groß (max. 5 MB).',
    'err_photo_failed' => 'Upload fehlgeschlagen. Bitte erneut versuchen.',
    'err_photo_missing' => 'Kein Foto ausgewählt.',
    'label_linkedin' => 'LinkedIn',
    'placeholder_linkedin' => 'https://www.linkedin.com/in/...',
    'save' => 'Speichern',
    'linkedin_updated' => 'LinkedIn-Link aktualisiert.',
    'err_linkedin_invalid' => 'Der LinkedIn-Link muss mit http:// oder https:// beginnen.',
    'err_linkedin_failed' => 'Aktualisierung fehlgeschlagen. Bitte erneut versuchen.',
    'label_avis_title' => 'Meine Bewertung',
    'label_avis_text' => 'Ihre Bewertung',
    'placeholder_avis' => 'Teilen Sie Ihre Erfahrung mit SlapIA...',
    'label_satisfaction' => 'Ihre Bewertung',
    'publish' => 'Veröffentlichen',
    'avis_updated' => 'Bewertung veröffentlicht.',
    'err_avis_empty' => 'Bitte schreiben Sie eine Bewertung, bevor Sie veröffentlichen.',
    'err_avis_too_long' => 'Ihre Bewertung ist zu lang (max. 2000 Zeichen).',
    'err_avis_failed' => 'Veröffentlichung fehlgeschlagen. Bitte erneut versuchen.',
    'err_satisfaction_invalid' => 'Ungültige Bewertung.',
    'preview_label' => 'Vorschau — so erscheint sie auf der Website',
    'preview_empty' => 'Schreiben Sie Ihre Bewertung, um die Vorschau zu sehen.',
```

- [ ] **Step 4: Lint all three files**

Run: `php -l lang/fr.php && php -l lang/en.php && php -l lang/de.php`
Expected: `No syntax errors detected` × 3.

- [ ] **Step 5: Verify key parity across languages**

Run:
```bash
php -r '
$fr = array_keys((require "lang/fr.php")["dashboard"]);
$en = array_keys((require "lang/en.php")["dashboard"]);
$de = array_keys((require "lang/de.php")["dashboard"]);
sort($fr); sort($en); sort($de);
echo ($fr === $en && $fr === $de) ? "OK: identical key sets\n" : "MISMATCH\n";
'
```
Expected: `OK: identical key sets`

- [ ] **Step 6: Commit**

```bash
git add lang/fr.php lang/en.php lang/de.php
git commit -m "i18n: add translations for dashboard profile features"
```

---

## Final Whole-Branch Review

After all 9 tasks are complete, dispatch a final whole-branch code review covering the full diff introduced by this plan (Tasks 1-9 together), checking against every item in Global Constraints above — in particular: CSRF coverage on every write endpoint, that no endpoint accepts a client-supplied page/account ID for the acting user's own data, the invoice-view endpoint's SSRF protection (index-only, scheme-checked), `escHtml()`/scheme-check discipline in the rewritten `dashboard.js`, satisfaction-value validation matching the exact 5 real Notion select values, and full i18n coverage (no hardcoded strings in the JS or PHP).
