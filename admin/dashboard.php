<?php
/**
 * Admin Dashboard - SlapIA
 * Displays analytics from Notion
 */

// Basic Auth (Hashed)
$valid_user = 'Thomas';

$valid_pass_hash = 'fa6560f20066e4efa8838f9bfb1d31e8d92c76890e07f7d70c42beecb1215b0f';

if (!isset($_SERVER['PHP_AUTH_USER']) ||
$_SERVER['PHP_AUTH_USER'] != $valid_user ||
hash('sha256', $_SERVER['PHP_AUTH_PW']) !== $valid_pass_hash) {
    header('WWW-Authenticate: Basic realm="SlapIA Admin"');
    header('HTTP/1.0 401 Unauthorized');
    die('Unauthorized');
}

include_once __DIR__ . '/../includes/config.php';
define('NOTION_API_KEY', config('NOTION_API_KEY'));
define('NOTION_ANALYTICS_DB_ID', config('NOTION_ANALYTICS_DB_ID'));

// Fetch Data from Notion
function getAnalytics($limit = 100)
{
    if (!defined('NOTION_ANALYTICS_DB_ID') || empty(NOTION_ANALYTICS_DB_ID)) {
        return ['error' => 'Database ID not set'];
    }

    $ch = curl_init('https://api.notion.com/v1/databases/' . NOTION_ANALYTICS_DB_ID . '/query');
    $payload = [
        'page_size' => $limit,
        'sorts' => [
            ['property' => 'Date', 'direction' => 'descending']
        ]
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . NOTION_API_KEY,
            'Content-Type: application/json',
            'Notion-Version: 2022-06-28'
        ]
    ]);

    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    return $data['results'] ?? [];
}

$logs = getAnalytics(100);

// Check if error
$notion_error = null;
if (isset($logs['error'])) {
    $notion_error = $logs['error'];
    $logs = [];

}

$stats = [
    'total_views' => count($logs),
    'unique_visitors' => 0,
    'top_pages' => []
];

$visitors = [];
foreach ($logs as $log) {
    $props = $log['properties'];

    // Visitor ID
    $vid = $props['VisitorID']['rich_text'][0]['plain_text'] ?? 'unknown';
    $visitors[$vid] = true;

    // Page
    $page = $props['Page']['title'][0]['plain_text'] ?? 'unknown';
    if (!isset($stats['top_pages'][$page]))
        $stats['top_pages'][$page] = 0;
    $stats['top_pages'][$page]++;
}
$stats['unique_visitors'] = count($visitors);
arsort($stats['top_pages']);

?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SlapIA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #0a0a0a; color: #fff; font-family: 'Inter', sans-serif; }
        .card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); }
        .table { color: #ccc; }
        .table-hover tbody tr:hover { color: #fff; background-color: rgba(255,255,255,0.1); }
    </style>
</head>
<body class="p-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-chart-line text-primary"></i> SlapIA Analytics</h1>
            <span class="badge bg-secondary">Last 100 Hits</span>
        </div>

        <?php if ($notion_error): ?>
            <div class="alert alert-warning">
                <strong>Attention:</strong> <?php echo htmlspecialchars($notion_error); ?> (Veuillez configurer NOTION_ANALYTICS_DB_ID).
            </div>
        <?php
endif; ?>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card p-3">
                    <h5 class="text-white-50">Vues Totales</h5>
                    <div class="display-4 fw-bold"><?php echo $stats['total_views']; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3">
                    <h5 class="text-white-50">Visiteurs Uniques</h5>
                    <div class="display-4 fw-bold text-success"><?php echo $stats['unique_visitors']; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3">
                    <h5 class="text-white-50">Page Top 1</h5>
                    <div class="fs-4 fw-bold text-info text-truncate">
                        <?php echo array_key_first($stats['top_pages']) ?? '-'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Logs -->
        <h3 class="mb-3">Derniers Logs</h3>
        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Page</th>
                            <th>Visitor ID</th>
                            <th>IP</th>
                            <th>Pays (Lang)</th>
                            <th>Referrer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log):
    $props = $log['properties'];
    $date = $props['Date']['date']['start'] ?? '-';
    $page = $props['Page']['title'][0]['plain_text'] ?? '-';
    $vid = $props['VisitorID']['rich_text'][0]['plain_text'] ?? '-';
    $ip = $props['IP']['rich_text'][0]['plain_text'] ?? '-';
    $ref = $props['Referrer']['rich_text'][0]['plain_text'] ?? '-';
?>
                        <tr>
                            <td><?php echo date('d/m H:i', strtotime($date)); ?></td>
                            <td class="text-info"><?php echo $page; ?></td>
                            <td><small class="text-white-50"><?php echo substr($vid, 0, 8); ?>...</small></td>
                            <td><?php echo $ip; ?></td>
                            <td>unk</td>
                            <td><small><?php echo substr($ref, 0, 30); ?></small></td>
                        </tr>
                        <?php
endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
