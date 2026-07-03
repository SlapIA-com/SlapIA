<?php
// Suppress warnings/errors from breaking output
error_reporting(0);
ini_set('display_errors', 0);

include_once __DIR__ . '/../includes/config.php';

const GITHUB_REPO = 'ThomasLap13/SlapIA-Tool';
const RELEASE_CACHE_TTL = 600; // 10 minutes

/**
 * Fetch the latest non-draft, non-prerelease GitHub release and locate its .exe asset.
 * Cached on disk to stay well under GitHub's unauthenticated rate limit.
 */
function fetchLatestExeRelease(): ?array
{
    $cacheFile = sys_get_temp_dir() . '/slapia_gh_latest_release.json';

    if (is_readable($cacheFile) && (time() - filemtime($cacheFile)) < RELEASE_CACHE_TTL) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached) return $cached;
    }

    $ch = curl_init('https://api.github.com/repos/' . GITHUB_REPO . '/releases/latest');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'User-Agent: SlapIA-Website',
            'Accept: application/vnd.github+json',
        ],
        CURLOPT_TIMEOUT => 8,
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $status !== 200) {
        // Serve a stale cache rather than fail outright if GitHub is unreachable.
        if (is_readable($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached) return $cached;
        }
        return null;
    }

    $data = json_decode($response, true);
    if (!$data || empty($data['assets'])) {
        return null;
    }

    $exeAsset = null;
    foreach ($data['assets'] as $asset) {
        if (preg_match('/\.exe$/i', $asset['name'] ?? '')) {
            $exeAsset = $asset;
            break;
        }
    }
    if (!$exeAsset) {
        return null;
    }

    $result = [
        'version'      => $data['tag_name'] ?? '',
        'published_at' => $data['published_at'] ?? '',
        'exe_name'     => $exeAsset['name'],
        'exe_size'     => (int)($exeAsset['size'] ?? 0),
        'exe_url'      => $exeAsset['browser_download_url'],
    ];

    @file_put_contents($cacheFile, json_encode($result));
    return $result;
}

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
