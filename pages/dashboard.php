<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

// Auth Protection
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /login');
    exit;
}

$page_title = t('dash_title') . ' - SlapIA';
$page_description = t('login_meta_desc');
include '../includes/header.php';
include '../includes/components.php';

// ─── Fetch user data ─────────────────────────────────────────────────────────
$notionApiKey = config('NOTION_API_KEY');
$userId       = $_SESSION['user_id'];

$ch = curl_init('https://api.notion.com/v1/pages/' . $userId);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $notionApiKey,
        'Notion-Version: 2022-06-28'
    ]
]);
$response = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
unset($ch);

$userPage = json_decode($response, true);
$props    = $userPage['properties'] ?? [];
$icon     = $userPage['icon']       ?? null;

// Member since
$memberSince = isset($userPage['created_time'])
    ? date('M Y', strtotime($userPage['created_time']))
    : '';

// Photo / icon
$getUserIcon = function() use ($icon) {
    if (!$icon) return null;
    if ($icon['type'] === 'emoji')    return $icon['emoji'];
    if ($icon['type'] === 'external') return $icon['external']['url'];
    if ($icon['type'] === 'file')     return $icon['file']['url'];
    return null;
};

// Sync session
$_SESSION['user_name'] = $props['Prenom NOM']['title'][0]['text']['content'] ?? $_SESSION['user_name'];
$photoProperty = $props['Photo']['files'] ?? [];
$photoUrl = '';
if (!empty($photoProperty)) {
    $photoUrl = $photoProperty[0]['file']['url'] ?? $photoProperty[0]['external']['url'] ?? '';
}
if (empty($photoUrl) && $icon && in_array($icon['type'], ['external','file'])) {
    $photoUrl = $icon[$icon['type']]['url'] ?? '';
}
$_SESSION['user_photo'] = $photoUrl;

// ─── Property helpers ─────────────────────────────────────────────────────────
$getUserName         = fn() => $props['Prenom NOM']['title'][0]['text']['content']                  ?? '';
$getUserEmail        = fn() => $props['Email']['email']                                              ?? '';
$getUserPhone        = fn() => $props['Téléphone']['phone_number']                                   ?? '';
$getUserCompany      = fn() => $props['Nom d\'entreprise']['rich_text'][0]['text']['content']        ?? '';
$getUserJob          = fn() => $props['Job']['rich_text'][0]['text']['content']                      ?? '';
$getUserLocation     = fn() => $props['Location']['rich_text'][0]['text']['content']                 ?? '';
$getUserLinkedin     = fn() => $props['Linkedin']['url']                                             ?? '';
$getFacturationStatus = fn() => $props['Facturation']['select']['name']                             ?? 'Non disponible';
$getAvis             = fn() => $props['Avis clients']['rich_text'][0]['text']['content']             ?? '';
$getUserStatus       = fn() => $props['Status']['select']['name']
                               ?? $props['Status']['rich_text'][0]['text']['content']
                               ?? 'Client';

// ─── Profile completion ───────────────────────────────────────────────────────
$profileFields = [
    'Nom'         => !empty($getUserName()),
    'Téléphone'   => !empty($getUserPhone()),
    'Entreprise'  => !empty($getUserCompany()),
    'Poste'       => !empty($getUserJob()),
    'Localisation'=> !empty($getUserLocation()),
    'LinkedIn'    => !empty($getUserLinkedin()),
];
$completedCount = count(array_filter($profileFields));
$totalFields    = count($profileFields);
$completionPct  = (int) round(($completedCount / $totalFields) * 100);

// Admin helper
$getNotionProp = function($p) {
    if (!$p) return '';
    $type = $p['type'] ?? '';
    if (($type === 'title' || $type === 'rich_text') && !empty($p[$type])) {
        return $p[$type][0]['plain_text'] ?? '';
    }
    if ($type === 'email')  return $p['email']          ?? '';
    if ($type === 'select') return $p['select']['name'] ?? '';
    return '';
};

// Invoices
$invoices = $props['Factures']['files'] ?? [];

// Status
$isAdmin  = ($getUserStatus() === 'Admin');

// ─── Admin data ───────────────────────────────────────────────────────────────
$adminData = [
    'list_users'           => [],
    'list_newsletter'      => [],
    'list_pending_billing' => [],
    'recent'               => [],
    'chart'                => [],
];

if ($isAdmin) {
    $headers = [
        'Authorization: Bearer ' . $notionApiKey,
        'Notion-Version: 2022-06-28',
        'Content-Type: application/json'
    ];

    // Helper for admin cURL calls
    $notionQuery = function(string $dbId, array $payload = []) use ($headers): array {
        $ch = curl_init('https://api.notion.com/v1/databases/' . $dbId . '/query');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);
        return ($code === 200) ? (json_decode($res, true) ?? []) : [];
    };

    // Users list (up to 100)
    $usersDb  = config('NOTION_SATISFACTION_DATABASE_ID');
    $leadsDb  = config('NOTION_CONTACT_DATABASE_ID');
    $newsDb   = config('NOTION_Newsletter_DATABASE_ID');

    $adminData['list_users']      = $notionQuery($usersDb, ['page_size' => 100])['results'] ?? [];
    $adminData['list_newsletter'] = $notionQuery($newsDb,  ['page_size' => 100])['results'] ?? [];

    // Pending billing
    $adminData['list_pending_billing'] = $notionQuery($usersDb, [
        'filter' => ['property' => 'Facturation', 'select' => ['equals' => 'En attente']]
    ])['results'] ?? [];

    // Recent leads
    $recentLeads = $notionQuery($leadsDb, [
        'page_size' => 5,
        'sorts' => [['timestamp' => 'created_time', 'direction' => 'descending']]
    ])['results'] ?? [];

    foreach ($recentLeads as $l) {
        $name  = $l['properties']['Prenom NOM']['title'][0]['text']['content'] ?? 'Anonyme';
        $email = $l['properties']['Email']['email'] ?? '';
        $adminData['recent'][] = [
            'type'  => 'lead',
            'name'  => $name,
            'email' => $email,
            'date'  => date('d/m H:i', strtotime($l['created_time'])),
            'ts'    => strtotime($l['created_time']),
        ];
    }

    // Recent newsletter
    $recentNews = $notionQuery($newsDb, [
        'page_size' => 5,
        'sorts' => [['timestamp' => 'created_time', 'direction' => 'descending']]
    ])['results'] ?? [];

    foreach ($recentNews as $n) {
        $email = $n['properties']['Email']['title'][0]['text']['content'] ?? 'Newsletter';
        $adminData['recent'][] = [
            'type'  => 'newsletter',
            'name'  => 'Subscriber',
            'email' => $email,
            'date'  => date('d/m H:i', strtotime($n['created_time'])),
            'ts'    => strtotime($n['created_time']),
        ];
    }

    usort($adminData['recent'], fn($a, $b) => $b['ts'] <=> $a['ts']);
    $adminData['recent'] = array_slice($adminData['recent'], 0, 10);

    // Chart data (6 months)
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $months[date('M Y', strtotime("-$i months"))] = ['u' => 0, 'n' => 0];
    }
    foreach ($adminData['list_users'] as $u) {
        $m = date('M Y', strtotime($u['created_time']));
        if (isset($months[$m])) $months[$m]['u']++;
    }
    foreach ($adminData['list_newsletter'] as $n) {
        $m = date('M Y', strtotime($n['created_time']));
        if (isset($months[$m])) $months[$m]['n']++;
    }
    $adminData['chart'] = [
        'labels' => array_keys($months),
        'users'  => array_column(array_values($months), 'u'),
        'news'   => array_column(array_values($months), 'n'),
    ];
}

