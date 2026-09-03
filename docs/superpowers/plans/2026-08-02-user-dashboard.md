# Espace Utilisateur (Client) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the client-facing account page (`pages/dashboard.php`) that a logged-in `particulier`/`entreprise` user lands on: profile, billing status, downloadable invoices, and self-service password change — styled as a professional SaaS/e-commerce account portal.

**Architecture:** Same conventions as the already-built admin dashboard (Notion as the only backend, `requireLogin()`-gated page + JSON API endpoints + vanilla JS rendering), but scoped to exactly one account — the session's own user, never a parameter the client controls. A new `NotionAPI::files()` helper removes the file-extraction duplication that would otherwise exist between the admin and client code paths.

**Tech Stack:** PHP 8, existing `NotionAPI` class, existing i18n system (fr/en/de), existing CSS design system.

## Global Constraints

- Every route/endpoint gated by `requireLogin()` (already implemented in `includes/auth.php`).
- Every mutating endpoint (password change) gated by CSRF via `verifyCSRFToken()` (already implemented).
- The target account is ALWAYS derived from `currentUser()['id']` (the session) — no endpoint accepts a page ID or email from the client that would let one user view/modify another's data.
- Real Notion schema (verified live in the auth-foundation and admin-dashboard sub-projects, base "ERP"/Satisfaction, ID `2f0b2071-3b6f-8054-bf30-d158398a892a`): `Prenom NOM` (title), `Email` (email), `Nom d'entreprise` (rich_text), `Type de service` (select), `Facturation` (select, exactly `Facturé, Payé, En cours, En attente, Dispensé`), `Prix` (number), `Factures` (files), `Dernière connexion` (date, may not exist yet — read as null, never fatal), `Mot de passe` (rich_text, bcrypt hash).
- Invoice files: `type: "file"` (Notion-hosted, presigned URL, ~1h expiry) or `type: "external"` (permanent URL) — both safe to hand to the browser as-is right after being fetched. `type: "file_upload"` is write-only and never appears on read (established during the admin-dashboard invoice work).
- Password change here requires verifying the CURRENT password first (`verifyPassword()`, already exists) before calling `updatePassword()` (already exists) — unlike the admin's direct reset, this is the user acting on their own account so the extra check is warranted.
- No SQL database — Notion remains the only backend.
- No automated test framework — verification uses `php -l`, `php -r` CLI smoke checks, and `curl`, exactly as in prior sub-projects.
- **Live-data safety:** `.env` holds a real production Notion API key. Automated verification during implementation must NEVER change a real password or mutate real account data. Mutating endpoints are verified via the guard-only check (anonymous request → redirect, established pattern from prior sub-projects) — never by actually exercising the success path against a real account. Read-only verification (`getOwnAccountDetails()` against the user's own real admin account, `thomas25.lapierre@outlook.com`) is fine and expected.
- All user-facing strings via `t('dashboard.xxx')`, translated in fr/en/de.
- Visual direction: styled like a professional SaaS/e-commerce account portal — reuse the existing design tokens and the badge/card patterns already established for the admin dashboard, executed cleanly rather than introducing new visual language.

---

### Task 1: Shared file-extraction helper + admin refactor

**Files:**
- Modify: `includes/notion.php`
- Modify: `includes/notion-admin.php`

**Interfaces:**
- Produces: `NotionAPI::files(array $prop): array` — takes a raw Notion `files`-type property value and returns `[{name, url}, ...]`, extracting `file.url` or `external.url` per entry.
- Consumed by: Task 2 (`includes/notion-client.php`). Also replaces the inline extraction already in `includes/notion-admin.php`'s `listAllAccounts()`, so both admin and client code share one implementation.

- [ ] **Step 1: Add `NotionAPI::files()` to `includes/notion.php`**

Add this method to the `NotionAPI` class, right after the existing `number()` method (around line 180):

```php
    public static function files(array $prop): array
    {
        $items = $prop['files'] ?? [];
        return array_map(function ($f) {
            $url = $f['file']['url'] ?? $f['external']['url'] ?? '';
            return ['name' => $f['name'] ?? 'fichier', 'url' => $url];
        }, $items);
    }
```

- [ ] **Step 2: Lint the file**

Run: `php -l includes/notion.php`
Expected: `No syntax errors detected in includes/notion.php`

- [ ] **Step 3: Refactor `listAllAccounts()` in `includes/notion-admin.php` to use it**

Find this block in `includes/notion-admin.php` (inside `listAllAccounts()`):
```php
        $files = $props['Factures']['files'] ?? [];

        // "file" (Notion-hosted) exposes a presigned URL valid ~1h; "external"
        // exposes a permanent URL. Both are safe to hand to the browser as-is
        // for viewing/downloading right after this data was fetched.
        $invoiceFiles = array_map(function ($f) {
            $url = $f['file']['url'] ?? $f['external']['url'] ?? '';
            return ['name' => $f['name'] ?? 'facture.pdf', 'url' => $url];
        }, $files);
```
Replace it with:
```php
        // "file" (Notion-hosted) exposes a presigned URL valid ~1h; "external"
        // exposes a permanent URL. Both are safe to hand to the browser as-is
        // for viewing/downloading right after this data was fetched.
        $invoiceFiles = NotionAPI::files($props['Factures'] ?? []);
        $files        = $props['Factures']['files'] ?? []; // kept for the invoiceCount line below
```

Do not change anything else in `listAllAccounts()` — `'invoiceCount' => count($files),` and `'invoiceFiles' => $invoiceFiles,` stay exactly as they are; only where `$invoiceFiles` comes from changes.

- [ ] **Step 4: Lint the file**

Run: `php -l includes/notion-admin.php`
Expected: `No syntax errors detected in includes/notion-admin.php`

- [ ] **Step 5: Verify the refactor didn't change behavior (live, read-only)**

```bash
php -r "
require 'includes/notion-admin.php';
\$accounts = listAllAccounts();
foreach (\$accounts as \$a) {
    if (\$a['invoiceCount'] > 0) {
        echo json_encode(\$a['invoiceFiles'], JSON_PRETTY_PRINT) . PHP_EOL;
        break;
    }
}
"
```
Expected: the same shape as before this refactor — an array with `name` and `url` keys for the known invoiced account. This call is read-only (`listAllAccounts()` never writes).

- [ ] **Step 6: Commit**

```bash
git add includes/notion.php includes/notion-admin.php
git commit -m "refactor(notion): extract shared files-property helper, dedupe admin invoice extraction"
```

---

### Task 2: Client's own account data

**Files:**
- Create: `includes/notion-client.php`

**Interfaces:**
- Consumes: `notion()`, `NotionAPI::{richText,title,select,number,files}` (`includes/notion.php`), `config()` (`includes/config.php`).
- Produces: `getOwnAccountDetails(string $pageId): ?array` — returns `{name, email, company, service, billing, price, lastLogin, invoices}` or `null` on failure. Used by Task 3.

- [ ] **Step 1: Create `includes/notion-client.php`**

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notion.php';

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
        'name'      => NotionAPI::title($props['Prenom NOM'] ?? []) ?: 'Utilisateur',
        'email'     => $props['Email']['email'] ?? '',
        'company'   => NotionAPI::richText($props["Nom d'entreprise"] ?? []),
        'service'   => NotionAPI::select($props['Type de service'] ?? []),
        'billing'   => NotionAPI::select($props['Facturation'] ?? []),
        'price'     => NotionAPI::number($props['Prix'] ?? []),
        'lastLogin' => $props['Dernière connexion']['date']['start'] ?? null,
        'invoices'  => NotionAPI::files($props['Factures'] ?? []),
    ];
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l includes/notion-client.php`
Expected: `No syntax errors detected in includes/notion-client.php`

- [ ] **Step 3: Verify against a real account (read-only, safe — the user's own admin account)**

```bash
php -r "
require 'includes/notion-users.php';
require 'includes/notion-client.php';
\$u = findUserByEmail('thomas25.lapierre@outlook.com');
if (\$u) {
    \$details = getOwnAccountDetails(\$u['id']);
    echo json_encode(\$details, JSON_PRETTY_PRINT) . PHP_EOL;
}
"
```
Expected: a JSON object with `name`, `email`, `billing`, etc. populated with real values, and `invoices` as an array (possibly empty, possibly containing the known test invoice).

- [ ] **Step 4: Commit**

```bash
git add includes/notion-client.php
git commit -m "feat(dashboard): add client's own account data fetch"
```

---

### Task 3: Dashboard data API endpoint

**Files:**
- Create: `api/dashboard-data.php`

**Interfaces:**
- Consumes: `requireLogin()`, `currentUser()` (`includes/auth.php`), `getOwnAccountDetails()` (`includes/notion-client.php`).
- Produces: `GET /api/dashboard-data.php` → `{success:true, account:{...}}` or `{success:false, error}`. Consumed by Task 7 (`assets/js/dashboard.js`).

- [ ] **Step 1: Create `api/dashboard-data.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-client.php';

