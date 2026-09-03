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

    $input    = json_decode(file_get_contents('php://input'), true) ?: [];
    $phone    = array_key_exists('phone', $input) ? $input['phone'] : null;
    $location = array_key_exists('location', $input) ? $input['location'] : null;

    if ($phone === null && $location === null) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_contact_fields')]);
        exit;
    }

    $me = currentUser();

    if ($phone !== null) {
        $phone = trim((string)$phone);
        if ($phone !== '' && !preg_match('/^[0-9+\-.() ]{1,30}$/', $phone)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => t('dashboard.err_phone_invalid')]);
            exit;
        }
        if (!updateOwnPhone($me['id'], $phone)) {
            ob_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => t('dashboard.err_phone_failed')]);
            exit;
        }
    }

    if ($location !== null) {
        $location = trim((string)$location);
        if (mb_strlen($location) > 500) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => t('dashboard.err_location_too_long')]);
            exit;
        }
        if (!updateOwnLocation($me['id'], $location)) {
            ob_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => t('dashboard.err_location_failed')]);
            exit;
        }
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Dashboard Update Contact] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
