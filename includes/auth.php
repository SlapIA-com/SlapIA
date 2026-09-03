<?php
require_once __DIR__ . '/config.php';

/**
 * Session-based auth helpers: current user, route guards, rate limiting.
 * Role resolution from Notion data lives in notion-users.php (co-located
 * with the code that reads the Notion "Status" property).
 */

function currentUser(): ?array
{
    if (empty($_SESSION['logged_in'])) return null;
    return [
        'id'    => $_SESSION['user_id'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'name'  => $_SESSION['user_name'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'particulier',
    ];
}

function requireLogin(): void
{
    if (!currentUser()) {
        header('Location: /login');
        exit;
    }
}

function requireRole(string ...$roles): void
{
    $user = currentUser();
    if (!$user) {
        header('Location: /login');
        exit;
    }
    if (!in_array($user['role'], $roles, true)) {
        header('Location: /404');
        exit;
    }
}

function requireAdmin(): void
{
    requireRole('admin');
}

/**
 * File-based rate limiter — true if the action is allowed, false if blocked.
 * Max $maxAttempts within a $windowSeconds window, keyed by $key.
 */
function rateLimitCheck(string $key, int $maxAttempts, int $windowSeconds): bool
{
    $file = sys_get_temp_dir() . '/slapia_rl_' . md5($key) . '.json';
    $now  = time();

    $handle = fopen($file, 'c+');
    if ($handle === false) return true; // fail open rather than block legitimate users

    flock($handle, LOCK_EX);

    $contents = stream_get_contents($handle);
    $data = $contents !== false && $contents !== '' ? (json_decode($contents, true) ?? []) : [];
    $data = array_values(array_filter($data, fn($ts) => ($now - $ts) < $windowSeconds));

    if (count($data) >= $maxAttempts) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return false;
    }

    $data[] = $now;

    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, json_encode($data));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return true;
}

function rateLimitReset(string $key): void
{
    $file = sys_get_temp_dir() . '/slapia_rl_' . md5($key) . '.json';
    if (file_exists($file)) @unlink($file);
}

function logFailedLogin(string $email, string $reason, string $ip): void
{
    $logFile = sys_get_temp_dir() . '/slapia_failed_logins.log';
    $line    = date('Y-m-d H:i:s') . "\t" . $ip . "\t" . $email . "\t" . $reason . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    error_log('[SlapIA Auth] ' . $reason . ' — ' . $email . ' — IP: ' . $ip);
}
