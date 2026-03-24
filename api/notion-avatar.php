<?php
/**
 * Notion Avatar Proxy
 * Fetches the profile picture or page icon from a Notion page and caches it locally.
 * Handles: Photos (Files property), Page icons (external/file/emoji).
 *
 * Usage: /api/notion-avatar.php?id=NOTION_PAGE_ID[&size=40]
 */
include_once '../includes/config.php';

// ─── Validate page ID (UUID format) ─────────────────────────────────────────
$pageId = $_GET['id'] ?? '';
if (!preg_match('/^[0-9a-f]{8}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{12}$/i', $pageId)) {
    http_response_code(400);
    exit;
}

$pageIdClean = preg_replace('/[^a-f0-9]/i', '', $pageId);

// ─── Cache config ────────────────────────────────────────────────────────────
$cacheDir  = sys_get_temp_dir();
$cacheMeta = $cacheDir . '/notion_avatar_' . $pageIdClean . '.meta';
$cacheImg  = $cacheDir . '/notion_avatar_' . $pageIdClean . '.img';
$cacheTTL  = 3600; // 1 hour

// ─── Serve from cache if fresh ───────────────────────────────────────────────
if (file_exists($cacheMeta) && file_exists($cacheImg) && (time() - filemtime($cacheMeta)) < $cacheTTL) {
    $meta = json_decode(file_get_contents($cacheMeta), true);
    if ($meta && isset($meta['type'])) {
        header('Content-Type: ' . $meta['type']);
        header('Cache-Control: public, max-age=3600');
        header('X-Cache: HIT');
        if ($meta['type'] === 'image/svg+xml') {
            echo file_get_contents($cacheImg);
        } else {
            readfile($cacheImg);
        }
        exit;
    }
}

// ─── Fetch Notion page ───────────────────────────────────────────────────────
$notionApiKey = config('NOTION_API_KEY');
$ch = curl_init('https://api.notion.com/v1/pages/' . $pageId);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $notionApiKey,
        'Notion-Version: 2022-06-28',
    ]
]);
$res      = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
unset($ch);

if ($httpCode !== 200) {
    serveDefaultAvatar($cacheMeta, $cacheImg);
    exit;
}

$page = json_decode($res, true);

// ─── Resolve image URL (priority: Photo property > page icon) ────────────────
$imageUrl  = null;
$emojiChar = null;

// 1. Photo property (Files & Media)
$photoFiles = $page['properties']['Photo']['files'] ?? [];
if (!empty($photoFiles)) {
    $imageUrl = $photoFiles[0]['file']['url'] ?? $photoFiles[0]['external']['url'] ?? null;
}

// 2. Page icon
if (!$imageUrl && isset($page['icon'])) {
    $icon = $page['icon'];
    if ($icon['type'] === 'external') {
        $imageUrl = $icon['external']['url'] ?? null;
    } elseif ($icon['type'] === 'file') {
        $imageUrl = $icon['file']['url'] ?? null;
    } elseif ($icon['type'] === 'emoji') {
        $emojiChar = $icon['emoji'] ?? null;
    }
}

// ─── Handle emoji: generate inline SVG ───────────────────────────────────────
if ($emojiChar) {
    $svg = buildEmojiSvg($emojiChar);
    file_put_contents($cacheMeta, json_encode(['type' => 'image/svg+xml']));
    file_put_contents($cacheImg,  $svg);

    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=3600');
    header('X-Cache: MISS');
    echo $svg;
    exit;
}

// ─── No image found → default avatar ─────────────────────────────────────────
if (!$imageUrl) {
    serveDefaultAvatar($cacheMeta, $cacheImg);
    exit;
}

// ─── Fetch the actual image ───────────────────────────────────────────────────
$ch2 = curl_init($imageUrl);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 3,
]);
$imgData  = curl_exec($ch2);
$imgCode  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$imgType  = curl_getinfo($ch2, CURLINFO_CONTENT_TYPE);
unset($ch2);

if ($imgCode !== 200 || empty($imgData)) {
    serveDefaultAvatar($cacheMeta, $cacheImg);
    exit;
}

// ─── Sanitize content type ────────────────────────────────────────────────────
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
$imgType      = strtok((string)$imgType, ';');
if (!in_array($imgType, $allowedTypes, true)) {
    $imgType = 'image/jpeg';
}

// ─── Save to cache ────────────────────────────────────────────────────────────
file_put_contents($cacheMeta, json_encode(['type' => $imgType]), LOCK_EX);
file_put_contents($cacheImg,  $imgData, LOCK_EX);

header('Content-Type: ' . $imgType);
header('Cache-Control: public, max-age=3600');
header('X-Cache: MISS');
echo $imgData;
exit;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function buildEmojiSvg(string $emoji): string {
    $escaped = htmlspecialchars($emoji, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100">
  <rect width="100" height="100" rx="20" fill="rgba(26,26,26,1)"/>
  <text x="50" y="68" font-size="52" text-anchor="middle" dominant-baseline="auto" font-family="Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif">{$escaped}</text>
</svg>
SVG;
}

function serveDefaultAvatar(string $cacheMeta, string $cacheImg): void {
    $initials = '?';
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100">
  <rect width="100" height="100" rx="50" fill="#1a1a2e"/>
  <circle cx="50" cy="38" r="18" fill="rgba(255,255,255,0.15)"/>
  <ellipse cx="50" cy="80" rx="28" ry="20" fill="rgba(255,255,255,0.15)"/>
</svg>
SVG;
    file_put_contents($cacheMeta, json_encode(['type' => 'image/svg+xml']));
    file_put_contents($cacheImg,  $svg);

    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=300');
    echo $svg;
}
