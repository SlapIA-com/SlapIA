<?php
// Suppress warnings/errors from breaking output
error_reporting(0);
ini_set('display_errors', 0);

include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/github-release.php';

// ?info=1 returns JSON metadata (used by the Solution page to display version/size).
if (isset($_GET['info'])) {
    $release = fetchLatestExeRelease();
    header('Content-Type: application/json; charset=utf-8');
    if ($release) {
        echo json_encode(['success' => true] + $release);
    } else {
        http_response_code(502);
        echo json_encode(['success' => false]);
    }
    exit;
}

// Default behaviour: stream the latest .exe through this server (no HTTP redirect). The
// Microsoft Store's package URL field, and winget/App Installer manifests, require a direct
// download link — GitHub's own release URLs always redirect (twice: once to the versioned
// tag URL, then again to a signed objects.githubusercontent.com blob), which those tools reject.
$release = fetchLatestExeRelease();
if (!$release || empty($release['exe_url'])) {
    http_response_code(502);
    echo 'Impossible de recuperer la derniere version de SlapIA Tool.';
    exit;
}

set_time_limit(0);

$ch = curl_init($release['exe_url']);
curl_setopt_array($ch, [
    CURLOPT_FOLLOWLOCATION => true, // follow GitHub's own redirect chain server-side
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_HTTPHEADER => ['User-Agent: SlapIA-Website'],
    CURLOPT_HEADERFUNCTION => function ($curl, $header) {
        if (preg_match('/^Content-Length:\s*(\d+)/i', $header, $m)) {
            header('Content-Length: ' . trim($m[1]));
        }
        return strlen($header);
    },
    CURLOPT_WRITEFUNCTION => function ($curl, $chunk) {
        echo $chunk;
        @ob_flush();
        @flush();
        return strlen($chunk);
    },
]);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($release['exe_name']) . '"');
header('Cache-Control: no-store');

curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status >= 400 && !headers_sent()) {
    http_response_code(502);
}
