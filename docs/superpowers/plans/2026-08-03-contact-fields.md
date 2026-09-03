# Contact Fields (Phone / Address / Orders) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expose three Notion properties that already exist in the live database but appear nowhere on the site — `Téléphone`, `Location`, `Différentes commandes` — as editable client fields (phone/address) and a read-only client field + admin-editable field (orders).

**Architecture:** Same pattern as the previous dashboard-profile-features plan: PHP write functions in `includes/notion-client.php` (client, self-service) and `includes/notion-admin.php` (admin, multi-account), new small JSON endpoints under `api/`, extensions to the existing dashboard/admin page shells and their JS.

**Tech Stack:** PHP 8, Notion API (`includes/notion.php`'s `NotionAPI` class), vanilla JS, existing `t()` i18n helper.

## Global Constraints

- Every new/modified endpoint under `api/` calls `requireLogin()` (client) or `requireAdmin()` (admin) before touching Notion data, and verifies CSRF via the `X-CSRF-Token` header (all writes here are JSON, no multipart).
- The client endpoint (`api/dashboard-update-contact.php`) must target only `currentUser()['id']` — never accept a page ID from the request body.
- The admin endpoint (`api/admin-update-contact-exec.php`) legitimately accepts a `page_id` in the body — this mirrors the existing `api/admin-update-account-exec.php` pattern for admin-only multi-account endpoints.
- Every Notion write function returns `bool`, checks `!empty($result['error']) || ($result['http_code'] ?? 0) >= 300`, `error_log()`s on failure with the page ID and encoded result, and returns `false` — never a silent success.
- Phone validation: trim, empty string allowed (clears the field), non-empty values must match `/^[0-9+\-.() ]{1,30}$/` — reject anything else.
- Location and Différentes commandes are free text (Notion `rich_text`): trim, cap at 500 chars (location) / 2000 chars (orders — same cap as the existing avis text field), empty string allowed (clears the field by writing `[]`).
- **i18n scope decision (confirmed with the user):** `assets/js/dashboard.js` and `pages/dashboard.php` follow the existing full i18n convention (`window.DASHBOARD_I18N`, `t('dashboard.xxx')`). `assets/js/admin.js` has **no** existing i18n wiring — every string in it today is hardcoded French, a pre-existing state from an earlier sub-project, not something this plan changes wholesale. For **only the new UI this plan adds** to admin.js (the "Détails" toggle, its 3 field labels, its save button, its success message), add a minimal `window.ADMIN_I18N` object (mirroring `DASHBOARD_I18N`'s pattern) sourced from new `t('admin.xxx')` keys — do not touch or retrofit any of admin.js's pre-existing hardcoded strings.
- Every Notion-sourced string rendered into `innerHTML` in `assets/js/dashboard.js` and `assets/js/admin.js` must go through the existing `escHtml()` helper in that file.
- Do not exercise any mutating Notion write (phone/location/orders save) against the live database during automated task verification — guard checks and static/lint checks only. Full functional verification is manual, done by the user.

---

### Task 1: Extend `includes/notion-client.php` — phone and location self-service writes

**Files:**
- Modify: `includes/notion-client.php`

**Interfaces:**
- Consumes: `notion()` singleton, `NotionAPI::richText` (existing).
- Produces (used by Task 3):
  - `getOwnAccountDetails(string $pageId): ?array` — now also returns `phone`, `location`, `orders` keys.
  - `updateOwnPhone(string $pageId, string $phone): bool`
  - `updateOwnLocation(string $pageId, string $location): bool`

- [ ] **Step 1: Add the three new `getOwnAccountDetails()` keys**

In `includes/notion-client.php`, find this exact block (the `return [...]` inside `getOwnAccountDetails()`):

```php
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
```

Replace it with:

```php
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
```

- [ ] **Step 2: Append `updateOwnPhone()` and `updateOwnLocation()`**

Append these two functions at the end of `includes/notion-client.php` (after `uploadOwnPhoto()`'s closing `}`):

```php

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
```

- [ ] **Step 3: Lint the file**

Run: `php -l includes/notion-client.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add includes/notion-client.php
git commit -m "feat(dashboard): add phone and location self-service writes to notion-client"
```

---

### Task 2: Extend `includes/notion-admin.php` — phone/location/orders admin writes

**Files:**
- Modify: `includes/notion-admin.php`

**Interfaces:**
- Consumes: `notion()`, `NotionAPI::richText` (existing).
- Produces (used by Task 4):
  - `listAllAccounts(): array` — each account array now also has `phone`, `location`, `orders`.
  - `updateAccountContactDetails(string $pageId, string $phone, string $location, string $orders): bool`

- [ ] **Step 1: Add the three new fields to `listAllAccounts()`**

In `includes/notion-admin.php`, find this exact block inside `listAllAccounts()`:

```php
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
        ];
```

Replace it with:

```php
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
```

- [ ] **Step 2: Append `updateAccountContactDetails()`**

Append this function at the end of `includes/notion-admin.php` (after `uploadInvoiceFile()`'s closing `}`):

```php

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
```

- [ ] **Step 3: Lint the file**

Run: `php -l includes/notion-admin.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add includes/notion-admin.php
git commit -m "feat(admin): add phone/location/orders write to notion-admin"
```

---

### Task 3: New endpoint `api/dashboard-update-contact.php`

**Files:**
- Create: `api/dashboard-update-contact.php`

**Interfaces:**
- Consumes: `requireLogin()`, `currentUser()`, `verifyCSRFToken()`, `updateOwnPhone()`/`updateOwnLocation()` (Task 1), `t()`.
- Produces: `POST /api/dashboard-update-contact.php` — JSON body with `phone` and/or `location` (either key may be omitted; at least one must be present), header `X-CSRF-Token`. Response `{success: true}` or `{success: false, error: string}`.

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
    $phone    = array_key_exists('phone', $input) ? $input['phone'] : null;
    $location = array_key_exists('location', $input) ? $input['location'] : null;

    if ($phone === null && $location === null) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_contact_fields')]);
        exit;
    }

    $me = currentUser();

    if ($phone !== null) {
        $phone = trim((string)$phone);
        if ($phone !== '' && !preg_match('/^[0-9+\-.() ]{1,30}$/', $phone)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => t('dashboard.err_phone_invalid')]);
            exit;
        }
        if (!updateOwnPhone($me['id'], $phone)) {
            ob_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => t('dashboard.err_phone_failed')]);
            exit;
        }
    }

    if ($location !== null) {
        $location = trim((string)$location);
        if (mb_strlen($location) > 500) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => t('dashboard.err_location_too_long')]);
            exit;
        }
        if (!updateOwnLocation($me['id'], $location)) {
            ob_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => t('dashboard.err_location_failed')]);
            exit;
        }
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Dashboard Update Contact] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/dashboard-update-contact.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Guard check**