requireLogin();

header('Content-Type: application/json');
ob_start();

try {
    $me = currentUser();
    $account = getOwnAccountDetails($me['id']);

    if ($account === null) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true, 'account' => $account]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Dashboard Data] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/dashboard-data.php`
Expected: `No syntax errors detected in api/dashboard-data.php`

- [ ] **Step 3: Verify the login guard rejects anonymous requests**

With XAMPP Apache running:
```bash
curl -sI http://slapia.local/api/dashboard-data.php
```
Expected: `302` redirect to `/login` (from `requireLogin()`), confirming the guard fires before any Notion call.

- [ ] **Step 4: Commit**

```bash
git add api/dashboard-data.php
git commit -m "feat(dashboard): add client dashboard data endpoint"
```

---

### Task 4: Password change endpoint

**Files:**
- Create: `api/dashboard-change-password.php`

**Interfaces:**
- Consumes: `requireLogin()`, `currentUser()` (`includes/auth.php`), `verifyCSRFToken()` (`includes/config.php`), `notion()` (`includes/notion.php`), `verifyPassword()`, `updatePassword()` (`includes/notion-users.php`).
- Produces: `POST /api/dashboard-change-password.php` with JSON body `{current_password, new_password}` → `{success, error?}`. Consumed by Task 7.

- [ ] **Step 1: Create `api/dashboard-change-password.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-users.php';

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

    $input          = json_decode(file_get_contents('php://input'), true) ?: [];
    $currentPassword = $input['current_password'] ?? '';
    $newPassword     = $input['new_password'] ?? '';

    if (strlen($newPassword) < 8) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_password_length')]);
        exit;
    }

    $me   = currentUser();
    $page = notion()->getPage($me['id']);

    if (!empty($page['error']) || ($page['http_code'] ?? 0) >= 300) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
        exit;
    }

    if (!verifyPassword($page, $currentPassword)) {
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_wrong_current_password')]);
        exit;
    }

    if (!updatePassword($me['id'], $newPassword)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_password_update_failed')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Dashboard Change Password] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/dashboard-change-password.php`
Expected: `No syntax errors detected in api/dashboard-change-password.php`

- [ ] **Step 3: Verify the login guard rejects anonymous requests (do not exercise the success path against any real account)**

```bash
curl -s -o /dev/null -w "%{http_code}\n" -X POST http://slapia.local/api/dashboard-change-password.php -H "Content-Type: application/json" -d '{}'
```
Expected: `302` (redirect to `/login`, no session cookie sent) — confirms the guard fires first. Do NOT test the success path (a real password change) against any real account, including the admin's own — same live-data safety constraint used throughout this project. Full functional verification is the user's own manual QA step.

- [ ] **Step 4: Commit**

```bash
git add api/dashboard-change-password.php
git commit -m "feat(dashboard): add self-service password change endpoint"
```

---

### Task 5: Dashboard page shell

**Files:**
- Create: `pages/dashboard.php`

**Interfaces:**
- Consumes: `requireLogin()`, `currentUser()` (`includes/auth.php`), `generateCSRFToken()` (`includes/config.php`), `t()` (`includes/i18n.php`), existing `includes/header.php`/`includes/footer.php`.
- Produces: the `/dashboard` route (via the existing generic `pages/$1.php` rewrite — no `.htaccess` change needed), a page shell with section containers (`#dashboard-profile`, `#dashboard-billing`, `#dashboard-invoices`, `#dashboard-password`) that Task 7's JS renders into.

- [ ] **Step 1: Create `pages/dashboard.php`**

```php
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$me = currentUser();
if ($me['role'] === 'admin') {
    header('Location: /admin');
    exit;
}

$page_title = t('dashboard.title');
$csrf = generateCSRFToken();
include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="assets/css/dashboard.css">

<section class="section">
  <div class="container">
    <h1 class="page-hero__title"><?php echo t('dashboard.title'); ?></h1>

    <div id="dashboard-alert"></div>

    <div id="dashboard-summary" class="dash-summary"></div>

    <div class="dash-grid">
      <div id="dashboard-profile" class="dash-card"></div>
      <div id="dashboard-billing" class="dash-card"></div>
    </div>

    <div id="dashboard-invoices" class="dash-card"></div>

    <div id="dashboard-password" class="dash-card"></div>
  </div>
</section>

<script>window.DASHBOARD_CSRF_TOKEN = <?php echo json_encode($csrf); ?>;</script>
<script src="assets/js/dashboard.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
```

- [ ] **Step 2: Lint the file**

Run: `php -l pages/dashboard.php`
Expected: `No syntax errors detected in pages/dashboard.php`

- [ ] **Step 3: Verify the page is protected and resolves via the existing rewrite**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://slapia.local/dashboard
```
Expected: `302` (redirect to `/login` for an anonymous request), confirming both the route resolves via the existing `pages/$1.php` rule and `requireLogin()` fires.

- [ ] **Step 4: Commit**

```bash
git add pages/dashboard.php
git commit -m "feat(dashboard): add client dashboard page shell"
```

---

### Task 6: Dashboard stylesheet

**Files:**
- Create: `assets/css/dashboard.css`

**Interfaces:**
- Produces: CSS classes consumed by Task 5's markup and Task 7's rendered HTML: `.dash-summary`, `.dash-grid`, `.dash-card`, `.dash-badge` (+ role/billing modifiers), `.dash-invoice-row`, `.dash-field`.

- [ ] **Step 1: Create `assets/css/dashboard.css`**

```css
/* Client dashboard — loaded only by pages/dashboard.php.
   Styled as a professional SaaS/e-commerce account portal: clear
   hierarchy, a summary strip up top, real invoice-line styling. */

.dash-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
  padding: 24px 28px;
  border-radius: 16px;
  background: var(--paper);
  border: 1px solid var(--line);
  margin: 28px 0;
}
.dash-summary__greeting { font-family: var(--font-display); font-size: 1.3rem; font-weight: 700; color: var(--ink); }
.dash-summary__sub { color: var(--ink-fade); font-size: 0.9rem; margin-top: 4px; }

