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
    $reviewId = filter_var($input['review_id'] ?? '', FILTER_VALIDATE_INT);
    $name     = $input['name'] ?? '';
    $comment  = $input['comment'] ?? '';
    $satisfaction = $input['satisfaction'] ?? null;
    $satisfaction = ($satisfaction === null || $satisfaction === '') ? null : filter_var($satisfaction, FILTER_VALIDATE_INT);

    if (!$reviewId) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('admin.err_fields')]);
        exit;
    }

    $result = adminUpdateReview($reviewId, (string)$name, (string)$comment, $satisfaction);

    if (!$result['success']) {
        ob_clean();
        http_response_code($result['error'] === 'server_error' ? 500 : 400);
        echo json_encode(['success' => false, 'error' => t('admin.err_fields')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Admin Review Update] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