```bash
curl -s -o /dev/null -w "%{http_code}" http://slapia.local/api/dashboard-update-contact.php -X POST -H "Content-Type: application/json" -d "{\"phone\":\"0600000000\"}"
```
Expected: `302` (redirect to `/login`).

- [ ] **Step 4: Commit**

```bash
git add api/dashboard-update-contact.php
git commit -m "feat(dashboard): add phone/location update endpoint"
```

---

### Task 4: New endpoint `api/admin-update-contact-exec.php`

**Files:**
- Create: `api/admin-update-contact-exec.php`

**Interfaces:**
- Consumes: `requireAdmin()`, `verifyCSRFToken()`, `updateAccountContactDetails()` (Task 2), `t()`.
- Produces: `POST /api/admin-update-contact-exec.php` — JSON body `{page_id, phone, location, orders}` (all 4 required; phone/location/orders may be empty strings to clear), header `X-CSRF-Token`. Response `{success: true}` or `{success: false, error: string}`.

- [ ] **Step 1: Write the endpoint**

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

    $input    = json_decode(file_get_contents('php://input'), true) ?: [];
    $pageId   = trim($input['page_id'] ?? '');
    $phone    = array_key_exists('phone', $input) ? $input['phone'] : null;
    $location = array_key_exists('location', $input) ? $input['location'] : null;
    $orders   = array_key_exists('orders', $input) ? $input['orders'] : null;

    if ($pageId === '' || $phone === null || $location === null || $orders === null) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('admin.err_fields')]);
        exit;
    }

    if (!updateAccountContactDetails($pageId, (string)$phone, (string)$location, (string)$orders)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('admin.err_update_failed')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Admin Update Contact] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l api/admin-update-contact-exec.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Guard check**

