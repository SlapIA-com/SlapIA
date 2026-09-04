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

    $input         = json_decode(file_get_contents('php://input'), true) ?: [];
    $clientId      = filter_var($input['client_id'] ?? '', FILTER_VALIDATE_INT);
    $prestationId  = filter_var($input['prestation_id'] ?? '', FILTER_VALIDATE_INT);

    $fields = [
        'type_service'       => $input['type_service'] ?? '',
        'description'        => $input['description'] ?? '',
        'prix'               => $input['prix'] ?? '',
        'statut_facturation' => $input['statut_facturation'] ?? '',
        'date_debut'         => $input['date_debut'] ?? '',
        'date_fin'           => $input['date_fin'] ?? '',
    ];

    if ($prestationId) {
        $result = updatePrestation($prestationId, $fields);
    } elseif ($clientId) {
        $result = addPrestation($clientId, $fields);
    } else {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('admin.err_fields')]);
        exit;
    }

    if (!$result['success']) {
        ob_clean();
        $status = ($result['error'] ?? '') === 'server_error' ? 500 : 400;
        http_response_code($status);
        echo json_encode(['success' => false, 'error' => t('admin.err_fields')]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true, 'id' => $result['id'] ?? $prestationId]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Admin Prestation Save] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