// ─── Status display config ────────────────────────────────────────────────────
$status      = $getUserStatus();
$statusConf  = match(true) {
    $isAdmin                              => ['fa-user-shield', 'Espace Admin',        'rgba(239,68,68,.15)',   '#ef4444'],
    stripos($status,'Entreprise') !== false => ['fa-building',   'Espace Entreprise',   'rgba(59,130,246,.15)', '#3b82f6'],
    stripos($status,'Particulier') !== false => ['fa-user-tie', 'Espace Particulier',  'rgba(168,85,247,.15)', '#a855f7'],
    default                               => ['fa-user',         'Espace Client',       'rgba(255,255,255,.1)', '#fff'],
};
[$statusIcon, $statusLabel, $statusColor, $statusText] = $statusConf;

// Billing badge
$factStatus = $getFacturationStatus();
$billingConf = match($factStatus) {
    'En attente' => ['bg-danger',  'text-danger',  '#ef4444', 'rgba(239,68,68,.1)'],
    'Réglé','Payé' => ['bg-success','text-success','#10b981', 'rgba(16,185,129,.1)'],
    default       => ['bg-secondary','text-secondary','#71717a','rgba(255,255,255,.05)'],
};
[$billBg, $billText, $billHex, $billBgHex] = $billingConf;

// CSRF token (generated once)
$csrfToken = generateCSRFToken();
?>

<style>
/* ── Base ──────────────────────────────────────────────────────────────── */
.dash-sidebar-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 12px 16px;
    border: none;
    border-radius: 14px;
    background: transparent;
    color: rgba(255,255,255,.6);
    font-size: .875rem;
    font-weight: 500;
    cursor: pointer;
    text-align: left;
    transition: background .2s, color .2s;
    margin-bottom: 4px;
    text-decoration: none;
}
.dash-sidebar-btn:hover {
    background: rgba(255,255,255,.05);
    color: #fff;
}
.dash-sidebar-btn.active {
    background: rgba(255,255,255,.08);
    color: #fff;
}
.dash-sidebar-btn .dash-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    flex-shrink: 0;
    transition: background .2s;
}
.dash-sidebar-btn.active .dash-icon  { background: rgba(255,255,255,.12); }
.dash-sidebar-divider {
    height: 1px;
    background: rgba(255,255,255,.05);
    margin: 8px 0 12px;
}
.dash-sidebar-section-label {
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: rgba(255,255,255,.25);
    padding: 0 16px;
    margin-bottom: 6px;
}

/* ── Completion bar ───────────────────────────────────────────────────── */
.completion-bar-wrap { height: 4px; border-radius: 99px; background: rgba(255,255,255,.06); overflow: hidden; }
.completion-bar-fill { height: 100%; border-radius: 99px; transition: width .8s cubic-bezier(.4,0,.2,1); }

/* ── Form inputs ─────────────────────────────────────────────────────── */
.dash-input {
    background: rgba(255,255,255,.05) !important;
    border: 1px solid rgba(255,255,255,.08) !important;
    color: #fff !important;
    border-radius: 12px !important;
    padding: 12px 16px !important;
    transition: border-color .2s, background .2s;
    font-size: .9rem;
}
.dash-input:focus {
    background: rgba(255,255,255,.08) !important;
    border-color: rgba(191,90,242,.5) !important;
    box-shadow: 0 0 0 3px rgba(191,90,242,.1) !important;
    outline: none;
}
.dash-input:disabled {
    background: rgba(255,255,255,.02) !important;
    border-color: rgba(255,255,255,.04) !important;
    color: rgba(255,255,255,.35) !important;
    cursor: not-allowed;
}
.dash-label {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: rgba(255,255,255,.4);
    margin-bottom: 8px;
    display: block;
}

/* ── Notification center ─────────────────────────────────────────────── */
.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px;
    border-radius: 16px;
    transition: background .2s;
    cursor: pointer;
    position: relative;
}
.notif-item:hover { background: rgba(255,255,255,.03); }
.notif-item.unread { background: rgba(191,90,242,.04); }
.notif-item.unread::before {
    content: '';
    position: absolute;
    left: 6px;
    top: 50%;
    transform: translateY(-50%);
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: var(--accent-purple);
}
.notif-icon-wrap {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: .85rem;
}
.notif-dismiss {
    background: none;
    border: none;
    color: rgba(255,255,255,.2);
    padding: 2px 6px;
    border-radius: 6px;
    cursor: pointer;
    opacity: 0;
    transition: opacity .2s, color .2s;
    flex-shrink: 0;
    margin-left: auto;
}
.notif-item:hover .notif-dismiss { opacity: 1; }
.notif-dismiss:hover { color: rgba(255,255,255,.8); }
.notif-filter-btn {
    padding: 6px 14px;
    border-radius: 99px;
    font-size: .75rem;
    font-weight: 600;
    border: 1px solid rgba(255,255,255,.08);
    background: transparent;
    color: rgba(255,255,255,.4);
    cursor: pointer;
    transition: all .2s;
}
.notif-filter-btn.active {
    background: rgba(255,255,255,.08);
    color: #fff;
    border-color: rgba(255,255,255,.15);
}
.notif-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    border-radius: 99px;
    background: #ef4444;
    color: #fff;
    font-size: .6rem;
    font-weight: 700;
    padding: 0 4px;
    margin-left: auto;
    flex-shrink: 0;
}

/* ── Invoice cards ───────────────────────────────────────────────────── */
.invoice-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    border-radius: 16px;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.06);
    transition: background .2s, border-color .2s;
    margin-bottom: 12px;
}
.invoice-card:hover { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); }

/* ── KPI cards ───────────────────────────────────────────────────────── */
.kpi-card {
    padding: 24px;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,.06);
    height: 100%;
    position: relative;
    overflow: hidden;
}
.kpi-card::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: inherit;
    z-index: -1;
}
.kpi-number { font-size: 2.5rem; font-weight: 800; line-height: 1; margin: 12px 0 4px; }

