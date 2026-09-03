<?php
/**
 * Charge un éventuel fichier .env en local et expose un helper config().
 */

function loadEnv($path)
{
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (preg_match('/^(["\'])(.*)\1$/', $value, $m)) {
            $value = $m[2];
        }
        if ($name === '') continue;
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/../.env');

/**
 * Récupère une valeur de configuration en privilégiant les variables d'environnement.
 */
function config(string $key, $default = null)
{
    $value = getenv($key);
    if ($value === false) {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    return $value;
}

/**
 * True if the request reached us over HTTPS. Checks both the standard
 * $_SERVER['HTTPS'] flag AND the X-Forwarded-Proto header: in production
 * the app container sits behind the Caddy reverse proxy, which terminates
 * TLS and talks plain HTTP to PHP internally, so $_SERVER['HTTPS'] alone is
 * never true there — without this, session and remember-me cookies would
 * silently never get the Secure flag in production. Safe to trust the
 * header here because the app containers aren't reachable except through
 * the proxy (no public port maps directly to slapia-prod).
 */
function isSecureRequest(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    return strcasecmp(trim(explode(',', $proto)[0]), 'https') === 0;
}

/**
 * Appends a cache-busting query string (the file's last-modified time) to a
 * site-root-relative asset path, e.g. assetUrl('/assets/css/style.css').
 * Without this, updated CSS/JS wouldn't reach visitors with a warm cache
 * until the 1-month Cache-Control on these files (see .htaccess) expires,
 * since the filename itself never changes across deploys.
 */
function assetUrl(string $path): string
{
    $full = __DIR__ . '/../' . ltrim($path, '/');
    $v = @filemtime($full);
    return $path . ($v ? '?v=' . $v : '');
}

// ─────────────────────────────────────────────────────────────────────────
//  Session bootstrap (secure cookie params + remember-me restoration)
// ─────────────────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 1 day
        'path'     => '/',
        'domain'   => '',
        'secure'   => isSecureRequest(),
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
            setcookie('remember_token', '', time() - 3600, '/', '', isSecureRequest(), true);
        }
        return;
    }

    $data = json_decode(@file_get_contents($file), true);

    if (!$data || empty($data['user_id']) || ($data['expires'] ?? 0) < time()) {
        @unlink($file);
        if (!headers_sent()) {
            setcookie('remember_token', '', time() - 3600, '/', '', isSecureRequest(), true);
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
