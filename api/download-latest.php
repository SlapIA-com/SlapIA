<?php
// Suppress warnings/errors from breaking output
error_reporting(0);
ini_set('display_errors', 0);

include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/github-release.php';

// ?info=1 returns JSON metadata (used by the Solution page to display version/size).
// The actual download button uses the permanent /downloads/latest/SlapIA-Tool-Setup.exe
// path (rewritten in .htaccess straight to GitHub's own "latest" redirect), so this
// endpoint only needs to hit the GitHub API when metadata is actually requested.
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

// Default behaviour: send to the permanent download link.
header('Location: /downloads/latest/SlapIA-Tool-Setup.exe');
exit;
