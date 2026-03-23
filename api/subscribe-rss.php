<?php
header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$turnstileResponse = $input['cf-turnstile-response'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Adresse email invalide']);
    exit;
}

if (empty($turnstileResponse)) {
    http_response_code(400);
    echo json_encode(['error' => 'Veuillez valider le Captcha Cloudflare.']);
    exit;
}

$secretKey = config('TURNSTILE_SECRET_KEY');
$ip = $_SERVER['REMOTE_ADDR'];

$verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
$data = [
    'secret' => $secretKey,
    'response' => $turnstileResponse,
    'remoteip' => $ip
];

$chCF = curl_init();
curl_setopt($chCF, CURLOPT_URL, $verifyUrl);
curl_setopt($chCF, CURLOPT_POST, true);
curl_setopt($chCF, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($chCF, CURLOPT_RETURNTRANSFER, true);
$responseCF = curl_exec($chCF);
$httpCodeCF = curl_getinfo($chCF, CURLINFO_HTTP_CODE);
curl_close($chCF);

$responseKeys = json_decode($responseCF, true);
if ($httpCodeCF !== 200 || empty($responseKeys['success'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Validation Captcha échouée.']);
    exit;
}

$notionApiKey = config('NOTION_API_KEY');
$newsletterDbId = config('NOTION_Newsletter_DATABASE_ID');

if (empty($newsletterDbId) || empty($notionApiKey)) {
    http_response_code(500);
    error_log('[RSS Subscribe] Missing NOTION_API_KEY or NOTION_Newsletter_DATABASE_ID');
    echo json_encode(['error' => 'Erreur de configuration serveur']);
    exit;
}

$payload = [
    'parent' => [
        'database_id' => $newsletterDbId
    ],
    'properties' => [
        'Email' => [
            'title' => [
                [
                    'text' => [
                        'content' => $email
                    ]
                ]
            ]
        ]
    ]
];

$ch = curl_init('https://api.notion.com/v1/pages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $notionApiKey,
        'Content-Type: application/json',
        'Notion-Version: 2022-06-28'
    ],
    CURLOPT_TIMEOUT => 15
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError || $httpCode !== 200) {
    error_log("[RSS Subscribe] Notion API Error: $httpCode - Response: $response");
    http_response_code(500);
    echo json_encode(['error' => 'Impossible de sauvegarder votre inscription']);
    exit;
}

echo json_encode(['success' => true]);
