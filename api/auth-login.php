<?php
/**
 * API pour authentifier l'utilisateur via la base de données Notion ERP
 */

include_once __DIR__ . '/../includes/config.php';
$notionApiKey = config('NOTION_API_KEY');
$notionDbId = config('NOTION_ERP_DATABASE_ID'); // ID de la base de données ERP complète

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

try {
    header('Content-Type: application/json');
    
    // Auth Check
    if (!$notionDbId) {
        throw new Exception("L'ID de la base de données des comptes n'est pas configuré.");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    // Fallback for form-data
    if (!$input) {
        $input = $_POST;
    }

    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Veuillez remplir tous les champs.']);
        exit;
    }

    // Query Notion for this email
    // NOTE: Since standard API rate limits apply, no rapid brute-force possible, but let's query carefully
    $queryData = [
        'filter' => [
            'property' => 'Email',
            'email' => [
                'equals' => $email
            ]
        ]
    ];

    $ch = curl_init('https://api.notion.com/v1/databases/' . $notionDbId . '/query');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($queryData),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $notionApiKey,
            'Content-Type: application/json',
            'Notion-Version: 2022-06-28'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    unset($ch);

    if ($error || $httpCode >= 400) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données.']);
        exit;
    }

    $responseData = json_decode($response, true);
    $results = $responseData['results'] ?? [];

    if (count($results) === 0) {
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect.']);
        exit;
    }

    // The CRM might contain multiple entries for the same email (e.g. multiple contact form submissions)
    // We must find the one that actually has a password defined!
    $validUserPage = null;
    $storedHash = '';

    foreach ($results as $page) {
        $passwordProperty = $page['properties']['Mot de passe']['rich_text'] ?? [];
        $hash = '';
        if (count($passwordProperty) > 0) {
            $hash = $passwordProperty[0]['text']['content'] ?? '';
        }
        
        if (!empty($hash)) {
            $validUserPage = $page;
            $storedHash = $hash;
            break; // Found the active account!
        }
    }

    if (!$validUserPage || empty($storedHash)) {
        // No password set yet for this user
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => "Votre compte n'est pas encore activé. Veuillez suivre les instructions reçues par email ou nous contacter."]);
        exit;
    }

    // Assign the valid page
    $userPage = $validUserPage;

    // Verify password gracefully
    $needsHashUpgrade = false;
    
    // Check if the stored password is a bcrypt hash (starts with $2y$)
    if (strpos($storedHash, '$2y$') === 0) {
        if (!password_verify($password, $storedHash)) {
            ob_clean();
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect.']);
            exit;
        }
    } else {
        // Plain text password assigned manually by Admin in Notion
        if ($storedHash !== $password) {
            ob_clean();
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect.']);
            exit;
        }
        $needsHashUpgrade = true; // Flag to upgrade to secure hash
    }

    // Auto-upgrade security: Hash the plain text password and update Notion
    if ($needsHashUpgrade) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $updateData = [
            'properties' => [
                'Mot de passe' => [
                    'rich_text' => [['text' => ['content' => $hashedPassword]]]
                ]
            ]
        ];
        $chUpd = curl_init('https://api.notion.com/v1/pages/' . $userPage['id']);
        curl_setopt_array($chUpd, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode($updateData),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $notionApiKey,
                'Content-Type: application/json',
                'Notion-Version: 2022-06-28'
            ]
        ]);
        curl_exec($chUpd);
        unset($chUpd);
    }

    // Success! Setup Session
    // Extract First Name / Last Name (Title property 'Prenom NOM')
    $nameProperty = $userPage['properties']['Prenom NOM']['title'] ?? [];
    $fullName = '';
    if (count($nameProperty) > 0) {
        $fullName = $nameProperty[0]['text']['content'] ?? '';
    }

    $_SESSION['user_id'] = $userPage['id'];
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $fullName;
    $_SESSION['logged_in'] = true;

    ob_clean();
    echo json_encode([
        'success' => true,
        'redirect' => '/dashboard'
    ]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Auth Login] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur interne.']);
}
