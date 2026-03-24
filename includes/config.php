<?php
/**
 * Charge un éventuel fichier .env en local et expose un helper config()
 */

// Start session securely if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 1 day
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']), // true on HTTPS
        'httponly' => true, // prevent XSS
        'samesite' => 'Lax' // prevent CSRF
    ]);
    session_start();
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
