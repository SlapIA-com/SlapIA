<?php
/**
 * Real-time Visitor Counter
 * Uses a file-based session tracker (last active timestamp)
 */

header('Content-Type: application/json');

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');



// Suppress HTML errors to ensure JSON validity
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to capture any unwanted HTML warnings
ob_start();

try {
    header('Content-Type: application/json');

    // Prevent caching
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Start session to uniquely identify the browser/device
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    // File to store active sessions
    // Attempt to use system temp dir if local dir is not writable
    $file = __DIR__ . '/visitors_log.json';
    if (!is_writable(__DIR__) && !is_writable($file)) {
        $file = sys_get_temp_dir() . '/formationia_visitors_log.json';
    }

    $window = 30; // 30 seconds window for real-time accuracy

    // Get unique visitor ID from session
    $visitorId = session_id();
    if (empty($visitorId)) {
        $visitorId = md5($_SERVER['REMOTE_ADDR'] . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
    }
    $now = time();

    // Read existing data
    $data = [];
    if (file_exists($file)) {
        $json = @file_get_contents($file);
        if ($json) {
            $data = json_decode($json, true) ?? [];
        }
    }

    // Update current visitor
    $data[$visitorId] = $now;

    // Clean up old sessions
    foreach ($data as $id => $timestamp) {
        if ($now - $timestamp > $window) {
            unset($data[$id]);
        }
    }

    // Save back to file
    @file_put_contents($file, json_encode($data));

    // Clear any previous output (warnings, etc.)
    ob_clean();

    // Return count
    echo json_encode([
        'count' => count($data),
        'window_minutes' => $window / 60
    ]);

}
catch (Throwable $e) {
    ob_clean(); // Clear buffer before sending error JSON
    echo json_encode([
        'count' => 1,
        'window_minutes' => 0.5,
        'error' => 'Internal logic error'
    ]);
}
?>
