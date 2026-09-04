<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-accounts.php';

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

    $input    = json_decode(file_get_contents('php://input'), true) ?: [];
    $clientId = filter_var($input['page_id'] ?? '', FILTER_VALIDATE_INT);
    $phone    = array_key_exists('phone', $input) ? $input['phone'] : null;
    $location = array_key_exists('location', $input) ? $input['location'] : null;
    $orders   = array_key_exists('orders', $input) ? $input['orders'] : null;

    if (!$clientId || $phone === null || $location === null || $orders === null) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('admin.err_fields')]);
        exit;
    }

    if (!updateAccountContactDetails($clientId, (string)$phone, (string)$location, (string)$orders)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('admin.err_update_failed')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Admin Update Contact] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
