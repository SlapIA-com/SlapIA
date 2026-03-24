<?php
/**
 * Password Reset — Step 2
 * Validates the token and sets a new password in Notion.
 */

include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/lang.php';
include_once __DIR__ . '/../includes/notion.php';

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

try {
    header('Content-Type: application/json');

    $input    = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $email    = strtolower(trim($input['email'] ?? ''));
    $token    = trim($input['token'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($token) || empty($password)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tous les champs sont requis.']);
        exit;
    }

    if (strlen($password) < 8) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Le mot de passe doit contenir au moins 8 caractères.']);
        exit;
    }

    // Load stored token data
    $tokenFile = sys_get_temp_dir() . '/reset_token_' . md5($email) . '.json';

    if (!file_exists($tokenFile)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Lien invalide ou expiré.']);
        exit;
    }

    $data = json_decode(file_get_contents($tokenFile), true);
    if (!$data) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Lien invalide.']);
        exit;
    }

    // Check expiry
    if (time() > ($data['expires'] ?? 0)) {
        @unlink($tokenFile);
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ce lien a expiré. Veuillez en demander un nouveau.']);
        exit;
    }

    // Constant-time token comparison (compare sha256 hashes)
    if (!hash_equals($data['token'] ?? '', hash('sha256', $token))) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Lien invalide.']);
        exit;
    }

    if (strtolower($data['email'] ?? '') !== $email) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Lien invalide.']);
        exit;
    }

    // Update the password in Notion
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $updateResult   = notion()->updatePage($data['user_id'], [
        'properties' => [
            'Mot de passe' => [
                'rich_text' => [['text' => ['content' => $hashedPassword]]],
            ],
        ],
    ]);

    if (isset($updateResult['error']) || ($updateResult['http_code'] ?? 0) >= 400) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Impossible de mettre à jour le mot de passe. Réessayez.']);
        exit;
    }

    // Invalidate the token
    @unlink($tokenFile);

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Reset Exec] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
}