/* ── Admin table ─────────────────────────────────────────────────────── */
.admin-table { --bs-table-bg: transparent !important; --bs-table-color: #fff !important; }
.admin-table td, .admin-table th {
    background: transparent !important;
    color: inherit !important;
    border-bottom: 1px solid rgba(255,255,255,.05) !important;
}
.admin-table thead th {
    color: #71717a !important;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: .05em;
}

/* ── Quick stats bar ─────────────────────────────────────────────────── */
.quick-stat {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .8rem;
    color: rgba(255,255,255,.45);
}
.quick-stat strong { color: rgba(255,255,255,.8); font-weight: 600; }

/* ── Pulse ───────────────────────────────────────────────────────────── */
.pulse-green {
    width: 8px; height: 8px;
    background: #10b981;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(16,185,129,.7);
    animation: pulse-green 2s infinite;
}
@keyframes pulse-green {
    0%   { transform: scale(.95); box-shadow: 0 0 0 0 rgba(16,185,129,.7); }
    70%  { transform: scale(1);   box-shadow: 0 0 0 10px rgba(16,185,129,0); }
    100% { transform: scale(.95); box-shadow: 0 0 0 0 rgba(16,185,129,0); }
}

/* ── Stars ───────────────────────────────────────────────────────────── */
.star-item { cursor: pointer; transition: transform .15s; }
.star-item:hover { transform: scale(1.2); }

/* ── Tab content ─────────────────────────────────────────────────────── */
.tab-pane-dash { animation: dashFadeIn .35s ease-out; }
@keyframes dashFadeIn {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Mobile ──────────────────────────────────────────────────────────── */
@media (max-width: 767px) {
    .dashboard-section { padding-top: 1.5rem !important; }
    .dash-header-avatar { width: 80px !important; height: 80px !important; }
    .dash-header-title  { font-size: 1.6rem !important; }
    .col-lg-3 .bento-card { margin-bottom: 16px; }
    .dash-sidebar-wrap {
        display: flex;
        overflow-x: auto;
        gap: 8px;
        padding-bottom: 6px;
        scrollbar-width: none;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
    }
    .dash-sidebar-wrap::-webkit-scrollbar { display: none; }
    .dash-sidebar-btn { white-space: nowrap; flex-shrink: 0; margin-bottom: 0; }
    .dash-sidebar-divider, .dash-sidebar-section-label { display: none; }
    .kpi-number { font-size: 1.8rem; }
    .quick-stat { font-size: .72rem; }
}
</style>

<div class="grid-bg"></div>

<section class="dashboard-section py-5 mt-3" style="min-height: 80vh;">
<div class="container">

<!-- ══ HEADER ═══════════════════════════════════════════════════════════════ -->
<div class="row mb-4 align-items-center fade-in-up">
    <div class="col-md-auto mb-4 mb-md-0">
        <?php $userIcon = $getUserIcon(); ?>
        <div class="dash-header-avatar position-relative rounded-4 overflow-hidden border border-white border-opacity-10 shadow-lg"
             style="width:120px;height:120px;background:rgba(255,255,255,.03);">
            <?php if ($userIcon && strpos($userIcon,'http') === 0): ?>
                <img src="<?php echo htmlspecialchars($userIcon); ?>" alt="Photo de profil" class="w-100 h-100" style="object-fit:cover;">
            <?php elseif ($userIcon): ?>
                <div class="w-100 h-100 d-flex align-items-center justify-content-center" style="font-size:3.5rem;"><?php echo $userIcon; ?></div>
            <?php else: ?>
                <div class="w-100 h-100 d-flex align-items-center justify-content-center text-white" style="background:rgba(191,90,242,.15);font-size:2.5rem;">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
            <?php if ($completionPct === 100): ?>
            <div class="position-absolute bottom-0 end-0 m-1">
                <div style="width:22px;height:22px;border-radius:50%;background:#10b981;display:flex;align-items:center;justify-content:center;font-size:.6rem;border:2px solid #000;">
                    <i class="fas fa-check text-white"></i>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md">
        <div class="d-flex align-items-center gap-3 flex-wrap mb-1">
            <h1 id="dash-title" class="dash-header-title fw-bold text-white mb-0" style="font-size:2rem;">
                <?php
                $hour = (int)date('H');
                $greeting = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');
                echo $greeting . ', ' . htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]);
                ?>
            </h1>
            <div class="badge rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2"
                 style="background:<?php echo $statusColor; ?>;border:1px solid rgba(255,255,255,.1);color:<?php echo $statusText; ?>;font-size:.72rem;font-weight:700;letter-spacing:.4px;">
                <i class="fas <?php echo $statusIcon; ?>"></i> <?php echo $statusLabel; ?>
            </div>
        </div>

        <!-- Quick stats -->
        <div class="d-flex flex-wrap gap-4 mt-3">
            <?php if ($memberSince): ?>
            <div class="quick-stat">
                <i class="fas fa-calendar-alt"></i>
                Membre depuis <strong><?php echo $memberSince; ?></strong>
            </div>
            <?php endif; ?>
            <div class="quick-stat">
                <i class="fas fa-id-card"></i>
                Profil <strong><?php echo $completionPct; ?>% complet</strong>
            </div>
            <?php if ($factStatus !== 'Non disponible'): ?>
            <div class="quick-stat">
                <i class="fas fa-receipt"></i>
                Facturation : <strong style="color:<?php echo $billHex; ?>;"><?php echo htmlspecialchars($factStatus); ?></strong>
            </div>
            <?php endif; ?>
        </div>

        <!-- Completion bar -->
        <?php if ($completionPct < 100): ?>
        <div class="mt-3" style="max-width:320px;">
            <div class="completion-bar-wrap">
                <div class="completion-bar-fill"
                     style="width:<?php echo $completionPct; ?>%;background:linear-gradient(90deg,var(--accent-purple),var(--accent-blue));">
                </div>
            </div>
            <p class="text-secondary smaller mt-1 opacity-60">
                <i class="fas fa-info-circle me-1"></i>
                <?php echo $totalFields - $completedCount; ?> champ<?php echo ($totalFields - $completedCount > 1) ? 's' : ''; ?> à remplir
            </p>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-auto mt-3 mt-md-0">
        <a href="/api/auth-logout.php" data-no-swup
           class="btn btn-sm px-4 py-2 rounded-pill d-inline-flex align-items-center gap-2"
           style="background:rgba(239,68,68,.05);border:1px solid rgba(239,68,68,.2);color:#ef4444;text-decoration:none;transition:all .3s;">
            <i class="fas fa-sign-out-alt"></i> <?php echo t('dash_logout'); ?>
        </a>
    </div>
</div>

