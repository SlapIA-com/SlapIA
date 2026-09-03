<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-users.php';
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

    $input       = json_decode(file_get_contents('php://input'), true) ?: [];
    $email       = strtolower(trim($input['email'] ?? ''));
    $newPassword = $input['new_password'] ?? '';

    if ($email === '' || strlen($newPassword) < 8) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('admin.err_reset_fields')]);
        exit;
    }

    $userPage = findUserByEmail($email);
    if (!$userPage) {
        ob_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => t('admin.err_account_not_found')]);
        exit;
    }

    if (!resetAccountPassword($userPage['id'], $newPassword)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('admin.err_update_failed')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Admin Reset Password] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
