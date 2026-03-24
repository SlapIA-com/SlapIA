<?php
/**
 * API pour authentifier l'utilisateur via la base de données Notion ERP
 */

include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/lang.php';
$notionApiKey = config('NOTION_API_KEY');
$notionDbId = config('NOTION_SATISFACTION_DATABASE_ID'); // ID de la base de données ERP complète

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
    $turnstileResponse = $input['cf-turnstile-response'] ?? '';

    if (empty($email) || empty($password)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('err_all_fields')]);
        exit;
    }

    // Cloudflare Turnstile Validation
    if (empty($turnstileResponse)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Veuillez compléter la sécurité Cloudflare.']);
        exit;
    }

    $secretKey = config('TURNSTILE_SECRET_KEY');
    $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $verifyData = [
        'secret' => $secretKey,
        'response' => $turnstileResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $chVerify = curl_init();
    curl_setopt($chVerify, CURLOPT_URL, $verifyUrl);
    curl_setopt($chVerify, CURLOPT_POST, true);
    curl_setopt($chVerify, CURLOPT_POSTFIELDS, http_build_query($verifyData));
    curl_setopt($chVerify, CURLOPT_RETURNTRANSFER, true);
    $responseVerify = curl_exec($chVerify);
    $httpCodeVerify = curl_getinfo($chVerify, CURLINFO_HTTP_CODE);
    unset($chVerify);

    $responseKeys = json_decode($responseVerify, true);
    if ($httpCodeVerify !== 200 || !isset($responseKeys['success']) || !$responseKeys['success']) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Validation de sécurité échouée.']);
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
        $responseData = json_decode($response, true);
        $notionMsg = $responseData['message'] ?? $response ?? 'Erreur inconnue';
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Notion HTTP ' . $httpCode . ' : ' . $notionMsg]);
        exit;
    }

    $responseData = json_decode($response, true);
    if (!isset($responseData['results'])) {
        ob_clean();
        http_response_code(500);
        $notionError = $responseData['message'] ?? 'Erreur inconnue de l\'API Notion';
        echo json_encode(['success' => false, 'error' => 'Erreur API Notion : ' . $notionError]);
        exit;
    }

    $results = $responseData['results'] ?? [];

    if (count($results) === 0) {
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => t('err_invalid_credentials')]);
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
        echo json_encode(['success' => false, 'error' => t('err_not_activated')]);
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
            echo json_encode(['success' => false, 'error' => t('err_invalid_credentials')]);
            exit;
        }
    } else {
        // Plain text password assigned manually by Admin in Notion
        if ($storedHash !== $password) {
            ob_clean();
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => t('err_invalid_credentials')]);
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
    echo json_encode(['success' => false, 'error' => 'Exception PHP : ' . $e->getMessage() . ' à la ligne ' . $e->getLine()]);
}
