# Socle Comptes & Authentification — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a working login / logout / password-reset system to the SlapIA site, backed by the existing Notion "Satisfaction" database, with roles (Particulier / Entreprise / Admin) resolved centrally instead of duplicated ad-hoc checks.

**Architecture:** PHP session-based auth. All Notion access goes through the existing `NotionAPI` class (`includes/notion.php`). New `includes/auth.php` owns session/role/rate-limit concerns; new `includes/notion-users.php` owns all Notion reads/writes related to user accounts. API endpoints under `api/` return JSON and are called via `fetch()` from the login/reset pages (matches the approach the user chose over a plain-form-POST alternative). Clean URLs (`/login`, `/reset-password`) are served from a new `pages/` directory via `.htaccess` rewrite, mirroring the old site's convention.

**Tech Stack:** PHP 8, Apache + `.htaccess` (XAMPP), Notion API (existing `NotionAPI` class), Cloudflare Turnstile, n8n webhook for transactional email. No test framework in this project — verification steps below use `php -l`, `php -r` CLI smoke checks, and `curl`/browser checks instead of a unit test runner (per the approved spec).

## Global Constraints

- Backend for accounts stays **Notion** (`NOTION_SATISFACTION_DATABASE_ID`) — no SQL migration.
- Roles: `Status` property → `''` (empty) = **admin**, `'Entreprise'` = **entreprise**, anything else (`'Particuliers'`) = **particulier**. This mapping must exist in exactly one function: `resolveUserRole()`.
- Hosting is a single server (gitsync deploy, `.env` mapped locally, never committed) — file-based rate-limiting and remember-me tokens in `sys_get_temp_dir()` are acceptable and must NOT be replaced with a database.
- No self-registration: `/register` always redirects to `/login`.
- Transactional emails go through an n8n webhook (`N8N_AUTH_WEBHOOK_URL`), payload includes an `event` field so one webhook can branch on email type.
- Never reveal whether an email exists in responses (login and reset-request both return generic messages).
- All user-facing strings go through `t('auth.xxx')` (fr/en/de) — no hardcoded UI strings in PHP.
- Git repo was just initialized at the project root (`C:\xampp\htdocs\Slapia`) with an initial baseline commit `25315ed`. Every task below ends with its own commit.

---

### Task 1: Session bootstrap, remember-me restore & CSRF helpers

**Files:**
- Modify: `includes/config.php`

**Interfaces:**
- Produces: `generateCSRFToken(): string`, `verifyCSRFToken(?string $token): bool` — used by every API endpoint from Task 5 onward.
- Produces: session is started with secure cookie params and `$_SESSION['logged_in']`/`user_id`/`user_email`/`user_name`/`user_role` restored from a remember-me cookie if present, on every request that includes `config.php` (i.e. every page and API endpoint).

- [ ] **Step 1: Add session bootstrap + CSRF helpers to `includes/config.php`**

Open `includes/config.php` and append the following after the existing `config()` function (keep everything already in the file untouched):

```php

// ─────────────────────────────────────────────────────────────────────────
//  Session bootstrap (secure cookie params + remember-me restoration)
// ─────────────────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 1 day
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    _restoreSessionFromRememberToken();
}

/**
 * Restore an expired PHP session from a long-lived remember-me token stored on disk.
 */
function _restoreSessionFromRememberToken(): void
{
    if (!empty($_SESSION['logged_in'])) return;

    $token = $_COOKIE['remember_token'] ?? '';
    if (!$token || !preg_match('/^[a-f0-9]{64}$/', $token)) return;

    $file = sys_get_temp_dir() . '/slapia_rt_' . $token . '.json';

    if (!is_readable($file)) {
        if (!headers_sent()) {
            setcookie('remember_token', '', time() - 3600, '/', '', !empty($_SERVER['HTTPS']), true);
        }
        return;
    }

    $data = json_decode(@file_get_contents($file), true);

    if (!$data || empty($data['user_id']) || ($data['expires'] ?? 0) < time()) {
        @unlink($file);
        if (!headers_sent()) {
            setcookie('remember_token', '', time() - 3600, '/', '', !empty($_SERVER['HTTPS']), true);
        }
        return;
    }

    $_SESSION['user_id']    = $data['user_id'];
    $_SESSION['user_email'] = $data['user_email'] ?? '';
    $_SESSION['user_name']  = $data['user_name'] ?? '';
    $_SESSION['user_role']  = $data['user_role'] ?? 'particulier';
    $_SESSION['logged_in']  = true;

    session_regenerate_id(true);

    if (rand(1, 100) === 1) {
        _cleanupExpiredRememberTokens();
    }
}

/** Delete remember-me token files that have passed their expiry date. */
function _cleanupExpiredRememberTokens(): void
{
    $now   = time();
    $files = glob(sys_get_temp_dir() . '/slapia_rt_*.json') ?: [];
    foreach ($files as $f) {
        $d = json_decode(@file_get_contents($f), true);
        if (!$d || ($d['expires'] ?? 0) < $now) {
            @unlink($f);
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────
//  CSRF protection
// ─────────────────────────────────────────────────────────────────────────

function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken(?string $token): bool
{
    if (!isset($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l includes/config.php`
Expected: `No syntax errors detected in includes/config.php`

- [ ] **Step 3: Smoke-test CSRF + session behavior from the CLI**

