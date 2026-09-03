<?php
/**
 * Récupère de vrais avis clients depuis la base Notion "Satisfaction"
 * (mêmes colonnes que sur le site historique : Prenom NOM, Job, Status,
 * Nom d'entreprise, Linkedin, Avis clients, Satisfaction, Photo).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notion.php';
require_once __DIR__ . '/stats.php'; // parseSatisfactionTo5Scale()

/**
 * @return array<int, array{prenom:string, nom:string, profession:string, avis:string,
 *                           note:?float, page_id:?string, status:string, entreprise:string, linkedin:string}>
 */
function getNotionReviews(int $limit = 12): array
{
    $apiKey = config('NOTION_API_KEY', '');
    $dbId   = config('NOTION_SATISFACTION_DATABASE_ID', '');
    if ($apiKey === '' || $dbId === '') return [];

    $cacheFile = sys_get_temp_dir() . '/slapia_reviews_' . md5($dbId . '_' . $limit) . '.json';
    $cacheTtl  = 3600; // 1 heure

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) return $cached;
    }

    $response = notion()->queryDatabaseAll($dbId);
    if (!empty($response['error'])) return [];

    $getText = function ($prop) {
        if (!$prop || !is_array($prop)) return '';
        $type = $prop['type'] ?? '';
        if ($type === 'title' || $type === 'rich_text') {
            return NotionAPI::richText($prop, $type);
        }
        if ($type === 'select') return NotionAPI::select($prop);
        if ($type === 'url') return $prop['url'] ?? '';
        return '';
    };

    $reviews = [];
    foreach ($response['results'] as $page) {
        $props = $page['properties'] ?? [];

        $prenom = '';
        $nom = '';
        if (isset($props['Prenom NOM'])) {
            $full = trim($getText($props['Prenom NOM']));
            if ($full !== '') {
                $parts = preg_split('/\s+/', $full, 2);
                $prenom = $parts[0] ?? '';
                $nom = $parts[1] ?? '';
            }
        }

        $avis = isset($props['Avis clients']) ? trim($getText($props['Avis clients'])) : '';
        if ($avis === '') continue; // pas d'avis écrit : on ne l'affiche pas

        $note = isset($props['Satisfaction']) ? parseSatisfactionTo5Scale($props['Satisfaction']) : null;

        $photo = null;
        foreach (['Photo', 'Image', 'Avatar', 'Profile Image'] as $field) {
            if (isset($props[$field]) && ($props[$field]['type'] ?? '') === 'files') {
                $files = $props[$field]['files'] ?? [];
                if (!empty($files)) {
                    $photo = $files[0]['file']['url'] ?? $files[0]['external']['url'] ?? null;
                    break;
                }
            }
        }

        $reviews[] = [
            'prenom'     => $prenom,
            'nom'        => $nom,
            'profession' => isset($props['Job']) ? $getText($props['Job']) : '',
            'avis'       => $avis,
            'note'       => $note,
            'page_id'    => $page['id'] ?? null,
            'status'     => isset($props['Status']) ? $getText($props['Status']) : '',
            'entreprise' => isset($props["Nom d'entreprise"]) ? $getText($props["Nom d'entreprise"]) : '',
            'linkedin'   => isset($props['Linkedin']) ? $getText($props['Linkedin']) : '',
            'photo'      => $photo,
        ];

        if (count($reviews) >= $limit) break;
    }

    @file_put_contents($cacheFile, json_encode($reviews), LOCK_EX);

    return $reviews;
}
