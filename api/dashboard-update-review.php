<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-client.php';

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

    $input        = json_decode(file_get_contents('php://input'), true) ?: [];
    $reviewText   = trim($input['review'] ?? '');
    $satisfaction = $input['satisfaction'] ?? '';

    if ($reviewText === '') {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_avis_empty')]);
        exit;
    }

    if (mb_strlen($reviewText) > 2000) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_avis_too_long')]);
        exit;
    }

    if (!in_array($satisfaction, OWN_REVIEW_SATISFACTION_VALUES, true)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_satisfaction_invalid')]);
        exit;
    }

    $me = currentUser();
    if (!updateOwnReview($me['id'], $reviewText, $satisfaction)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_avis_failed')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Dashboard Update Review] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
