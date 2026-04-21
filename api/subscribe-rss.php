<?php
// Suppress warnings/errors from breaking JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/../includes/config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method Not Allowed', 405);
    }

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        $input = [];
    }

    // ── CSRF verification ────────────────────────────────────────────────────
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['error' => 'Requête invalide.']);
        exit;
    }

    $email = trim($input['email'] ?? '');
    $turnstileResponse = $input['cf-turnstile-response'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Adresse email invalide', 400);
    }

    if (empty($turnstileResponse)) {
        throw new Exception('Veuillez valider le Captcha Cloudflare.', 400);
    }

    $secretKey = config('TURNSTILE_SECRET_KEY');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

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
    
    // Cloudflare Turnstile Verification

    $responseKeys = json_decode($responseCF, true);
    if ($httpCodeCF !== 200 || empty($responseKeys['success'])) {
        throw new Exception('Validation Captcha échouée.', 400);
    }

    $notionApiKey = config('NOTION_API_KEY');
    $newsletterDbId = config('NOTION_Newsletter_DATABASE_ID');

    include_once __DIR__ . '/../includes/lang.php';

    if (empty($newsletterDbId) || empty($notionApiKey)) {
        error_log('[RSS Subscribe] Missing NOTION_API_KEY or NOTION_Newsletter_DATABASE_ID');
        throw new Exception(t('err_server'), 500);
    }

    // --- NEW: DUPLICATE CHECK ---
    $queryPayload = [
        'filter' => [
            'property' => 'Email',
            'title' => [
                'equals' => $email
            ]
        ]
    ];

    $chQuery = curl_init('https://api.notion.com/v1/databases/' . $newsletterDbId . '/query');
    curl_setopt_array($chQuery, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($queryPayload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $notionApiKey,
            'Content-Type: application/json',
            'Notion-Version: 2022-06-28'
        ]
    ]);
    $queryResponse = curl_exec($chQuery);
    $queryHttpCode = curl_getinfo($chQuery, CURLINFO_HTTP_CODE);

    if ($queryHttpCode === 200) {
        $resultData = json_decode($queryResponse, true);
        if (!empty($resultData['results'])) {
            throw new Exception(t('rss_error_already_subscribed'), 409);
        }
    }
    // --- END DUPLICATE CHECK ---

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
    unset($ch);

    if ($curlError || $httpCode !== 200) {
        error_log("[RSS Subscribe] Notion API Error: $httpCode - Response: $response");
        // Notion return error mesage
        $notionErr = json_decode($response, true);
        error_log('[RSS Subscribe] Notion API refused: ' . ($notionErr['message'] ?? 'Unknown') . ' HTTP ' . $httpCode);
        throw new Exception('Impossible de sauvegarder votre inscription. Veuillez réessayer.', 500);
    }

    ob_clean();
    echo json_encode(['success' => true, 'message' => t('rss_success')]);

} catch (Exception $e) {
    ob_clean();
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    ob_clean();
    error_log("[RSS Subscribe] Fatal Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur critique du serveur.']);
}

