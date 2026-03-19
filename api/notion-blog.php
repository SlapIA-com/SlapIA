<?php
/**
 * API pour récupérer les articles de blog depuis Notion
 */

// Configuration Notion
include_once __DIR__ . '/../includes/config.php';

// Use config() directly — do NOT use define() to avoid conflicts
// when included from different entry points.

/**
 * Helper pour extraire du texte simple
 */
function getNotionText($prop)
{
    if (!$prop || !is_array($prop))
        return '';
    $type = $prop['type'] ?? '';
    if ($type === 'title' || $type === 'rich_text') {
        $parts = $prop[$type] ?? [];
        $txt = '';
        foreach ($parts as $p) {
            $txt .= ($p['plain_text'] ?? '');
        }
        return trim($txt);
    }
    return '';
}

/**
 * Helper pour extraire la date
 */
function getNotionDate($prop)
{
    if (!$prop || !is_array($prop) || !isset($prop['type']) || $prop['type'] !== 'date')
        return '';
    return $prop['date']['start'] ?? '';
}

/**
 * Helper pour extraire l'URL d'une image
 */
function getNotionImage($prop)
{
    if (!$prop || !is_array($prop) || !isset($prop['type']) || $prop['type'] !== 'files')
        return null;
    $files = $prop['files'] ?? [];
    if (!empty($files) && isset($files[0]['file']['url'])) {
        return $files[0]['file']['url'];
    } elseif (!empty($files) && isset($files[0]['external']['url'])) {
        return $files[0]['external']['url'];
    }
    return null;
}

/**
 * Récupère les articles de blog depuis Notion (avec cache de 30 minutes)
 */
function getBlogArticles($limit = 50)
{
    $notionApiKey = config('NOTION_API_KEY');
    $blogDbId = config('NOTION_PAGE_ID');

    // Sécurité: si l'ID de la DB n'est pas défini, on retourne un tableau vide
    if (empty($blogDbId) || empty($notionApiKey)) {
        error_log('[SlapIA Blog] Missing NOTION_API_KEY or NOTION_PAGE_ID');
        return [];
    }

    $cacheFile = sys_get_temp_dir() . '/notion_blog_' . md5($blogDbId . '_' . $limit) . '.json';
    $cacheTime = 1800; // 30 minutes

    // Utiliser le cache si disponible et récent
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        $cachedData = json_decode(file_get_contents($cacheFile), true);
        if ($cachedData && is_array($cachedData))
            return $cachedData;
    }

    $allResults = [];
    $startCursor = null;
    $fetched = 0;

    do {
        $payload = [
            'page_size' => min(100, $limit - $fetched),
            'sorts' => [
                [
                    'property' => 'Publication Date',
                    'direction' => 'descending'
                ]
            ]
        ];

        if ($startCursor)
            $payload['start_cursor'] = $startCursor;

        $ch = curl_init('https://api.notion.com/v1/databases/' . $blogDbId . '/query');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $notionApiKey,
                'Content-Type: application/json',
                'Notion-Version: 2022-06-28',
                'User-Agent: FormationIA/1.0'
            ],
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError)
            break;

        $data = json_decode($response, true);
        if ($httpCode !== 200)
            break;

        $pageResults = $data['results'] ?? [];
        $allResults = array_merge($allResults, $pageResults);

        $has_more = $data['has_more'] ?? false;
        $startCursor = $data['next_cursor'] ?? null;
        $fetched = count($allResults);
    } while ($has_more && $fetched < $limit);

    $articles = [];

    foreach ($allResults as $page) {
        $props = $page['properties'] ?? [];

        $titre = getNotionText($props['Titre'] ?? null);

        // Ignorer les lignes sans titre (souvent des brouillons vides dans Notion)
        if (empty($titre))
            continue;

        $contenu = getNotionText($props['Contenu'] ?? null);
        $extrait = getNotionText($props['Extrait'] ?? null);

        // Utiliser Publication Date si dispo, sinon la date de création de la ligne Notion
        $pubDateTmp = getNotionDate($props['Publication Date'] ?? null);
        if (empty($pubDateTmp)) {
            $pubDateTmp = $page['created_time'] ?? date('Y-m-d\TH:i:s\Z');
        }
        $date = date('Y-m-d', strtotime($pubDateTmp));
        $dateRfc = date(DATE_RSS, strtotime($pubDateTmp));

        $image = getNotionImage($props['Image'] ?? null);

        // Fallback: si pas d'image, on essaie l'icon / cover de la page Notion
        if (empty($image) && !empty($page['cover'])) {
            $c = $page['cover'];
            if ($c['type'] === 'file')
                $image = $c['file']['url'] ?? null;
            elseif ($c['type'] === 'external')
                $image = $c['external']['url'] ?? null;
        }

        $id = $page['id'];

        // Générer un slug simple à partir du titre pour l'URL éventuelle
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $titre))));

        $articles[] = [
            'id' => $id,
            'titre' => $titre,
            'slug' => $slug,
            'extrait' => $extrait,
            'contenu' => $contenu, // Raw text — encoding is done in the template
            'contenu_raw' => $contenu, // Original utilisé pour RSS
            'date' => $date,
            'date_rss' => $dateRfc,
            'image' => $image,
            'url' => 'https://www.slapia.com/blog#' . $slug // L'ancre vers l'article dans la page blog
        ];
    }

    if (!empty($articles)) {
        file_put_contents($cacheFile, json_encode($articles), LOCK_EX);
    }

    return $articles;
}

// Si appelé directement, retourner le JSON pour débug ou API
if (basename($_SERVER['PHP_SELF']) === 'notion-blog.php') {
    header('Content-Type: application/json');
    echo json_encode(getBlogArticles());
}
