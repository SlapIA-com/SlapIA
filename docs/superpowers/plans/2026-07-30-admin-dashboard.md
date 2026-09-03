# Dashboard Admin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a standalone, admin-only dashboard (`pages/admin.php`) covering account management (role/billing status, password reset), an invoice view with real PDF upload to Notion, an RSS-subscriber list, and three analytics charts — all backed by the live Notion "ERP" database.

**Architecture:** PHP + vanilla JS (fetch + DOM rendering), matching the auth foundation's conventions exactly. All Notion access goes through the existing `NotionAPI` class, extended with the Notion File Upload API (3-step: create → send → attach). A new `includes/notion-admin.php` owns every admin-dashboard Notion operation; `pages/admin.php` is a single page with client-side tab switching (Overview / Comptes / Abonnés RSS / Factures), backed by one aggregate `api/admin-data.php` plus small mutation endpoints. Charts render via a self-hosted Chart.js (no external CDN, keeps the existing strict CSP).

**Tech Stack:** PHP 8, existing `NotionAPI` class, Chart.js 4.4.4 (self-hosted), existing i18n system (fr/en/de), existing CSS design system + a new admin-only stylesheet.

## Global Constraints

- Every route/endpoint in this plan is gated by `requireAdmin()` (already implemented in `includes/auth.php` from the auth-foundation sub-project).
- Every mutating endpoint verifies CSRF via `verifyCSRFToken()` (already implemented in `includes/config.php`).
- Notion write helpers must check the response (`http_code`/`error`) and return `bool`/`null` on failure, never report success silently — same discipline as the auth foundation's `includes/notion-users.php`.
- Real Notion schema (verified live against the production "ERP" database, ID `2f0b2071-3b6f-8054-bf30-d158398a892a`):
  - `Status` (select): options are exactly `Particulier`, `Entreprise` (empty = admin).
  - `Facturation` (select): options are exactly `Facturé`, `Payé`, `En cours`, `En attente`, `Dispensé`.
  - `Prix` (number), `Type de service` (select), `Nom d'entreprise` (rich_text), `Factures` (files), `Prenom NOM` (title), `Email` (email).
  - `Dernière connexion` (date) does **not** exist yet — the user must create it manually in Notion. Code must degrade gracefully (read as `null`, write failures logged not fatal) until it exists.