<!-- ══ LAYOUT ════════════════════════════════════════════════════════════════ -->
<div class="row g-4">

    <!-- ── Sidebar ───────────────────────────────────────────────────────── -->
    <div class="col-lg-3 fade-in-up delay-100">
        <div class="bento-card p-3" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;position:sticky;top:100px;">
            <div class="dash-sidebar-wrap flex-column">

                <div class="dash-sidebar-section-label">Mon espace</div>

                <button class="dash-sidebar-btn active" id="tab-profile" onclick="switchTab('profile')">
                    <div class="dash-icon" style="background:rgba(41,151,255,.15);color:#2997ff;"><i class="fas fa-user-circle"></i></div>
                    <span><?php echo t('dash_tab_profile'); ?></span>
                </button>

                <button class="dash-sidebar-btn" id="tab-billing" onclick="switchTab('billing')">
                    <div class="dash-icon" style="background:rgba(16,185,129,.12);color:#10b981;"><i class="fas fa-receipt"></i></div>
                    <span><?php echo t('dash_tab_billing'); ?></span>
                    <?php if ($factStatus === 'En attente'): ?>
                    <span class="notif-badge">!</span>
                    <?php endif; ?>
                </button>

                <button class="dash-sidebar-btn" id="tab-notifications" onclick="switchTab('notifications')">
                    <div class="dash-icon" style="background:rgba(191,90,242,.12);color:#bf5af2;"><i class="fas fa-bell"></i></div>
                    <span>Notifications</span>
                    <span class="notif-badge d-none" id="sidebarNotifBadge"></span>
                </button>

                <?php if (!$isAdmin): ?>
                <button class="dash-sidebar-btn" id="tab-reviews" onclick="switchTab('reviews')">
                    <div class="dash-icon" style="background:rgba(251,191,36,.12);color:#fbbf24;"><i class="fas fa-star"></i></div>
                    <span><?php echo t('dash_tab_reviews'); ?></span>
                </button>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                <div class="dash-sidebar-divider"></div>
                <div class="dash-sidebar-section-label">Administration</div>

                <button class="dash-sidebar-btn" id="tab-admin" onclick="switchTab('admin')">
                    <div class="dash-icon" style="background:rgba(239,68,68,.12);color:#ef4444;"><i class="fas fa-shield-alt"></i></div>
                    <span>Vue d'ensemble</span>
                </button>

                <button class="dash-sidebar-btn" id="tab-admin-emails" onclick="switchTab('admin-emails')">
                    <div class="dash-icon" style="background:rgba(251,191,36,.12);color:#fbbf24;"><i class="fas fa-users-cog"></i></div>
                    <span>Utilisateurs</span>
                    <span class="ms-auto text-secondary small"><?php echo count($adminData['list_users']); ?></span>
                </button>

                <button class="dash-sidebar-btn" id="tab-admin-newsletter" onclick="switchTab('admin-newsletter')">
                    <div class="dash-icon" style="background:rgba(16,185,129,.12);color:#10b981;"><i class="fas fa-mail-bulk"></i></div>
                    <span>Newsletter</span>
                    <span class="ms-auto text-secondary small"><?php echo count($adminData['list_newsletter']); ?></span>
                </button>

                <button class="dash-sidebar-btn" id="tab-admin-audit" onclick="switchTab('admin-audit')">
                    <div class="dash-icon" style="background:rgba(99,102,241,.12);color:#818cf8;"><i class="fas fa-history"></i></div>
                    <span>Journal d'audit</span>
                </button>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- ── Content ───────────────────────────────────────────────────────── -->
    <div class="col-lg-9 fade-in-up delay-200">

        <!-- ═══ PROFILE TAB ═══════════════════════════════════════════════ -->
        <div id="content-profile" class="tab-pane-dash">
            <div class="bento-card p-4 p-md-5" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">

                <!-- Profile header -->
                <div class="d-flex justify-content-between align-items-start mb-5 flex-wrap gap-3">
                    <div>
                        <h3 class="h4 text-white fw-bold mb-1 d-flex align-items-center gap-3">
                            <i class="fas fa-id-card text-primary"></i> <?php echo t('dash_profile_info'); ?>
                        </h3>
                        <p class="text-secondary small mb-0">Vos informations personnelles et professionnelles</p>
                    </div>
                    <div class="text-end">
                        <div class="text-white small fw-bold mb-1"><?php echo $completionPct; ?>%</div>
                        <div class="completion-bar-wrap" style="width:80px;">
                            <?php $barColor = $completionPct >= 80 ? '#10b981' : ($completionPct >= 50 ? '#fbbf24' : '#ef4444'); ?>
                            <div class="completion-bar-fill" style="width:<?php echo $completionPct; ?>%;background:<?php echo $barColor; ?>;"></div>
                        </div>
                        <div class="text-secondary smaller mt-1"><?php echo $completedCount; ?>/<?php echo $totalFields; ?> champs</div>
                    </div>
                </div>

                <div id="profileAlert" class="alert d-none small py-3 mb-4 rounded-4" role="alert"></div>

                <form id="profileForm">
                    <!-- Informations personnelles -->
                    <p class="dash-label mb-3"><i class="fas fa-user me-2 opacity-50"></i>Informations personnelles</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="dash-label"><?php echo t('dash_full_name'); ?></label>
                            <input type="text" class="form-control dash-input" name="name"
                                   value="<?php echo htmlspecialchars($getUserName()); ?>"
                                   placeholder="Prénom NOM" required>
                        </div>
                        <div class="col-md-6">
                            <label class="dash-label"><?php echo t('dash_email_readonly'); ?></label>
                            <input type="email" class="form-control dash-input"
                                   value="<?php echo htmlspecialchars($getUserEmail()); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="dash-label"><?php echo t('dash_phone'); ?></label>
                            <input type="text" class="form-control dash-input" name="phone"
                                   value="<?php echo htmlspecialchars($getUserPhone()); ?>"
                                   placeholder="+33 6 12 34 56 78"
                                   <?php if (empty($getUserPhone())) echo 'style="border-color:rgba(251,191,36,.3)!important;"'; ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="dash-label"><?php echo t('dash_location_city'); ?></label>
                            <input type="text" class="form-control dash-input" name="location"
                                   value="<?php echo htmlspecialchars($getUserLocation()); ?>"
                                   placeholder="Paris, France"
                                   <?php if (empty($getUserLocation())) echo 'style="border-color:rgba(251,191,36,.3)!important;"'; ?>>
                        </div>
                    </div>

                    <!-- Professionnel -->
                    <p class="dash-label mb-3"><i class="fas fa-briefcase me-2 opacity-50"></i>Informations professionnelles</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="dash-label"><?php echo t('dash_company'); ?></label>
                            <input type="text" class="form-control dash-input" name="company"
                                   value="<?php echo htmlspecialchars($getUserCompany()); ?>"
                                   placeholder="Nom de votre entreprise"
                                   <?php if (empty($getUserCompany())) echo 'style="border-color:rgba(251,191,36,.3)!important;"'; ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="dash-label"><?php echo t('dash_job_title'); ?></label>
                            <input type="text" class="form-control dash-input" name="job"
                                   value="<?php echo htmlspecialchars($getUserJob()); ?>"
                                   placeholder="Votre poste"
                                   <?php if (empty($getUserJob())) echo 'style="border-color:rgba(251,191,36,.3)!important;"'; ?>>
                        </div>
                        <div class="col-12">
                            <label class="dash-label"><?php echo t('dash_linkedin_link'); ?></label>
                            <input type="url" class="form-control dash-input" name="linkedin"
                                   value="<?php echo htmlspecialchars($getUserLinkedin()); ?>"
                                   placeholder="https://linkedin.com/in/votre-profil"
                                   <?php if (empty($getUserLinkedin())) echo 'style="border-color:rgba(251,191,36,.3)!important;"'; ?>>
                        </div>
                    </div>

                    <!-- Sécurité -->
                    <p class="dash-label mb-3"><i class="fas fa-shield-alt me-2 opacity-50"></i>Sécurité</p>
                    <div class="row g-3 mb-5">
                        <div class="col-md-6">
                            <label class="dash-label"><?php echo t('dash_new_password'); ?></label>
                            <input type="password" class="form-control dash-input" name="password"
                                   placeholder="<?php echo t('dash_password_hint'); ?>" minlength="8">
                            <div class="text-secondary smaller mt-2 opacity-50">
                                <i class="fas fa-info-circle me-1"></i> Min. 8 caractères, chiffre ou symbole requis.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="dash-label">Authentification 2FA</label>
                            <div class="d-flex align-items-center gap-3 dash-input" style="cursor:not-allowed;opacity:.5;">
                                <i class="fas fa-lock text-warning"></i>
                                <span class="text-white small">Bientôt disponible</span>
                                <div class="form-check form-switch ms-auto mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" disabled>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn-primary-glow px-5 py-3 rounded-pill fw-bold border-0" id="btnSaveProfile">
                            <i class="fas fa-save me-2"></i> <?php echo t('dash_update_btn'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ═══ BILLING TAB ════════════════════════════════════════════════ -->
        <div id="content-billing" class="tab-pane-dash d-none">

            <?php if ($isAdmin && !empty($adminData['list_pending_billing'])): ?>
            <div class="bento-card p-4 mb-4" style="background:rgba(239,68,68,.05);border:1px solid rgba(239,68,68,.15);border-radius:24px;">
                <h3 class="h5 text-white fw-bold mb-4 d-flex align-items-center gap-2">
                    <i class="fas fa-exclamation-circle text-danger"></i>
                    File d'attente — Facturation en attente (<?php echo count($adminData['list_pending_billing']); ?>)
                </h3>
                <div class="row g-3">
                    <?php foreach ($adminData['list_pending_billing'] as $pb):
                        $pbProps = $pb['properties'] ?? [];
                        $pbName  = $getNotionProp($pbProps['Prenom NOM']) ?: 'N.A';
                        $pbEmail = $getNotionProp($pbProps['Email']);
                        $pbPhoto = '';
                        $pbPhotoFiles = $pbProps['Photo']['files'] ?? [];
                        if (!empty($pbPhotoFiles)) {
                            $pbPhoto = $pbPhotoFiles[0]['file']['url'] ?? $pbPhotoFiles[0]['external']['url'] ?? '';
                        }
                        if (empty($pbPhoto) && isset($pb['icon']) && $pb['icon']['type'] !== 'emoji') {
                            $t = $pb['icon']['type'];
                            $pbPhoto = $pb['icon'][$t]['url'] ?? '';
                        }
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-4" style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.05);">
                            <div class="rounded-circle overflow-hidden flex-shrink-0" style="width:44px;height:44px;background:#333;">
                                <?php if ($pbPhoto): ?>
                                    <img src="<?php echo htmlspecialchars($pbPhoto); ?>" alt="" class="w-100 h-100" style="object-fit:cover;">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary"><i class="fas fa-user"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="overflow-hidden flex-grow-1">
                                <div class="text-white small fw-bold text-truncate"><?php echo htmlspecialchars($pbName); ?></div>
                                <div class="text-secondary smaller text-truncate"><?php echo htmlspecialchars($pbEmail); ?></div>
                                <?php $pbInvoices = $pbProps['Factures']['files'] ?? [];
                                if (!empty($pbInvoices)): ?>
                                <div class="mt-1">
                                    <?php foreach (array_slice($pbInvoices, 0, 2) as $f):
                                        $fUrl = $f['file']['url'] ?? $f['external']['url'] ?? '#'; ?>
                                    <a href="<?php echo htmlspecialchars($fUrl); ?>" target="_blank"
                                       class="badge bg-white bg-opacity-10 text-white text-decoration-none px-2 py-1 me-1 rounded-pill smaller">
                                        <i class="fas fa-file-download me-1"></i><?php echo htmlspecialchars($f['name'] ?? 'Doc'); ?>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="bento-card p-4 p-md-5" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">
                <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
                    <div>
                        <h3 class="h4 text-white fw-bold mb-1"><?php echo t('dash_billing_history'); ?></h3>
                        <p class="text-secondary small mb-0"><?php echo t('dash_billing_subtitle'); ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-2 px-4 py-2 rounded-pill"
                         style="background:<?php echo $billBgHex; ?>;border:1px solid rgba(255,255,255,.08);">
                        <i class="fas fa-circle" style="font-size:.45rem;color:<?php echo $billHex; ?>;"></i>
                        <span class="small fw-bold" style="color:<?php echo $billHex; ?>;"><?php echo htmlspecialchars($factStatus); ?></span>
                    </div>
                </div>

                <?php if (empty($invoices)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-folder-open mb-3 d-block" style="font-size:3rem;opacity:.2;"></i>
                    <p class="text-secondary"><?php echo t('dash_no_documents'); ?></p>
                </div>
                <?php else: ?>
                    <?php foreach ($invoices as $idx => $inv):
                        $invUrl = $inv['file']['url'] ?? $inv['external']['url'] ?? '#';
                        $invName = $inv['name'] ?? 'Document';
                        $isLast = $idx === count($invoices) - 1;
                    ?>
                    <div class="invoice-card" style="<?php echo $isLast ? 'margin-bottom:0;' : ''; ?>">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:44px;height:44px;background:rgba(239,68,68,.1);">
                            <i class="far fa-file-pdf text-danger"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="text-white fw-medium text-truncate"><?php echo htmlspecialchars($invName); ?></div>
                            <div class="text-secondary smaller opacity-75"><?php echo date('d/m/Y'); ?></div>
                        </div>
                        <a href="<?php echo htmlspecialchars($invUrl); ?>" target="_blank"
                           class="btn btn-sm px-4 py-2 rounded-pill text-white flex-shrink-0"
                           style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);text-decoration:none;">
                            <i class="fas fa-download me-1"></i> <?php echo t('dash_download'); ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ═══ NOTIFICATIONS TAB ══════════════════════════════════════════ -->
        <div id="content-notifications" class="tab-pane-dash d-none">
            <div class="bento-card p-4 p-md-5" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">

                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <div>
                        <h3 class="h4 text-white fw-bold mb-1 d-flex align-items-center gap-3">
                            <i class="fas fa-bell text-primary"></i> Notifications
                        </h3>
                        <p class="text-secondary small mb-0">Toutes vos alertes et mises à jour</p>
                    </div>
                    <button onclick="markAllReadDashboard()" class="btn btn-sm px-4 py-2 rounded-pill"
                            style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.6);font-size:.8rem;">
                        <i class="fas fa-check-double me-1"></i> Tout marquer comme lu
                    </button>
                </div>

                <!-- Filter tabs -->
                <div class="d-flex gap-2 flex-wrap mb-4">
                    <button class="notif-filter-btn active" data-filter="all" onclick="filterNotifs(this,'all')">Toutes</button>
                    <button class="notif-filter-btn" data-filter="unread" onclick="filterNotifs(this,'unread')">Non lues</button>
                    <button class="notif-filter-btn" data-filter="billing" onclick="filterNotifs(this,'billing')">Facturation</button>
                    <button class="notif-filter-btn" data-filter="system" onclick="filterNotifs(this,'system')">Système</button>
                </div>

                <!-- Notification list -->
                <div id="dashNotifList">
                    <div class="text-center py-5 text-secondary">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3 d-block opacity-30"></i>
                        Chargement…
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ REVIEWS TAB ════════════════════════════════════════════════ -->
        <?php if (!$isAdmin): ?>
        <div id="content-reviews" class="tab-pane-dash d-none">
            <div class="bento-card p-4 p-md-5" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">
                <h3 class="h4 text-white fw-bold mb-2 d-flex align-items-center gap-3">
                    <i class="fas fa-star text-warning"></i> <?php echo t('dash_review_title'); ?>
                </h3>
                <p class="text-secondary small mb-5">Partagez votre expérience avec SlapIA. Votre avis nous aide à nous améliorer.</p>

                <div id="reviewAlert" class="alert d-none small py-3 mb-4 rounded-4" role="alert"></div>

                <form id="reviewForm">
                    <!-- Stars -->
                    <div class="p-5 rounded-4 mb-4 text-center" style="background:rgba(255,255,255,.02);border:1px dashed rgba(255,255,255,.08);">
                        <p class="text-secondary small mb-3 text-uppercase fw-bold tracking-wide"><?php echo t('dash_satisfaction_note'); ?></p>
                        <?php
                        $currentStars = 0;
                        $sat = (string)($props['Satisfaction']['select']['name'] ?? '');
                        if (preg_match('/[1-5]/', $sat, $m)) $currentStars = (int)$m[0];
                        elseif (strpos($sat,'⭐') !== false) $currentStars = mb_substr_count($sat,'⭐');
                        ?>
                        <input type="hidden" name="satisfaction" id="satisfactionInput" value="<?php echo str_repeat('⭐', $currentStars); ?>">
                        <div class="d-flex justify-content-center gap-3 mb-3">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="<?php echo $i <= $currentStars ? 'fas' : 'far'; ?> fa-star text-warning star-item"
                               style="font-size:2rem;" id="star-<?php echo $i; ?>"
                               onclick="setStars(<?php echo $i; ?>)"
                               onmouseenter="previewStars(<?php echo $i; ?>)"
                               onmouseleave="restoreStars()"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="text-warning small fw-bold" id="starText">
                            <?php echo $currentStars ? $currentStars . '/5 étoile' . ($currentStars > 1 ? 's' : '') : 'Sélectionnez une note'; ?>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="dash-label mb-3"><?php echo t('dash_review_label'); ?></label>
                        <textarea class="form-control dash-input" name="avis" rows="10"
                                  placeholder="<?php echo t('dash_review_placeholder'); ?>"
                                  style="min-height:220px;line-height:1.7;resize:vertical;"><?php echo htmlspecialchars($getAvis()); ?></textarea>
                    </div>

                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn-primary-glow px-5 py-3 rounded-pill fw-bold border-0" id="btnSaveReview">
                            <i class="fas fa-paper-plane me-2"></i> <?php echo t('dash_review_save'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- ═══ ADMIN TABS ═════════════════════════════════════════════════ -->
        <?php if ($isAdmin): ?>

        <!-- Admin overview -->
        <div id="content-admin" class="tab-pane-dash d-none">

            <!-- Status bar -->
            <div class="bento-card p-4 mb-4" style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);border-radius:20px;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-4 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <div class="pulse-green"></div>
                            <span class="text-white small fw-bold opacity-75">API Notion</span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-2 py-1 rounded-pill small">Connecté</span>
                        </div>
                        <div class="quick-stat border-start border-white border-opacity-10 ps-4">
                            <i class="fas fa-clock"></i>
                            Synchro <strong><?php echo date('H:i:s'); ?></strong>
                        </div>
                        <?php if (!empty($adminData['list_pending_billing'])): ?>
                        <div class="quick-stat">
                            <i class="fas fa-exclamation-circle text-danger"></i>
                            <strong style="color:#ef4444;"><?php echo count($adminData['list_pending_billing']); ?> en attente</strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="/admin-reset-pwd" class="btn btn-sm btn-outline-danger px-4 py-2 rounded-pill">
                            <i class="fas fa-key me-2"></i> Reset MDP
                        </a>
                        <a href="https://www.notion.so" target="_blank" class="btn btn-sm btn-outline-glass px-4 py-2 rounded-pill">
                            <i class="fas fa-external-link-alt me-2"></i> Notion CRM
                        </a>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="kpi-card" style="background:linear-gradient(135deg,rgba(88,86,214,.12),rgba(15,15,15,.5));">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-3 p-2" style="background:rgba(88,86,214,.2);"><i class="fas fa-users text-primary small"></i></div>
                            <span class="text-secondary small fw-bold text-uppercase"><?php echo t('admin_total_users'); ?></span>
                        </div>
                        <div class="kpi-number text-white"><?php echo count($adminData['list_users']); ?></div>
                        <div class="text-secondary smaller opacity-60">utilisateurs enregistrés</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-card" style="background:linear-gradient(135deg,rgba(16,185,129,.1),rgba(15,15,15,.5));">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-3 p-2" style="background:rgba(16,185,129,.2);"><i class="fas fa-paper-plane text-success small"></i></div>
                            <span class="text-secondary small fw-bold text-uppercase"><?php echo t('admin_total_subscribers'); ?></span>
                        </div>
                        <div class="kpi-number text-white"><?php echo count($adminData['list_newsletter']); ?></div>
                        <div class="text-secondary smaller opacity-60">abonnés newsletter</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-card" style="background:linear-gradient(135deg,rgba(239,68,68,.08),rgba(15,15,15,.5));">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-3 p-2" style="background:rgba(239,68,68,.15);"><i class="fas fa-hourglass-half text-danger small"></i></div>
                            <span class="text-secondary small fw-bold text-uppercase">Factures en attente</span>
                        </div>
                        <div class="kpi-number" style="color:<?php echo empty($adminData['list_pending_billing']) ? '#fff' : '#ef4444'; ?>;">
                            <?php echo count($adminData['list_pending_billing']); ?>
                        </div>
                        <div class="text-secondary smaller opacity-60">paiements à valider</div>
                    </div>
                </div>
            </div>

            <!-- Growth Chart -->
            <div class="bento-card p-4 mb-4" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">
                <h4 class="text-white fw-bold mb-4 d-flex align-items-center gap-3">
                    <i class="fas fa-chart-line text-primary"></i> Courbes de Croissance — 6 derniers mois
                </h4>
                <div style="height:300px;position:relative;">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>

            <!-- Recent activity -->
            <div class="bento-card p-4 p-md-5" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="h4 text-white fw-bold mb-0 d-flex align-items-center gap-3">
                        <i class="fas fa-history text-primary"></i> <?php echo t('admin_recent_activity'); ?>
                    </h3>
                    <a href="https://www.notion.so" target="_blank" class="btn btn-sm btn-outline-glass rounded-pill px-4">
                        <i class="fas fa-external-link-alt me-2"></i> CRM
                    </a>
                </div>

                <?php if (empty($adminData['recent'])): ?>
                <p class="text-secondary text-center py-4">Aucune activité récente.</p>
                <?php else: ?>
                <?php foreach ($adminData['recent'] as $act): ?>
                <div class="d-flex align-items-start gap-4 mb-4 pb-4 border-bottom border-white border-opacity-5">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:42px;height:42px;background:<?php echo $act['type']==='lead' ? 'rgba(251,191,36,.1)' : 'rgba(16,185,129,.1)'; ?>;">
                        <i class="fas <?php echo $act['type']==='lead' ? 'fa-envelope text-warning' : 'fa-rss text-success'; ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="text-white mb-0 fw-bold"><?php echo htmlspecialchars($act['name'] ?: 'Inscrit'); ?></h6>
                            <span class="text-secondary smaller"><?php echo $act['date']; ?></span>
                        </div>
                        <p class="text-secondary small mb-1 opacity-75"><?php echo htmlspecialchars($act['email']); ?></p>
                        <span class="badge rounded-pill px-2 py-1 smaller text-uppercase"
                              style="background:<?php echo $act['type']==='lead' ? 'rgba(251,191,36,.1)' : 'rgba(16,185,129,.1)'; ?>;color:<?php echo $act['type']==='lead' ? '#fbbf24' : '#10b981'; ?>;">
                            <?php echo $act['type']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Admin Newsletter List -->
        <div id="content-admin-newsletter" class="tab-pane-dash d-none">
            <div class="bento-card p-4 p-md-5" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <h3 class="h4 text-white fw-bold mb-0 d-flex align-items-center gap-3">
                        <i class="fas fa-mail-bulk text-success"></i>
                        <?php echo t('admin_total_subscribers'); ?>
                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-3 py-1">
                            <?php echo count($adminData['list_newsletter']); ?>
                        </span>
                    </h3>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="position-relative">
                            <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary" style="font-size:.75rem;opacity:.5;"></i>
                            <input type="text" onkeyup="filterTable('newsletterTable', this.value)"
                                   class="form-control dash-input rounded-pill py-2 ps-5 pe-4"
                                   placeholder="Filtrer…" style="width:220px;font-size:.8rem;">
                        </div>
                        <button onclick="exportTableToCSV('newsletterTable','newsletter_slapia')"
                                class="btn btn-sm btn-outline-glass px-3 py-2 rounded-pill text-white" style="font-size:.8rem;border-color:rgba(255,255,255,.1);">
                            <i class="fas fa-download me-1"></i> CSV
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table admin-table border-0 m-0" id="newsletterTable">
                        <thead>
                            <tr style="border-bottom:2px solid rgba(255,255,255,.05)!important;">
                                <th class="py-3 border-0">Email</th>
                                <th class="py-3 border-0">Date d'inscription</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adminData['list_newsletter'] as $n): ?>
                            <tr class="align-middle">
                                <td class="py-3 text-white"><?php echo htmlspecialchars($getNotionProp($n['properties']['Email'])); ?></td>
                                <td class="py-3 text-secondary small"><?php echo date('d/m/Y H:i', strtotime($n['created_time'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Admin Users List -->
        <div id="content-admin-emails" class="tab-pane-dash d-none">
            <div class="bento-card p-4 p-md-5" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <h3 class="h4 text-white fw-bold mb-0 d-flex align-items-center gap-3">
                        <i class="fas fa-users-cog text-warning"></i>
                        <?php echo t('admin_total_users'); ?>
                        <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning border border-warning border-opacity-20 px-3 py-1">
                            <?php echo count($adminData['list_users']); ?>
                        </span>
                    </h3>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="position-relative">
                            <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary" style="font-size:.75rem;opacity:.5;"></i>
                            <input type="text" onkeyup="filterTable('usersTable', this.value)"
                                   class="form-control dash-input rounded-pill py-2 ps-5 pe-4"
                                   placeholder="Rechercher…" style="width:240px;font-size:.85rem;">
                        </div>
                        <button onclick="exportTableToCSV('usersTable','users_slapia')"
                                class="btn btn-sm btn-outline-glass px-3 py-2 rounded-pill text-white" style="font-size:.8rem;border-color:rgba(255,255,255,.1);">
                            <i class="fas fa-download me-1"></i> CSV
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table admin-table border-0 m-0" id="usersTable">
                        <thead>
                            <tr style="border-bottom:2px solid rgba(255,255,255,.05)!important;">
                                <th class="py-3 border-0">Nom</th>
                                <th class="py-3 border-0">Email</th>
                                <th class="py-3 border-0">Entreprise</th>
                                <th class="py-3 border-0">Statut</th>
                                <th class="py-3 border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adminData['list_users'] as $u):
                                $uStatus = $getNotionProp($u['properties']['Status']) ?: 'Client';
                                $uName   = $getNotionProp($u['properties']['Prenom NOM']) ?: 'N.A';
                                $uEmail  = $getNotionProp($u['properties']['Email']);
                                $uCo     = $getNotionProp($u['properties']['Nom d\'entreprise']);
                            ?>
                            <tr class="align-middle">
                                <td class="py-3">
                                    <span class="text-white fw-medium"><?php echo htmlspecialchars($uName); ?></span>
                                </td>
                                <td class="py-3 text-secondary small"><?php echo htmlspecialchars($uEmail); ?></td>
                                <td class="py-3 text-secondary small opacity-75"><?php echo htmlspecialchars($uCo); ?></td>
                                <td class="py-3">
                                    <select class="form-select form-select-sm bg-transparent border-0 text-white small p-0 fw-bold"
                                            style="cursor:pointer;width:auto;"
                                            onchange="updateUserRole('<?php echo htmlspecialchars($u['id']); ?>', this.value)">
                                        <option value="Client"    class="bg-dark" <?php echo $uStatus==='Client'    ? 'selected' : ''; ?>>Client</option>
                                        <option value="Entreprise" class="bg-dark" <?php echo $uStatus==='Entreprise' ? 'selected' : ''; ?>>Entreprise</option>
                                        <option value="Particulier" class="bg-dark" <?php echo $uStatus==='Particulier' ? 'selected' : ''; ?>>Particulier</option>
                                        <option value="Admin"     class="bg-dark text-danger" <?php echo $uStatus==='Admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </td>
                                <td class="py-3 text-end">
                                    <a href="mailto:<?php echo htmlspecialchars($uEmail); ?>"
                                       class="btn btn-sm px-3 py-1 rounded-pill me-1 text-white"
                                       style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);font-size:.75rem;">
                                        <i class="fas fa-envelope me-1"></i> Email
                                    </a>
                                    <a href="/admin-reset-pwd?email=<?php echo urlencode($uEmail); ?>"
                                       class="btn btn-sm btn-outline-danger px-3 py-1 rounded-pill"
                                       style="font-size:.75rem;">
                                        <i class="fas fa-key me-1"></i> Reset
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Admin Audit Log -->
        <div id="content-admin-audit" class="tab-pane-dash d-none">
            <div class="bento-card p-4 p-md-5" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">
                <h3 class="h4 text-white fw-bold mb-4 d-flex align-items-center gap-3">
                    <i class="fas fa-history text-indigo"></i> Journal d'Audit
                </h3>
                <div class="table-responsive">
                    <table class="table admin-table border-0 m-0">
                        <thead>
                            <tr style="border-bottom:2px solid rgba(255,255,255,.05)!important;">
                                <th class="py-3 border-0">Type</th>
                                <th class="py-3 border-0">Acteur / Cible</th>
                                <th class="py-3 border-0">Date</th>
                                <th class="py-3 border-0 text-end">Sync</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adminData['recent'] as $act): ?>
                            <tr class="align-middle">
                                <td class="py-3">
                                    <span class="badge rounded-pill px-3 py-1 smaller text-uppercase"
                                          style="background:<?php echo $act['type']==='lead' ? 'rgba(59,130,246,.1)' : 'rgba(16,185,129,.1)'; ?>;color:<?php echo $act['type']==='lead' ? '#60a5fa' : '#34d399'; ?>;border:1px solid <?php echo $act['type']==='lead' ? 'rgba(59,130,246,.2)' : 'rgba(16,185,129,.2)'; ?>;">
                                        <?php echo strtoupper($act['type']); ?>
                                    </span>
                                </td>
                                <td class="py-3">
                                    <div class="text-white small fw-medium"><?php echo htmlspecialchars($act['name']); ?></div>
                                    <div class="text-secondary smaller opacity-50"><?php echo htmlspecialchars($act['email']); ?></div>
                                </td>
                                <td class="py-3 text-secondary small"><?php echo $act['date']; ?></td>
                                <td class="py-3 text-end"><i class="fas fa-check-circle text-success opacity-40"></i></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php endif; ?>

    </div><!-- /col-lg-9 -->
</div><!-- /row -->
</div><!-- /container -->
</section>

<script>
// ── Tab switching ────────────────────────────────────────────────────────────
const tabTitles = {
    'profile':            '<?php echo t('dash_tab_profile'); ?>',
    'billing':            '<?php echo t('dash_tab_billing'); ?>',
    'notifications':      'Notifications',
    'reviews':            '<?php echo t('dash_tab_reviews'); ?>',
    'admin':              'Administration SlapIA',
    'admin-newsletter':   'Liste Newsletter',
    'admin-emails':       'Gestion Utilisateurs',
    'admin-audit':        'Journal d\'Audit',
};

function switchTab(tabId) {
    document.querySelectorAll('.tab-pane-dash').forEach(c => c.classList.add('d-none'));
    document.querySelectorAll('.dash-sidebar-btn').forEach(b => b.classList.remove('active'));

    const pane = document.getElementById('content-' + tabId);
    const btn  = document.getElementById('tab-' + tabId);
    if (pane) pane.classList.remove('d-none');
    if (btn)  btn.classList.add('active');

    if (tabTitles[tabId]) document.getElementById('dash-title').textContent = tabTitles[tabId];

    // Load notifications when tab is opened
    if (tabId === 'notifications') renderDashNotifs();

    // Init chart when admin tab opens
    if (tabId === 'admin') initGrowthChart();

    // Update URL hash without scroll
    history.replaceState(null, '', '#' + tabId);
}

// Open tab from URL hash on load
(function() {
    const hash = location.hash.replace('#', '');
    const urlParam = new URLSearchParams(location.search).get('tab');
    const target = urlParam || hash;
    if (target) switchTab(target);
})();

// ── Table filter & CSV export ────────────────────────────────────────────────
function filterTable(tableId, query) {
    const rows = document.getElementById(tableId).getElementsByTagName('tr');
    query = query.toLowerCase();
    for (let i = 1; i < rows.length; i++) {
        rows[i].style.display = rows[i].textContent.toLowerCase().includes(query) ? '' : 'none';
    }
}

function exportTableToCSV(tableId, filename) {
    const rows = document.getElementById(tableId).querySelectorAll('tr');
    const csv  = Array.from(rows).map(r =>
        Array.from(r.querySelectorAll('th,td')).map(c => `"${c.innerText.replace(/"/g,'""')}"`).join(',')
    );
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(blob),
        download: `${filename}_${new Date().toISOString().split('T')[0]}.csv`
    });
    a.click();
}

