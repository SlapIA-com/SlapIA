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

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $result = createClient([
        'nom_complet'    => $input['nom_complet'] ?? '',
        'email'          => $input['email'] ?? '',
        'nom_entreprise' => $input['nom_entreprise'] ?? '',
        'telephone'      => $input['telephone'] ?? '',
        'location'       => $input['location'] ?? '',
        'job_domaine'    => $input['job_domaine'] ?? '',
        'linkedin'       => $input['linkedin'] ?? '',
        'role'           => $input['role'] ?? 'particulier',
        'password'       => $input['password'] ?? '',
    ]);

    if (!$result['success']) {
        ob_clean();
        $status = ($result['error'] ?? '') === 'duplicate_email' ? 409 : 400;
        $errKey = [
            'duplicate_email'  => 'admin.err_duplicate_email',
            'invalid_fields'   => 'admin.err_fields',
            'invalid_password' => 'admin.err_reset_fields',
        ][$result['error']] ?? 'auth.err_server';
        if ($status === 400 && $result['error'] === 'server_error') $status = 500;
        http_response_code($status);
        echo json_encode(['success' => false, 'error' => t($errKey)]);
        exit;
    }

    ob_clean();
    echo json_encode([
        'success'   => true,
        'client_id' => $result['client_id'],
        'password'  => $result['password'],
    ]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Admin Create Client] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => t('auth.err_server')]);
}
