<?php
/**
 * API pour déconnecter l'utilisateur
 */

include_once __DIR__ . '/../includes/config.php';

// Delete the remember-me token file and cookie before destroying the session.
$rememberToken = $_COOKIE['remember_token'] ?? '';
if ($rememberToken && preg_match('/^[a-f0-9]{64}$/', $rememberToken)) {
    $tokenFile = sys_get_temp_dir() . '/slapia_rt_' . $rememberToken . '.json';
    @unlink($tokenFile);
}
setcookie('remember_token', '', time() - 3600, '/', '', !empty($_SERVER['HTTPS']), true);

// Destroy PHP session.
if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    session_destroy();
}

header('Location: /login');
exit;