Run:
```bash
php -r "
require 'includes/config.php';
\$t = generateCSRFToken();
echo strlen(\$t) === 64 ? 'TOKEN_OK' : 'TOKEN_BAD', PHP_EOL;
var_dump(verifyCSRFToken(\$t));
var_dump(verifyCSRFToken('not-the-token'));
"
```
Expected output:
```
TOKEN_OK
bool(true)
bool(false)
```

- [ ] **Step 4: Commit**

```bash
git add includes/config.php
git commit -m "feat(auth): add session bootstrap, remember-me restore and CSRF helpers"
```

---

### Task 2: Auth session helpers & rate limiting

**Files:**
- Create: `includes/auth.php`

**Interfaces:**
- Consumes: nothing from Notion (pure session/file helpers). Assumes `config.php` has already been required (session started, `generateCSRFToken`/`verifyCSRFToken` available).
- Produces: `currentUser(): ?array` (keys `id`,`email`,`name`,`role`), `requireLogin(): void`, `requireRole(string ...$roles): void`, `requireAdmin(): void`, `rateLimitCheck(string $key, int $maxAttempts, int $windowSeconds): bool`, `rateLimitReset(string $key): void`, `logFailedLogin(string $email, string $reason, string $ip): void`. Used by Tasks 5, 7, 9, 10.

- [ ] **Step 1: Create `includes/auth.php`**

```php
<?php
require_once __DIR__ . '/config.php';

/**
 * Session-based auth helpers: current user, route guards, rate limiting.
 * Role resolution from Notion data lives in notion-users.php (co-located
 * with the code that reads the Notion "Status" property).
 */

function currentUser(): ?array
{
    if (empty($_SESSION['logged_in'])) return null;
    return [
        'id'    => $_SESSION['user_id'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'name'  => $_SESSION['user_name'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'particulier',
    ];
}

function requireLogin(): void
{
    if (!currentUser()) {
        header('Location: /login');
        exit;
    }
}

function requireRole(string ...$roles): void
{
    $user = currentUser();
    if (!$user) {
        header('Location: /login');
        exit;
    }
    if (!in_array($user['role'], $roles, true)) {
        header('Location: /404');
        exit;
    }
}

function requireAdmin(): void
{
    requireRole('admin');
}

/**
 * File-based rate limiter — true if the action is allowed, false if blocked.
 * Max $maxAttempts within a $windowSeconds window, keyed by $key.
 */
function rateLimitCheck(string $key, int $maxAttempts, int $windowSeconds): bool
{
    $file = sys_get_temp_dir() . '/slapia_rl_' . md5($key) . '.json';
    $now  = time();
    $data = [];

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?? [];
    }

    $data = array_values(array_filter($data, fn($ts) => ($now - $ts) < $windowSeconds));

    if (count($data) >= $maxAttempts) {
        return false;
    }

    $data[] = $now;
    file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}

function rateLimitReset(string $key): void
{
    $file = sys_get_temp_dir() . '/slapia_rl_' . md5($key) . '.json';
    if (file_exists($file)) @unlink($file);
}

function logFailedLogin(string $email, string $reason, string $ip): void
{
    $logFile = sys_get_temp_dir() . '/slapia_failed_logins.log';
    $line    = date('Y-m-d H:i:s') . "\t" . $ip . "\t" . $email . "\t" . $reason . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    error_log('[SlapIA Auth] ' . $reason . ' — ' . $email . ' — IP: ' . $ip);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l includes/auth.php`
Expected: `No syntax errors detected in includes/auth.php`

- [ ] **Step 3: Smoke-test rate limiting from the CLI**

Run:
```bash
php -r "
require 'includes/auth.php';
rateLimitReset('plan_test_key');
var_dump(rateLimitCheck('plan_test_key', 2, 60)); // true (1st)
var_dump(rateLimitCheck('plan_test_key', 2, 60)); // true (2nd)
var_dump(rateLimitCheck('plan_test_key', 2, 60)); // false (3rd, over max)
rateLimitReset('plan_test_key');
var_dump(rateLimitCheck('plan_test_key', 2, 60)); // true again after reset
"
```
Expected output: `bool(true)` `bool(true)` `bool(false)` `bool(true)`

- [ ] **Step 4: Commit**

```bash
git add includes/auth.php
git commit -m "feat(auth): add session helpers, route guards and rate limiting"
```

---

### Task 3: Notion-backed user operations

**Files:**
- Create: `includes/notion-users.php`

**Interfaces:**
- Consumes: `notion(): NotionAPI` and `NotionAPI::{richText,title,select}()` from `includes/notion.php` (already exists), `config()` from `includes/config.php`.
- Produces: `resolveUserRole(string $statusValue): string`, `findUserByEmail(string $email): ?array`, `verifyPassword(array $userPage, string $password): bool`, `upgradePasswordHash(string $pageId, string $plainPassword): void`, `userDisplayName(array $userPage): string`, `userRole(array $userPage): string`, `setResetToken(string $pageId): string`, `validateResetToken(string $email, string $token): ?array`, `clearResetToken(string $pageId): void`, `updatePassword(string $pageId, string $plainPassword): void`. Used by Tasks 5, 7, 9.

- [ ] **Step 1: Create `includes/notion-users.php`**

