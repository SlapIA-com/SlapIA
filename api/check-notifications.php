<?php
/**
 * API Endpoint: Check Notifications
 * Aggregates platform updates and personal alerts from Notion.
 * Includes read/unread state from session.
 */
include_once '../includes/config.php';

ini_set('display_errors', 0);
ob_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

$notionApiKey = config('NOTION_API_KEY');
$userId = $_SESSION['user_id'] ?? '';

if (empty($userId)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'ID utilisateur manquant']);
    exit;
}

// Read state from session
$readIds   = $_SESSION['read_notif_ids'] ?? [];
$readAllTs = $_SESSION['notif_read_all_ts'] ?? 0;

$notifications = [];

// Helper: build notification with read state
$makeNotif = function(string $id, string $title, string $desc, int $ts, string $icon, string $iconBg, string $iconColor, string $link = '#', bool $pinned = false) use ($readIds, $readAllTs) {
    $isRead = in_array($id, $readIds, true) || $ts < $readAllTs;
    return [
        'id'         => $id,
        'title'      => $title,
        'desc'       => $desc,
        'ts'         => $ts,
        'icon'       => $icon,
        'icon_bg'    => $iconBg,
        'icon_color' => $iconColor,
        'link'       => $link,
        'pinned'     => $pinned,
        'read'       => $isRead,
    ];
};

// ── 1. Fetch User Data (cache 5 min) ─────────────────────────────────────────
$cacheKey  = 'notif_user_' . md5($userId);
$cacheTTL  = 300;
$userData  = null;
$cacheFile = sys_get_temp_dir() . '/' . $cacheKey . '.json';

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    $userData = json_decode(file_get_contents($cacheFile), true) ?: null;
}

if (!$userData) {
    $ch = curl_init('https://api.notion.com/v1/pages/' . $userId);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $notionApiKey,
            'Notion-Version: 2022-06-28'
        ]
    ]);
    $res      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode !== 200) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Erreur API Notion']);
        exit;
    }

    $userData = json_decode($res, true);

    @file_put_contents($cacheFile, json_encode($userData), LOCK_EX);
}

$userData    = $userData ?? [];
$props       = $userData['properties'] ?? [];
$lastEdited  = $userData['last_edited_time'] ?? date('c');
$createdTime = $userData['created_time'] ?? date('c');
$createdTs   = strtotime($createdTime);
$now         = time();
$accountAge  = $now - $createdTs;

// ── 2. Facture en attente (critique, épinglée) ────────────────────────────────
$billingStatus = $props['Facturation']['select']['name'] ?? '';
if ($billingStatus === 'En attente') {
    $notifications[] = $makeNotif(
        'user_pending_payment',
        'Facture en attente de règlement',
        'Action requise pour conserver votre accès complet.',
        $now + 9999,
        'fas fa-exclamation-circle', 'bg-danger', 'text-danger',
        '/dashboard?tab=billing',
        true
    );
}

// ── 3. Nouvelles factures (3 dernières) ──────────────────────────────────────
$invoices = array_reverse($props['Factures']['files'] ?? []);
$invCount = 0;
foreach ($invoices as $inv) {
    if ($invCount >= 3) break;
    $invName = $inv['name'] ?? 'Facture';
    $notifId = 'inv_' . md5($invName);
    $notifications[] = $makeNotif(
        $notifId,
        'Nouvelle facture disponible',
        htmlspecialchars($invName, ENT_QUOTES, 'UTF-8'),
        strtotime($lastEdited),
        'fas fa-file-invoice-dollar', 'bg-success', 'text-success',
        '/dashboard?tab=billing'
    );
    $invCount++;
}

// ── 4. Profil incomplet ───────────────────────────────────────────────────────
$fields  = ['Prenom NOM', 'Téléphone', 'Job', 'Location', 'Linkedin'];
$missing = 0;
foreach ($fields as $f) {
    $val = '';
    if (isset($props[$f])) {
        $type = $props[$f]['type'] ?? '';
        if ($type === 'title')        $val = $props[$f]['title'][0]['plain_text'] ?? '';
        elseif ($type === 'rich_text')  $val = $props[$f]['rich_text'][0]['plain_text'] ?? '';
        elseif ($type === 'phone_number') $val = $props[$f]['phone_number'] ?? '';
        elseif ($type === 'url')        $val = $props[$f]['url'] ?? '';
    }
    if (empty($val)) $missing++;
}
if ($missing >= 2) {
    $notifications[] = $makeNotif(
        'profile_incomplete',
        'Profil incomplet',
        $missing . ' champ' . ($missing > 1 ? 's' : '') . ' manquant' . ($missing > 1 ? 's' : '') . '. Complétez votre profil pour en profiter à 100%.',
        $createdTs + 3600,
        'fas fa-user-edit', 'bg-primary', 'text-primary',
        '/dashboard?tab=profile'
    );
}

