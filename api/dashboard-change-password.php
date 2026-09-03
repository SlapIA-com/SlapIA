<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-users.php';

requireLogin();

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

    $input          = json_decode(file_get_contents('php://input'), true) ?: [];
    $currentPassword = $input['current_password'] ?? '';
    $newPassword     = $input['new_password'] ?? '';

    if (strlen($newPassword) < 8) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_password_length')]);
        exit;
    }

    $me   = currentUser();
    $page = notion()->getPage($me['id']);

    if (!empty($page['error']) || ($page['http_code'] ?? 0) >= 300) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
        exit;
    }

    if (!verifyPassword($page, $currentPassword)) {
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_wrong_current_password')]);
        exit;
    }

    if (!updatePassword($me['id'], $newPassword)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_password_update_failed')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Dashboard Change Password] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
