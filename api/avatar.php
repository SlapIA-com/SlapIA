<?php
/**
 * Avatar Proxy — sert la photo de profil stockée localement pour un client,
 * ou un avatar par défaut si aucune photo n'est définie.
 *
 * Usage : /api/avatar.php?id=CLIENT_ID
 *
 * Remplace api/notion-avatar.php (qui proxifiait la photo/icône Notion).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$clientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($clientId) {
    try {
        $stmt = db()->prepare('SELECT photo_path, photo_mime FROM clients WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $clientId]);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        error_log('[SlapIA Avatar] lookup failed for client ' . $clientId . ': ' . $e->getMessage());
        $row = null;
    }

    if ($row && !empty($row['photo_path'])) {
        $path = storagePath('avatars') . '/' . basename($row['photo_path']);
        if (is_readable($path)) {
            header('Content-Type: ' . ($row['photo_mime'] ?: 'image/jpeg'));
            header('Cache-Control: public, max-age=3600');
            readfile($path);
            exit;
        }
    }
}

serveDefaultAvatar();
exit;

function serveDefaultAvatar(): void
{
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100">
  <rect width="100" height="100" rx="50" fill="#171320"/>
  <circle cx="50" cy="38" r="18" fill="rgba(255,255,255,0.15)"/>
  <ellipse cx="50" cy="80" rx="28" ry="20" fill="rgba(255,255,255,0.15)"/>
</svg>
SVG;
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=300');
    echo $svg;
}
