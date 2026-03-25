<?php
/**
 * Password Reset — Step 1
 * Generates a token, stores it in Notion, and triggers an n8n webhook to send the email.
 */

include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/lang.php';
include_once __DIR__ . '/../includes/notion.php';

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

try {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $email = strtolower(trim($input['email'] ?? ''));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Adresse email invalide.']);
        exit;
    }

    // Rate limit: max 3 requests per 15 min per email
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $rlKey    = 'reset_rl_' . md5($email);
    
    // Simple rate limit using temp file (since it's just for throttling)
    $rlFile   = sys_get_temp_dir() . '/' . $rlKey . '.json';
    $now      = time();
    $attempts = [];

    if (file_exists($rlFile)) {
        $attempts = json_decode(file_get_contents($rlFile), true) ?? [];
        $attempts = array_filter($attempts, fn($ts) => ($now - $ts) < 900);
    }

    if (count($attempts) >= 3) {
        ob_clean();
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Trop de demandes. Réessayez dans 15 minutes.']);
        exit;
    }

    $attempts[] = $now;
    file_put_contents($rlFile, json_encode(array_values($attempts)), LOCK_EX);

    // 1. Find user in Notion
    $dbId   = config('NOTION_SATISFACTION_DATABASE_ID');
    $result = notion()->queryDatabase($dbId, [
        'filter' => ['property' => 'Email', 'email' => ['equals' => $email]],
    ]);

    $userPageId = null;
    $userName   = 'Utilisateur'; // Default fallback

    foreach ($result['results'] ?? [] as $page) {
        $userPageId = $page['id'];
        $userName   = NotionAPI::richText($page['properties']['Prenom NOM'] ?? [], 'title') ?: 'Utilisateur';
        break;
    }

    // If user exists, process reset
    if ($userPageId) {
        // 2. Generate secure token
        $token   = bin2hex(random_bytes(32));
        $expiry  = date('c', $now + 3600); // 1 hour from now, ISO8601

        // 3. Update Notion with Token and Expiry
        $updateData = [
            'properties' => [
                'Reset Token' => [
                    'rich_text' => [['text' => ['content' => $token]]]
                ],
                'Reset Expiry' => [
                    'date' => ['start' => $expiry]
                ]
            ]
        ];

        $updateResult = notion()->updatePage($userPageId, $updateData);

        if (isset($updateResult['error']) || ($updateResult['http_code'] ?? 0) >= 400) {
            error_log('[SlapIA Reset] Notion update failed: ' . json_encode($updateResult));
        } else {
            // 4. Trigger n8n webhook
            $resetUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
                . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email);

            $webhookUrl = config('N8N_RESET_WEBHOOK_URL');
            if ($webhookUrl) {
                $payload = json_encode([
                    'email'     => $email,
                    'name'      => $userName,
                    'reset_url' => $resetUrl,
                ]);
                $chWh = curl_init($webhookUrl);
                curl_setopt_array($chWh, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $payload,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 5,
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                ]);
                curl_exec($chWh);
            } else {
                error_log('[SlapIA Reset] No N8N_RESET_WEBHOOK_URL configured. URL: ' . $resetUrl);
            }
        }
    }

    // Always return success to avoid email enumeration
    ob_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Reset Request] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
}