```bash
curl -s -o /dev/null -w "%{http_code}" http://slapia.local/api/admin-update-contact-exec.php -X POST -H "Content-Type: application/json" -d "{\"page_id\":\"x\",\"phone\":\"\",\"location\":\"\",\"orders\":\"\"}"
```
Expected: `302` (redirect — `requireAdmin()` redirects anonymous requests to `/login` same as `requireLogin()`).

- [ ] **Step 4: Commit**

```bash
git add api/admin-update-contact-exec.php
git commit -m "feat(admin): add contact details update endpoint"
```

---

### Task 5: Client dashboard — phone/location fields, orders line, i18n keys

**Files:**
- Modify: `pages/dashboard.php`
- Modify: `assets/js/dashboard.js`

**Interfaces:**
- Consumes: `window.DASHBOARD_I18N` new keys, `/api/dashboard-update-contact.php` (Task 3), `account.phone`/`account.location`/`account.orders` from `/api/dashboard-data.php` (already flows through automatically once Task 1 lands, no endpoint change needed there).
- Produces: two new editable fields in the Profile card (phone, location) and a new read-only line in the Billing card (orders).

- [ ] **Step 1: Extend `window.DASHBOARD_I18N` in `pages/dashboard.php`**

In `pages/dashboard.php`, find this line inside the `json_encode([...])` call:

```php
    'preview_empty' => t('dashboard.preview_empty'),
], JSON_UNESCAPED_UNICODE); ?>;
```

Replace it with:

```php
    'preview_empty' => t('dashboard.preview_empty'),
    'label_phone' => t('dashboard.label_phone'),
    'placeholder_phone' => t('dashboard.placeholder_phone'),
    'err_phone_invalid' => t('dashboard.err_phone_invalid'),
    'phone_updated' => t('dashboard.phone_updated'),
    'label_location' => t('dashboard.label_location'),
    'placeholder_location' => t('dashboard.placeholder_location'),
    'location_updated' => t('dashboard.location_updated'),
    'label_orders' => t('dashboard.label_orders'),
    'empty_orders' => t('dashboard.empty_orders'),
], JSON_UNESCAPED_UNICODE); ?>;
```

- [ ] **Step 2: Lint the file**

