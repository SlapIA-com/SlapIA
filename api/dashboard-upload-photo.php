<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-client.php';

requireLogin();

header('Content-Type: application/json');
ob_start();

try {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => t('auth.err_csrf')]);
        exit;
    }

    if (empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
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

    $me = currentUser();
    if (!uploadOwnPhoto($me['id'], $file['tmp_name'], $safeName, $mimeType)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => t('dashboard.err_photo_failed')]);
        exit;
    }

    // Invalidate api/notion-avatar.php's cache for this page so the new
    // photo shows immediately instead of after its 1-hour TTL.
    $pageIdClean = preg_replace('/[^a-f0-9]/i', '', $me['id']);
    $cacheDir    = sys_get_temp_dir();
    @unlink($cacheDir . '/slapia_avatar_' . $pageIdClean . '.meta');
    @unlink($cacheDir . '/slapia_avatar_' . $pageIdClean . '.img');

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Dashboard Upload Photo] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
