<?php
// Suppress PHP warnings/notices — config.php is included before ob_start() in most API files.
error_reporting(0);
ini_set('display_errors', 0);

/**
 * Charge un éventuel fichier .env en local et expose un helper config()
 */

// Start session securely if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 1 day
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
    _restoreSessionFromRememberToken();
}

/**
 * Restore an expired PHP session from a long-lived remember-me token stored on disk.
 * Called once right after session_start(). Safe to call from any context (API or page).
 */
function _restoreSessionFromRememberToken(): void
{
    // Already authenticated — nothing to do.
    if (!empty($_SESSION['logged_in'])) return;

    $token = $_COOKIE['remember_token'] ?? '';

    // Validate format: 64 lowercase hex chars.
    if (!$token || !preg_match('/^[a-f0-9]{64}$/', $token)) return;

    $file = sys_get_temp_dir() . '/slapia_rt_' . $token . '.json';

    if (!is_readable($file)) {
        // Cookie references a token that no longer exists — clear it.
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

    // Restore session.
    $_SESSION['user_id']    = $data['user_id'];
    $_SESSION['user_email'] = $data['user_email'] ?? '';
    $_SESSION['user_name']  = $data['user_name'] ?? '';
    $_SESSION['logged_in']  = true;

    session_regenerate_id(true);

    // Occasional cleanup of stale token files (≈1% of restorations).
    if (rand(1, 100) === 1) {
        _cleanupExpiredRememberTokens();
    }
}

/**
 * Delete token files that have passed their expiry date.
 */
function _cleanupExpiredRememberTokens(): void
{
    $dir   = sys_get_temp_dir();
    $now   = time();
    $files = glob($dir . '/slapia_rt_*.json') ?: [];
    foreach ($files as $f) {
        $d = json_decode(@file_get_contents($f), true);
        if (!$d || ($d['expires'] ?? 0) < $now) {
            @unlink($f);
        }
    }
}


// Ne charge le fichier .env que s'il existe encore (utile en développement local)
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
        // Skip lines without '='
        if (strpos($line, '=') === false) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        // Strip surrounding quotes (single or double)
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

// Essayez de charger .env uniquement pour le développement local ; en production sur Kubernetes,
// le fichier n’existe pas et loadEnv() ne fera rien.
loadEnv(__DIR__ . '/../.env');



/**
 * Récupère une valeur de configuration en privilégiant les variables d’environnement.
 *
 * @param string $key Nom de la variable (.env, Secret Kubernetes, etc.)
 * @param mixed $default Valeur par défaut si la variable est absente
 *
 * @return mixed
 */
function config(string $key, $default = null)
{
    // getenv() renvoie false si la variable n’existe pas
    $value = getenv($key);
    if ($value === false) {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    return $value;
}

/**
 * CSRF Protection Helpers
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>