- RSS subscribers live in a separate Notion database, ID `32cb2071-3b6f-80df-9294-e394733f4f2f` ("RSS Subscriber"), with a single property: `Email` (title). There is no separate "Newsletter" database — the admin dashboard must not assume one exists.
- No SQL database — Notion remains the only backend, per the auth foundation's spec.
- No automated test framework in this project — verification uses `php -l`, `php -r` CLI smoke checks, and `curl`, exactly as in the auth-foundation plan.
- **Live-data safety (critical):** `.env` holds a real production Notion API key. Automated verification during implementation must NEVER mutate a real client account (role change, billing change, password reset, invoice upload against a real client page). Where a mutating endpoint needs live verification, test only against the CSRF-gate (expect 403 with no session, as established in the auth-foundation plan) or, if a real write must be exercised, only against the user's own admin account (`thomas25.lapierre@outlook.com`) with the user's prior awareness — never a real client account. Full end-to-end QA of mutating admin actions is the user's own manual step, called out explicitly in each relevant task.
- Chart.js is pinned to version 4.4.4 and self-hosted at `assets/js/vendor/chart.min.js` — never loaded from a CDN.
- All user-facing strings go through `t('admin.xxx')` / `t('nav.admin')`, translated in fr/en/de (per user's explicit choice to keep full i18n parity with the public site).

---

### Task 1: Notion File Upload API support

**Files:**
- Modify: `includes/notion.php`

**Interfaces:**
- Produces: `NotionAPI::createFileUpload(string $filename, string $contentType): array` (returns the raw Notion response — contains `id`, `upload_url`, `status`, etc., or `error`/`http_code` on failure), `NotionAPI::sendFileUpload(string $uploadUrl, string $localFilePath, string $filename, string $mimeType): array` (returns the raw Notion response after sending file bytes — contains `status` which should become `"uploaded"` on success).
- Consumed by: Task 7 (`includes/notion-admin.php`'s `uploadInvoiceFile()`).

Notion's File Upload API (verified against official docs, `Notion-Version: 2026-03-11`) works in two calls this task must support:
1. `POST https://api.notion.com/v1/file_uploads` with JSON body `{"mode": "single_part", "filename": "...", "content_type": "..."}` → response includes `id` and `upload_url`.
2. `POST` to the returned `upload_url`, with `multipart/form-data` body containing a `file` field with the raw file bytes → response `status` becomes `"uploaded"` on success (single-part mode does not require a separate "complete" call — the send step finishes it).

The existing `NotionAPI::request()` method always sets `Content-Type: application/json`, which cannot be reused for step 2 (multipart upload) — this task adds a dedicated method for that step instead of modifying `request()`.

- [ ] **Step 1: Add `createFileUpload()` to the `NotionAPI` class**

Open `includes/notion.php` and add this method inside the `NotionAPI` class, right after the existing `updatePage()` method (around line 40):

```php
    /** POST /v1/file_uploads — step 1 of the Notion File Upload flow. */
    public function createFileUpload(string $filename, string $contentType): array
    {
        return $this->request('POST', '/file_uploads', [
            'mode'         => 'single_part',
            'filename'     => $filename,
            'content_type' => $contentType,
        ]);
    }

    /**
     * POST to the file upload's own upload_url — step 2 of the Notion File
     * Upload flow. Bypasses request() because this call needs
     * multipart/form-data with raw file bytes, not JSON.
     */
    public function sendFileUpload(string $uploadUrl, string $localFilePath, string $filename, string $mimeType): array
    {
        $ch = curl_init($uploadUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Notion-Version: ' . NOTION_VERSION,
            ],
            CURLOPT_POSTFIELDS     => [
                'file' => new CURLFile($localFilePath, $mimeType, $filename),
            ],
        ]);

        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);

        if ($curlErr) {
            return ['error' => $curlErr, 'http_code' => 0];
        }

        $decoded = json_decode($raw, true) ?? [];
        $decoded['http_code'] = $httpCode;
        return $decoded;
    }
```

- [ ] **Step 2: Bump `NOTION_VERSION` for file-upload support**

Still in `includes/notion.php`, find the constant definition near the top:
```php
    define('NOTION_VERSION', '2022-06-28');
```
Change it to:
```php
    define('NOTION_VERSION', '2022-06-28'); // NOTE: file uploads require 2026-03-11 (see createFileUpload/sendFileUpload below, which pass their own header override — see Step 3)
```

Actually — since the File Upload API needs `Notion-Version: 2026-03-11` while the rest of this class's existing calls were built and tested against `2022-06-28`, do NOT change the global constant (that could silently change behavior for every other existing call in the codebase). Instead, revert Step 2 and use a dedicated constant:

```php
    define('NOTION_VERSION', '2022-06-28');
    define('NOTION_FILE_UPLOAD_VERSION', '2026-03-11');
```

Then update the two new methods from Step 1 to use `NOTION_FILE_UPLOAD_VERSION` instead of `NOTION_VERSION` in their headers:
- In `createFileUpload()`: this method calls `$this->request(...)`, which always uses `NOTION_VERSION`. Add a new private helper instead of routing through `request()`:

```php
    /** POST /v1/file_uploads — step 1 of the Notion File Upload flow. */
    public function createFileUpload(string $filename, string $contentType): array
    {
        $ch = curl_init(NOTION_API_BASE . '/file_uploads');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Notion-Version: ' . NOTION_FILE_UPLOAD_VERSION,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode([
                'mode'         => 'single_part',
                'filename'     => $filename,
                'content_type' => $contentType,
            ]),
        ]);

        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);

        if ($curlErr) {
            return ['error' => $curlErr, 'http_code' => 0];
        }

        $decoded = json_decode($raw, true) ?? [];
        $decoded['http_code'] = $httpCode;
        return $decoded;
    }
```

- And update `sendFileUpload()`'s header line from `'Notion-Version: ' . NOTION_VERSION,` to `'Notion-Version: ' . NOTION_FILE_UPLOAD_VERSION,`.

(This replaces the earlier Step 1 code for `createFileUpload()` — use this version, and use `NOTION_FILE_UPLOAD_VERSION` in `sendFileUpload()`.)

- [ ] **Step 3: Lint the file**

Run: `php -l includes/notion.php`
Expected: `No syntax errors detected in includes/notion.php`

- [ ] **Step 4: Smoke-test the two-step upload with a throwaway test file (safe — does not touch any real client page)**

Create a tiny temporary test file:
```bash
echo "test invoice content" > /tmp/test-invoice.txt
```

Run:
```bash
php -r "
require 'includes/notion.php';
require 'includes/config.php';
\$n = notion();
\$create = \$n->createFileUpload('test-invoice.txt', 'text/plain');
echo 'CREATE status: ' . (\$create['status'] ?? 'ERROR') . ', id: ' . (\$create['id'] ?? 'none') . PHP_EOL;
if (!empty(\$create['id'])) {
    \$send = \$n->sendFileUpload(\$create['upload_url'], '/tmp/test-invoice.txt', 'test-invoice.txt', 'text/plain');
    echo 'SEND status: ' . (\$send['status'] ?? 'ERROR') . PHP_EOL;
}
"
```
Expected: `CREATE status: pending, id: <some-uuid>` followed by `SEND status: uploaded`. This only creates a transient file-upload object in Notion (not attached to any page) — it does not touch any client data. Delete the temp file afterward: `rm /tmp/test-invoice.txt`.

If the response shows an error or unexpected status, report BLOCKED with the full response — do not guess a fix, the File Upload API may have changed since this plan was written.

- [ ] **Step 5: Commit**

```bash
git add includes/notion.php
git commit -m "feat(admin): add Notion File Upload API support (create + send)"
```

---

### Task 2: RSS subscribers and last-login tracking

**Files:**
- Modify: `.env`
- Modify: `includes/notion-users.php`

**Interfaces:**
- Produces: `setLastLogin(string $pageId): bool` in `includes/notion-users.php`.
- Consumed by: Task 3 (`includes/notion-admin.php`'s `listRssSubscribers()` reads the new env key), Task 8 (`api/auth-login.php` calls `setLastLogin()`).

- [ ] **Step 1: Add the RSS Subscriber database ID to `.env`**

Open `.env` and append:
```
NOTION_RSS_SUBSCRIBER_DATABASE_ID=32cb2071-3b6f-80df-9294-e394733f4f2f
```

- [ ] **Step 2: Add `setLastLogin()` to `includes/notion-users.php`**

Add this function at the end of `includes/notion-users.php`:

```php
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
```

- [ ] **Step 3: Lint the file**

Run: `php -l includes/notion-users.php`
Expected: `No syntax errors detected in includes/notion-users.php`

- [ ] **Step 4: Smoke-test against the user's own admin account (safe — this is the user's own account, not a client)**

```bash
php -r "
require 'includes/notion-users.php';
\$u = findUserByEmail('thomas25.lapierre@outlook.com');
var_dump(\$u !== null);
if (\$u) {
    \$ok = setLastLogin(\$u['id']);
    var_dump(\$ok);
}
"
```
Expected: `bool(true)` then either `bool(true)` (if the "Dernière connexion" property already exists in Notion) or `bool(false)` (if it doesn't exist yet — expected until the user creates it, not a bug). Report which outcome you saw.

- [ ] **Step 5: Commit**

```bash
git add .env includes/notion-users.php
```

Note: `.env` is gitignored — only `includes/notion-users.php` will actually be staged. Commit:
```bash
git commit -m "feat(admin): add last-login tracking and RSS subscriber DB config"
```

---

### Task 3: Admin-dashboard Notion operations

**Files:**
- Create: `includes/notion-admin.php`

**Interfaces:**
- Consumes: `notion()`, `NotionAPI::{richText,title,select,number,createFileUpload,sendFileUpload}` (`includes/notion.php`), `resolveUserRole()` (`includes/notion-users.php`), `updatePassword()` (`includes/notion-users.php`, reused for admin-triggered password resets — do not duplicate the hashing logic), `config()` (`includes/config.php`).
- Produces: `listAllAccounts(): array` (each item: `id, name, email, company, service, role, billing, price, lastLogin, invoiceCount`), `updateAccountRole(string $pageId, string $role): bool` (`$role` must be `'particulier'|'entreprise'|'admin'`), `updateAccountBilling(string $pageId, string $billing): bool` (`$billing` must be one of the 5 real Facturation values), `listRssSubscribers(): array` (each item: `email, subscribedAt`), `resetAccountPassword(string $pageId, string $newPassword): bool`, `uploadInvoiceFile(string $pageId, string $localFilePath, string $filename, string $mimeType): bool`. Used by Tasks 4, 5, 6, 7.

- [ ] **Step 1: Create `includes/notion-admin.php`**

```php
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

        $files = $props['Factures']['files'] ?? [];

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
    $dbId   = config('NOTION_RSS_SUBSCRIBER_DATABASE_ID');
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
    $existingFiles = $page['properties']['Factures']['files'] ?? [];

    $newFileEntries = array_map(function ($f) {
        // Re-express already-hosted files the same way Notion returned them,
        // dropping any fields Notion doesn't accept back on write.
        if (($f['type'] ?? '') === 'file_upload') {
            return ['type' => 'file_upload', 'file_upload' => ['id' => $f['file_upload']['id']], 'name' => $f['name'] ?? ''];
        }
        return ['type' => 'external', 'external' => ['url' => $f['file']['url'] ?? $f['external']['url'] ?? ''], 'name' => $f['name'] ?? ''];
    }, $existingFiles);

    $newFileEntries[] = [
        'type'        => 'file_upload',
        'file_upload' => ['id' => $create['id']],
        'name'        => $filename,
    ];

    $result = notion()->updatePage($pageId, [
        'properties' => [
            'Factures' => ['files' => $newFileEntries],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Admin] uploadInvoiceFile attach step failed for page ' . $pageId . ': ' . json_encode($result));
        return false;
    }

    return true;
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l includes/notion-admin.php`
Expected: `No syntax errors detected in includes/notion-admin.php`

- [ ] **Step 3: Smoke-test the pure logic (no network)**

```bash
php -r "
require 'includes/notion-admin.php';
var_dump(roleToNotionStatus('entreprise') === 'Entreprise');
var_dump(roleToNotionStatus('particulier') === 'Particulier');
var_dump(roleToNotionStatus('admin') === '');
var_dump(in_array('En attente', ADMIN_BILLING_STATUSES, true));
"
```
Expected: four `bool(true)` lines.

- [ ] **Step 4: Smoke-test live reads (safe, read-only, no mutation)**

```bash
php -r "
require 'includes/notion-admin.php';
\$accounts = listAllAccounts();
echo 'Accounts found: ' . count(\$accounts) . PHP_EOL;
if (!empty(\$accounts)) {
    echo json_encode(\$accounts[0], JSON_PRETTY_PRINT) . PHP_EOL;
}
\$subs = listRssSubscribers();
echo 'RSS subscribers found: ' . count(\$subs) . PHP_EOL;
"
```
Expected: a positive account count with a sensible first record (name/email populated, `role` one of `particulier`/`entreprise`/`admin`), and a subscriber count ≥ 0. Do NOT call `updateAccountRole()`, `updateAccountBilling()`, `resetAccountPassword()`, or `uploadInvoiceFile()` in this task's verification — those are mutating and covered by the live-data safety constraint; their correctness is verified by code review and by the CSRF-gate checks in the tasks that expose them via API endpoints.

- [ ] **Step 5: Commit**

```bash
git add includes/notion-admin.php
git commit -m "feat(admin): add Notion-backed account, RSS subscriber, and invoice operations"
```

---

### Task 4: Admin data API endpoint

**Files:**
- Create: `api/admin-data.php`

**Interfaces:**
- Consumes: `requireAdmin()` (`includes/auth.php`), `listAllAccounts()`, `listRssSubscribers()` (`includes/notion-admin.php`).
- Produces: `GET /api/admin-data.php` → JSON `{success:true, accounts:[...], rssSubscribers:[...], chart:{growth:{labels,accounts,rss}, billing:{labels,counts}, roles:{labels,counts}}}`. Consumed by Task 11 (`assets/js/admin.js`).

- [ ] **Step 1: Create `api/admin-data.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-admin.php';

requireAdmin();

header('Content-Type: application/json');
ob_start();

try {
    $accounts = listAllAccounts();
    $rss      = listRssSubscribers();

    // Growth chart: new accounts + new RSS subscribers per month, last 6 months.
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $months[date('M Y', strtotime("-$i months"))] = ['accounts' => 0, 'rss' => 0];
    }
    // Account creation date isn't directly exposed by listAllAccounts(); approximate
    // growth using created_time is out of scope here since listAllAccounts() only
    // returns display fields — use lastLogin-independent counts of 0 for accounts
    // this endpoint doesn't have creation dates for. Real per-month account growth
    // requires each page's created_time, added below via a second lightweight pass.
    $dbId = config('NOTION_SATISFACTION_DATABASE_ID');
    $rawAccounts = notion()->queryDatabaseAll($dbId);
    foreach ($rawAccounts['results'] ?? [] as $page) {
        $hash = NotionAPI::richText($page['properties']['Mot de passe'] ?? []);
        if ($hash === '') continue;
        $m = date('M Y', strtotime($page['created_time']));
        if (isset($months[$m])) $months[$m]['accounts']++;
    }
    $rawRss = notion()->queryDatabaseAll(config('NOTION_RSS_SUBSCRIBER_DATABASE_ID'));
    foreach ($rawRss['results'] ?? [] as $page) {
        $m = date('M Y', strtotime($page['created_time']));
        if (isset($months[$m])) $months[$m]['rss']++;
    }

    // Billing status breakdown.
    $billingCounts = array_fill_keys(ADMIN_BILLING_STATUSES, 0);
    foreach ($accounts as $a) {
        if (isset($billingCounts[$a['billing']])) $billingCounts[$a['billing']]++;
    }

    // Role breakdown.
    $roleCounts = ['particulier' => 0, 'entreprise' => 0, 'admin' => 0];
    foreach ($accounts as $a) {
        $roleCounts[$a['role']]++;
    }

    ob_clean();
    echo json_encode([
        'success'        => true,
        'accounts'       => $accounts,
        'rssSubscribers' => $rss,
        'chart'          => [
            'growth'  => [
                'labels'   => array_keys($months),
                'accounts' => array_column(array_values($months), 'accounts'),
                'rss'      => array_column(array_values($months), 'rss'),
            ],
            'billing' => [
                'labels' => array_keys($billingCounts),
                'counts' => array_values($billingCounts),
            ],
            'roles'   => [
                'labels' => array_keys($roleCounts),
                'counts' => array_values($roleCounts),
            ],
        ],
    ]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Admin Data] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/admin-data.php`
Expected: `No syntax errors detected in api/admin-data.php`

- [ ] **Step 3: Verify the admin guard rejects anonymous requests**

With XAMPP Apache running:
```bash
curl -s -o /dev/null -w "%{http_code}\n" http://slapia.local/api/admin-data.php
```
Expected: a redirect status (302) to `/login` (from `requireAdmin()` → `requireRole('admin')` → not logged in → `header('Location: /login')`), confirming the guard is active before any data is returned. Use `-I` if you want to see the `Location` header explicitly: `curl -sI http://slapia.local/api/admin-data.php`.

- [ ] **Step 4: Commit**

```bash
git add api/admin-data.php
git commit -m "feat(admin): add aggregate admin dashboard data endpoint"
```

---

### Task 5: Account update endpoint (role + billing status)

**Files:**
- Create: `api/admin-update-account-exec.php`

**Interfaces:**
- Consumes: `requireAdmin()`, `verifyCSRFToken()`, `updateAccountRole()`, `updateAccountBilling()` (`includes/notion-admin.php`).
- Produces: `POST /api/admin-update-account-exec.php` with JSON body `{page_id, role?, billing?}` → `{success, error?}`. At least one of `role`/`billing` must be present; both can be sent together. Consumed by Task 11.

- [ ] **Step 1: Create `api/admin-update-account-exec.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-admin.php';

requireAdmin();

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

    $input  = json_decode(file_get_contents('php://input'), true) ?: [];
    $pageId = trim($input['page_id'] ?? '');
    $role   = $input['role'] ?? null;
    $billing = $input['billing'] ?? null;

    if ($pageId === '' || ($role === null && $billing === null)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('admin.err_fields')]);
        exit;
    }

    if ($role !== null && !updateAccountRole($pageId, $role)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('admin.err_update_failed')]);
        exit;
    }

    if ($billing !== null && !updateAccountBilling($pageId, $billing)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('admin.err_update_failed')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Admin Update Account] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/admin-update-account-exec.php`
Expected: `No syntax errors detected in api/admin-update-account-exec.php`

- [ ] **Step 3: Verify guards without mutating any account**

```bash
curl -s -o /dev/null -w "%{http_code}\n" -X POST http://slapia.local/api/admin-update-account-exec.php -H "Content-Type: application/json" -d '{}'
```
Expected: `302` (redirect to `/login` from `requireAdmin()`, since no session cookie is sent) — confirms the admin guard fires before CSRF/field checks, and no live account is touched by this verification.

- [ ] **Step 4: Commit**

```bash
git add api/admin-update-account-exec.php
git commit -m "feat(admin): add account role/billing update endpoint"
```

---

### Task 6: Admin password reset endpoint

**Files:**
- Create: `api/admin-reset-password-exec.php`

**Interfaces:**
- Consumes: `requireAdmin()`, `verifyCSRFToken()`, `findUserByEmail()` (`includes/notion-users.php`), `resetAccountPassword()` (`includes/notion-admin.php`).
- Produces: `POST /api/admin-reset-password-exec.php` with JSON body `{email, new_password}` → `{success, error?}`. Consumed by Task 11.

- [ ] **Step 1: Create `api/admin-reset-password-exec.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-users.php';
require_once __DIR__ . '/../includes/notion-admin.php';

requireAdmin();

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

    $input       = json_decode(file_get_contents('php://input'), true) ?: [];
    $email       = strtolower(trim($input['email'] ?? ''));
    $newPassword = $input['new_password'] ?? '';

    if ($email === '' || strlen($newPassword) < 8) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('admin.err_reset_fields')]);
        exit;
    }

    $userPage = findUserByEmail($email);
    if (!$userPage) {
        ob_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => t('admin.err_account_not_found')]);
        exit;
    }

    if (!resetAccountPassword($userPage['id'], $newPassword)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('admin.err_update_failed')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Admin Reset Password] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/admin-reset-password-exec.php`
Expected: `No syntax errors detected in api/admin-reset-password-exec.php`

- [ ] **Step 3: Verify the admin guard without resetting any real password**

```bash
curl -s -o /dev/null -w "%{http_code}\n" -X POST http://slapia.local/api/admin-reset-password-exec.php -H "Content-Type: application/json" -d '{}'
```
Expected: `302` (redirect to `/login`, no session cookie sent) — confirms the guard fires first. Do NOT test this endpoint's success path against any real account (including the admin's own) during automated verification — password resets are exactly the kind of mutation the live-data safety constraint exists to prevent from happening accidentally. Full functional verification is the user's own manual QA step.

- [ ] **Step 4: Commit**

```bash
git add api/admin-reset-password-exec.php
git commit -m "feat(admin): add admin-triggered password reset endpoint"
```

---

### Task 7: Invoice upload endpoint

**Files:**
- Create: `api/admin-upload-invoice-exec.php`

**Interfaces:**
- Consumes: `requireAdmin()`, `verifyCSRFToken()`, `uploadInvoiceFile()` (`includes/notion-admin.php`).
- Produces: `POST /api/admin-upload-invoice-exec.php` (multipart form: `page_id`, `csrf_token` as a form field since this is a `multipart/form-data` request rather than JSON, plus a `file` field) → `{success, error?}`. Consumed by Task 11.

- [ ] **Step 1: Create `api/admin-upload-invoice-exec.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-admin.php';

requireAdmin();

header('Content-Type: application/json');
ob_start();

try {
    // Multipart requests carry the CSRF token as a POST field, not a header.
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => t('auth.err_csrf')]);
        exit;
    }

    $pageId = trim($_POST['page_id'] ?? '');
    if ($pageId === '' || empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('admin.err_upload_fields')]);
        exit;
    }

    $file     = $_FILES['file'];
    $mimeType = mime_content_type($file['tmp_name']);

    if ($mimeType !== 'application/pdf') {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('admin.err_upload_type')]);
        exit;
    }

    $maxBytes = 10 * 1024 * 1024; // 10 MB
    if ($file['size'] > $maxBytes) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('admin.err_upload_size')]);
        exit;
    }

    // Sanitize the filename Notion will display (strip path separators and
    // control characters; keep it under Notion's 900-byte filename limit).
    $originalName = basename($file['name']);
    $safeName     = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
    $safeName     = substr($safeName, 0, 200);
    if ($safeName === '') $safeName = 'facture.pdf';

    if (!uploadInvoiceFile($pageId, $file['tmp_name'], $safeName, $mimeType)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('admin.err_upload_failed')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Admin Upload Invoice] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/admin-upload-invoice-exec.php`
Expected: `No syntax errors detected in api/admin-upload-invoice-exec.php`

- [ ] **Step 3: Verify the admin guard without uploading anything to any real account**

```bash
curl -s -o /dev/null -w "%{http_code}\n" -X POST http://slapia.local/api/admin-upload-invoice-exec.php
```
Expected: `302` (redirect to `/login`). Do NOT test the success path against a real client's `Factures` property during automated verification — invoice upload is a real, visible mutation on a client's Notion page. Defer full functional verification to the user's own manual QA.

- [ ] **Step 4: Commit**

```bash
git add api/admin-upload-invoice-exec.php
git commit -m "feat(admin): add invoice PDF upload endpoint"
```

---

### Task 8: Last-login write-through on successful login

**Files:**
- Modify: `api/auth-login.php:96-101` (the session-setting block, right after `session_regenerate_id(true);` and before the remember-me block)

**Interfaces:**
- Consumes: `setLastLogin()` (Task 2, `includes/notion-users.php`).

- [ ] **Step 1: Call `setLastLogin()` after a successful login**

In `api/auth-login.php`, find:
```php
    $_SESSION['user_id']    = $userPage['id'];
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name']  = userDisplayName($userPage);
    $_SESSION['user_role']  = userRole($userPage);
    session_regenerate_id(true);
    $_SESSION['logged_in']  = true;
```
Replace it with:
```php
    $_SESSION['user_id']    = $userPage['id'];
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name']  = userDisplayName($userPage);
    $_SESSION['user_role']  = userRole($userPage);
    session_regenerate_id(true);
    $_SESSION['logged_in']  = true;

    // Best-effort; never blocks login even if the Notion property doesn't exist yet.
    if (!setLastLogin($userPage['id'])) {
        error_log('[SlapIA Auth Login] Failed to record last login for user ' . $userPage['id']);
    }
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/auth-login.php`
Expected: `No syntax errors detected in api/auth-login.php`

- [ ] **Step 3: Verify the file still behaves correctly on the CSRF-gate path (no real login exercised)**

```bash
curl -s -X POST http://slapia.local/api/auth-login.php -H "Content-Type: application/json" -d '{"email":"","password":""}'
```
Expected: HTTP 403 with a CSRF error (same as before this change — confirms the edit didn't break the file's control flow before reaching the new code, which only runs after a real successful login).

- [ ] **Step 4: Commit**

```bash
git add api/auth-login.php
git commit -m "feat(admin): record last-login timestamp on successful authentication"
```

---

### Task 9: Self-hosted Chart.js

**Files:**
- Create: `assets/js/vendor/chart.min.js`

**Interfaces:**
- Produces: the global `Chart` constructor, loaded via `<script src="assets/js/vendor/chart.min.js">` in `pages/admin.php` (Task 10). Consumed by Task 11 (`assets/js/admin.js`).

- [ ] **Step 1: Download Chart.js 4.4.4 (UMD build) to the vendor directory**

```bash
mkdir -p assets/js/vendor
curl -s -o assets/js/vendor/chart.min.js https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js
```

- [ ] **Step 2: Verify the download succeeded and looks like real JS**

```bash
wc -c assets/js/vendor/chart.min.js
head -c 100 assets/js/vendor/chart.min.js
```
Expected: file size around 200KB (not a few bytes — that would mean an error page was saved instead), and the first bytes look like a comment header or minified JS (`/**` or `!function` etc.), not HTML (`<!DOCTYPE` or `<html`).

- [ ] **Step 3: Commit**

```bash
git add assets/js/vendor/chart.min.js
git commit -m "chore(admin): self-host Chart.js 4.4.4 (no external CDN dependency)"
```

---

### Task 10: Admin page shell and stylesheet

**Files:**
- Create: `pages/admin.php`
- Create: `assets/css/admin.css`

**Interfaces:**
- Consumes: `requireAdmin()` (`includes/auth.php`), `generateCSRFToken()` (`includes/config.php`), `t()` (`includes/i18n.php`), existing `includes/header.php`/`footer.php`.
- Produces: the `/admin` route (via the existing generic `pages/$1.php` rewrite — no `.htaccess` change needed), a page shell with 4 tab containers (`#admin-tab-overview`, `#admin-tab-accounts`, `#admin-tab-rss`, `#admin-tab-invoices`) that Task 11's JS renders into.

- [ ] **Step 1: Create `assets/css/admin.css`**

```css
/* Admin dashboard — loaded only by pages/admin.php, never the public site. */

.admin-tabs {
  display: flex;
  gap: 8px;
  border-bottom: 1px solid var(--line);
  margin-bottom: 32px;
  flex-wrap: wrap;
}
.admin-tab-btn {
  font-family: var(--font-mono);
  font-size: 0.8rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  padding: 12px 18px;
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  color: var(--ink-fade);
  cursor: pointer;
}
.admin-tab-btn.is-active {
  color: var(--ink);
  border-bottom-color: var(--signal);
}
.admin-tab-panel { display: none; }
.admin-tab-panel.is-active { display: block; }

.admin-kpi-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}
.admin-kpi-card {
  padding: 20px;
  border-radius: 14px;
  border: 1px solid var(--line);
  background: var(--paper);
}
.admin-kpi-card .num { font-family: var(--font-display); font-size: 2rem; font-weight: 700; color: var(--ink); }
.admin-kpi-card .label { font-size: 0.8rem; color: var(--ink-fade); text-transform: uppercase; letter-spacing: 0.04em; }

.admin-chart-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 32px;
}
@media (max-width: 900px) { .admin-chart-grid { grid-template-columns: 1fr; } }
.admin-chart-card {
  padding: 20px;
  border-radius: 14px;
  border: 1px solid var(--line);
  background: var(--paper);
}
.admin-chart-card canvas { max-height: 260px; }

.admin-table-wrap { overflow-x: auto; }
.admin-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.admin-table th, .admin-table td {
  padding: 10px 12px;
  border-bottom: 1px solid var(--line);
  text-align: left;
  white-space: nowrap;
}
.admin-table th {
  font-family: var(--font-mono);
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ink-soft);
}
.admin-table select {
  font-family: var(--font-body);
  font-size: 0.85rem;
  padding: 4px 8px;
  border-radius: 8px;
  border: 1px solid var(--line-strong);
  background: var(--white);
  color: var(--ink);
}

.admin-badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 600;
}
.admin-badge--admin { background: var(--forest-glow); color: var(--forest); }
.admin-badge--entreprise { background: rgba(179,111,224,0.15); color: var(--signal-deep); }
.admin-badge--particulier { background: var(--line); color: var(--ink-soft); }

.admin-invoice-card {
  padding: 16px;
  border-radius: 12px;
  border: 1px solid var(--line);
  background: var(--paper);
  margin-bottom: 12px;
}
.admin-invoice-upload {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-top: 10px;
}
```

- [ ] **Step 2: Create `pages/admin.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$page_title = t('admin.title');
$csrf = generateCSRFToken();
include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="assets/css/admin.css">

<section class="section">
  <div class="container">
    <h1 class="page-hero__title"><?php echo t('admin.title'); ?></h1>

    <nav class="admin-tabs">
      <button class="admin-tab-btn is-active" data-tab="overview"><?php echo t('admin.tab_overview'); ?></button>
      <button class="admin-tab-btn" data-tab="accounts"><?php echo t('admin.tab_accounts'); ?></button>
      <button class="admin-tab-btn" data-tab="rss"><?php echo t('admin.tab_rss'); ?></button>
      <button class="admin-tab-btn" data-tab="invoices"><?php echo t('admin.tab_invoices'); ?></button>
    </nav>

    <div id="admin-tab-overview" class="admin-tab-panel is-active"></div>
    <div id="admin-tab-accounts" class="admin-tab-panel"></div>
    <div id="admin-tab-rss" class="admin-tab-panel"></div>
    <div id="admin-tab-invoices" class="admin-tab-panel"></div>
  </div>
</section>

<script src="assets/js/vendor/chart.min.js"></script>
<script>window.ADMIN_CSRF_TOKEN = <?php echo json_encode($csrf); ?>;</script>
<script src="assets/js/admin.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
```

- [ ] **Step 3: Lint the PHP file**

Run: `php -l pages/admin.php`
Expected: `No syntax errors detected in pages/admin.php`

- [ ] **Step 4: Verify the page loads and is protected**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://slapia.local/admin
```
Expected: `302` (redirected to `/login`, confirming `requireAdmin()` fires for an anonymous request). This also confirms the clean URL resolves via the existing `pages/$1.php` rewrite rule without any `.htaccess` change.

- [ ] **Step 5: Commit**

```bash
git add pages/admin.php assets/css/admin.css
git commit -m "feat(admin): add admin dashboard page shell and stylesheet"
```

---

### Task 11: Admin dashboard client-side logic

**Files:**
- Create: `assets/js/admin.js`

**Interfaces:**
- Consumes: `GET /api/admin-data.php`, `POST /api/admin-update-account-exec.php`, `POST /api/admin-reset-password-exec.php`, `POST /api/admin-upload-invoice-exec.php` (Tasks 4–7), the global `Chart` constructor (Task 9), `window.ADMIN_CSRF_TOKEN` (Task 10), the DOM containers from Task 10 (`#admin-tab-overview`, `#admin-tab-accounts`, `#admin-tab-rss`, `#admin-tab-invoices`, `.admin-tab-btn`).

- [ ] **Step 1: Create `assets/js/admin.js`**

```javascript
(function () {
  var data = null;

  function escHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  // ── Tabs ──────────────────────────────────────────────────────────────
  document.querySelectorAll('.admin-tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.admin-tab-btn').forEach(function (b) { b.classList.remove('is-active'); });
      document.querySelectorAll('.admin-tab-panel').forEach(function (p) { p.classList.remove('is-active'); });
      btn.classList.add('is-active');
      document.getElementById('admin-tab-' + btn.dataset.tab).classList.add('is-active');
    });
  });

  // ── Load data ─────────────────────────────────────────────────────────
  fetch('/api/admin-data.php')
    .then(function (r) { return r.json(); })
    .then(function (json) {
      if (!json.success) throw new Error(json.error || 'Erreur');
      data = json;
      renderOverview();
      renderAccounts();
      renderRss();
      renderInvoices();
    })
    .catch(function (e) {
      ['admin-tab-overview', 'admin-tab-accounts', 'admin-tab-rss', 'admin-tab-invoices'].forEach(function (id) {
        document.getElementById(id).innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(e.message) + '</span></div>';
      });
    });

  // ── Overview: KPIs + 3 charts ────────────────────────────────────────
  function renderOverview() {
    var el = document.getElementById('admin-tab-overview');
    var pendingCount = data.accounts.filter(function (a) { return a.billing === 'En attente'; }).length;

    el.innerHTML =
      '<div class="admin-kpi-row">' +
        '<div class="admin-kpi-card"><div class="num">' + data.accounts.length + '</div><div class="label">Comptes</div></div>' +
        '<div class="admin-kpi-card"><div class="num">' + data.rssSubscribers.length + '</div><div class="label">Abonnés RSS</div></div>' +
        '<div class="admin-kpi-card"><div class="num">' + pendingCount + '</div><div class="label">Factures en attente</div></div>' +
      '</div>' +
      '<div class="admin-chart-grid">' +
        '<div class="admin-chart-card" style="grid-column:1/-1;"><canvas id="chart-growth"></canvas></div>' +
        '<div class="admin-chart-card"><canvas id="chart-billing"></canvas></div>' +
        '<div class="admin-chart-card"><canvas id="chart-roles"></canvas></div>' +
      '</div>';

    new Chart(document.getElementById('chart-growth'), {
      type: 'line',
      data: {
        labels: data.chart.growth.labels,
        datasets: [
          { label: 'Comptes', data: data.chart.growth.accounts, borderColor: '#9147C4', tension: 0.3 },
          { label: 'Abonnés RSS', data: data.chart.growth.rss, borderColor: '#7A3F87', tension: 0.3 },
        ],
      },
      options: { responsive: true, plugins: { title: { display: true, text: 'Croissance — 6 derniers mois' } } },
    });

    new Chart(document.getElementById('chart-billing'), {
      type: 'bar',
      data: {
        labels: data.chart.billing.labels,
        datasets: [{ label: 'Comptes', data: data.chart.billing.counts, backgroundColor: '#B36FE0' }],
      },
      options: { responsive: true, plugins: { title: { display: true, text: 'Statuts de facturation' } } },
    });

    new Chart(document.getElementById('chart-roles'), {
      type: 'pie',
      data: {
        labels: data.chart.roles.labels,
        datasets: [{ data: data.chart.roles.counts, backgroundColor: ['#B36FE0', '#7A3F87', '#E36FC4'] }],
      },
      options: { responsive: true, plugins: { title: { display: true, text: 'Répartition des rôles' } } },
    });
  }

  // ── Accounts table ────────────────────────────────────────────────────
  var BILLING_OPTIONS = ['Facturé', 'Payé', 'En cours', 'En attente', 'Dispensé'];
  var ROLE_OPTIONS = ['particulier', 'entreprise', 'admin'];

  function accountRowHtml(a) {
    return '<tr data-id="' + escHtml(a.id) + '" data-search="' + escHtml((a.name + ' ' + a.email + ' ' + a.company).toLowerCase()) + '">' +
      '<td>' + escHtml(a.name) + '</td>' +
      '<td>' + escHtml(a.email) + '</td>' +
      '<td>' + escHtml(a.company) + '</td>' +
      '<td>' + escHtml(a.service) + '</td>' +
      '<td><select class="role-select">' + ROLE_OPTIONS.map(function (r) {
        return '<option value="' + r + '"' + (a.role === r ? ' selected' : '') + '>' + r + '</option>';
      }).join('') + '</select></td>' +
      '<td><select class="billing-select">' + BILLING_OPTIONS.map(function (b) {
        return '<option value="' + escHtml(b) + '"' + (a.billing === b ? ' selected' : '') + '>' + escHtml(b) + '</option>';
      }).join('') + '</select></td>' +
      '<td>' + (a.lastLogin ? new Date(a.lastLogin).toLocaleString('fr-FR') : '—') + '</td>' +
      '<td><button class="btn btn--ghost reset-pwd-btn" data-email="' + escHtml(a.email) + '">Reset MDP</button></td>' +
    '</tr>';
  }

  function filterTable(tableId, query) {
    query = query.toLowerCase();
    document.querySelectorAll('#' + tableId + ' tbody tr').forEach(function (row) {
      row.style.display = row.dataset.search.indexOf(query) === -1 ? 'none' : '';
    });
  }

  function exportTableToCSV(tableId, filename) {
    var rows = Array.from(document.querySelectorAll('#' + tableId + ' tr')).filter(function (r) { return r.style.display !== 'none'; });
    var csv = rows.map(function (row) {
      return Array.from(row.querySelectorAll('th, td')).map(function (cell) {
        var text = cell.querySelector('select') ? cell.querySelector('select').value : cell.textContent.trim();
        return '"' + text.replace(/"/g, '""') + '"';
      }).join(',');
    }).join('\n');
    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '.csv';
    link.click();
  }

  function renderAccounts() {
    var el = document.getElementById('admin-tab-accounts');
    var rows = data.accounts.map(accountRowHtml).join('');

    el.innerHTML =
      '<div style="display:flex; gap:10px; margin-bottom:16px;">' +
        '<input type="text" placeholder="Rechercher…" oninput="window.__adminFilterAccounts(this.value)" class="field" style="flex:1; max-width:280px;">' +
        '<button class="btn btn--ghost" onclick="window.__adminExportAccounts()">Exporter CSV</button>' +
      '</div>' +
      '<div class="admin-table-wrap"><table class="admin-table" id="accountsTable"><thead><tr>' +
        '<th>Nom</th><th>Email</th><th>Entreprise</th><th>Service</th><th>Rôle</th><th>Facturation</th><th>Dernière connexion</th><th>Actions</th>' +
      '</tr></thead><tbody>' + rows + '</tbody></table></div>';

    window.__adminFilterAccounts = function (q) { filterTable('accountsTable', q); };
    window.__adminExportAccounts = function () { exportTableToCSV('accountsTable', 'comptes_slapia'); };

    el.querySelectorAll('.role-select').forEach(function (sel) {
      sel.addEventListener('change', function () {
        updateAccount(sel.closest('tr').dataset.id, { role: sel.value });
      });
    });
    el.querySelectorAll('.billing-select').forEach(function (sel) {
      sel.addEventListener('change', function () {
        updateAccount(sel.closest('tr').dataset.id, { billing: sel.value });
      });
    });
    el.querySelectorAll('.reset-pwd-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var newPassword = prompt('Nouveau mot de passe pour ' + btn.dataset.email + ' (8 caractères min.) :');
        if (!newPassword) return;
        resetPassword(btn.dataset.email, newPassword);
      });
    });
  }

  function updateAccount(pageId, fields) {
    var body = Object.assign({ page_id: pageId }, fields);
    fetch('/api/admin-update-account-exec.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.ADMIN_CSRF_TOKEN },
      body: JSON.stringify(body),
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (!json.success) alert('Erreur: ' + json.error);
      });
  }

  function resetPassword(email, newPassword) {
    fetch('/api/admin-reset-password-exec.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.ADMIN_CSRF_TOKEN },
      body: JSON.stringify({ email: email, new_password: newPassword }),
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        alert(json.success ? 'Mot de passe changé.' : 'Erreur: ' + json.error);
      });
  }

  // ── RSS subscribers ───────────────────────────────────────────────────
  function renderRss() {
    var el = document.getElementById('admin-tab-rss');
    var rows = data.rssSubscribers.map(function (s) {
      return '<tr><td>' + escHtml(s.email) + '</td><td>' + (s.subscribedAt ? new Date(s.subscribedAt).toLocaleString('fr-FR') : '—') + '</td></tr>';
    }).join('');
    el.innerHTML =
      '<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Email</th><th>Inscrit le</th></tr></thead><tbody>' + rows + '</tbody></table></div>';
  }

  // ── Invoices ──────────────────────────────────────────────────────────
  function renderInvoices() {
    var el = document.getElementById('admin-tab-invoices');
    var cards = data.accounts.map(function (a) {
      return '<div class="admin-invoice-card">' +
        '<div><strong>' + escHtml(a.name) + '</strong> — ' + escHtml(a.email) + '</div>' +
        '<div>' + escHtml(a.service) + (a.price ? ' — ' + a.price + ' €' : '') + ' — <span class="admin-badge admin-badge--' + a.role + '">' + escHtml(a.billing) + '</span></div>' +
        '<div>' + a.invoiceCount + ' facture(s) attachée(s)</div>' +
        '<form class="admin-invoice-upload" data-id="' + escHtml(a.id) + '">' +
          '<input type="file" accept="application/pdf" required>' +
          '<button type="submit" class="btn btn--ghost">Uploader</button>' +
        '</form>' +
      '</div>';
    }).join('');
    el.innerHTML = cards;

    el.querySelectorAll('.admin-invoice-upload').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var fileInput = form.querySelector('input[type="file"]');
        if (!fileInput.files.length) return;

        var body = new FormData();
        body.append('page_id', form.dataset.id);
        body.append('csrf_token', window.ADMIN_CSRF_TOKEN);
        body.append('file', fileInput.files[0]);

        fetch('/api/admin-upload-invoice-exec.php', { method: 'POST', body: body })
          .then(function (r) { return r.json(); })
          .then(function (json) {
            alert(json.success ? 'Facture uploadée.' : 'Erreur: ' + json.error);
          });
      });
    });
  }
})();
```

- [ ] **Step 2: Verify the file is well-formed JS**

```bash
node --check assets/js/admin.js 2>&1 || echo "node not available, skipping — visually re-check the file for balanced braces instead"
```
If `node` isn't available, re-read the file once and confirm every function/brace is balanced (no syntax check tool is required by this project, but a quick self-check here catches typos before commit).

- [ ] **Step 3: Verify the page renders and pulls in this script (guard still active, no data exposed)**

```bash
curl -s http://slapia.local/admin | grep -o 'admin.js'
```
Expected: no output and the command doesn't error — because `requireAdmin()` redirects before the page body (containing the script tag) is ever rendered for an anonymous request. This confirms the guard, not the script tag itself; full rendering is verified visually by the user once logged in.

- [ ] **Step 4: Commit**

```bash
git add assets/js/admin.js
git commit -m "feat(admin): add admin dashboard client-side rendering and actions"
```

---

### Task 12: Admin nav link & translations

**Files:**
- Modify: `includes/header.php`
- Modify: `lang/fr.php`
- Modify: `lang/en.php`
- Modify: `lang/de.php`

**Interfaces:**
- Consumes: `currentUser()` (`includes/auth.php`, already returns `role`).
- Produces: an "Administration" nav link visible only to admin users, and all `t('admin.*')` keys used by Tasks 5, 6, 7, 10 resolving to real copy in fr/en/de.

- [ ] **Step 1: Add the admin nav link in `includes/header.php`**

Find the block added in the auth-foundation sub-project:
```php
      <?php $me = currentUser(); ?>
      <a href="<?php echo $me ? '/dashboard' : '/login'; ?>" class="btn btn--ghost">
        <?php echo $me ? t('nav.dashboard') : t('nav.login'); ?>
      </a>
```
Replace it with:
```php
      <?php $me = currentUser(); ?>
      <?php if ($me && $me['role'] === 'admin'): ?>
        <a href="/admin" class="btn btn--ghost"><?php echo t('nav.admin'); ?></a>
      <?php endif; ?>
      <a href="<?php echo $me ? '/dashboard' : '/login'; ?>" class="btn btn--ghost">
        <?php echo $me ? t('nav.dashboard') : t('nav.login'); ?>
      </a>
```

Find the matching mobile-menu block:
```php
    <a href="<?php echo $me ? '/dashboard' : '/login'; ?>" class="btn btn--on-dark btn--block" style="margin-bottom:10px;">
      <?php echo $me ? t('nav.dashboard') : t('nav.login'); ?>
    </a>
```
Replace it with:
```php
    <?php if ($me && $me['role'] === 'admin'): ?>
      <a href="/admin" class="btn btn--on-dark btn--block" style="margin-bottom:10px;"><?php echo t('nav.admin'); ?></a>
    <?php endif; ?>
    <a href="<?php echo $me ? '/dashboard' : '/login'; ?>" class="btn btn--on-dark btn--block" style="margin-bottom:10px;">
      <?php echo $me ? t('nav.dashboard') : t('nav.login'); ?>
    </a>
```

- [ ] **Step 2: Add `nav.admin` and the `admin` section to `lang/fr.php`**

Inside the existing `'nav' => [ ... ]` array, add:
```php
    'admin' => 'Administration',
```

New top-level `'admin'` array (add it near the `'auth'` section, e.g. right after it):
```php
  'admin' => [
    'title' => 'Dashboard Admin',
    'tab_overview' => 'Vue d\'ensemble',
    'tab_accounts' => 'Comptes',
    'tab_rss' => 'Abonnés RSS',
    'tab_invoices' => 'Factures',
    'err_fields' => 'Requête invalide : champ manquant.',
    'err_update_failed' => 'La mise à jour a échoué. Réessayez.',
    'err_reset_fields' => 'Email invalide ou mot de passe trop court (8 caractères min.).',
    'err_account_not_found' => 'Aucun compte trouvé avec cet email.',
    'err_upload_fields' => 'Fichier ou compte manquant.',
    'err_upload_type' => 'Seuls les fichiers PDF sont acceptés.',
    'err_upload_size' => 'Fichier trop volumineux (10 Mo max).',
    'err_upload_failed' => 'L\'upload a échoué. Réessayez.',
  ],

```

- [ ] **Step 3: Add the English equivalents to `lang/en.php`**

Inside `'nav' => [ ... ]`:
```php
    'admin' => 'Admin',
```

New `'admin'` array:
```php
  'admin' => [
    'title' => 'Admin Dashboard',
    'tab_overview' => 'Overview',
    'tab_accounts' => 'Accounts',
    'tab_rss' => 'RSS Subscribers',
    'tab_invoices' => 'Invoices',
    'err_fields' => 'Invalid request: missing field.',
    'err_update_failed' => 'Update failed. Please try again.',
    'err_reset_fields' => 'Invalid email or password too short (8 characters min.).',
    'err_account_not_found' => 'No account found with this email.',
    'err_upload_fields' => 'Missing file or account.',
    'err_upload_type' => 'Only PDF files are accepted.',
    'err_upload_size' => 'File too large (10 MB max).',
    'err_upload_failed' => 'Upload failed. Please try again.',
  ],

```

- [ ] **Step 4: Add the German equivalents to `lang/de.php`**

Inside `'nav' => [ ... ]`:
```php
    'admin' => 'Admin',
```

New `'admin'` array:
```php
  'admin' => [
    'title' => 'Admin-Dashboard',
    'tab_overview' => 'Übersicht',
    'tab_accounts' => 'Konten',
    'tab_rss' => 'RSS-Abonnenten',
    'tab_invoices' => 'Rechnungen',
    'err_fields' => 'Ungültige Anfrage: fehlendes Feld.',
    'err_update_failed' => 'Aktualisierung fehlgeschlagen. Bitte erneut versuchen.',
    'err_reset_fields' => 'Ungültige E-Mail oder Passwort zu kurz (mind. 8 Zeichen).',
    'err_account_not_found' => 'Kein Konto mit dieser E-Mail gefunden.',
    'err_upload_fields' => 'Datei oder Konto fehlt.',
    'err_upload_type' => 'Nur PDF-Dateien werden akzeptiert.',
    'err_upload_size' => 'Datei zu groß (max. 10 MB).',
    'err_upload_failed' => 'Upload fehlgeschlagen. Bitte erneut versuchen.',
  ],

```

- [ ] **Step 5: Lint all four files**

Run: `php -l includes/header.php && php -l lang/fr.php && php -l lang/en.php && php -l lang/de.php`
Expected: `No syntax errors detected` for each.

- [ ] **Step 6: Verify translations resolve and cross-reference every `t('admin.*')` call site**

```bash
php -r "
\$_GET['lang']='fr'; require 'includes/i18n.php';
echo t('admin.title'), ' / ', t('nav.admin'), PHP_EOL;
"
```
Expected: real French strings, not raw keys.

Then grep every `t('admin.*')` call across the files this plan created (`api/admin-update-account-exec.php`, `api/admin-reset-password-exec.php`, `api/admin-upload-invoice-exec.php`, `pages/admin.php`) and confirm each key exists in all three lang files added in this task — this mirrors the cross-task consistency check the final review did for the auth foundation, and should be done now rather than left for a final review to catch:

```bash
grep -ohE "t\('admin\.[a-z_]+'\)" api/admin-*.php pages/admin.php | sort -u
```
Compare the resulting key list against the keys added to `lang/fr.php` in Step 2 — every key printed here must exist there.

- [ ] **Step 7: Commit**

```bash
git add includes/header.php lang/fr.php lang/en.php lang/de.php
git commit -m "feat(admin): add admin nav link and fr/en/de translations"
```

---

## End-to-end verification (after all 12 tasks — user's own manual QA)

- [ ] Log in as the admin account (`thomas25.lapierre@outlook.com`) → "Administration" link appears in the nav → `/admin` loads with all 4 tabs.
- [ ] Overview tab: 3 KPI numbers look right, all 3 charts render with real data.
- [ ] Accounts tab: table lists all real accounts; search filters rows; CSV export downloads the currently-visible rows; changing a role or billing status on a **test account you're comfortable mutating** persists after a page reload.
- [ ] Reset a password for a test account via the "Reset MDP" button, then log in with the new password to confirm it actually took effect.
- [ ] Upload a real PDF to a test account's invoices, confirm it appears attached in Notion itself (open the page in Notion to verify).
- [ ] RSS tab lists real subscribers.
- [ ] Confirm the "Dernière connexion" column populates after creating the property in Notion and logging in again.
- [ ] Site still works in all three languages with no visible raw `admin.xxx` keys.
