<?php
/**
 * API pour authentifier l'utilisateur via la base de données Notion ERP
 */

include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/lang.php';
$notionApiKey = config('NOTION_API_KEY');
$notionDbId   = config('NOTION_SATISFACTION_DATABASE_ID');

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// ─────────────────────────────────────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * File-based rate limiter — returns true if the action is allowed, false if blocked.
 * Max $maxAttempts within a $windowSeconds window, keyed by $key.
 */
function rateLimitCheck(string $key, int $maxAttempts = 5, int $windowSeconds = 900): bool
{
    $file = sys_get_temp_dir() . '/rl_' . md5($key) . '.json';
    $now  = time();
    $data = [];

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?? [];
    }

    // Remove expired entries
    $data = array_filter($data, fn($ts) => ($now - $ts) < $windowSeconds);

    if (count($data) >= $maxAttempts) {
        return false; // Blocked
    }

    $data[] = $now;
    file_put_contents($file, json_encode(array_values($data)), LOCK_EX);
    return true;
}

/**
 * Reset the rate limit counter for a key (called on successful login).
 */
function rateLimitReset(string $key): void
{
    $file = sys_get_temp_dir() . '/rl_' . md5($key) . '.json';
    if (file_exists($file)) @unlink($file);
}

/**
 * Append a line to the failed-login log file.
 */
function logFailedLogin(string $email, string $reason, string $ip): void
{
    $logFile = sys_get_temp_dir() . '/slapia_failed_logins.log';
    $line    = date('Y-m-d H:i:s') . "\t" . $ip . "\t" . $email . "\t" . $reason . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    error_log('[SlapIA Auth] Failed login — ' . $reason . ' — ' . $email . ' — IP: ' . $ip);
}

// ─────────────────────────────────────────────────────────────────────────────
//  Main
// ─────────────────────────────────────────────────────────────────────────────