Run: `php -l pages/dashboard.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Extend `renderProfile()` in `assets/js/dashboard.js` with phone and location fields**

Find this exact block in `assets/js/dashboard.js`:

```javascript
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
```

Replace it with:

```javascript
      '<div class="dash-field-edit">' +
        '<label for="linkedin-input">' + escHtml(I18N.label_linkedin) + '</label>' +
        '<div class="dash-field-edit__row">' +
          '<input type="url" id="linkedin-input" value="' + escHtml(account.linkedin || '') + '" placeholder="' + escHtml(I18N.placeholder_linkedin) + '">' +
          '<button type="button" class="btn btn--primary" id="linkedin-save-btn">' + escHtml(I18N.save) + '</button>' +
        '</div>' +
      '</div>' +
      '<div id="linkedin-alert"></div>' +
      '<div class="dash-field-edit">' +
        '<label for="phone-input">' + escHtml(I18N.label_phone) + '</label>' +
        '<div class="dash-field-edit__row">' +
          '<input type="tel" id="phone-input" value="' + escHtml(account.phone || '') + '" placeholder="' + escHtml(I18N.placeholder_phone) + '">' +
          '<button type="button" class="btn btn--primary" id="phone-save-btn">' + escHtml(I18N.save) + '</button>' +
        '</div>' +
      '</div>' +
      '<div id="phone-alert"></div>' +
      '<div class="dash-field-edit">' +
        '<label for="location-input">' + escHtml(I18N.label_location) + '</label>' +
        '<div class="dash-field-edit__row">' +
          '<input type="text" id="location-input" value="' + escHtml(account.location || '') + '" placeholder="' + escHtml(I18N.placeholder_location) + '">' +
          '<button type="button" class="btn btn--primary" id="location-save-btn">' + escHtml(I18N.save) + '</button>' +
        '</div>' +
      '</div>' +
      '<div id="location-alert"></div>';

    document.getElementById('photo-change-btn').addEventListener('click', function () {
      document.getElementById('photo-input').click();
    });
    document.getElementById('photo-input').addEventListener('change', onPhotoSelected);
    document.getElementById('linkedin-save-btn').addEventListener('click', onLinkedinSave);
    document.getElementById('phone-save-btn').addEventListener('click', onPhoneSave);
    document.getElementById('location-save-btn').addEventListener('click', onLocationSave);
  }
```

- [ ] **Step 4: Add `onPhoneSave()` and `onLocationSave()` functions**

Find this exact block (right after `onLinkedinSave()`'s closing, before `renderAvis()`):

```javascript
  function renderAvis() {
```

Replace it with:

```javascript
  function onPhoneSave() {
    var alertBox = document.getElementById('phone-alert');
    alertBox.innerHTML = '';
    var value = document.getElementById('phone-input').value.trim();

    fetch('/api/dashboard-update-contact.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.DASHBOARD_CSRF_TOKEN },
      body: JSON.stringify({ phone: value }),
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json.success) {
          account.phone = value;
          alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.phone_updated) + '</span></div>';
        } else {
          alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
        }
      });
  }

  function onLocationSave() {
    var alertBox = document.getElementById('location-alert');
    alertBox.innerHTML = '';
    var value = document.getElementById('location-input').value.trim();

    fetch('/api/dashboard-update-contact.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.DASHBOARD_CSRF_TOKEN },
      body: JSON.stringify({ location: value }),
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json.success) {
          account.location = value;
          alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.location_updated) + '</span></div>';
        } else {
          alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
        }
      });
  }

  function renderAvis() {
```

- [ ] **Step 5: Add the read-only orders line to `renderBilling()`**

Find this exact block:

```javascript
  function renderBilling() {
    document.getElementById('dashboard-billing').innerHTML =
      '<h2>' + escHtml(I18N.label_billing) + '</h2>' +
      (account.price ? '<div class="dash-price">' + account.price + ' €</div>' : '') +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_status) + '</span><span class="dash-field__value">' + escHtml(account.billing || '—') + '</span></div>' +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_last_login) + '</span><span class="dash-field__value">' + (account.lastLogin ? new Date(account.lastLogin).toLocaleString('fr-FR') : '—') + '</span></div>';
  }
```

Replace it with:

```javascript
  function renderBilling() {
    document.getElementById('dashboard-billing').innerHTML =
      '<h2>' + escHtml(I18N.label_billing) + '</h2>' +
      (account.price ? '<div class="dash-price">' + account.price + ' €</div>' : '') +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_status) + '</span><span class="dash-field__value">' + escHtml(account.billing || '—') + '</span></div>' +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_last_login) + '</span><span class="dash-field__value">' + (account.lastLogin ? new Date(account.lastLogin).toLocaleString('fr-FR') : '—') + '</span></div>' +
      '<div class="dash-field"><span class="dash-field__label">' + escHtml(I18N.label_orders) + '</span><span class="dash-field__value">' + escHtml(account.orders || I18N.empty_orders) + '</span></div>';
  }
