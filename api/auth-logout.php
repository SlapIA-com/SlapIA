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
