<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/client-account.php';

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

    $input    = json_decode(file_get_contents('php://input'), true) ?: [];
    $linkedin = trim($input['linkedin'] ?? '');

    if ($linkedin !== '' && !preg_match('#^https?://#i', $linkedin)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_linkedin_invalid')]);
        exit;
    }

    $me = currentUser();
    if (!updateOwnLinkedin((int)$me['id'], $linkedin)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_linkedin_failed')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Dashboard Update Linkedin] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
