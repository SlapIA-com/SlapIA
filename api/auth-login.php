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
        // auto-upgrade legacy plain-text; best-effort, must not block login
        if (!upgradePasswordHash($userPage['id'], $password)) {
            error_log('[SlapIA Auth Login] Failed to upgrade legacy password hash for user ' . $userPage['id']);
        }
    }

    rateLimitReset('login_ip_' . $ip);
    rateLimitReset('login_email_' . strtolower($email));

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
    $redirect = $_SESSION['user_role'] === 'admin' ? '/admin' : '/dashboard';
    echo json_encode(['success' => true, 'redirect' => $redirect]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Auth Login] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