```

- [ ] **Step 6: Verify JS syntax**

Run: `node --check assets/js/dashboard.js`
Expected: no output (success).

- [ ] **Step 7: Commit**

```bash
git add pages/dashboard.php assets/js/dashboard.js
git commit -m "feat(dashboard): render phone/location fields and orders line"
```

---

### Task 6: Admin dashboard — expandable "Détails" row for phone/location/orders

**Files:**
- Modify: `pages/admin.php`
- Modify: `assets/js/admin.js`

**Interfaces:**
- Consumes: new `admin.*` i18n keys, `/api/admin-update-contact-exec.php` (Task 4), `a.phone`/`a.location`/`a.orders` from `/api/admin-data.php` (flows through automatically once Task 2 lands).
- Produces: `window.ADMIN_I18N` (new — this page has no i18n JS object yet); a "Détails" toggle button per account row that reveals an editable phone/location/orders panel.

- [ ] **Step 1: Add `window.ADMIN_I18N` to `pages/admin.php`**

Find this exact line in `pages/admin.php`:

```php
<script>window.ADMIN_CSRF_TOKEN = <?php echo json_encode($csrf); ?>;</script>
```

Replace it with:

```php
<script>
window.ADMIN_CSRF_TOKEN = <?php echo json_encode($csrf); ?>;
window.ADMIN_I18N = <?php echo json_encode([
    'details_btn' => t('admin.details_btn'),
    'close_btn' => t('admin.close_btn'),
    'label_phone' => t('admin.label_phone'),
    'label_location' => t('admin.label_location'),
    'label_orders' => t('admin.label_orders'),
    'save' => t('admin.save'),
    'contact_updated' => t('admin.contact_updated'),
    'err_fields' => t('admin.err_fields'),
    'err_update_failed' => t('admin.err_update_failed'),
], JSON_UNESCAPED_UNICODE); ?>;
</script>
```

This is the **only** i18n wiring this task adds — every other string already in `assets/js/admin.js` stays hardcoded French, per the Global Constraints' i18n scope decision.

- [ ] **Step 2: Lint the file**

Run: `php -l pages/admin.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Add the details-row markup and "Détails" button in `assets/js/admin.js`**

Find this exact block (the top of the file, right after the `BILLING_CLASS`... no — find `accountRowHtml`):

```javascript
  function accountRowHtml(a) {
    return '<tr data-id="' + escHtml(a.id) + '" data-search="' + escHtml((a.name + ' ' + a.email + ' ' + a.company).toLowerCase()) + '">' +
      '<td><div style="display:flex; align-items:center; gap:10px;"><img src="/api/notion-avatar.php?id=' + encodeURIComponent(a.id) + '" alt="" class="admin-avatar" loading="lazy">' + escHtml(a.name) + '</div></td>' +
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
```

Replace it with:

```javascript
  var I18N = window.ADMIN_I18N || {};

  function accountRowHtml(a) {
    return '<tr data-id="' + escHtml(a.id) + '" data-search="' + escHtml((a.name + ' ' + a.email + ' ' + a.company).toLowerCase()) + '">' +
      '<td><div style="display:flex; align-items:center; gap:10px;"><img src="/api/notion-avatar.php?id=' + encodeURIComponent(a.id) + '" alt="" class="admin-avatar" loading="lazy">' + escHtml(a.name) + '</div></td>' +
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
      '<td><button class="btn btn--ghost reset-pwd-btn" data-email="' + escHtml(a.email) + '">Reset MDP</button> ' +
        '<button class="btn btn--ghost details-toggle-btn" data-id="' + escHtml(a.id) + '">' + escHtml(I18N.details_btn) + '</button></td>' +
    '</tr>';
  }

  function accountDetailsRowHtml(a) {
    return '<tr class="admin-details-row" data-details-for="' + escHtml(a.id) + '" style="display:none;">' +
      '<td colspan="8">' +
        '<div class="admin-details-panel">' +
          '<div class="admin-details-field">' +
            '<label>' + escHtml(I18N.label_phone) + '</label>' +
            '<input type="tel" class="detail-phone" value="' + escHtml(a.phone || '') + '">' +
          '</div>' +
          '<div class="admin-details-field">' +
            '<label>' + escHtml(I18N.label_location) + '</label>' +
            '<input type="text" class="detail-location" value="' + escHtml(a.location || '') + '">' +
          '</div>' +
          '<div class="admin-details-field admin-details-field--wide">' +
            '<label>' + escHtml(I18N.label_orders) + '</label>' +
            '<textarea class="detail-orders">' + escHtml(a.orders || '') + '</textarea>' +
          '</div>' +
          '<button type="button" class="btn btn--primary detail-save-btn" data-id="' + escHtml(a.id) + '">' + escHtml(I18N.save) + '</button>' +
          '<div class="detail-alert"></div>' +
        '</div>' +
      '</td>' +
    '</tr>';
  }
```

