<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-accounts.php';

requireAdmin();

header('Content-Type: application/json');
ob_start();

try {
    $accounts = listAllAccounts();
    $rss      = listRssSubscribers();
    $reviews  = listAdminReviews();

    // Growth chart: new accounts + new RSS subscribers per month, last 6 months.
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $months[date('M Y', strtotime("-$i months"))] = ['accounts' => 0, 'rss' => 0];
    }

    $pdo = db();

    $accountRows = $pdo->query(
        "SELECT a.created_at FROM comptes a
         WHERE a.mot_de_passe_hash IS NOT NULL AND a.mot_de_passe_hash != ''"
    )->fetchAll();
    foreach ($accountRows as $r) {
        $m = date('M Y', strtotime($r['created_at']));
        if (isset($months[$m])) $months[$m]['accounts']++;
    }

    $rssRows = $pdo->query('SELECT date_creation FROM rss_subscriber')->fetchAll();
    foreach ($rssRows as $r) {
        $m = date('M Y', strtotime($r['date_creation']));
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
        'reviews'        => $reviews,
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
