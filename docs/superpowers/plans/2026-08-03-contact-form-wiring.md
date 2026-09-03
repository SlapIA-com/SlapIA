# Contact Form Wiring Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace `contact.php`'s unreliable raw `mail()` submission with CSRF protection, Cloudflare Turnstile, rate limiting, a write to the existing (but unused) Notion "Contact" database, and a best-effort email via the existing n8n webhook.

**Architecture:** `contact.php` stays a traditional server-rendered POST-back page (no JS/fetch conversion — the visible form is unchanged). A new `includes/notion-contact.php` owns the Notion write, mirroring the bool-return/error_log pattern used everywhere else in this codebase. The n8n webhook call mirrors the existing fire-and-forget pattern in `api/auth-reset-request.php`.

**Tech Stack:** PHP 8, Notion API (`includes/notion.php`'s `NotionAPI::request()`), Cloudflare Turnstile (existing site/secret keys in `.env`), existing `t()` i18n helper.

## Global Constraints

- The user has already added two new properties to the live `NOTION_CONTACT_DATABASE_ID` database before this plan executes: `Entreprise` (rich_text) and `Sujet` (rich_text). Do not attempt to create or verify these via automated means during task execution — assume they exist, per the design spec.
- Never touch the Notion properties `prise de contact ok ?` (an internal admin follow-up flag) or `Date de création` (auto-set) — `submitContactMessage()` must not include them in its write.
- The form's single "name" field splits into `Prenom` (title) and `Nom` (rich_text) using the same first-word/rest split already used by `getNotionReviews()` in `includes/reviews.php`.
- CSRF token verified via a hidden `csrf_token` POST field (not a header — this is a traditional form POST, not a fetch/JSON endpoint, matching this file's existing architecture).
- Turnstile response arrives directly as `$_POST['cf-turnstile-response']` — no custom JS needed, unlike `pages/login.php` which reads it via JS because that page converts its form to a fetch POST.
- Rate limiting: `rateLimitCheck('contact_ip_' . $ip, 5, 900)` (5 submissions / 15 min per IP), same pattern as `api/auth-login.php`/`api/auth-reset-request.php`.
- Message length capped at 5000 characters (`mb_strlen`).
- The n8n webhook call reuses the existing `N8N_AUTH_WEBHOOK_URL` env var with `event: 'contact_form'` in its payload — no new webhook URL. It is best-effort: a failed or unconfigured webhook must never block or fail the form submission (only `error_log()`s), matching `api/auth-reset-request.php`'s exact pattern.
- Every Notion write function returns `bool`, checks `!empty($result['error']) || ($result['http_code'] ?? 0) >= 300`, `error_log()`s on failure, returns `false` — never a silent success.
- Do not exercise any mutating Notion write or real Turnstile/webhook call against the live database/services during automated task verification — guard checks (missing CSRF token, missing Turnstile response) and `php -l` only. Full functional verification (a real submission) is manual, done by the user.

---

### Task 1: Create `includes/notion-contact.php`

**Files:**
- Create: `includes/notion-contact.php`

**Interfaces:**
- Consumes: `notion()` singleton, `config()` (existing).
- Produces (used by Task 2): `submitContactMessage(string $prenom, string $nom, string $email, string $company, string $subject, string $message): bool`

- [ ] **Step 1: Write the file**

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notion.php';

/**
 * Creates a new entry in the public "Contact" Notion database from a
 * contact-form submission. Never touches "prise de contact ok ?" (an
 * internal admin follow-up flag, manually checked once someone has been
 * recontacted) or "Date de création" (auto-set by Notion on page creation).
 */
function submitContactMessage(string $prenom, string $nom, string $email, string $company, string $subject, string $message): bool
{
    $dbId = config('NOTION_CONTACT_DATABASE_ID');

    $result = notion()->request('POST', '/pages', [
        'parent' => ['database_id' => $dbId],
        'properties' => [
            'Prenom'     => ['title' => [['text' => ['content' => $prenom]]]],
            'Nom'        => ['rich_text' => $nom !== '' ? [['text' => ['content' => $nom]]] : []],
            'Email'      => ['email' => $email],
            'Entreprise' => ['rich_text' => $company !== '' ? [['text' => ['content' => $company]]] : []],
            'Sujet'      => ['rich_text' => $subject !== '' ? [['text' => ['content' => $subject]]] : []],
            'Message'    => ['rich_text' => [['text' => ['content' => $message]]]],
        ],
    ]);

    if (!empty($result['error']) || ($result['http_code'] ?? 0) >= 300) {
        error_log('[SlapIA Contact] submitContactMessage failed: ' . json_encode($result));
        return false;
    }

    return true;
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l includes/notion-contact.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add includes/notion-contact.php
git commit -m "feat(contact): add Notion write for contact form submissions"
```

---

### Task 2: Rewire `contact.php` — CSRF, Turnstile, rate limit, Notion write, n8n webhook

**Files:**
- Modify: `contact.php`
- Modify: `assets/css/style.css`

**Interfaces:**
- Consumes: `generateCSRFToken()`/`verifyCSRFToken()` (from `includes/config.php`), `rateLimitCheck()` (from `includes/auth.php`), `submitContactMessage()` (Task 1), `config()`, `t()`.
- Produces: the rebuilt POST-handling block and form markup in `contact.php`.

- [ ] **Step 1: Replace the top of `contact.php` (requires + POST-handling block)**

Find this exact block at the top of `contact.php`:

```php
<?php
require_once 'includes/i18n.php';
$page_title = t('contact.meta_title');
$page_description = t('contact.meta_description');

$errors = [];
$sent = isset($_GET['sent']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $consent = isset($_POST['consent']);

    if ($name === '') $errors[] = t('contact.err_name');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('contact.err_email');
    if ($message === '') $errors[] = t('contact.err_message');
    if (!$consent) $errors[] = t('contact.err_consent');

    if (empty($errors)) {
        $to = "contact@slapia.com";
        $mailSubject = "[Slapia] Nouveau message" . ($subject !== '' ? " — " . $subject : "");
        $body = "Langue du site : $lang\n"
              . "Nom : $name\n"
              . "E-mail : $email\n"
              . "Entreprise : " . ($company !== '' ? $company : "-") . "\n"
              . "Sujet : " . ($subject !== '' ? $subject : "-") . "\n\n"
              . "Message :\n$message\n";

        $headers = "From: no-reply@slapia.com\r\n";
        $headers .= "Reply-To: " . str_replace(["\r", "\n"], '', $email) . "\r\n";

        $previous_timeout = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', 5);
        @mail($to, $mailSubject, $body, $headers);
        ini_set('default_socket_timeout', $previous_timeout);

        header('Location: contact.php?sent=1');
        exit;
    }
}

include 'includes/header.php';
?>
```

Replace it with:

```php
<?php
require_once 'includes/config.php';
require_once 'includes/i18n.php';
require_once 'includes/auth.php';
require_once 'includes/notion-contact.php';

$page_title = t('contact.meta_title');
$page_description = t('contact.meta_description');

$errors = [];
$sent = isset($_GET['sent']);
$csrf = generateCSRFToken();
$turnstileSiteKey = config('TURNSTILE_SITE_KEY', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $company   = trim($_POST['company'] ?? '');
    $subject   = trim($_POST['subject'] ?? '');
    $message   = trim($_POST['message'] ?? '');
    $consent   = isset($_POST['consent']);
    $csrfToken = $_POST['csrf_token'] ?? '';
    $turnstile = $_POST['cf-turnstile-response'] ?? '';
    $ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = t('contact.err_csrf');
    } elseif (!rateLimitCheck('contact_ip_' . $ip, 5, 900)) {
        $errors[] = t('contact.err_rate_limit');
    } else {
        if ($name === '') $errors[] = t('contact.err_name');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('contact.err_email');
        if ($message === '' || mb_strlen($message) > 5000) $errors[] = t('contact.err_message');
        if (!$consent) $errors[] = t('contact.err_consent');

        if ($turnstile === '') {
            $errors[] = t('contact.err_captcha');
        } else {
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
                $errors[] = t('contact.err_captcha_failed');
            }
        }
    }

    if (empty($errors)) {
        $parts  = preg_split('/\s+/', $name, 2);
        $prenom = $parts[0] ?? '';
        $nom    = $parts[1] ?? '';

        if (!submitContactMessage($prenom, $nom, $email, $company, $subject, $message)) {
            $errors[] = t('contact.err_server');
        } else {
            $webhookUrl = config('N8N_AUTH_WEBHOOK_URL');
            if ($webhookUrl) {
                $ch = curl_init($webhookUrl);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => json_encode([
                        'event'   => 'contact_form',
                        'name'    => $name,
                        'email'   => $email,
                        'company' => $company,
                        'subject' => $subject,
                        'message' => $message,
                    ]),
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 5,
                ]);
                curl_exec($ch);
            } else {
                error_log('[SlapIA Contact] N8N_AUTH_WEBHOOK_URL non configuré, email non envoyé pour ' . $email);
            }

            header('Location: contact.php?sent=1');
            exit;
        }
    }
}

