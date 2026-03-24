<?php
/**
 * API pour authentifier l'utilisateur via la base de données Notion ERP
 */

include_once __DIR__ . '/../includes/config.php';
$notionApiKey = config('NOTION_API_KEY');
$notionDbId = config('NOTION_CONTACT_DATABASE_ID'); // The main ERP database

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

    // We assume the first matching result is the user
    $userPage = $results[0];
    
    // Extract Hash Password from 'Mot de passe' property (Rich text)
    $passwordProperty = $userPage['properties']['Mot de passe']['rich_text'] ?? [];
    $storedHash = '';
    if (count($passwordProperty) > 0) {
        $storedHash = $passwordProperty[0]['text']['content'] ?? '';
    }

    if (empty($storedHash)) {
        // No password set yet for this user (they might have been created manually in ERP but never registered on site)
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => "Votre compte n'est pas encore activé. Veuillez suivre les instructions reçues par email ou nous contacter."]);
        exit;
    }

    // Verify password
    if (!password_verify($password, $storedHash)) {
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect.']);
        exit;
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