```php
<?php
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

function upgradePasswordHash(string $pageId, string $plainPassword): void
{
    notion()->updatePage($pageId, [
        'properties' => [
            'Mot de passe' => [
                'rich_text' => [['text' => ['content' => password_hash($plainPassword, PASSWORD_BCRYPT)]]],
            ],
        ],
    ]);
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

function setResetToken(string $pageId): string
{
    $token  = bin2hex(random_bytes(32));
    $expiry = date('c', time() + 3600);

    notion()->updatePage($pageId, [
        'properties' => [
            'Reset Token'  => ['rich_text' => [['text' => ['content' => $token]]]],
            'Reset Expiry' => ['date' => ['start' => $expiry]],
        ],
    ]);

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

function clearResetToken(string $pageId): void
{
    notion()->updatePage($pageId, [
        'properties' => [
            'Reset Token'  => ['rich_text' => []],
            'Reset Expiry' => ['date' => null],
        ],
    ]);
}

function updatePassword(string $pageId, string $plainPassword): void
{
    notion()->updatePage($pageId, [
        'properties' => [
            'Mot de passe' => [
                'rich_text' => [['text' => ['content' => password_hash($plainPassword, PASSWORD_BCRYPT)]]],
            ],
        ],
    ]);
    clearResetToken($pageId);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l includes/notion-users.php`
Expected: `No syntax errors detected in includes/notion-users.php`

- [ ] **Step 3: Smoke-test the pure role-mapping logic (no network)**

Run:
```bash
php -r "
require 'includes/notion-users.php';
var_dump(resolveUserRole('') === 'admin');
var_dump(resolveUserRole('Entreprise') === 'entreprise');
var_dump(resolveUserRole('Particuliers') === 'particulier');
var_dump(resolveUserRole('AnythingElse') === 'particulier');
"
```
Expected output: `bool(true)` four times.

- [ ] **Step 4: Smoke-test the live Notion lookup**

Make sure `.env` has a valid `NOTION_API_KEY` (already present). Then run, **replacing `you@example.com` with an email that actually exists in the Notion "Satisfaction" database** (e.g. your own admin account):

```bash
php -r "
require 'includes/notion-users.php';
\$u = findUserByEmail('you@example.com');
var_dump(\$u !== null);
if (\$u) {
    echo userDisplayName(\$u), ' / role: ', userRole(\$u), PHP_EOL;
}
"
```
Expected: `bool(true)` followed by the account's name and role (`admin` if its `Status` is empty).

- [ ] **Step 5: Commit**

```bash
git add includes/notion-users.php
git commit -m "feat(auth): add Notion-backed user lookup, password and reset-token operations"
```

---

### Task 4: Routing, security headers & new config keys

**Files:**
- Modify: `.htaccess`
- Modify: `.env` (local only, not committed — instructions below)

**Interfaces:**
- Produces: clean URLs `/login`, `/reset-password`, `/register` resolving to `pages/login.php`, `pages/reset-password.php`, `pages/register.php` (created in later tasks). Produces security headers (CSP, X-Frame-Options, etc.) on every response. Produces new config keys `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`, `N8N_AUTH_WEBHOOK_URL` read via the existing `config()` helper.

- [ ] **Step 1: Rewrite `.htaccess`**

Replace the entire contents of `.htaccess` with:

```apache
# Sécurité : empêcher l'accès direct aux fichiers sensibles
<FilesMatch "^\.env|composer\.(json|lock)|config\.php$">
    Require all denied
</FilesMatch>

<IfModule mod_rewrite.c>
    RewriteEngine On

    # Clean URLs: /login -> pages/login.php (only if the file exists, so
    # existing flat pages like /tarifs.php keep working untouched).
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{DOCUMENT_ROOT}/pages/$1.php -f
    RewriteRule ^([^/]+)$ pages/$1.php [L,QSA]
</IfModule>

<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "camera=(), microphone=(), geolocation=()"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://challenges.cloudflare.com; style-src 'self' 'unsafe-inline'; frame-src 'self' https://challenges.cloudflare.com; connect-src 'self' https://api.notion.com https://*.n8n.cloud; img-src 'self' data: https:; font-src 'self'; object-src 'none'; base-uri 'self';"
</IfModule>

# Page 404 personnalisée
ErrorDocument 404 /404.php
```

- [ ] **Step 2: Add the new config keys to `.env`**

Open `.env` (not committed to git) and append:
```
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
N8N_AUTH_WEBHOOK_URL=
```
Fill in `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` from your Cloudflare Turnstile dashboard (reuse the existing widget for slapia.com if you still have it, or create a new one), and `N8N_AUTH_WEBHOOK_URL` from your n8n workflow that will handle the `password_reset` event. Login/reset will work end-to-end only once these are filled in — that's expected, later tasks' verification steps will note where a blank value shows up.

- [ ] **Step 3: Verify the rewrite rule works**

Create a temporary file `pages/route-check.php`:
```php
<?php echo 'ROUTE_OK';
```
Then, with Apache running (XAMPP control panel), run:
```bash
curl -s http://localhost/Slapia/route-check
```
Expected output: `ROUTE_OK`

Delete the temporary file afterwards:
```bash
rm pages/route-check.php
```
(Adjust the URL if the site is served from a different local host/path than `http://localhost/Slapia/`.)

- [ ] **Step 4: Verify security headers are present**

Run:
```bash
curl -sI http://localhost/Slapia/index.php
```
Expected: response headers include `X-Frame-Options: SAMEORIGIN` and a `Content-Security-Policy` line. Also open the homepage in a browser and confirm it still renders normally (theme toggle, images, fonts) — the CSP is intentionally tight (no external CDNs), so anything that silently stopped loading means a domain is missing from the policy.

- [ ] **Step 5: Commit**

```bash
git add .htaccess
git commit -m "feat(auth): add clean-URL routing for pages/ and security headers"
```

(`.env` is gitignored — nothing to commit there.)

---

### Task 5: Login API endpoint

**Files:**
- Create: `api/auth-login.php`