// ── Admin: update user role ──────────────────────────────────────────────────
async function updateUserRole(pageId, newStatus) {
    if (!confirm(`Changer le statut en "${newStatus}" ?`)) { location.reload(); return; }
    try {
        const res = await fetch('/api/admin-update-role-exec.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ page_id: pageId, status: newStatus, csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' })
        });
        const data = await res.json();
        if (!data.success) alert('Erreur: ' + (data.error || 'Inconnue'));
    } catch { alert('Erreur réseau.'); }
}

// ── Growth chart ─────────────────────────────────────────────────────────────
<?php if ($isAdmin && isset($adminData['chart'])): ?>
let growthChartInstance = null;
function initGrowthChart() {
    const el = document.getElementById('growthChart');
    if (!el || growthChartInstance) return;
    growthChartInstance = new Chart(el.getContext('2d'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($adminData['chart']['labels']); ?>,
            datasets: [{
                label: 'Utilisateurs',
                data: <?php echo json_encode($adminData['chart']['users']); ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,.08)',
                fill: true, tension: .4, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#3b82f6'
            },{
                label: 'Newsletter',
                data: <?php echo json_encode($adminData['chart']['news']); ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,.08)',
                fill: true, tension: .4, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#10b981'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: true, position: 'top', align: 'end',
                    labels: { color: 'rgba(255,255,255,.6)', font: { size: 11, weight:'600' }, boxWidth: 10, usePointStyle: true }
                },
                tooltip: {
                    backgroundColor: 'rgba(10,10,10,.95)', titleColor: '#fff', bodyColor: 'rgba(255,255,255,.75)',
                    borderColor: 'rgba(255,255,255,.1)', borderWidth: 1, padding: 12, cornerRadius: 12
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.04)' },
                     ticks: { color: 'rgba(255,255,255,.35)', font: { size: 11 }, stepSize: 1 } },
                x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,.35)', font: { size: 11 } } }
            }
        }
    });
}
<?php endif; ?>