include 'includes/header.php';
?>
```

- [ ] **Step 2: Add the CSRF hidden field and Turnstile widget to the form**

Find this exact block:

```php
      <form method="post" action="contact.php" novalidate>
        <div class="form-grid">
```

Replace it with:

```php
      <form method="post" action="contact.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
        <div class="form-grid">
```

Then find this exact block:

```php
        <label class="consent-check">
          <input type="checkbox" name="consent" required <?php echo isset($_POST['consent']) ? 'checked' : ''; ?>>
          <span><?php echo t('contact.consent_text'); ?> <a href="confidentialite.php"><?php echo t('contact.consent_link'); ?></a>.</span>
        </label>
        <button type="submit" class="btn btn--signal"><?php echo t('contact.submit'); ?> <span class="btn__arrow">→</span></button>
      </form>
```

Replace it with:

```php
        <label class="consent-check">
          <input type="checkbox" name="consent" required <?php echo isset($_POST['consent']) ? 'checked' : ''; ?>>
          <span><?php echo t('contact.consent_text'); ?> <a href="confidentialite.php"><?php echo t('contact.consent_link'); ?></a>.</span>
        </label>
        <div class="contact-turnstile-wrap">
          <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($turnstileSiteKey); ?>"></div>
        </div>
        <button type="submit" class="btn btn--signal"><?php echo t('contact.submit'); ?> <span class="btn__arrow">→</span></button>
      </form>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