// ── 5. Bienvenue (7 premiers jours) ──────────────────────────────────────────
if ($accountAge < 86400 * 7) {
    $notifications[] = $makeNotif(
        'welcome_new',
        'Bienvenue sur SlapIA !',
        'Votre compte est actif. Explorez vos formations et ressources disponibles.',
        $createdTs,
        'fas fa-star', 'bg-info', 'text-info',
        '/formation'
    );
}

// ── 6. Demande d'avis (après 30 jours, si aucun avis laissé) ─────────────────
if ($accountAge > 86400 * 30) {
    $hasReview = false;
    $avisProps = $props['Avis clients'] ?? $props['Avis'] ?? null;
    if ($avisProps) {
        $type = $avisProps['type'] ?? '';
        if ($type === 'rich_text' && !empty($avisProps['rich_text'])) $hasReview = true;
        elseif ($type === 'relation' && !empty($avisProps['relation']))  $hasReview = true;
        elseif ($type === 'select' && !empty($avisProps['select']))      $hasReview = true;
        elseif ($type === 'number' && $avisProps['number'] !== null)     $hasReview = true;
    }
    if (!$hasReview) {
        $notifications[] = $makeNotif(
            'review_request',
            'Partagez votre expérience',
            'Vous utilisez SlapIA depuis plus d\'un mois. Votre avis nous aide à progresser !',
            $createdTs + 86400 * 30,
            'fas fa-comment-dots', 'bg-warning', 'text-warning',
            '/dashboard?tab=profile'
        );
    }
}

// ── 7. Nouveaux articles de blog (7 derniers jours) ──────────────────────────
$blogDbId = config('NOTION_PAGE_ID');
if (!empty($blogDbId)) {
    $sevenDaysAgo = date('c', $now - 86400 * 7);
    $blogPayload  = [
        'page_size' => 5,
        'filter'    => [
            'property' => 'Publication Date',
            'date'     => ['after' => $sevenDaysAgo]
        ],
        'sorts' => [['property' => 'Publication Date', 'direction' => 'descending']]
    ];

    $chBlog = curl_init('https://api.notion.com/v1/databases/' . $blogDbId . '/query');
    curl_setopt_array($chBlog, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($blogPayload),
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $notionApiKey,
            'Content-Type: application/json',
            'Notion-Version: 2022-06-28'
        ]
    ]);
    $blogRes      = curl_exec($chBlog);
    $blogHttpCode = curl_getinfo($chBlog, CURLINFO_HTTP_CODE);

    if ($blogHttpCode === 200) {
        $blogData = json_decode($blogRes, true);
        foreach ($blogData['results'] ?? [] as $article) {
            $titleProp = null;
            foreach ($article['properties'] as $p) {
                if (($p['type'] ?? '') === 'title') { $titleProp = $p; break; }
            }
            $titre = '';
            foreach (($titleProp['title'] ?? []) as $part) {
                $titre .= $part['plain_text'] ?? '';
            }
            $titre = trim($titre);
            if (empty($titre)) continue;

            // Build slug from title
            $slugRaw = strtolower($titre);
            $slugRaw = preg_replace('/[^a-z0-9\s-]/u', '', $slugRaw);
            $slug    = trim(preg_replace('/[\s-]+/', '-', $slugRaw), '-');

            $pubDate = $article['properties']['Publication Date']['date']['start'] ?? ($article['created_time'] ?? date('c'));
            $pubTs   = strtotime($pubDate);
            $artId   = 'blog_' . md5($article['id']);

            $notifications[] = $makeNotif(
                $artId,
                'Nouvel article publié',
                htmlspecialchars($titre, ENT_QUOTES, 'UTF-8'),
                $pubTs,
                'fas fa-newspaper', 'bg-secondary', 'text-light',
                '/blog#' . $slug
            );
        }
    }
}

// ── Sort: épinglées d'abord, puis par timestamp desc ─────────────────────────
usort($notifications, function($a, $b) {
    if ($a['pinned'] !== $b['pinned']) return $a['pinned'] ? -1 : 1;
    return $b['ts'] <=> $a['ts'];
});

$unreadCount = count(array_filter($notifications, fn($n) => !$n['read']));

ob_clean();
echo json_encode([
    'success'       => true,
    'notifications' => array_slice($notifications, 0, 10),
    'unread_count'  => $unreadCount,
]);
exit;