// ── Stars ────────────────────────────────────────────────────────────────────
let currentStarValue = <?php echo $currentStars ?? 0; ?>;

const starLabels = ['','1 étoile','2 étoiles','3 étoiles','4 étoiles','5 étoiles'];

function renderStars(count, preview = false) {
    for (let i = 1; i <= 5; i++) {
        const star = document.getElementById('star-' + i);
        if (!star) return;
        star.className = (i <= count ? 'fas' : 'far') + ' fa-star text-warning star-item';
        star.style.fontSize = '2rem';
    }
    const label = document.getElementById('starText');
    if (label && !preview) label.textContent = count ? starLabels[count] : 'Sélectionnez une note';
}

function setStars(count) {
    currentStarValue = count;
    const input = document.getElementById('satisfactionInput');
    if (input) input.value = '⭐'.repeat(count);
    renderStars(count);
    const label = document.getElementById('starText');
    if (label) label.textContent = starLabels[count];
}

function previewStars(count) { renderStars(count, true); }
function restoreStars()      { renderStars(currentStarValue, true); }

// ── Profile & review update ──────────────────────────────────────────────────
async function handleUpdate(formId, btnId, alertId) {
    const form    = document.getElementById(formId);
    const btn     = document.getElementById(btnId);
    const alertEl = document.getElementById(alertId);
    const origHTML = btn.innerHTML;

    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> <?php echo t('dash_saving'); ?>';
    btn.disabled  = true;
    alertEl.classList.add('d-none');

    const data = Object.fromEntries(new FormData(form).entries());

    try {
        const res    = await fetch('/api/auth-update.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
        });
        const result = await res.json();

        if (result.success) {
            alertEl.className = 'alert alert-success small py-2 rounded-4';
            alertEl.textContent = result.message || '<?php echo t('dash_update_success_default'); ?>';
            alertEl.classList.remove('d-none');
            if (data.name) setTimeout(() => location.reload(), 1500);
        } else {
            alertEl.className = 'alert alert-danger small py-2 rounded-4';
            alertEl.textContent = result.error || '<?php echo t('dash_update_error_default'); ?>';
            alertEl.classList.remove('d-none');
        }
    } catch {
        alertEl.className = 'alert alert-danger small py-2 rounded-4';
        alertEl.textContent = '<?php echo t('login_error_server'); ?>';
        alertEl.classList.remove('d-none');
    } finally {
        btn.innerHTML = origHTML;
        btn.disabled  = false;
        if (alertEl.classList.contains('alert-success')) {
            setTimeout(() => alertEl.classList.add('d-none'), 3500);
        }
    }
}