- [ ] **Step 4: Wire the row pairing, toggle, and save handler in `renderAccounts()`**

Find this exact block:

```javascript
  function renderAccounts() {
    var el = document.getElementById('admin-tab-accounts');
    var rows = data.accounts.map(accountRowHtml).join('');
```

Replace it with:

```javascript
  function renderAccounts() {
    var el = document.getElementById('admin-tab-accounts');
    var rows = data.accounts.map(function (a) { return accountRowHtml(a) + accountDetailsRowHtml(a); }).join('');
```

Then find this exact block (the end of `renderAccounts()`, right after the `reset-pwd-btn` wiring):

```javascript
    el.querySelectorAll('.reset-pwd-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var newPassword = prompt('Nouveau mot de passe pour ' + btn.dataset.email + ' (8 caractères min.) :');
        if (!newPassword) return;
        resetPassword(btn.dataset.email, newPassword);
      });
    });
  }
```

Replace it with:

```javascript
    el.querySelectorAll('.reset-pwd-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var newPassword = prompt('Nouveau mot de passe pour ' + btn.dataset.email + ' (8 caractères min.) :');
        if (!newPassword) return;
        resetPassword(btn.dataset.email, newPassword);
      });
    });
    el.querySelectorAll('.details-toggle-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var row = el.querySelector('.admin-details-row[data-details-for="' + btn.dataset.id + '"]');
        var isHidden = row.style.display === 'none';
        row.style.display = isHidden ? 'table-row' : 'none';
        btn.textContent = isHidden ? I18N.close_btn : I18N.details_btn;
      });
    });
    el.querySelectorAll('.detail-save-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var panel = btn.closest('.admin-details-panel');
        var phone = panel.querySelector('.detail-phone').value.trim();
        var location = panel.querySelector('.detail-location').value.trim();
        var orders = panel.querySelector('.detail-orders').value.trim();
        var alertBox = panel.querySelector('.detail-alert');
        alertBox.innerHTML = '';

        fetch('/api/admin-update-contact-exec.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.ADMIN_CSRF_TOKEN },
          body: JSON.stringify({ page_id: btn.dataset.id, phone: phone, location: location, orders: orders }),
        })
          .then(function (r) { return r.json(); })
          .then(function (json) {
            if (json.success) {
              alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + escHtml(I18N.contact_updated) + '</span></div>';
            } else {
              alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + escHtml(json.error) + '</span></div>';
            }
          });
      });
    });
  }
```

- [ ] **Step 5: Exclude details rows from CSV export**

Find this exact block:

```javascript
  function exportTableToCSV(tableId, filename) {
    var rows = Array.from(document.querySelectorAll('#' + tableId + ' tr')).filter(function (r) { return r.style.display !== 'none'; });
```

Replace it with:

```javascript
  function exportTableToCSV(tableId, filename) {
    var rows = Array.from(document.querySelectorAll('#' + tableId + ' tr:not(.admin-details-row)')).filter(function (r) { return r.style.display !== 'none'; });
```

