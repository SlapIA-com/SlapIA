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
        $token = setResetToken($userPage['id']);
        if ($token === null) {
            error_log('[SlapIA Reset] setResetToken failed for user ' . $userPage['id'] . ', reset email not sent.');
        } else {
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