```

- [ ] **Step 3: Add minimal spacing CSS for the Turnstile wrapper**

Append to the end of `assets/css/style.css`:

```css
.contact-turnstile-wrap { margin: 20px 0; }
```

- [ ] **Step 4: Lint the PHP file**

Run: `php -l contact.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Guard checks**

With the local server running (`http://slapia.local/`), verify the new validation layers reject bad input without ever reaching Notion or crashing:

```bash
# Missing CSRF token → should redisplay the form with an error, not a 500
curl -s -o /dev/null -w "%{http_code}" http://slapia.local/contact.php -X POST -d "name=Test&email=test@example.com&message=hi&consent=1"
```
Expected: `200` (the page re-renders with `$errors` populated — a traditional POST-back page returns 200 with the error shown inline, it does not redirect or 4xx).

```bash
# With a CSRF token that doesn't match any session (a bare request has no session cookie) → same 200-with-errors behavior, and specifically must not crash on a missing Turnstile response either
curl -s "http://slapia.local/contact.php" -X POST -d "name=Test&email=test@example.com&message=hi&consent=1&csrf_token=bogus" | grep -o "Requête invalide\|Invalid request\|Ungültige Anfrage" || echo "NO_CSRF_ERROR_FOUND"
```
Expected: the CSRF error message text is found in the response body (confirms the branch is reached and rendered, not a crash). If translations aren't the default language, seeing `NO_CSRF_ERROR_FOUND` alone isn't necessarily a failure — the important thing is the earlier `200` status check and that no PHP fatal error/warning appears in the response body.