**Interfaces:**
- Consumes: `verifyCSRFToken()` (Task 1), `rateLimitCheck/rateLimitReset/logFailedLogin` (Task 2), `findUserByEmail/verifyPassword/upgradePasswordHash/userDisplayName/userRole` (Task 3), `config('TURNSTILE_SECRET_KEY')`, `t()` from `includes/i18n.php`.
- Produces: `POST /api/auth-login.php` — JSON `{success, error?}` or `{success:true, redirect:'/dashboard'}`, sets `$_SESSION['logged_in']` + remember-me cookie on success. Consumed by `pages/login.php` (Task 8).

- [ ] **Step 1: Create `api/auth-login.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-users.php';

header('Content-Type: application/json');
ob_start();

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => t('auth.err_csrf')]);
        exit;
    }

    $email      = trim($input['email'] ?? '');
    $password   = $input['password'] ?? '';
    $turnstile  = $input['cf-turnstile-response'] ?? '';
    $rememberMe = !empty($input['remember_me']);
    $ip         = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if ($email === '' || $password === '') {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('auth.err_fields')]);
        exit;
    }

    if (!rateLimitCheck('login_ip_' . $ip, 10, 900) || !rateLimitCheck('login_email_' . strtolower($email), 5, 900)) {
        ob_clean();
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => t('auth.err_rate_limit')]);
        exit;
    }

    if ($turnstile === '') {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('auth.err_captcha')]);
        exit;
    }

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => config('TURNSTILE_SECRET_KEY'),
            'response' => $turnstile,
            'remoteip' => $ip,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $verify = json_decode(curl_exec($ch), true);
    if (empty($verify['success'])) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => t('auth.err_captcha_failed')]);
        exit;
    }

    $userPage = findUserByEmail($email);
    if (!$userPage || !verifyPassword($userPage, $password)) {
        logFailedLogin($email, $userPage ? 'wrong_password' : 'email_not_found', $ip);
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => t('auth.err_invalid')]);
        exit;
    }

    $hash = NotionAPI::richText($userPage['properties']['Mot de passe'] ?? []);
    if (strpos($hash, '$2y$') !== 0) {
        upgradePasswordHash($userPage['id'], $password); // auto-upgrade legacy plain-text
    }

    rateLimitReset('login_ip_' . $ip);
    rateLimitReset('login_email_' . strtolower($email));

    $_SESSION['user_id']    = $userPage['id'];
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name']  = userDisplayName($userPage);
    $_SESSION['user_role']  = userRole($userPage);
    session_regenerate_id(true);
    $_SESSION['logged_in']  = true;

    if ($rememberMe) {
        $lifetime = 30 * 24 * 3600;
        $token    = bin2hex(random_bytes(32));
        $file     = sys_get_temp_dir() . '/slapia_rt_' . $token . '.json';
        file_put_contents($file, json_encode([
            'user_id'    => $_SESSION['user_id'],
            'user_email' => $_SESSION['user_email'],
            'user_name'  => $_SESSION['user_name'],
            'user_role'  => $_SESSION['user_role'],
            'expires'    => time() + $lifetime,
        ]), LOCK_EX);

        setcookie('remember_token', $token, [
            'expires'  => time() + $lifetime,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    ob_clean();
    echo json_encode(['success' => true, 'redirect' => '/dashboard']);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Auth Login] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/auth-login.php`
Expected: `No syntax errors detected in api/auth-login.php`

- [ ] **Step 3: Verify field validation without needing Turnstile/Notion**

With Apache running:
```bash
curl -s -X POST http://localhost/Slapia/api/auth-login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"","password":""}'
```
Expected: HTTP 403 with `{"success":false,"error":"..."}` — CSRF check fails first since no valid token/session cookie was sent, which is correct (this endpoint must reject anonymous requests without a session). This confirms the CSRF gate is active; full login flow verification happens in Task 8 once the login page exists and can supply a real CSRF token + Turnstile response.

- [ ] **Step 4: Commit**

```bash
git add api/auth-login.php
git commit -m "feat(auth): add login API endpoint"
```

---

### Task 6: Logout & disabled self-registration

**Files:**
- Create: `api/auth-logout.php`
- Create: `api/auth-register.php`
- Create: `pages/register.php`

**Interfaces:**
- Produces: `GET /api/auth-logout.php` clears session + remember-me cookie/file and redirects to `/login`. `/register` and direct hits to `api/auth-register.php` redirect to `/login` (no self-registration, matches the old site).

- [ ] **Step 1: Create `api/auth-logout.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

if (!empty($_COOKIE['remember_token'])) {
    @unlink(sys_get_temp_dir() . '/slapia_rt_' . $_COOKIE['remember_token'] . '.json');
    setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

header('Location: /login');
exit;
```

- [ ] **Step 2: Create `api/auth-register.php`**

```php
<?php
// L'inscription autonome est désactivée. Comptes créés manuellement dans Notion.
header('Location: /login');
exit;
```

- [ ] **Step 3: Create `pages/register.php`**

```php
<?php
// L'inscription autonome est désactivée. Comptes créés manuellement dans Notion.
header('Location: /login');
exit;
```

- [ ] **Step 4: Lint all three files**

Run: `php -l api/auth-logout.php && php -l api/auth-register.php && php -l pages/register.php`
Expected: `No syntax errors detected` for each.

- [ ] **Step 5: Verify redirects with curl**

```bash
curl -sI http://localhost/Slapia/register | grep -i location
curl -sI http://localhost/Slapia/api/auth-register.php | grep -i location
curl -sI http://localhost/Slapia/api/auth-logout.php | grep -i location
```
Expected: each prints `Location: /login`.

