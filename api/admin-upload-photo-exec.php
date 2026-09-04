<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/client-account.php';

requireAdmin();

header('Content-Type: application/json');
ob_start();

try {
    // Multipart requests carry the CSRF token as a POST field, not a header.
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => t('auth.err_csrf')]);
        exit;
    }

    $clientId = filter_var($_POST['page_id'] ?? '', FILTER_VALIDATE_INT);
    if (!$clientId || empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_photo_missing')]);
        exit;
    }

    $file     = $_FILES['file'];
    $mimeType = mime_content_type($file['tmp_name']);

    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowedTypes[$mimeType])) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_photo_type')]);
        exit;
    }

    $maxBytes = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxBytes) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_photo_size')]);
        exit;
    }

    $safeName = 'photo-profil.' . $allowedTypes[$mimeType];

    if (!uploadOwnPhoto($clientId, $file['tmp_name'], $safeName, $mimeType)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_photo_failed')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Admin Upload Photo] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
