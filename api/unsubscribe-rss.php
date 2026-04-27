<?php
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

    // CSRF verification
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
        throw new Exception('Adresse email invalide.', 400);
    }

    if (empty($turnstileResponse)) {
        throw new Exception('Veuillez valider le Captcha Cloudflare.', 400);
    }

    // Turnstile verification
    $secretKey = config('TURNSTILE_SECRET_KEY');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $chCF = curl_init();
    curl_setopt($chCF, CURLOPT_URL, 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt($chCF, CURLOPT_POST, true);
    curl_setopt($chCF, CURLOPT_POSTFIELDS, http_build_query([
        'secret'   => $secretKey,
        'response' => $turnstileResponse,
        'remoteip' => $ip
    ]));
    curl_setopt($chCF, CURLOPT_RETURNTRANSFER, true);
    $responseCF  = curl_exec($chCF);
    $httpCodeCF  = curl_getinfo($chCF, CURLINFO_HTTP_CODE);
    curl_close($chCF);

    $cfData = json_decode($responseCF, true);
    if ($httpCodeCF !== 200 || empty($cfData['success'])) {
        throw new Exception('Validation Captcha échouée.', 400);
    }

    $notionApiKey    = config('NOTION_API_KEY');
    $newsletterDbId  = config('NOTION_Newsletter_DATABASE_ID');

    include_once __DIR__ . '/../includes/lang.php';

    if (empty($newsletterDbId) || empty($notionApiKey)) {
        error_log('[RSS Unsubscribe] Missing NOTION_API_KEY or NOTION_Newsletter_DATABASE_ID');
        throw new Exception(t('err_server'), 500);
    }

    // Search for the email in Notion
    $queryPayload = [
        'filter' => [
            'property' => 'Email',
            'title'    => ['equals' => $email]
        ]
    ];

    $chQuery = curl_init('https://api.notion.com/v1/databases/' . $newsletterDbId . '/query');
    curl_setopt_array($chQuery, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($queryPayload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $notionApiKey,
            'Content-Type: application/json',
            'Notion-Version: 2022-06-28'
        ]
    ]);
    $queryResponse = curl_exec($chQuery);
    $queryHttpCode = curl_getinfo($chQuery, CURLINFO_HTTP_CODE);
    curl_close($chQuery);

    if ($queryHttpCode !== 200) {
        error_log('[RSS Unsubscribe] Notion query failed: ' . $queryHttpCode . ' - ' . $queryResponse);
        throw new Exception('Erreur lors de la vérification. Veuillez réessayer.', 500);
    }

    $resultData = json_decode($queryResponse, true);

    if (empty($resultData['results'])) {
        throw new Exception('Aucune inscription trouvée pour cet email.', 404);
    }

    // Archive (= delete) the Notion page
    $pageId = $resultData['results'][0]['id'];

    $chPatch = curl_init('https://api.notion.com/v1/pages/' . $pageId);
    curl_setopt_array($chPatch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_POSTFIELDS     => json_encode(['archived' => true]),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $notionApiKey,
            'Content-Type: application/json',
            'Notion-Version: 2022-06-28'
        ],
        CURLOPT_TIMEOUT        => 15
    ]);
    $patchResponse = curl_exec($chPatch);
    $patchHttpCode = curl_getinfo($chPatch, CURLINFO_HTTP_CODE);
    curl_close($chPatch);

    if ($patchHttpCode !== 200) {
        error_log('[RSS Unsubscribe] Notion archive failed: ' . $patchHttpCode . ' - ' . $patchResponse);
        throw new Exception('Impossible de traiter votre désabonnement. Veuillez réessayer.', 500);
    }

    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Vous avez bien été désabonné de nos alertes.']);

} catch (Exception $e) {
    ob_clean();
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    ob_clean();
    error_log('[RSS Unsubscribe] Fatal Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur critique du serveur.']);
}
