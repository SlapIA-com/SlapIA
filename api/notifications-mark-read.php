<?php
/**
 * API Endpoint: Mark Notifications as Read
 * Stores read notification IDs in the session.
 */
include_once '../includes/config.php';

ob_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    ob_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Requête invalide.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$notifId  = $data['id']       ?? null;
$markAll  = $data['mark_all'] ?? false;

if (!isset($_SESSION['read_notif_ids'])) {
    $_SESSION['read_notif_ids'] = [];
}

if ($markAll) {
    $_SESSION['notif_read_all_ts'] = time();
    $_SESSION['read_notif_ids'] = [];
} elseif ($notifId) {
    if (!in_array($notifId, $_SESSION['read_notif_ids'], true)) {
        $_SESSION['read_notif_ids'][] = $notifId;
    }
}

ob_clean();
echo json_encode(['success' => true, 'read_ids' => $_SESSION['read_notif_ids']]);
exit;