This prevents an opened "Détails" panel from corrupting the CSV output (its `<td colspan="8">` doesn't line up with the header columns).

- [ ] **Step 6: Verify JS syntax**

Run: `node --check assets/js/admin.js`
Expected: no output (success).

- [ ] **Step 7: Commit**

```bash
git add pages/admin.php assets/js/admin.js
git commit -m "feat(admin): add expandable contact details panel per account"
```

---

### Task 7: `assets/css/admin.css` — details panel styles

**Files:**
- Modify: `assets/css/admin.css`

**Interfaces:**
- Produces: `.admin-details-panel`, `.admin-details-field`, `.admin-details-field--wide` classes used by Task 6's JS.

- [ ] **Step 1: Append the new styles**

Append to the end of `assets/css/admin.css`:

```css
.admin-details-row td { white-space: normal; }
.admin-details-panel {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  align-items: flex-end;
  padding: 16px;
  background: var(--paper);
  border-radius: 10px;
}
.admin-details-field { display: flex; flex-direction: column; gap: 4px; min-width: 160px; }
.admin-details-field--wide { flex: 1 1 260px; }
.admin-details-field label {
  font-size: 0.75rem;
  color: var(--ink-fade);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.admin-details-field input, .admin-details-field textarea {
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid var(--line-strong);
  background: var(--white);
  color: var(--ink);
  font-family: inherit;
  font-size: 0.85rem;
}
.admin-details-field textarea { min-height: 60px; resize: vertical; }
```

- [ ] **Step 2: Commit**

```bash
git add assets/css/admin.css
git commit -m "feat(admin): add styles for contact details panel"
```

---

### Task 8: Translations (fr/en/de)

**Files:**
- Modify: `lang/fr.php`
- Modify: `lang/en.php`
- Modify: `lang/de.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: every `dashboard.*` and `admin.*` key referenced by Tasks 5-6 that doesn't already exist, in all 3 languages.

- [ ] **Step 1: Add the new `dashboard.*` keys to `lang/fr.php`**

In `lang/fr.php`, inside the existing `'dashboard' => [ ... ]` array, add before the closing `],`:

```php
    'label_phone' => 'Téléphone',
    'placeholder_phone' => '06 12 34 56 78',
    'err_phone_invalid' => 'Numéro de téléphone invalide.',
    'err_phone_failed' => 'La mise à jour a échoué. Réessayez.',
    'phone_updated' => 'Téléphone mis à jour.',
    'label_location' => 'Adresse',
    'placeholder_location' => 'Votre adresse',
    'err_location_too_long' => 'Adresse trop longue (500 caractères max).',
    'err_location_failed' => 'La mise à jour a échoué. Réessayez.',
    'location_updated' => 'Adresse mise à jour.',
    'err_contact_fields' => 'Aucune donnée à mettre à jour.',
    'label_orders' => 'Commandes',
    'empty_orders' => 'Aucune commande enregistrée pour le moment.',
```

- [ ] **Step 2: Add the new `admin.*` keys to `lang/fr.php`**

In `lang/fr.php`, inside the existing `'admin' => [ ... ]` array, add before the closing `],`:

```php
    'details_btn' => 'Détails',
    'close_btn' => 'Fermer',
    'label_phone' => 'Téléphone',
    'label_location' => 'Adresse',
    'label_orders' => 'Commandes',
    'save' => 'Enregistrer',
    'contact_updated' => 'Enregistré.',