.dash-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}
@media (max-width: 780px) { .dash-grid { grid-template-columns: 1fr; } }

.dash-card {
  background: var(--white);
  border: 1px solid var(--line);
  border-radius: 16px;
  padding: 26px 28px;
  margin-bottom: 20px;
}
.dash-card h2 {
  font-family: var(--font-mono);
  font-size: 0.78rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--ink-fade);
  margin: 0 0 18px;
}

.dash-field { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: 0.95rem; }
.dash-field:last-child { border-bottom: none; }
.dash-field__label { color: var(--ink-fade); }
.dash-field__value { color: var(--ink); font-weight: 500; }

.dash-badge {
  display: inline-block;
  padding: 5px 14px;
  border-radius: 999px;
  font-size: 0.82rem;
  font-weight: 600;
}
.dash-badge--paid, .dash-badge--dispense { background: rgba(16,185,129,0.12); color: #0e9f6e; }
.dash-badge--pending, .dash-badge--cours { background: rgba(245,158,11,0.14); color: #b45309; }
.dash-badge--invoiced { background: var(--line); color: var(--ink-soft); }

.dash-price { font-family: var(--font-display); font-size: 1.8rem; font-weight: 700; color: var(--ink); margin: 10px 0; }

.dash-invoice-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border: 1px solid var(--line);
  border-radius: 10px;
  margin-bottom: 10px;
}
.dash-invoice-row__name { font-weight: 500; color: var(--ink); }
.dash-invoice-row a.btn { padding: 8px 16px; font-size: 0.8rem; }
.dash-invoice-empty { color: var(--ink-fade); font-size: 0.92rem; }

.dash-password-form { display: grid; gap: 16px; max-width: 420px; }
```

- [ ] **Step 2: Commit**

```bash
git add assets/css/dashboard.css
git commit -m "feat(dashboard): add client dashboard stylesheet"
```

---

### Task 7: Dashboard client-side logic

**Files:**
- Create: `assets/js/dashboard.js`

**Interfaces:**
- Consumes: `GET /api/dashboard-data.php` (Task 3), `POST /api/dashboard-change-password.php` (Task 4), `window.DASHBOARD_CSRF_TOKEN` (Task 5), the DOM containers from Task 5 (`#dashboard-summary`, `#dashboard-profile`, `#dashboard-billing`, `#dashboard-invoices`, `#dashboard-password`, `#dashboard-alert`).

- [ ] **Step 1: Create `assets/js/dashboard.js`**

```javascript
(function () {
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

  fetch('/api/dashboard-data.php')
    .then(function (r) { return r.json(); })
    .then(function (json) {
      if (!json.success) throw new Error(json.error || 'Erreur');
      render(json.account);
      renderPasswordForm();
    })
    .catch(function (e) {
      document.getElementById('dashboard-alert').innerHTML =
        '<div class="alert alert--error"><span>!</span><span>' + escHtml(e.message) + '</span></div>';
    });

  function render(account) {
    document.getElementById('dashboard-summary').innerHTML =
      '<div>' +
        '<div class="dash-summary__greeting">Bonjour, ' + escHtml(account.name) + '</div>' +
        '<div class="dash-summary__sub">' + escHtml(account.service || 'Aucune formation en cours') + '</div>' +
      '</div>' +
      '<span class="dash-badge ' + (BILLING_CLASS[account.billing] || 'dash-badge--invoiced') + '">' + escHtml(account.billing || '—') + '</span>';

    document.getElementById('dashboard-profile').innerHTML =
      '<h2>Profil</h2>' +
      '<div class="dash-field"><span class="dash-field__label">Nom</span><span class="dash-field__value">' + escHtml(account.name) + '</span></div>' +
      '<div class="dash-field"><span class="dash-field__label">Email</span><span class="dash-field__value">' + escHtml(account.email) + '</span></div>' +
      '<div class="dash-field"><span class="dash-field__label">Entreprise</span><span class="dash-field__value">' + escHtml(account.company || '—') + '</span></div>';

    document.getElementById('dashboard-billing').innerHTML =
      '<h2>Facturation</h2>' +
      (account.price ? '<div class="dash-price">' + account.price + ' €</div>' : '') +
      '<div class="dash-field"><span class="dash-field__label">Statut</span><span class="dash-field__value">' + escHtml(account.billing || '—') + '</span></div>' +
      '<div class="dash-field"><span class="dash-field__label">Dernière connexion</span><span class="dash-field__value">' + (account.lastLogin ? new Date(account.lastLogin).toLocaleString('fr-FR') : '—') + '</span></div>';

    var invoicesEl = document.getElementById('dashboard-invoices');
    var rows = (account.invoices || []).map(function (f) {
      return '<div class="dash-invoice-row">' +
        '<span class="dash-invoice-row__name">📄 ' + escHtml(f.name) + '</span>' +
        '<a href="' + escHtml(f.url) + '" target="_blank" rel="noopener noreferrer" class="btn btn--ghost">Télécharger</a>' +
      '</div>';
    }).join('');
    invoicesEl.innerHTML = '<h2>Mes factures</h2>' + (rows || '<div class="dash-invoice-empty">Aucune facture pour le moment.</div>');
  }

  function renderPasswordForm() {
    var el = document.getElementById('dashboard-password');
    el.innerHTML =
      '<h2>Changer le mot de passe</h2>' +
      '<div id="password-alert"></div>' +
      '<form id="password-form" class="dash-password-form" novalidate>' +
        '<div class="field">' +
          '<label for="current_password">Mot de passe actuel</label>' +
          '<input type="password" id="current_password" name="current_password" required>' +
        '</div>' +
        '<div class="field">' +
          '<label for="new_password">Nouveau mot de passe</label>' +
          '<input type="password" id="new_password" name="new_password" minlength="8" required>' +
        '</div>' +
        '<button type="submit" class="btn btn--primary">Mettre à jour</button>' +
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
            alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>Mot de passe mis à jour.</span></div>';
            document.getElementById('password-form').reset();
          } else {
            alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
          }
        });
    });
  }
})();
```

- [ ] **Step 2: Verify the file is well-formed JS**

```bash
node --check assets/js/dashboard.js
```
Expected: no output (exit code 0). If `node` isn't available, re-read the file once and confirm every brace/paren is balanced.

- [ ] **Step 3: Verify the page loads with the guard active (no data exposed to an anonymous request)**

```bash
curl -s http://slapia.local/dashboard | grep -o 'dashboard.js'
```
Expected: no output — `requireLogin()` redirects before the page body (containing the script tag) is ever rendered for an anonymous request.

- [ ] **Step 4: Commit**

```bash
git add assets/js/dashboard.js
git commit -m "feat(dashboard): add client dashboard rendering and password-change form"
```

---

### Task 8: Translations

**Files:**
- Modify: `lang/fr.php`
- Modify: `lang/en.php`
- Modify: `lang/de.php`

**Interfaces:**
- Produces: all `t('dashboard.*')` keys used by Tasks 4, 5 resolving to real copy in fr/en/de. (`t('auth.err_csrf')`/`t('auth.err_server')` already exist from the auth-foundation sub-project and are reused as-is, not duplicated here.)

- [ ] **Step 1: Add the `dashboard` section to `lang/fr.php`**

Add this new top-level array, in the same location pattern as the existing `admin` section (after it, before `legal_common`):
```php
  'dashboard' => [
    'title' => 'Mon espace',
    'err_password_length' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
    'err_wrong_current_password' => 'Mot de passe actuel incorrect.',
    'err_password_update_failed' => 'La mise à jour a échoué. Réessayez.',
  ],

```

- [ ] **Step 2: Add the English equivalents to `lang/en.php`**

```php
  'dashboard' => [
    'title' => 'My account',
    'err_password_length' => 'The new password must be at least 8 characters.',
    'err_wrong_current_password' => 'Current password is incorrect.',
    'err_password_update_failed' => 'Update failed. Please try again.',
  ],

```

- [ ] **Step 3: Add the German equivalents to `lang/de.php`**

```php
  'dashboard' => [
    'title' => 'Mein Konto',
    'err_password_length' => 'Das neue Passwort muss mindestens 8 Zeichen lang sein.',
    'err_wrong_current_password' => 'Aktuelles Passwort ist falsch.',
    'err_password_update_failed' => 'Aktualisierung fehlgeschlagen. Bitte erneut versuchen.',
  ],

```

- [ ] **Step 4: Lint all three files**

Run: `php -l lang/fr.php && php -l lang/en.php && php -l lang/de.php`
Expected: `No syntax errors detected` for each.

- [ ] **Step 5: Verify translations resolve and cross-reference every `t('dashboard.*')` call site**

```bash
php -r "
\$_GET['lang']='fr'; require 'includes/i18n.php';
echo t('dashboard.title'), PHP_EOL;
"
grep -ohE "t\('dashboard\.[a-z_]+'\)" api/dashboard-change-password.php pages/dashboard.php | sort -u
```
Expected: `Mon espace` printed, and every key the grep prints exists in the `dashboard` array you just added to all three lang files.

- [ ] **Step 6: Commit**

```bash
git add lang/fr.php lang/en.php lang/de.php
git commit -m "feat(dashboard): add fr/en/de translations for the client dashboard"
```

---

## End-to-end verification (after all 8 tasks — user's own manual QA)

- [ ] Log in as a `particulier`/`entreprise` test account → `/dashboard` loads (not `/admin`).
- [ ] Log in as the admin account → `/dashboard` redirects to `/admin` (not shown to admins).
- [ ] Summary shows the right name, service, and billing badge color for each of the 5 billing statuses (test with a few different accounts if available).
- [ ] Profile and billing cards show correct data.
- [ ] Invoices list shows real, clickable/downloadable links for any attached invoice; shows the empty state for an account with none.
- [ ] Change password with the WRONG current password → clear error, no change.
- [ ] Change password with the correct current password + a valid new password → success message, then log out and log back in with the new password to confirm it actually took effect.
- [ ] Site still works in all three languages with no visible raw `dashboard.xxx` keys.