- [ ] **Step 6: Commit**

```bash
git add api/auth-logout.php api/auth-register.php pages/register.php
git commit -m "feat(auth): add logout endpoint and disabled self-registration redirects"
```

---

### Task 7: Password reset API endpoints

**Files:**
- Create: `api/auth-reset-request.php`
- Create: `api/auth-reset-exec.php`

**Interfaces:**
- Consumes: `verifyCSRFToken()`, `rateLimitCheck()` (Task 1/2), `findUserByEmail/setResetToken/userDisplayName/validateResetToken/updatePassword` (Task 3), `config('N8N_AUTH_WEBHOOK_URL')`, `t()`.
- Produces: `POST /api/auth-reset-request.php` → always `{success:true}`, triggers n8n webhook with `{event:'password_reset', email, name, reset_url}` if the account exists. `POST /api/auth-reset-exec.php` → `{success:true, redirect:'/login'}` or `{success:false, error}`. Consumed by `pages/reset-password.php` (Task 9).

- [ ] **Step 1: Create `api/auth-reset-request.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-users.php';

header('Content-Type: application/json');
ob_start();

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => t('auth.err_csrf')]);
        exit;
    }

    $email = strtolower(trim($input['email'] ?? ''));
    $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('auth.err_email')]);
        exit;
    }

    if (!rateLimitCheck('reset_ip_' . $ip, 5, 900) || !rateLimitCheck('reset_email_' . $email, 3, 900)) {
        ob_clean();
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => t('auth.err_rate_limit')]);
        exit;
    }

    $userPage = findUserByEmail($email);

    if ($userPage) {
        $token    = setResetToken($userPage['id']);
        $resetUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
                  . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email);

        $webhookUrl = config('N8N_AUTH_WEBHOOK_URL');
        if ($webhookUrl) {
            $ch = curl_init($webhookUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'event'     => 'password_reset',
                    'email'     => $email,
                    'name'      => userDisplayName($userPage),
                    'reset_url' => $resetUrl,
                ]),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);
            curl_exec($ch);
        } else {
            error_log('[SlapIA Reset] N8N_AUTH_WEBHOOK_URL non configuré. URL générée : ' . $resetUrl);
        }
    }

    // Toujours succès : ne jamais révéler si l'email existe.
    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Reset Request] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
```

- [ ] **Step 2: Create `api/auth-reset-exec.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-users.php';

header('Content-Type: application/json');
ob_start();

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => t('auth.err_csrf')]);
        exit;
    }

    $email    = strtolower(trim($input['email'] ?? ''));
    $token    = trim($input['token'] ?? '');
    $password = $input['password'] ?? '';

    if (strlen($password) < 8) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('auth.err_password_length')]);
        exit;
    }

    $userPage = validateResetToken($email, $token);
    if (!$userPage) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('auth.err_reset_invalid')]);
        exit;
    }

    updatePassword($userPage['id'], $password);

    ob_clean();
    echo json_encode(['success' => true, 'redirect' => '/login']);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Reset Exec] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
```

- [ ] **Step 3: Lint both files**

Run: `php -l api/auth-reset-request.php && php -l api/auth-reset-exec.php`
Expected: `No syntax errors detected` for each.

- [ ] **Step 4: Verify validation paths with curl**

```bash
curl -s -X POST http://localhost/Slapia/api/auth-reset-request.php \
  -H "Content-Type: application/json" -d '{"email":"not-an-email"}'
```
Expected: HTTP 403 (CSRF rejected first, same reasoning as Task 5 Step 3 — no session cookie sent). Full end-to-end verification (valid CSRF + real email) happens in Task 9 once `pages/reset-password.php` exists.

- [ ] **Step 5: Commit**

```bash
git add api/auth-reset-request.php api/auth-reset-exec.php
git commit -m "feat(auth): add password reset request and execution API endpoints"
```

---

### Task 8: Login page

**Files:**
- Create: `pages/login.php`