document.getElementById('profileForm')?.addEventListener('submit', e => { e.preventDefault(); handleUpdate('profileForm','btnSaveProfile','profileAlert'); });
<?php if (!$isAdmin): ?>
document.getElementById('reviewForm')?.addEventListener('submit',  e => { e.preventDefault(); handleUpdate('reviewForm','btnSaveReview','reviewAlert'); });
<?php endif; ?>

// ── Notification center ──────────────────────────────────────────────────────
let dashNotifData   = [];
let dashNotifFilter = 'all';

function timeAgo(ts) {
    const diff = Math.floor(Date.now() / 1000) - ts;
    if (diff < 60)    return 'à l\'instant';
    if (diff < 3600)  return Math.floor(diff/60) + ' min';
    if (diff < 86400) return Math.floor(diff/3600) + ' h';
    return Math.floor(diff/86400) + ' j';
}

const billingIds = ['user_pending_payment'];
const billingPfx = ['inv_'];
const systemIds  = ['system_admin','profile_incomplete','welcome_new'];

function getNotifCategory(id) {
    if (billingIds.includes(id) || billingPfx.some(p => id.startsWith(p))) return 'billing';
    if (systemIds.includes(id)) return 'system';
    return 'other';
}

function renderDashNotifs() {
    fetch('/api/check-notifications.php')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            dashNotifData = data.notifications || [];
            updateSidebarBadge(data.unread_count || 0);
            renderNotifList();
        })
        .catch(() => {
            document.getElementById('dashNotifList').innerHTML =
                '<p class="text-secondary text-center py-4">Impossible de charger les notifications.</p>';
        });
}

