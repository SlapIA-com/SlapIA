<?php
/**
 * API de Tracking SlapIA -> Notion
 * Reçoit les événements de visite et les envoie dans la base Notion "Logs Visiteurs"
 */

include_once __DIR__ . '/../includes/config.php';

// 1. Vérification du Consentement (Coté Serveur)
// On lit le cookie 'slapia_consent' défini par le bandeau JS
$consent = isset($_COOKIE['slapia_consent']) ? json_decode($_COOKIE['slapia_consent'], true) : null;

// Si pas de consentement explicite pour les "Analytics", on arrête tout.
// RGPD Strict : Pas de trace si pas d'accord.
if (!$consent || !isset($consent['analytics']) || $consent['analytics'] !== true) {
    http_response_code(200); // On renvoie 200 pour ne pas faire d'erreur JS, mais on ne fait rien.
    exit('No consent');
}

// 2. Configuration
define('NOTION_API_KEY', config('NOTION_API_KEY'));
// ID de la base de données "Logs Visiteurs"
define('NOTION_ANALYTICS_DB_ID', config('NOTION_ANALYTICS_DB_ID'));


// 3. Récupération des données
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid JSON');
}

// Données envoyées par le JS
$pageUrl = $data['url'] ?? '';
$visitorId = $data['visitor_id'] ?? 'unknown';
$sessionId = $data['session_id'] ?? 'unknown';
$referrer = $data['referrer'] ?? '';

// Données serveur
$ip = $_SERVER['REMOTE_ADDR'];
// Anonymisation IP (RGPD : on masque le dernier octet si on veut être puriste, 
// mais ici l'utilisateur veut "analyser beaucoup d'infos", donc on garde l'IP en clair ou hachée selon politique)
// $ip = preg_replace('/\.\d+$/', '.0', $ip); 

$userAgent = $_SERVER['HTTP_USER_AGENT'];

// 4. Envoi à Notion
$payload = [
    'parent' => ['database_id' => NOTION_ANALYTICS_DB_ID],
    'properties' => [
        'Page' => [
            'title' => [
                ['text' => ['content' => $pageUrl]]
            ]
        ],
        'VisitorID' => [
            'rich_text' => [
                ['text' => ['content' => $visitorId]]
            ]
        ],
        'SessionID' => [
            'rich_text' => [
                ['text' => ['content' => $sessionId]]
            ]
        ],
        'IP' => [
            'rich_text' => [
                ['text' => ['content' => $ip]]
            ]
        ],
        'UserAgent' => [
            'rich_text' => [
                ['text' => ['content' => $userAgent]]
            ]
        ],
        'Referrer' => [
            'rich_text' => [
                ['text' => ['content' => $referrer]]
            ]
        ],
        'Date' => [
            'date' => [
                'start' => date('c') // ISO 8601
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
        'Authorization: Bearer ' . NOTION_API_KEY,
        'Content-Type: application/json',
        'Notion-Version: 2022-06-28'
    ],
    CURLOPT_TIMEOUT => 5
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['status' => 'success']);
}
else {
    // Log error silently usually, but useful for debug
    http_response_code(500);
    echo json_encode(['status' => 'error', 'notion_response' => json_decode($response)]);
}
?>
