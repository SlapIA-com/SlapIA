<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/client-account.php';

requireLogin();

header('Content-Type: application/json');
ob_start();

try {
    $me = currentUser();
    $account = getOwnAccountDetails((int)$me['id']);

    if ($account === null) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true, 'account' => $account]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Dashboard Data] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
}
