<?php
/**
 * Shared helper to fetch the latest SlapIA Tool GitHub release (.exe asset + version).
 * Cached on disk so the GitHub API is hit at most once every RELEASE_CACHE_TTL seconds,
 * regardless of how many pages/endpoints need this info.
 */

if (!defined('GITHUB_REPO')) {
    define('GITHUB_REPO', 'ThomasLap13/SlapIA-Tool');
    define('RELEASE_CACHE_TTL', 600); // 10 minutes
}

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

/**
 * Fetch the subject line (first line) of the latest commit on the repo's default branch.
 */
function fetchLatestCommitSubject(): ?string
{
    $cacheFile = sys_get_temp_dir() . '/slapia_gh_latest_commit.json';

    if (is_readable($cacheFile) && (time() - filemtime($cacheFile)) < RELEASE_CACHE_TTL) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached && isset($cached['subject'])) return $cached['subject'];
    }

    $ch = curl_init('https://api.github.com/repos/' . GITHUB_REPO . '/commits/main');
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
        if (is_readable($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && isset($cached['subject'])) return $cached['subject'];
        }
        return null;
    }

    $data = json_decode($response, true);
    $message = $data['commit']['message'] ?? null;
    if (!$message) {
        return null;
    }

    // Keep only the subject line (first line of the commit message).
    $subject = trim(strtok($message, "\n"));

    @file_put_contents($cacheFile, json_encode(['subject' => $subject]));
    return $subject;
}
