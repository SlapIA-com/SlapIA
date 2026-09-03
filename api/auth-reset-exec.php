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

    $email    = strtolower(trim($input['email'] ?? ''));
    $token    = trim($input['token'] ?? '');
    $password = $input['password'] ?? '';

    if (strlen($password) < 8) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('auth.err_password_length')]);
        exit;
    }

    $userPage = validateResetToken($email, $token);
    if (!$userPage) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('auth.err_reset_invalid')]);
        exit;
    }

    if (!updatePassword($userPage['id'], $password)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true, 'redirect' => '/login']);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Reset Exec] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