```

- [ ] **Step 3: Add the equivalent `dashboard.*` and `admin.*` keys to `lang/en.php`**

In `lang/en.php`'s `'dashboard' => [ ... ]` array:

```php
    'label_phone' => 'Phone',
    'placeholder_phone' => '+1 555 123 4567',
    'err_phone_invalid' => 'Invalid phone number.',
    'err_phone_failed' => 'Update failed. Please try again.',
    'phone_updated' => 'Phone updated.',
    'label_location' => 'Address',
    'placeholder_location' => 'Your address',
    'err_location_too_long' => 'Address too long (500 characters max).',
    'err_location_failed' => 'Update failed. Please try again.',
    'location_updated' => 'Address updated.',
    'err_contact_fields' => 'Nothing to update.',
    'label_orders' => 'Orders',
    'empty_orders' => 'No orders recorded yet.',
```

In `lang/en.php`'s `'admin' => [ ... ]` array:

```php
    'details_btn' => 'Details',
    'close_btn' => 'Close',
    'label_phone' => 'Phone',
    'label_location' => 'Address',
    'label_orders' => 'Orders',
    'save' => 'Save',
    'contact_updated' => 'Saved.',
```

- [ ] **Step 4: Add the equivalent `dashboard.*` and `admin.*` keys to `lang/de.php`**

In `lang/de.php`'s `'dashboard' => [ ... ]` array:

```php
    'label_phone' => 'Telefon',
    'placeholder_phone' => '030 12345678',
    'err_phone_invalid' => 'Ungültige Telefonnummer.',
    'err_phone_failed' => 'Aktualisierung fehlgeschlagen. Bitte erneut versuchen.',
    'phone_updated' => 'Telefon aktualisiert.',
    'label_location' => 'Adresse',
    'placeholder_location' => 'Ihre Adresse',
    'err_location_too_long' => 'Adresse zu lang (max. 500 Zeichen).',
    'err_location_failed' => 'Aktualisierung fehlgeschlagen. Bitte erneut versuchen.',
    'location_updated' => 'Adresse aktualisiert.',
    'err_contact_fields' => 'Nichts zu aktualisieren.',
    'label_orders' => 'Bestellungen',
    'empty_orders' => 'Noch keine Bestellungen erfasst.',
```

In `lang/de.php`'s `'admin' => [ ... ]` array:

```php
    'details_btn' => 'Details',
    'close_btn' => 'Schließen',
    'label_phone' => 'Telefon',
    'label_location' => 'Adresse',
    'label_orders' => 'Bestellungen',
    'save' => 'Speichern',
    'contact_updated' => 'Gespeichert.',
```

- [ ] **Step 5: Lint all three files**

Run: `php -l lang/fr.php && php -l lang/en.php && php -l lang/de.php`
Expected: `No syntax errors detected` × 3.

- [ ] **Step 6: Verify key parity across languages (both namespaces)**

Run:
```bash
php -r '
foreach (["dashboard", "admin"] as $ns) {
    $fr = array_keys((require "lang/fr.php")[$ns]);
    $en = array_keys((require "lang/en.php")[$ns]);
    $de = array_keys((require "lang/de.php")[$ns]);
    sort($fr); sort($en); sort($de);
    echo $ns . ": " . (($fr === $en && $fr === $de) ? "OK: identical key sets\n" : "MISMATCH\n");
}
'
```
Expected: `dashboard: OK: identical key sets` and `admin: OK: identical key sets`.

- [ ] **Step 7: Commit**

```bash
git add lang/fr.php lang/en.php lang/de.php
git commit -m "i18n: add translations for contact fields (phone/location/orders)"
```

---

## Final Whole-Branch Review

After all 8 tasks are complete, dispatch a final whole-branch code review covering the full diff (Tasks 1-8 together), checking against every item in Global Constraints above — in particular: CSRF coverage on every write endpoint, that the client endpoint never accepts a page ID from the request, that the admin endpoint's page_id acceptance is legitimate (matches the existing admin pattern), phone/location/orders validation matching the exact rules specified, the i18n scope decision (dashboard.js fully i18n'd, admin.js's *new* strings i18n'd via `ADMIN_I18N` while its pre-existing strings are correctly left untouched), `escHtml()` discipline in both JS files, and full i18n key parity for both `dashboard.*` and `admin.*` namespaces across fr/en/de.