**Interfaces:**
- Consumes: `currentUser()` (Task 2), `generateCSRFToken()` (Task 1), `config('TURNSTILE_SITE_KEY')`, `t('auth.*')` (Task 10 adds the keys — page renders with raw key strings until then, which is fine for now), existing `includes/header.php`/`includes/footer.php` and `.field`/`.btn`/`.alert`/`.consent-check` CSS classes already in `assets/css/style.css`.
- Produces: the `/login` route (via Task 4's rewrite rule), posts to `/api/auth-login.php` (Task 5).

- [ ] **Step 1: Create `pages/login.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';

if (currentUser()) {
    header('Location: /dashboard');
    exit;
}

$page_title = t('auth.login_title');
$csrf = generateCSRFToken();
$turnstileSiteKey = config('TURNSTILE_SITE_KEY', '');
include __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container" style="max-width:480px;">
    <h1 class="page-hero__title"><?php echo t('auth.login_title'); ?></h1>

    <div id="login-alert"></div>

    <form id="login-form" novalidate>
      <div class="field">
        <label for="email"><?php echo t('auth.label_email'); ?></label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="field" style="margin-top:16px;">
        <label for="password"><?php echo t('auth.label_password'); ?></label>
        <input type="password" id="password" name="password" required>
      </div>
      <label class="consent-check" style="margin-top:16px;">
        <input type="checkbox" id="remember_me" name="remember_me">
        <span><?php echo t('auth.remember_me'); ?></span>
      </label>

      <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($turnstileSiteKey); ?>" style="margin-top:16px;"></div>

      <button type="submit" class="btn btn--primary btn--block" style="margin-top:20px;">
        <?php echo t('auth.submit_login'); ?>
      </button>
    </form>

    <p style="margin-top:16px;"><a href="/reset-password"><?php echo t('auth.forgot_password'); ?></a></p>
  </div>
</section>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script>
document.getElementById('login-form').addEventListener('submit', function (e) {
  e.preventDefault();
  var alertBox = document.getElementById('login-alert');
  alertBox.innerHTML = '';

  var turnstileField = document.querySelector('[name="cf-turnstile-response"]');

  fetch('/api/auth-login.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': '<?php echo $csrf; ?>',
    },
    body: JSON.stringify({
      email: document.getElementById('email').value,
      password: document.getElementById('password').value,
      remember_me: document.getElementById('remember_me').checked,
      'cf-turnstile-response': turnstileField ? turnstileField.value : '',
    }),
  })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        window.location.href = data.redirect;
      } else {
        alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + data.error + '</span></div>';
      }
    })
    .catch(function () {
      alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span><?php echo t('auth.err_server'); ?></span></div>';
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
```

- [ ] **Step 2: Lint the file**

Run: `php -l pages/login.php`
Expected: `No syntax errors detected in pages/login.php`

- [ ] **Step 3: Verify the page renders and the CSRF token is wired up**

With Apache running:
```bash
curl -s http://localhost/Slapia/login | grep -o 'X-CSRF-Token[^,]*'
```
Expected: a line showing the inline script contains `X-CSRF-Token` followed by a 64-char hex value (confirms `generateCSRFToken()` output is embedded).

Then open `http://localhost/Slapia/login` in a browser: confirm the form renders with the site's styling (fields, button), the Turnstile widget loads (once `TURNSTILE_SITE_KEY` is filled in `.env`), and submitting valid credentials for a real Notion account redirects to `/dashboard` (which doesn't exist yet — a 404 here is expected and fine, it confirms the login itself succeeded). Submitting wrong credentials should show a red alert with a generic error message, and 6 wrong attempts in a row should trigger the rate-limit message.

- [ ] **Step 4: Commit**

```bash
git add pages/login.php
git commit -m "feat(auth): add login page"
```

---

### Task 9: Password reset page

**Files:**
- Create: `pages/reset-password.php`

**Interfaces:**
- Consumes: `currentUser()`, `generateCSRFToken()`, `validateResetToken()` (Task 3), `t('auth.*')`.
- Produces: `/reset-password` (request mode, no query params) and `/reset-password?token=...&email=...` (reset mode). Posts to `/api/auth-reset-request.php` and `/api/auth-reset-exec.php` (Task 7).

- [ ] **Step 1: Create `pages/reset-password.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-users.php';

if (currentUser()) {
    header('Location: /dashboard');
    exit;
}

$token = trim($_GET['token'] ?? '');
$email = strtolower(trim($_GET['email'] ?? ''));
$mode  = ($token && $email) ? 'reset' : 'request';

if ($mode === 'reset' && !validateResetToken($email, $token)) {
    header('Location: /404');
    exit;
}

$page_title = t('auth.reset_title');
$csrf = generateCSRFToken();
include __DIR__ . '/../includes/header.php';
?>

<section class="section">
  <div class="container" style="max-width:480px;">
    <h1 class="page-hero__title"><?php echo t('auth.reset_title'); ?></h1>
    <div id="reset-alert"></div>

    <?php if ($mode === 'request'): ?>
      <form id="reset-request-form" novalidate>
        <div class="field">
          <label for="email"><?php echo t('auth.label_email'); ?></label>
          <input type="email" id="email" name="email" required>
        </div>
        <button type="submit" class="btn btn--primary btn--block" style="margin-top:20px;">
          <?php echo t('auth.submit_reset_request'); ?>
        </button>
      </form>
      <script>
      document.getElementById('reset-request-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var alertBox = document.getElementById('reset-alert');
        fetch('/api/auth-reset-request.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?php echo $csrf; ?>' },
          body: JSON.stringify({ email: document.getElementById('email').value }),
        })
          .then(function (r) { return r.json(); })
          .then(function () {
            alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span><?php echo t('auth.reset_request_sent'); ?></span></div>';
          });
      });
      </script>
    <?php else: ?>
      <form id="reset-exec-form" novalidate>
        <div class="field">
          <label for="password"><?php echo t('auth.label_new_password'); ?></label>
          <input type="password" id="password" name="password" minlength="8" required>
        </div>
        <button type="submit" class="btn btn--primary btn--block" style="margin-top:20px;">
          <?php echo t('auth.submit_reset_exec'); ?>
        </button>
      </form>
      <script>
      document.getElementById('reset-exec-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var alertBox = document.getElementById('reset-alert');
        fetch('/api/auth-reset-exec.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?php echo $csrf; ?>' },
          body: JSON.stringify({
            email: <?php echo json_encode($email); ?>,
            token: <?php echo json_encode($token); ?>,
            password: document.getElementById('password').value,
          }),
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data.success) {
              window.location.href = data.redirect;
            } else {
              alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + data.error + '</span></div>';
            }
          });
      });
      </script>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
```

- [ ] **Step 2: Lint the file**

Run: `php -l pages/reset-password.php`
Expected: `No syntax errors detected in pages/reset-password.php`

- [ ] **Step 3: Verify both modes manually**