Do not submit a request with a valid CSRF token and valid Turnstile response — that would attempt a real Notion write and a real webhook call, which is out of scope for automated verification.

- [ ] **Step 6: Commit**

```bash
git add contact.php assets/css/style.css
git commit -m "feat(contact): add CSRF, Turnstile, rate limiting, and Notion/n8n wiring"
```

---

### Task 3: Translations (fr/en/de)

**Files:**
- Modify: `lang/fr.php`
- Modify: `lang/en.php`
- Modify: `lang/de.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: 5 new `contact.*` keys referenced by Task 2, in all 3 languages.

- [ ] **Step 1: Add the new keys to `lang/fr.php`**

In `lang/fr.php`, inside the existing `'contact' => [ ... ]` array (find the block starting with `'contact' => [` around line 333), add before the closing `],`:

```php
    'err_csrf' => 'Requête invalide, merci de réessayer.',
    'err_rate_limit' => 'Trop de tentatives. Réessayez dans 15 minutes.',
    'err_captcha' => 'Veuillez compléter la sécurité Cloudflare.',
    'err_captcha_failed' => 'Validation de sécurité échouée.',
    'err_server' => 'Erreur serveur, merci de réessayer.',
```

- [ ] **Step 2: Add the equivalent keys to `lang/en.php`**

In `lang/en.php`'s `'contact' => [ ... ]` array, add before the closing `],`:

```php
    'err_csrf' => 'Invalid request, please try again.',
    'err_rate_limit' => 'Too many attempts. Please try again in 15 minutes.',
    'err_captcha' => 'Please complete the Cloudflare security check.',
    'err_captcha_failed' => 'Security check failed.',
    'err_server' => 'Server error, please try again.',
```

- [ ] **Step 3: Add the equivalent keys to `lang/de.php`**

In `lang/de.php`'s `'contact' => [ ... ]` array, add before the closing `],`:

```php
    'err_csrf' => 'Ungültige Anfrage, bitte erneut versuchen.',
    'err_rate_limit' => 'Zu viele Versuche. Bitte in 15 Minuten erneut versuchen.',
    'err_captcha' => 'Bitte die Cloudflare-Sicherheitsprüfung abschließen.',
    'err_captcha_failed' => 'Sicherheitsprüfung fehlgeschlagen.',
    'err_server' => 'Serverfehler, bitte erneut versuchen.',
```

- [ ] **Step 4: Lint all three files**

Run: `php -l lang/fr.php && php -l lang/en.php && php -l lang/de.php`
Expected: `No syntax errors detected` × 3.

- [ ] **Step 5: Verify key parity across languages**

Run:
```bash
php -r '
$fr = array_keys((require "lang/fr.php")["contact"]);
$en = array_keys((require "lang/en.php")["contact"]);
$de = array_keys((require "lang/de.php")["contact"]);
sort($fr); sort($en); sort($de);
echo ($fr === $en && $fr === $de) ? "OK: identical key sets\n" : "MISMATCH\n";
'
```
Expected: `OK: identical key sets`

- [ ] **Step 6: Commit**

```bash
git add lang/fr.php lang/en.php lang/de.php
git commit -m "i18n: add translations for contact form security messages"
```

---

## Final Whole-Branch Review

After all 3 tasks are complete, dispatch a final whole-branch code review covering the full diff, checking against every item in Global Constraints above — in particular: CSRF/Turnstile/rate-limit ordering (all three must be checked before any Notion write is attempted), the webhook call's best-effort behavior (a failed/missing webhook must never fail the user-visible submission), that `submitContactMessage()` never touches `prise de contact ok ?` or `Date de création`, and full i18n key parity for the 5 new `contact.*` keys across fr/en/de.
