<?php
/**
 * Password Reset — Step 1
 * Generates a token, stores it server-side, and triggers an n8n webhook to send the email.
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
    $rlFile   = sys_get_temp_dir() . '/rl_reset_' . md5($email) . '.json';
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

    // Check if email exists in Notion (don't reveal if it doesn't — always return success)
    $dbId   = config('NOTION_SATISFACTION_DATABASE_ID');
    $result = notion()->queryDatabase($dbId, [
        'filter' => ['property' => 'Email', 'email' => ['equals' => $email]],
    ]);

    $userExists = false;
    $userId     = null;
    foreach ($result['results'] ?? [] as $page) {
        $hash = $page['properties']['Mot de passe']['rich_text'][0]['text']['content'] ?? '';
        if (!empty($hash)) {
            $userExists = true;
            $userId     = $page['id'];
            break;
        }
    }

    if ($userExists) {
        // Generate a secure token
        $token   = bin2hex(random_bytes(32));
        $expires = $now + 3600; // 1 hour

        $tokenFile = sys_get_temp_dir() . '/reset_token_' . md5($email) . '.json';
        file_put_contents($tokenFile, json_encode([
            'token'   => hash('sha256', $token), // store hash only
            'user_id' => $userId,
            'email'   => $email,
            'expires' => $expires,
        ]), LOCK_EX);

        $resetUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
            . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email);

        // Trigger n8n webhook if configured
        $webhookUrl = config('N8N_RESET_WEBHOOK_URL');
        if ($webhookUrl) {
            $payload = json_encode([
                'email'     => $email,
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
            curl_close($chWh);
        } else {
            // Dev fallback: log to error_log
            error_log('[SlapIA Reset] URL: ' . $resetUrl);
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