With Apache running and `.env`'s `N8N_AUTH_WEBHOOK_URL` filled in (or not — a missing webhook just logs instead of sending, per Task 7):
1. Open `http://localhost/Slapia/reset-password` → request form should render.
2. Submit a real account email → confirm (via `error_log`/n8n execution history, or the response) that a `Reset Token`/`Reset Expiry` got written on that Notion page.
3. Copy the generated URL from the log line (or the n8n payload) and open `http://localhost/Slapia/reset-password?token=...&email=...` → the "new password" form should render (not a 404).
4. Submit a new password (8+ chars) → should redirect to `/login`, and logging in with the new password should succeed.
5. Reopen the same reset link a second time → should now redirect to `/404` (token was cleared by `updatePassword()`).

- [ ] **Step 4: Commit**

```bash
git add pages/reset-password.php
git commit -m "feat(auth): add password reset page (request + reset modes)"
```

---

### Task 10: Header nav integration & translations

**Files:**
- Modify: `includes/header.php`
- Modify: `lang/fr.php`
- Modify: `lang/en.php`
- Modify: `lang/de.php`

**Interfaces:**
- Consumes: `currentUser()` (Task 2).
- Produces: a Connexion/Mon espace link in both the desktop and mobile nav; all `t('auth.*')` and `t('nav.login')`/`t('nav.dashboard')` keys used by Tasks 8–9 resolve to real copy instead of falling back to the raw key string.

- [ ] **Step 1: Add `require_once` for auth in `includes/header.php`**

At the top of `includes/header.php`, change:
```php
<?php
require_once __DIR__ . '/i18n.php';
```
to:
```php
<?php
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/auth.php';
```

- [ ] **Step 2: Add the nav link in the desktop header actions**

In `includes/header.php`, find:
```php
      <a href="contact.php" class="btn btn--primary"><?php echo t('common.book_call'); ?> <span class="btn__arrow">→</span></a>
      <button class="nav-toggle" aria-label="<?php echo t('common.open_menu'); ?>"><span></span></button>
```
Replace it with:
```php
      <?php $me = currentUser(); ?>
      <a href="<?php echo $me ? '/dashboard' : '/login'; ?>" class="btn btn--ghost">
        <?php echo $me ? t('nav.dashboard') : t('nav.login'); ?>
      </a>
      <a href="contact.php" class="btn btn--primary"><?php echo t('common.book_call'); ?> <span class="btn__arrow">→</span></a>
      <button class="nav-toggle" aria-label="<?php echo t('common.open_menu'); ?>"><span></span></button>
```

- [ ] **Step 3: Add the same link in the mobile menu footer**

In `includes/header.php`, find:
```php
  <div class="mobile-menu__foot">
    <a href="contact.php" class="btn btn--signal btn--block"><?php echo t('common.book_call'); ?></a>
  </div>
```
Replace it with:
```php
  <div class="mobile-menu__foot">
    <a href="<?php echo $me ? '/dashboard' : '/login'; ?>" class="btn btn--on-dark btn--block" style="margin-bottom:10px;">
      <?php echo $me ? t('nav.dashboard') : t('nav.login'); ?>
    </a>
    <a href="contact.php" class="btn btn--signal btn--block"><?php echo t('common.book_call'); ?></a>
  </div>
```

- [ ] **Step 4: Add `nav.login`/`nav.dashboard` and the `auth` section to `lang/fr.php`**

In `lang/fr.php`, add two keys inside the existing `'nav' => [ ... ]` array (after `'contact' => 'Contact',`):
```php
    'login' => 'Connexion',
    'dashboard' => 'Mon espace',
```

Then add a new top-level `'auth'` array right after the `'contact' => [ ... ]` section closes (before `'legal_common' => [`):
```php
  'auth' => [
    'login_title' => 'Connexion',
    'reset_title' => 'Réinitialiser le mot de passe',
    'label_email' => 'Adresse e-mail',
    'label_password' => 'Mot de passe',
    'label_new_password' => 'Nouveau mot de passe',
    'remember_me' => 'Se souvenir de moi',
    'forgot_password' => 'Mot de passe oublié ?',
    'submit_login' => 'Se connecter',
    'submit_reset_request' => 'Envoyer le lien de réinitialisation',
    'submit_reset_exec' => 'Changer le mot de passe',
    'reset_request_sent' => "Si un compte existe avec cet e-mail, un lien de réinitialisation vient d'être envoyé.",
    'err_csrf' => 'Requête invalide, merci de réessayer.',
    'err_fields' => 'Merci de remplir tous les champs.',
    'err_email' => 'Adresse e-mail invalide.',
    'err_rate_limit' => 'Trop de tentatives. Réessayez dans 15 minutes.',
    'err_captcha' => 'Veuillez compléter la sécurité Cloudflare.',
    'err_captcha_failed' => 'Validation de sécurité échouée.',
    'err_invalid' => 'E-mail ou mot de passe incorrect.',
    'err_password_length' => 'Le mot de passe doit contenir au moins 8 caractères.',
    'err_reset_invalid' => 'Ce lien de réinitialisation est invalide ou a expiré.',
    'err_server' => 'Erreur serveur, merci de réessayer.',
  ],

```

- [ ] **Step 5: Add the English equivalents to `lang/en.php`**

In `lang/en.php`, inside `'nav' => [ ... ]` (after `'contact' => 'Contact',`):
```php
    'login' => 'Log in',
    'dashboard' => 'My account',
```

