<?php
/**
 * Admin Data API — returns dashboard admin data as JSON (lazy-loaded).
 * Only accessible to authenticated admin users.
 */

include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/lang.php';
include_once __DIR__ . '/../includes/notion.php';

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');

// Auth check
if (empty($_SESSION['logged_in'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

try {
    $userId = $_SESSION['user_id'] ?? '';
    if (!$userId) throw new Exception('user_id manquant');

    // Verify admin status from Notion
    $userPage = notion()->getPage($userId);
    $status   = $userPage['properties']['Status']['select']['name']
              ?? $userPage['properties']['Status']['rich_text'][0]['plain_text']
              ?? '';

    if ($status !== 'Admin') {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accès refusé.']);
        exit;
    }

    $notionApiKey = config('NOTION_API_KEY');
    $usersDb      = config('NOTION_SATISFACTION_DATABASE_ID');
    $leadsDb      = config('NOTION_CONTACT_DATABASE_ID');
    $newsDb       = config('NOTION_Newsletter_DATABASE_ID');

    // Helper for readable prop
    $getProp = function ($p) {
        if (!$p) return '';
        $type = $p['type'] ?? '';
        if (in_array($type, ['title', 'rich_text']) && !empty($p[$type])) {
            return $p[$type][0]['plain_text'] ?? '';
        }
        if ($type === 'email')  return $p['email']          ?? '';
        if ($type === 'select') return $p['select']['name'] ?? '';
        return '';
    };

    // ── Parallel queries using notion() helper (with 5-min cache) ────────────
    $listUsers      = notion()->queryDatabase($usersDb, ['page_size' => 100], 300)['results'] ?? [];
    $listNewsletter = notion()->queryDatabase($newsDb,  ['page_size' => 100], 300)['results'] ?? [];
    $pendingBilling = notion()->queryDatabase($usersDb, [
        'filter' => ['property' => 'Facturation', 'select' => ['equals' => 'En attente']]
    ], 120)['results'] ?? [];

    // Recent leads
    $recentLeads = notion()->queryDatabase($leadsDb, [
        'page_size' => 5,
        'sorts'     => [['timestamp' => 'created_time', 'direction' => 'descending']],
    ])['results'] ?? [];

    $recentNews = notion()->queryDatabase($newsDb, [
        'page_size' => 5,
        'sorts'     => [['timestamp' => 'created_time', 'direction' => 'descending']],
    ])['results'] ?? [];

    // Build recent activity
    $recent = [];
    foreach ($recentLeads as $l) {
        $recent[] = [
            'type'  => 'lead',
            'name'  => $l['properties']['Prenom NOM']['title'][0]['text']['content'] ?? 'Anonyme',
            'email' => $l['properties']['Email']['email'] ?? '',
            'date'  => date('d/m H:i', strtotime($l['created_time'])),
            'ts'    => strtotime($l['created_time']),
        ];
    }
    foreach ($recentNews as $n) {
        $recent[] = [
            'type'  => 'newsletter',
            'name'  => 'Subscriber',
            'email' => $n['properties']['Email']['title'][0]['text']['content'] ?? '',
            'date'  => date('d/m H:i', strtotime($n['created_time'])),
            'ts'    => strtotime($n['created_time']),
        ];
    }
    usort($recent, fn($a, $b) => $b['ts'] <=> $a['ts']);
    $recent = array_slice($recent, 0, 10);

    // Chart data (6 months)
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $months[date('M Y', strtotime("-$i months"))] = ['u' => 0, 'n' => 0];
    }
    foreach ($listUsers as $u) {
        $m = date('M Y', strtotime($u['created_time']));
        if (isset($months[$m])) $months[$m]['u']++;
    }
    foreach ($listNewsletter as $n) {
        $m = date('M Y', strtotime($n['created_time']));
        if (isset($months[$m])) $months[$m]['n']++;
    }

    // Serialize user list (only needed fields)
    $usersOut = array_map(function ($u) use ($getProp) {
        $props = $u['properties'] ?? [];
        return [
            'id'      => $u['id'],
            'name'    => $getProp($props['Prenom NOM'])  ?: 'N.A',
            'email'   => $getProp($props['Email']),
            'company' => $getProp($props['Nom d\'entreprise']),
            'status'  => $getProp($props['Status']) ?: 'Client',
        ];
    }, $listUsers);

    // Pending billing
    $pendingOut = array_map(function ($pb) use ($getProp) {
        $props = $pb['properties'] ?? [];
        $files = $props['Factures']['files'] ?? [];
        $docs  = array_map(fn($f) => [
            'name' => $f['name'] ?? 'Doc',
            'url'  => $f['file']['url'] ?? $f['external']['url'] ?? '#',
        ], array_slice($files, 0, 2));

        return [
            'id'      => $pb['id'],
            'name'    => $getProp($props['Prenom NOM'])  ?: 'N.A',
            'email'   => $getProp($props['Email']),
            'docs'    => $docs,
        ];
    }, $pendingBilling);

    // Newsletter
    $newsOut = array_map(function ($n) use ($getProp) {
        return [
            'email' => $getProp($n['properties']['Email']),
            'date'  => date('d/m/Y H:i', strtotime($n['created_time'])),
        ];
    }, $listNewsletter);

    ob_clean();
    echo json_encode([
        'success'   => true,
        'counts'    => [
            'users'      => count($listUsers),
            'newsletter' => count($listNewsletter),
            'pending'    => count($pendingBilling),
        ],
        'users'     => $usersOut,
        'newsletter'=> $newsOut,
        'pending'   => $pendingOut,
        'recent'    => $recent,
        'chart'     => [
            'labels' => array_keys($months),
            'users'  => array_column(array_values($months), 'u'),
            'news'   => array_column(array_values($months), 'n'),
        ],
    ]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Admin Data] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
}