function renderNotifList() {
    const container = document.getElementById('dashNotifList');
    if (!container) return;

    let filtered = dashNotifData.filter(n => {
        if (dashNotifFilter === 'unread')  return !n.read;
        if (dashNotifFilter === 'billing') return getNotifCategory(n.id) === 'billing';
        if (dashNotifFilter === 'system')  return getNotifCategory(n.id) === 'system';
        return true;
    });

    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-bell-slash fa-3x mb-3 d-block" style="opacity:.15;"></i>
                <p class="text-secondary">${dashNotifFilter === 'unread' ? 'Aucune notification non lue.' : 'Aucune notification.'}</p>
            </div>`;
        return;
    }

    container.innerHTML = filtered.map(n => `
        <div class="notif-item${n.read ? '' : ' unread'}" data-id="${n.id}" data-cat="${getNotifCategory(n.id)}"
             onclick="handleNotifClick('${n.id}','${n.link}')">
            <div class="notif-icon-wrap ${n.icon_bg} bg-opacity-10">
                <i class="${n.icon} ${n.icon_color}"></i>
            </div>
            <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="text-white small fw-bold">${n.title}</span>
                    ${n.pinned ? '<i class="fas fa-thumbtack text-secondary" style="font-size:.6rem;opacity:.5;" title="Épinglée"></i>' : ''}
                </div>
                <p class="text-secondary smaller mb-1 text-truncate opacity-75">${n.desc}</p>
                <span class="text-secondary" style="font-size:.7rem;">${timeAgo(n.ts)}</span>
            </div>
            ${!n.read ? `<button class="notif-dismiss" onclick="event.stopPropagation();dismissNotif('${n.id}')" title="Marquer comme lu">
                <i class="fas fa-times"></i>
            </button>` : ''}
        </div>
    `).join('<div style="height:1px;background:rgba(255,255,255,.04);margin:2px 16px;"></div>');
}

function handleNotifClick(id, link) {
    if (!dashNotifData.find(n => n.id === id)?.read) {
        dismissNotif(id);
    }
    if (link && link !== '#') window.location.href = link;
}

async function dismissNotif(id) {
    // Optimistic update
    const n = dashNotifData.find(n => n.id === id);
    if (n) n.read = true;
    renderNotifList();
    updateSidebarBadge(dashNotifData.filter(n => !n.read).length);

    // Update header badge too
    if (typeof fetchNotifications === 'function') fetchNotifications();

    await fetch('/api/notifications-mark-read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
}

async function markAllReadDashboard() {
    dashNotifData.forEach(n => n.read = true);
    renderNotifList();
    updateSidebarBadge(0);
    if (typeof fetchNotifications === 'function') fetchNotifications();

    await fetch('/api/notifications-mark-read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mark_all: true })
    });
}

function filterNotifs(btn, filter) {
    document.querySelectorAll('.notif-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    dashNotifFilter = filter;
    renderNotifList();
}

function updateSidebarBadge(count) {
    const badge = document.getElementById('sidebarNotifBadge');
    if (!badge) return;
    if (count > 0) {
        badge.textContent = count > 9 ? '9+' : count;
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }
}

// ── On load: fetch notifs for sidebar badge ──────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    fetch('/api/check-notifications.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) updateSidebarBadge(data.unread_count || 0);
        });

    <?php if ($isAdmin): ?>
    // Auto-init chart if admin tab is visible on load
    const adminContent = document.getElementById('content-admin');
    if (adminContent && !adminContent.classList.contains('d-none')) initGrowthChart();
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>