try {
    header('Content-Type: application/json');

    if (!$notionDbId) {
        throw new Exception("L'ID de la base de données des comptes n'est pas configuré.");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // ── CSRF verification ────────────────────────────────────────────────────
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Requête invalide.']);
        exit;
    }

    $email             = trim($input['email'] ?? '');
    $password          = $input['password'] ?? '';
    $turnstileResponse = $input['cf-turnstile-response'] ?? '';
    $rememberMe        = !empty($input['remember_me']);

    if (empty($email) || empty($password)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => t('err_all_fields')]);
        exit;
    }

    // ── Rate limiting (per IP + per email) ───────────────────────────────────
    $ip         = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $rlKeyIp    = 'login_ip_' . $ip;
    $rlKeyEmail = 'login_email_' . strtolower($email);

    if (!rateLimitCheck($rlKeyIp, 10, 900) || !rateLimitCheck($rlKeyEmail, 5, 900)) {
        ob_clean();
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Trop de tentatives. Réessayez dans 15 minutes.']);
        exit;
    }

    // ── Cloudflare Turnstile ─────────────────────────────────────────────────
    if (empty($turnstileResponse)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Veuillez compléter la sécurité Cloudflare.']);
        exit;
    }

    $secretKey  = config('TURNSTILE_SECRET_KEY');
    $verifyData = [
        'secret'   => $secretKey,
        'response' => $turnstileResponse,
        'remoteip' => $ip,
    ];

    $chVerify = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($chVerify, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($verifyData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $responseVerify = curl_exec($chVerify);
    $httpCodeVerify = curl_getinfo($chVerify, CURLINFO_HTTP_CODE);
    curl_close($chVerify);

    $responseKeys = json_decode($responseVerify, true);
    if ($httpCodeVerify !== 200 || empty($responseKeys['success'])) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Validation de sécurité échouée.']);
        exit;
    }

    // ── Query Notion ─────────────────────────────────────────────────────────
    $queryData = [
        'filter' => [
            'property' => 'Email',
            'email'    => ['equals' => $email],
        ],
    ];

    $ch = curl_init('https://api.notion.com/v1/databases/' . $notionDbId . '/query');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($queryData),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $notionApiKey,
            'Content-Type: application/json',
            'Notion-Version: 2022-06-28',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);

    if ($curlErr || $httpCode >= 400) {
        $notionMsg = json_decode($response, true)['message'] ?? 'Erreur inconnue';
        error_log('[SlapIA Auth Login] Notion error ' . $httpCode . ': ' . $notionMsg);
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur de connexion. Veuillez réessayer.']);
        exit;
    }

    $responseData = json_decode($response, true);
    if (!isset($responseData['results'])) {
        error_log('[SlapIA Auth Login] Notion API error: ' . ($responseData['message'] ?? 'Unknown'));
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur de connexion. Veuillez réessayer.']);
        exit;
    }

    $results = $responseData['results'] ?? [];

    if (count($results) === 0) {
        logFailedLogin($email, 'email_not_found', $ip);
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => t('err_invalid_credentials')]);
        exit;
    }

    // Find the entry that has a password set (CRM may have duplicates)
    $validUserPage = null;
    $storedHash    = '';

    foreach ($results as $page) {
        $pwProp = $page['properties']['Mot de passe']['rich_text'] ?? [];
        $hash   = $pwProp[0]['text']['content'] ?? '';
        if (!empty($hash)) {
            $validUserPage = $page;
            $storedHash    = $hash;
            break;
        }
    }

    if (!$validUserPage || empty($storedHash)) {
        logFailedLogin($email, 'account_not_activated', $ip);
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => t('err_not_activated')]);
        exit;
    }

    $userPage = $validUserPage;

    // ── Verify password ───────────────────────────────────────────────────────
    $needsHashUpgrade = false;

    if (strpos($storedHash, '$2y$') === 0) {
        if (!password_verify($password, $storedHash)) {
            logFailedLogin($email, 'wrong_password', $ip);
            ob_clean();
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => t('err_invalid_credentials')]);
            exit;
        }
    } else {
        // Plain text — auto-upgrade to bcrypt on next login
        if ($storedHash !== $password) {
            logFailedLogin($email, 'wrong_password', $ip);
            ob_clean();
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => t('err_invalid_credentials')]);
            exit;
        }
        $needsHashUpgrade = true;
    }

    // ── Auto-upgrade plain text → bcrypt ─────────────────────────────────────
    if ($needsHashUpgrade) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $updateData     = [
            'properties' => [
                'Mot de passe' => [
                    'rich_text' => [['text' => ['content' => $hashedPassword]]],
                ],
            ],
        ];
        $chUpd = curl_init('https://api.notion.com/v1/pages/' . $userPage['id']);
        curl_setopt_array($chUpd, [
            CURLOPT_RETURNTRANSFER   => true,
            CURLOPT_CUSTOMREQUEST    => 'PATCH',
            CURLOPT_POSTFIELDS       => json_encode($updateData),
            CURLOPT_TIMEOUT          => 10,
            CURLOPT_HTTPHEADER       => [
                'Authorization: Bearer ' . $notionApiKey,
                'Content-Type: application/json',
                'Notion-Version: 2022-06-28',
            ],
        ]);
        curl_exec($chUpd);
        curl_close($chUpd);
    }

    // ── Success — reset rate limits, build session ────────────────────────────
    rateLimitReset($rlKeyIp);
    rateLimitReset($rlKeyEmail);

    $nameProperty = $userPage['properties']['Prenom NOM']['title'] ?? [];
    $fullName     = $nameProperty[0]['text']['content'] ?? '';

    $_SESSION['user_id']    = $userPage['id'];
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name']  = $fullName;

    // Avatar served dynamically via /api/notion-avatar.php?id={user_id}

    // Security: regenerate session ID to prevent fixation
    session_regenerate_id(true);

    $_SESSION['logged_in'] = true;

    // ── Remember Me — extend session lifetime via a long-lived cookie ─────────
    if ($rememberMe) {
        $cookieLifetime = 30 * 24 * 3600; // 30 days
        $token          = bin2hex(random_bytes(32));
        $_SESSION['remember_token']   = $token;
        $_SESSION['remember_expires'] = time() + $cookieLifetime;

        setcookie(
            'remember_token',
            $token,
            [
                'expires'  => time() + $cookieLifetime,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    ob_clean();
    echo json_encode(['success' => true, 'redirect' => '/dashboard']);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Auth Login] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur. Veuillez réessayer.']);
}