New top-level `'auth'` array (same location, before `'legal_common' => [`):
```php
  'auth' => [
    'login_title' => 'Log in',
    'reset_title' => 'Reset your password',
    'label_email' => 'Email address',
    'label_password' => 'Password',
    'label_new_password' => 'New password',
    'remember_me' => 'Remember me',
    'forgot_password' => 'Forgot your password?',
    'submit_login' => 'Log in',
    'submit_reset_request' => 'Send reset link',
    'submit_reset_exec' => 'Change password',
    'reset_request_sent' => "If an account exists with this email, a reset link has just been sent.",
    'err_csrf' => 'Invalid request, please try again.',
    'err_fields' => 'Please fill in all fields.',
    'err_email' => 'Invalid email address.',
    'err_rate_limit' => 'Too many attempts. Please try again in 15 minutes.',
    'err_captcha' => 'Please complete the Cloudflare security check.',
    'err_captcha_failed' => 'Security check failed.',
    'err_invalid' => 'Incorrect email or password.',
    'err_password_length' => 'Password must be at least 8 characters.',
    'err_reset_invalid' => 'This reset link is invalid or has expired.',
    'err_server' => 'Server error, please try again.',
  ],

```

- [ ] **Step 6: Add the German equivalents to `lang/de.php`**

In `lang/de.php`, inside `'nav' => [ ... ]` (after `'contact' => 'Kontakt',` or whatever the existing German label is — keep the existing key ordering, just add these two):
```php
    'login' => 'Anmelden',
    'dashboard' => 'Mein Konto',
```

New top-level `'auth'` array (same location as the other two files):
```php
  'auth' => [
    'login_title' => 'Anmelden',
    'reset_title' => 'Passwort zurücksetzen',
    'label_email' => 'E-Mail-Adresse',
    'label_password' => 'Passwort',
    'label_new_password' => 'Neues Passwort',
    'remember_me' => 'Angemeldet bleiben',
    'forgot_password' => 'Passwort vergessen?',
    'submit_login' => 'Anmelden',
    'submit_reset_request' => 'Link zum Zurücksetzen senden',
    'submit_reset_exec' => 'Passwort ändern',
    'reset_request_sent' => 'Falls ein Konto mit dieser E-Mail existiert, wurde soeben ein Link zum Zurücksetzen gesendet.',
    'err_csrf' => 'Ungültige Anfrage, bitte erneut versuchen.',
    'err_fields' => 'Bitte alle Felder ausfüllen.',
    'err_email' => 'Ungültige E-Mail-Adresse.',
    'err_rate_limit' => 'Zu viele Versuche. Bitte in 15 Minuten erneut versuchen.',
    'err_captcha' => 'Bitte die Cloudflare-Sicherheitsprüfung abschließen.',
    'err_captcha_failed' => 'Sicherheitsprüfung fehlgeschlagen.',
    'err_invalid' => 'E-Mail oder Passwort falsch.',
    'err_password_length' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
    'err_reset_invalid' => 'Dieser Link zum Zurücksetzen ist ungültig oder abgelaufen.',
    'err_server' => 'Serverfehler, bitte erneut versuchen.',
  ],

```

- [ ] **Step 7: Lint all four modified files**

Run: `php -l includes/header.php && php -l lang/fr.php && php -l lang/en.php && php -l lang/de.php`
Expected: `No syntax errors detected` for each.

- [ ] **Step 8: Verify translations resolve and the nav renders**

```bash
php -r "
\$_GET['lang']='fr'; require 'includes/i18n.php';
echo t('auth.login_title'), ' / ', t('nav.login'), PHP_EOL;
"
php -r "
\$_GET['lang']='en'; require 'includes/i18n.php';
echo t('auth.login_title'), ' / ', t('nav.login'), PHP_EOL;
"
php -r "
\$_GET['lang']='de'; require 'includes/i18n.php';
echo t('auth.login_title'), ' / ', t('nav.login'), PHP_EOL;
"
```
Expected: three lines showing real translated strings (not the raw `auth.login_title` key), one per language.

Then open `http://localhost/Slapia/` in a browser (logged out): confirm a "Connexion" link now appears in both the desktop nav and the mobile menu, next to "Réserver un appel". Log in via `/login`, then reload the homepage: confirm the link now reads "Mon espace" and points to `/dashboard`.

- [ ] **Step 9: Commit**

```bash
git add includes/header.php lang/fr.php lang/en.php lang/de.php
git commit -m "feat(auth): wire login/dashboard nav link and add fr/en/de auth translations"
```

---

## End-to-end verification (after all 10 tasks)

- [ ] Full login flow: valid credentials + Turnstile → session set → redirected (404 on `/dashboard` is expected until sub-project 2 exists).
- [ ] Wrong password / unknown email → same generic error message both times (anti-enumeration).
- [ ] 5 wrong attempts on the same email within 15 min → rate-limited (429).
- [ ] Remember-me checked → close browser, reopen → still logged in (session restored from cookie/token file).
- [ ] Logout → session cleared, remember-me cookie/file removed, redirected to `/login`.
- [ ] Full reset flow: request → n8n webhook fires (check n8n execution log) → link works once → new password logs in → link rejected (404) on reuse.
- [ ] `/register` and `api/auth-register.php` both redirect to `/login`.
- [ ] A Notion account with empty `Status` resolves to role `admin`; `Particuliers`/`Entreprise` resolve to `particulier`/`entreprise` (verified via the Task 3 Step 4 CLI check against real accounts of each kind).
- [ ] `curl -sI` on any page shows the CSP/security headers from Task 4.
- [ ] Site still works in all three languages (fr/en/de) with no visible raw `auth.xxx` keys leaking into the UI.
