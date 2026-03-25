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

    // 1. Find user in Notion by email
    $dbId   = config('NOTION_SATISFACTION_DATABASE_ID');
    $result = notion()->queryDatabase($dbId, [
        'filter' => ['property' => 'Email', 'email' => ['equals' => $email]],
    ]);

    $userData = null;
    foreach ($result['results'] ?? [] as $page) {
        $props   = $page['properties'];
        $storedToken = $props['Reset Token']['rich_text'][0]['text']['content'] ?? '';
        $expiry      = $props['Reset Expiry']['date']['start'] ?? '';
        
        $userData = [
            'id'    => $page['id'],
            'token' => $storedToken,
            'expiry'=> $expiry ? strtotime($expiry) : 0
        ];
        break;
    }

    if (!$userData || empty($userData['token'])) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Lien invalide ou expiré.']);
        exit;
    }

    // 2. Check expiry
    if (time() > $userData['expiry']) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ce lien a expiré. Veuillez en demander un nouveau.']);
        exit;
    }

    // 3. Constant-time token comparison
    if (!hash_equals($userData['token'], $token)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Lien invalide.']);
        exit;
    }

    // 4. Update the password in Notion and CLEAR token fields
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $updateResult   = notion()->updatePage($userData['id'], [
        'properties' => [
            'Mot de passe' => [
                'rich_text' => [['text' => ['content' => $hashedPassword]]],
            ],
            'Reset Token' => [
                'rich_text' => [] // clear
            ],
            'Reset Expiry' => [
                'date' => null // clear
            ]
        ],
    ]);

    if (isset($updateResult['error']) || ($updateResult['http_code'] ?? 0) >= 400) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Impossible de mettre à jour le mot de passe. Réessayez.']);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Reset Exec] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
}
