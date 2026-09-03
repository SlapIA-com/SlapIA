<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-admin.php';

requireAdmin();

header('Content-Type: application/json');
ob_start();

try {
    $accounts = listAllAccounts();
    $rss      = listRssSubscribers();

    // Growth chart: new accounts + new RSS subscribers per month, last 6 months.
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $months[date('M Y', strtotime("-$i months"))] = ['accounts' => 0, 'rss' => 0];
    }
    // Account creation date isn't directly exposed by listAllAccounts(); approximate
    // growth using created_time is out of scope here since listAllAccounts() only
    // returns display fields — use lastLogin-independent counts of 0 for accounts
    // this endpoint doesn't have creation dates for. Real per-month account growth
    // requires each page's created_time, added below via a second lightweight pass.
    $dbId = config('NOTION_SATISFACTION_DATABASE_ID');
    $rawAccounts = notion()->queryDatabaseAll($dbId);
    foreach ($rawAccounts['results'] ?? [] as $page) {
        $hash = NotionAPI::richText($page['properties']['Mot de passe'] ?? []);
        if ($hash === '') continue;
        $m = date('M Y', strtotime($page['created_time']));
        if (isset($months[$m])) $months[$m]['accounts']++;
    }
    $rawRss = notion()->queryDatabaseAll(config('NOTION_RSS_SUBSCRIBER_DATABASE_ID'));
    foreach ($rawRss['results'] ?? [] as $page) {
        $m = date('M Y', strtotime($page['created_time']));
        if (isset($months[$m])) $months[$m]['rss']++;
    }

    // Billing status breakdown.
    $billingCounts = array_fill_keys(ADMIN_BILLING_STATUSES, 0);
    foreach ($accounts as $a) {
        if (isset($billingCounts[$a['billing']])) $billingCounts[$a['billing']]++;
    }

    // Role breakdown.
    $roleCounts = ['particulier' => 0, 'entreprise' => 0, 'admin' => 0];
    foreach ($accounts as $a) {
        $roleCounts[$a['role']]++;
    }

    ob_clean();
    echo json_encode([
        'success'        => true,
        'accounts'       => $accounts,
        'rssSubscribers' => $rss,
        'chart'          => [
            'growth'  => [
                'labels'   => array_keys($months),
                'accounts' => array_column(array_values($months), 'accounts'),
                'rss'      => array_column(array_values($months), 'rss'),
            ],
            'billing' => [
                'labels' => array_keys($billingCounts),
                'counts' => array_values($billingCounts),
            ],
            'roles'   => [
                'labels' => array_keys($roleCounts),
                'counts' => array_values($roleCounts),
            ],
        ],
    ]);

} catch (Throwable $e) {
    ob_clean();
    error_log('[SlapIA Admin Data] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
}
