<?php
/**
 * API pour envoyer les contacts vers Notion
 */



// Configuration Notion (Loaded from .env)
include_once __DIR__ . '/../includes/config.php';
$notionApiKey = config('NOTION_API_KEY');
$notionDbId = config('NOTION_CONTACT_DATABASE_ID');

// Suppress HTML errors to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);


// Start output buffering
ob_start();

try {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');

    // Check if cURL is enabled
    if (!function_exists('curl_init')) {
        throw new Exception('L\'extension PHP cURL n\'est pas activée.');
    }

    // Récupérer les données POST
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Données invalides']);
        exit;
    }

    $prenom = $input['prenom'] ?? '';
    $nom = $input['nom'] ?? '';
    $email = $input['email'] ?? '';
    $message = $input['message'] ?? '';
    $honeypot = $input['website_check'] ?? '';

    // Honeypot Spam Check
    if (!empty($honeypot)) {
        ob_clean();
        // Silent failure for bots (pretend success)
        echo json_encode(['success' => true, 'message' => 'Message envoyé !']);
        exit;
    }

    // Cloudflare Turnstile Validation
    $turnstileResponse = $input['cf-turnstile-response'] ?? '';
    $secretKey = config('TURNSTILE_SECRET_KEY');
    $ip = $_SERVER['REMOTE_ADDR'];

    // Force Turnstile Validation
    if (empty($turnstileResponse)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Veuillez compléter la sécurité Cloudflare.']);
        exit;
    }

    if (!empty($turnstileResponse)) { // Keep this block but now it's mandatory
        $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $data = [
            'secret' => $secretKey,
            'response' => $turnstileResponse,
            'remoteip' => $ip
        ];

        // Verify with Cloudflare using cURL (more robust than file_get_contents)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verifyUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseKeys = json_decode($response, true);

        if ($httpCode !== 200 || !isset($responseKeys['success']) || !$responseKeys['success']) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Validation de sécurité échouée (Captcha).']);
            exit;
        }
    }


    // Content Security Check
    $messageContent = strtolower($message);
    $forbiddenWords = ['caca', 'connard', 'pute', 'salope', 'batard', 'encule', 'merde', 'chiotte', 'bite', 'couille'];
    foreach ($forbiddenWords as $word) {
        if (strpos($messageContent, $word) !== false) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Veuillez rester poli et professionnel.']);
            exit;
        }
    }

    if (strlen($message) < 20) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Votre message est trop court (min. 20 caractères).']);
        exit;
    }


    // Validation
    if (empty($prenom) || empty($nom) || empty($email) || empty($message)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tous les champs sont requis']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email invalide']);
        exit;
    }

    // Créer l'entrée dans Notion
    $notionData = [
        'parent' => [
            'database_id' => $notionDbId
        ],
        'properties' => [
            'Prenom' => [
                'title' => [
                    [
                        'text' => [
                            'content' => $prenom
                        ]
                    ]
                ]
            ],
            'Nom' => [
                'rich_text' => [
                    [
                        'text' => [
                            'content' => $nom
                        ]
                    ]
                ]
            ],
            'Email' => [
                'email' => $email
            ],
            'Message' => [
                'rich_text' => [
                    [
                        'text' => [
                            'content' => $message
                        ]
                    ]
                ]
            ]
        ]
    ];

    // Envoyer la requête à Notion
    $ch = curl_init('https://api.notion.com/v1/pages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($notionData),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $notionApiKey,
            'Content-Type: application/json',
            'Notion-Version: 2022-06-28'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur de connexion: ' . $error]);
        exit;
    }

    $responseData = json_decode($response, true);

    ob_clean();
    if ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode([
            'success' => true,
            'message' => 'Contact enregistré avec succès dans Notion'
        ]);
    }
    else {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'error' => $responseData['message'] ?? 'Erreur Notion',
            'details' => $responseData
        ]);
    }

}
catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur interne: ' . $e->getMessage()]);
}
?>
