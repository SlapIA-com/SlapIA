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
 * Helper pour extraire les blocs d'une page (le contenu complet)
 */
function getNotionBlocks($pageId, $depth = 0)
{
    if ($depth > 2) return ''; // Limit recursion to 2 levels (enough for nested lists)

    $notionApiKey = config('NOTION_API_KEY');
    if (empty($pageId) || empty($notionApiKey)) return '';

    $ch = curl_init('https://api.notion.com/v1/blocks/' . $pageId . '/children?page_size=100');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $notionApiKey,
            'Notion-Version: 2022-06-28',
            'User-Agent: FormationIA/1.0'
        ],
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    if ($httpCode !== 200) return '';

    $data = json_decode($response, true);
    $blocks = $data['results'] ?? [];
    
    $fullText = '';
    foreach ($blocks as $block) {
        $type = $block['type'] ?? '';
        if (empty($type)) continue;

        $blockId = $block['id'] ?? '';
        $hasChildren = $block['has_children'] ?? false;
        $childrenHtml = '';
        
        // Recursively get children if they exist
        if ($hasChildren && $depth < 2) {
            $childrenHtml = getNotionBlocks($blockId, $depth + 1);
        }

        // List of types that use 'rich_text' property directly
        $richTextTypes = ['paragraph', 'heading_1', 'heading_2', 'heading_3', 'bulleted_list_item', 'numbered_list_item', 'quote', 'callout', 'toggle', 'todo'];
        
        if (in_array($type, $richTextTypes) && isset($block[$type]['rich_text'])) {
            $parts = $block[$type]['rich_text'];
            $blockText = '';
            foreach ($parts as $p) {
                $blockHtml = htmlspecialchars($p['plain_text'] ?? ($p['text']['content'] ?? ''));
                $ann = $p['annotations'] ?? [];
                if ($ann['bold'] ?? false) $blockHtml = '<strong>' . $blockHtml . '</strong>';
                if ($ann['italic'] ?? false) $blockHtml = '<em>' . $blockHtml . '</em>';
                $blockText .= $blockHtml;
            }
            
            // Global Markdown-style replacements for ALL rich text types
            $blockText = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $blockText);
            $blockText = preg_replace('/(?<!\*)\*(?!\s|\*)(.*?)(?<!\s|\*)\*(?!\*)/', '<em>$1</em>', $blockText);
            $blockText = str_replace('**', '', $blockText);
            if (in_array($type, ['paragraph', 'bulleted_list_item', 'numbered_list_item', 'quote', 'callout'])) {
                $bulletHtml = '<br><span style="margin-left:1.5rem;display:inline-block;color:var(--accent-blue,#2997ff);margin-right:8px;">•</span>';
                $blockText = preg_replace('/\s+\*\s+/', $bulletHtml, $blockText);
                $blockText = preg_replace('/(?<=\:\s)\*\s+/', $bulletHtml, $blockText);
                $blockText = nl2br($blockText);
            }
            
            // Don't skip paragraphs if they are just placeholders for spacing
            if (empty($blockText) && !in_array($type, ['paragraph', 'toggle', 'todo'])) continue;

            if ($type === 'paragraph') {
                // Check for markdown shortcuts in paragraph text (###, ##, #, -, *, 1.)
                if (strpos($blockText, '### ') === 0) {
                    $fullText .= "<h3>" . substr($blockText, 4) . "</h3>";
                } elseif (strpos($blockText, '## ') === 0) {
                    $fullText .= "<h2>" . substr($blockText, 3) . "</h2>";
                } elseif (strpos($blockText, '# ') === 0) {
                    $fullText .= "<h1>" . substr($blockText, 2) . "</h1>";
                } elseif (strpos($blockText, '- ') === 0 || strpos($blockText, '* ') === 0) {
                    $fullText .= "<li>" . substr($blockText, 2) . "</li>";
                } elseif (preg_match('/^\d+\.\s/', $blockText)) {
                    $fullText .= "<li>" . preg_replace('/^\d+\.\s/', '', $blockText) . "</li>";
                } else {
                    $fullText .= "<p>" . (empty($blockText) && empty($childrenHtml) ? "&nbsp;" : $blockText) . "</p>";
                }
                $fullText .= $childrenHtml;
            } elseif ($type === 'heading_1') $fullText .= "<h1>" . $blockText . "</h1>" . $childrenHtml;
            elseif ($type === 'heading_2') $fullText .= "<h2>" . $blockText . "</h2>" . $childrenHtml;
            elseif ($type === 'heading_3') $fullText .= "<h3>" . $blockText . "</h3>" . $childrenHtml;
            elseif ($type === 'quote') $fullText .= "<blockquote>" . $blockText . $childrenHtml . "</blockquote>";
            elseif ($type === 'toggle') $fullText .= "<details><summary style='cursor:pointer;font-weight:600;margin-bottom:0.5rem;'>" . $blockText . "</summary>" . ($childrenHtml ?: "<p style='padding-left:1rem;opacity:0.8;'><em>(Vide)</em></p>") . "</details>";
            elseif ($type === 'todo') $fullText .= "<div><input type='checkbox' disabled " . ($block['todo']['checked'] ? 'checked' : '') . "> " . $blockText . "</div>" . $childrenHtml;
            elseif ($type === 'callout') $fullText .= "<div class='notion-callout'>" . $blockText . $childrenHtml . "</div>";
            elseif ($type === 'bulleted_list_item') $fullText .= "<li class='list-bullet'>" . $blockText . ($childrenHtml ? "<ul>$childrenHtml</ul>" : "") . "</li>";
            elseif ($type === 'numbered_list_item') $fullText .= "<li class='list-number'>" . $blockText . ($childrenHtml ? "<ol>$childrenHtml</ol>" : "") . "</li>";
            else $fullText .= "<p>" . $blockText . "</p>" . $childrenHtml;
        } elseif ($type === 'divider') {
            $fullText .= "<hr style='border:0;border-top:1px solid rgba(255,255,255,0.1);margin:2rem 0;'>";
        } elseif ($type === 'image') {
            $imgUrl = $block['image']['file']['url'] ?? $block['image']['external']['url'] ?? '';
            if ($imgUrl) {
                $fullText .= "<div style='margin:2rem 0;'><img src='" . htmlspecialchars($imgUrl) . "' style='width:100%;border-radius:12px;' loading='lazy'></div>";
            }
        }
    }
    
    // Wrap adjacent list items into <ul> or <ol>
    $fullText = preg_replace('/(<li class=\'list-bullet\'>.*?<\/li>)+/s', '<ul>$0</ul>', $fullText);
    $fullText = preg_replace('/(<li class=\'list-number\'>.*?<\/li>)+/s', '<ol>$0</ol>', $fullText);
    
    // Clean up temporary classes
    $fullText = str_replace([' class=\'list-bullet\'', ' class=\'list-number\''], '', $fullText);
    
    return trim($fullText);
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
        unset($ch);

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

        // Dynamic property detection
        $titre = getNotionText($props['Titre'] ?? $props['Title'] ?? $props['Name'] ?? null);
        
        // If still empty, find the first 'title' type property
        if (empty($titre)) {
            foreach ($props as $p) {
                if (($p['type'] ?? '') === 'title') {
                    $titre = getNotionText($p);
                    break;
                }
            }
        }

        // Ignorer les lignes sans titre (souvent des brouillons vides dans Notion)
        if (empty($titre))
            continue;

        $contenu = getNotionText($props['Contenu'] ?? null);
        $extrait = getNotionText($props['Extrait'] ?? $props['Summary'] ?? null);

        // If 'Contenu' is missing, try to find another rich_text property that isn't 'Extrait' or 'slug'
        if (empty($contenu)) {
            foreach ($props as $name => $p) {
                if ($name !== 'Extrait' && ($p['type'] ?? '') === 'rich_text') {
                    $contenu = getNotionText($p);
                    if (!empty($contenu)) break;
                }
            }
        }
        
        // Final fallback for contenu is extrait
        if (empty($contenu)) {
            $contenu = $extrait;
        }

        // Utiliser Publication Date si dispo, sinon la date de création de la ligne Notion
        $pubDateTmp = getNotionDate($props['Publication Date'] ?? $props['Date'] ?? null);
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



    return $articles;
}

// Si appelé directement, retourner le JSON pour débug ou API
if (basename($_SERVER['PHP_SELF']) === 'notion-blog.php') {
    header('Content-Type: application/json');
    
    // Si un ID est passé, on ne renvoie que les blocs de cet article
    if (isset($_GET['id'])) {
        echo json_encode(['content' => getNotionBlocks($_GET['id'])]);
    } else {
        echo json_encode(getBlogArticles());
    }
}
