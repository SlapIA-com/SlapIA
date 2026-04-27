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

// Sync session name (avatar is served by /api/notion-avatar.php — no expiring URL stored)
$_SESSION['user_name'] = $props['Prenom NOM']['title'][0]['text']['content'] ?? $_SESSION['user_name'];

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
                               ?? '';

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

// Invoices
$invoices = $props['Factures']['files'] ?? [];

// Status
$isAdmin = ($getUserStatus() === 'Admin' || $getUserStatus() === '');

// Admin data is loaded lazily via /api/admin-data.php when admin tabs are first opened.

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

/* ── Skeleton loader ─────────────────────────────────────────────────── */
.skeleton {
    background: linear-gradient(90deg,rgba(255,255,255,.03) 25%,rgba(255,255,255,.08) 50%,rgba(255,255,255,.03) 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 8px;
}
@keyframes shimmer {
    0%   { background-position:  200% 0; }
    100% { background-position: -200% 0; }
}
.skeleton-line { height: 14px; margin-bottom: 10px; }
.skeleton-line.short { width: 45%; }
.skeleton-line.medium { width: 70%; }
.skeleton-line.full   { width: 100%; }
.skeleton-avatar { width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0; }
.skeleton-block  { height: 80px; border-radius: 16px; }
.skeleton-kpi    { height: 110px; border-radius: 20px; }

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
        <div class="dash-header-avatar position-relative rounded-4 overflow-hidden border border-white border-opacity-10 shadow-lg"
             style="width:120px;height:120px;background:rgba(255,255,255,.03);">
            <img src="/api/notion-avatar.php?id=<?php echo urlencode($userId); ?>"
                 alt="Photo de profil"
                 class="w-100 h-100"
                 style="object-fit:cover;"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div class="w-100 h-100 align-items-center justify-content-center text-white" style="background:rgba(191,90,242,.15);font-size:2.5rem;display:none;">
                <i class="fas fa-user"></i>
            </div>
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
                    <span class="ms-auto text-secondary small" id="sidebarCountUsers">…</span>
                </button>

                <button class="dash-sidebar-btn" id="tab-admin-newsletter" onclick="switchTab('admin-newsletter')">
                    <div class="dash-icon" style="background:rgba(16,185,129,.12);color:#10b981;"><i class="fas fa-mail-bulk"></i></div>
                    <span>Newsletter</span>
                    <span class="ms-auto text-secondary small" id="sidebarCountNews">…</span>
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

            <?php if ($isAdmin): ?>
            <!-- Pending billing (admin) — rendered by JS -->
            <div id="adminPendingBilling"></div>
            <?php endif; ?>

            <div class="bento-card p-4 p-md-5" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">
                <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
                    <div>
                        <h3 class="h4 text-white fw-bold mb-1"><?php echo t('dash_billing_history'); ?></h3>
                        <p class="text-secondary small mb-0"><?php echo t('dash_billing_subtitle'); ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <?php if (!empty($invoices)): ?>
                        <button onclick="printInvoices()" class="btn btn-sm px-3 py-2 rounded-pill"
                                style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.6);font-size:.78rem;">
                            <i class="fas fa-print me-1"></i> Imprimer
                        </button>
                        <?php endif; ?>
                        <div class="d-flex align-items-center gap-2 px-4 py-2 rounded-pill"
                             style="background:<?php echo $billBgHex; ?>;border:1px solid rgba(255,255,255,.08);">
                            <i class="fas fa-circle" style="font-size:.45rem;color:<?php echo $billHex; ?>;"></i>
                            <span class="small fw-bold" style="color:<?php echo $billHex; ?>;"><?php echo htmlspecialchars($factStatus); ?></span>
                        </div>
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
                    <!-- Skeleton -->
                    <?php for ($s = 0; $s < 4; $s++): ?>
                    <div class="d-flex align-items-start gap-3 p-3 mb-2">
                        <div class="skeleton skeleton-avatar"></div>
                        <div class="flex-grow-1">
                            <div class="skeleton skeleton-line medium"></div>
                            <div class="skeleton skeleton-line full"></div>
                            <div class="skeleton skeleton-line short"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
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

        <!-- Admin overview — JS rendered -->
        <div id="content-admin" class="tab-pane-dash d-none">
            <div id="adminOverviewInner">
                <!-- Skeleton -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4"><div class="skeleton skeleton-kpi"></div></div>
                    <div class="col-md-4"><div class="skeleton skeleton-kpi"></div></div>
                    <div class="col-md-4"><div class="skeleton skeleton-kpi"></div></div>
                </div>
                <div class="skeleton skeleton-block mb-4" style="height:320px;"></div>
                <div class="skeleton skeleton-block" style="height:200px;"></div>
            </div>
        </div>

        <!-- Admin Newsletter List — JS rendered -->
        <div id="content-admin-newsletter" class="tab-pane-dash d-none">
            <div id="adminNewsletterInner">
                <div class="skeleton skeleton-block" style="height:300px;"></div>
            </div>
        </div>

        <!-- Admin Users List — JS rendered -->
        <div id="content-admin-emails" class="tab-pane-dash d-none">
            <div id="adminUsersInner">
                <div class="skeleton skeleton-block" style="height:400px;"></div>
            </div>
        </div>

        <!-- Admin Audit Log — JS rendered -->
        <div id="content-admin-audit" class="tab-pane-dash d-none">
            <div id="adminAuditInner">
                <div class="skeleton skeleton-block" style="height:300px;"></div>
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

// ── Admin lazy-load state ────────────────────────────────────────────────────
const ADMIN_TABS = ['admin', 'admin-emails', 'admin-newsletter', 'admin-audit'];
let adminDataCache  = null;
let adminDataLoading = false;

function switchTab(tabId) {
    document.querySelectorAll('.tab-pane-dash').forEach(c => c.classList.add('d-none'));
    document.querySelectorAll('.dash-sidebar-btn').forEach(b => b.classList.remove('active'));

    const pane = document.getElementById('content-' + tabId);
    const btn  = document.getElementById('tab-' + tabId);
    if (pane) pane.classList.remove('d-none');
    if (btn)  btn.classList.add('active');

    if (tabTitles[tabId]) document.getElementById('dash-title').textContent = tabTitles[tabId];

    if (tabId === 'notifications') renderDashNotifs();

    // Lazy-load admin data on first admin tab click
    if (ADMIN_TABS.includes(tabId)) loadAdminData(tabId);

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

// ── Admin lazy loading ────────────────────────────────────────────────────────
let growthChartInstance = null;

async function loadAdminData(targetTab) {
    if (adminDataLoading) return;
    if (adminDataCache) { renderAdminTab(targetTab, adminDataCache); return; }
    adminDataLoading = true;
    try {
        const res  = await fetch('/api/admin-data.php');
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Erreur');
        adminDataCache = data;
        // Update sidebar counts
        const cu = document.getElementById('sidebarCountUsers');
        const cn = document.getElementById('sidebarCountNews');
        if (cu) cu.textContent = data.counts.users;
        if (cn) cn.textContent = data.counts.newsletter;
        renderAdminAllTabs(data);
        renderAdminTab(targetTab, data);
    } catch (e) {
        ['adminOverviewInner','adminUsersInner','adminNewsletterInner','adminAuditInner'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = `<p class="text-danger text-center py-4"><i class="fas fa-exclamation-circle me-2"></i>${e.message}</p>`;
        });
    } finally {
        adminDataLoading = false;
    }
}

function renderAdminTab(tabId, data) {
    if (tabId === 'admin' && !growthChartInstance) {
        setTimeout(() => initGrowthChart(data.chart), 50);
    }
}

function renderAdminAllTabs(data) {
    renderAdminOverview(data);
    renderAdminUsers(data);
    renderAdminNewsletter(data);
    renderAdminAudit(data);
    renderAdminPendingBilling(data);
}

function renderAdminOverview(data) {
    const el = document.getElementById('adminOverviewInner');
    if (!el) return;
    const syncTime = new Date().toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
    const pendingAlert = data.counts.pending > 0
        ? `<div class="quick-stat"><i class="fas fa-exclamation-circle text-danger"></i><strong style="color:#ef4444;">${data.counts.pending} en attente</strong></div>`
        : '';
    el.innerHTML = `
    <div class="bento-card p-4 mb-4" style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);border-radius:20px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <div class="pulse-green"></div>
                    <span class="text-white small fw-bold opacity-75">API Notion</span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-2 py-1 rounded-pill small">Connecté</span>
                </div>
                <div class="quick-stat border-start border-white border-opacity-10 ps-4">
                    <i class="fas fa-clock"></i> Synchro <strong>${syncTime}</strong>
                </div>
                ${pendingAlert}
            </div>
            <div class="d-flex gap-2">
                <a href="/admin-reset-pwd" class="btn btn-sm btn-outline-danger px-4 py-2 rounded-pill"><i class="fas fa-key me-2"></i> Reset MDP</a>
                <a href="https://www.notion.so" target="_blank" class="btn btn-sm btn-outline-glass px-4 py-2 rounded-pill"><i class="fas fa-external-link-alt me-2"></i> Notion CRM</a>
            </div>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="kpi-card" style="background:linear-gradient(135deg,rgba(88,86,214,.12),rgba(15,15,15,.5));">
                <div class="d-flex align-items-center gap-2"><div class="rounded-3 p-2" style="background:rgba(88,86,214,.2);"><i class="fas fa-users text-primary small"></i></div><span class="text-secondary small fw-bold text-uppercase">Utilisateurs</span></div>
                <div class="kpi-number text-white">${data.counts.users}</div>
                <div class="text-secondary smaller opacity-60">enregistrés</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card" style="background:linear-gradient(135deg,rgba(16,185,129,.1),rgba(15,15,15,.5));">
                <div class="d-flex align-items-center gap-2"><div class="rounded-3 p-2" style="background:rgba(16,185,129,.2);"><i class="fas fa-paper-plane text-success small"></i></div><span class="text-secondary small fw-bold text-uppercase">Newsletter</span></div>
                <div class="kpi-number text-white">${data.counts.newsletter}</div>
                <div class="text-secondary smaller opacity-60">abonnés</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card" style="background:linear-gradient(135deg,rgba(239,68,68,.08),rgba(15,15,15,.5));">
                <div class="d-flex align-items-center gap-2"><div class="rounded-3 p-2" style="background:rgba(239,68,68,.15);"><i class="fas fa-hourglass-half text-danger small"></i></div><span class="text-secondary small fw-bold text-uppercase">Factures en attente</span></div>
                <div class="kpi-number" style="color:${data.counts.pending > 0 ? '#ef4444' : '#fff'}">${data.counts.pending}</div>
                <div class="text-secondary smaller opacity-60">paiements à valider</div>
            </div>
        </div>
    </div>
    <div class="bento-card p-4 mb-4" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">
        <h4 class="text-white fw-bold mb-4 d-flex align-items-center gap-3"><i class="fas fa-chart-line text-primary"></i> Courbes de Croissance — 6 derniers mois</h4>
        <div style="height:300px;position:relative;"><canvas id="growthChart"></canvas></div>
    </div>
    <div class="bento-card p-4 p-md-5" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="h4 text-white fw-bold mb-0 d-flex align-items-center gap-3"><i class="fas fa-history text-primary"></i> Activité Récente</h3>
            <a href="https://www.notion.so" target="_blank" class="btn btn-sm btn-outline-glass rounded-pill px-4"><i class="fas fa-external-link-alt me-2"></i> CRM</a>
        </div>
        ${data.recent.length === 0 ? '<p class="text-secondary text-center py-4">Aucune activité récente.</p>' : data.recent.map(act => `
        <div class="d-flex align-items-start gap-4 mb-4 pb-4 border-bottom border-white border-opacity-5">
            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:42px;height:42px;background:${act.type==='lead'?'rgba(251,191,36,.1)':'rgba(16,185,129,.1)'};">
                <i class="fas ${act.type==='lead'?'fa-envelope text-warning':'fa-rss text-success'}"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="text-white mb-0 fw-bold">${escHtml(act.name||'Inscrit')}</h6>
                    <span class="text-secondary smaller">${act.date}</span>
                </div>
                <p class="text-secondary small mb-1 opacity-75">${escHtml(act.email)}</p>
                <span class="badge rounded-pill px-2 py-1 smaller text-uppercase"
                      style="background:${act.type==='lead'?'rgba(251,191,36,.1)':'rgba(16,185,129,.1)'};color:${act.type==='lead'?'#fbbf24':'#10b981'};">${act.type}</span>
            </div>
        </div>`).join('')}
    </div>`;
    // Init chart after DOM is updated
    setTimeout(() => initGrowthChart(data.chart), 50);
}

function renderAdminUsers(data) {
    const el = document.getElementById('adminUsersInner');
    if (!el) return;
    const rows = data.users.map(u => `
        <tr class="align-middle">
            <td class="py-3">
                <div class="d-flex align-items-center gap-3">
                    <img src="/api/notion-avatar.php?id=${encodeURIComponent(u.id)}" alt="" loading="lazy"
                         style="width:34px;height:34px;border-radius:50%;object-fit:cover;background:#1a1a2e;border:1px solid rgba(255,255,255,.08);flex-shrink:0;">
                    <span class="text-white fw-medium">${escHtml(u.name)}</span>
                </div>
            </td>
            <td class="py-3 text-secondary small">${escHtml(u.email)}</td>
            <td class="py-3 text-secondary small opacity-75">${escHtml(u.company)}</td>
            <td class="py-3">
                <select class="form-select form-select-sm bg-transparent border-0 text-white small p-0 fw-bold" style="cursor:pointer;width:auto;"
                        onchange="updateUserRole('${u.id}', this.value)">
                    ${['Client','Entreprise','Particulier','Admin'].map(s =>
                        `<option value="${s}" class="bg-dark"${u.status===s?' selected':''}>${s}</option>`
                    ).join('')}
                </select>
            </td>
            <td class="py-3 text-end">
                <a href="mailto:${escHtml(u.email)}" class="btn btn-sm px-3 py-1 rounded-pill me-1 text-white" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);font-size:.75rem;"><i class="fas fa-envelope me-1"></i> Email</a>
                <a href="/admin-reset-pwd?email=${encodeURIComponent(u.email)}" class="btn btn-sm btn-outline-danger px-3 py-1 rounded-pill" style="font-size:.75rem;"><i class="fas fa-key me-1"></i> Reset</a>
            </td>
        </tr>`).join('');
    el.innerHTML = `
    <div class="bento-card p-4 p-md-5" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h3 class="h4 text-white fw-bold mb-0 d-flex align-items-center gap-3">
                <i class="fas fa-users-cog text-warning"></i> Utilisateurs
                <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning border border-warning border-opacity-20 px-3 py-1">${data.counts.users}</span>
            </h3>
            <div class="d-flex gap-2 align-items-center">
                <div class="position-relative">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary" style="font-size:.75rem;opacity:.5;"></i>
                    <input type="text" onkeyup="filterTable('usersTable',this.value)" class="form-control dash-input rounded-pill py-2 ps-5 pe-4" placeholder="Rechercher…" style="width:240px;font-size:.85rem;">
                </div>
                <button onclick="exportTableToCSV('usersTable','users_slapia')" class="btn btn-sm btn-outline-glass px-3 py-2 rounded-pill text-white" style="font-size:.8rem;border-color:rgba(255,255,255,.1);"><i class="fas fa-download me-1"></i> CSV</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table admin-table border-0 m-0" id="usersTable">
                <thead><tr style="border-bottom:2px solid rgba(255,255,255,.05)!important;">
                    <th class="py-3 border-0">Nom</th><th class="py-3 border-0">Email</th><th class="py-3 border-0">Entreprise</th><th class="py-3 border-0">Statut</th><th class="py-3 border-0 text-end">Actions</th>
                </tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    </div>`;
}

function renderAdminNewsletter(data) {
    const el = document.getElementById('adminNewsletterInner');
    if (!el) return;
    const rows = data.newsletter.map(n => `
        <tr class="align-middle">
            <td class="py-3 text-white">${escHtml(n.email)}</td>
            <td class="py-3 text-secondary small">${n.date}</td>
        </tr>`).join('');
    el.innerHTML = `
    <div class="bento-card p-4 p-md-5" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h3 class="h4 text-white fw-bold mb-0 d-flex align-items-center gap-3">
                <i class="fas fa-mail-bulk text-success"></i> Newsletter
                <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-3 py-1">${data.counts.newsletter}</span>
            </h3>
            <div class="d-flex gap-2 align-items-center">
                <div class="position-relative">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary" style="font-size:.75rem;opacity:.5;"></i>
                    <input type="text" onkeyup="filterTable('newsletterTable',this.value)" class="form-control dash-input rounded-pill py-2 ps-5 pe-4" placeholder="Filtrer…" style="width:220px;font-size:.8rem;">
                </div>
                <button onclick="exportTableToCSV('newsletterTable','newsletter_slapia')" class="btn btn-sm btn-outline-glass px-3 py-2 rounded-pill text-white" style="font-size:.8rem;border-color:rgba(255,255,255,.1);"><i class="fas fa-download me-1"></i> CSV</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table admin-table border-0 m-0" id="newsletterTable">
                <thead><tr style="border-bottom:2px solid rgba(255,255,255,.05)!important;">
                    <th class="py-3 border-0">Email</th><th class="py-3 border-0">Date d'inscription</th>
                </tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    </div>`;
}

function renderAdminAudit(data) {
    const el = document.getElementById('adminAuditInner');
    if (!el) return;
    const rows = data.recent.map(act => `
        <tr class="align-middle">
            <td class="py-3">
                <span class="badge rounded-pill px-3 py-1 smaller text-uppercase"
                      style="background:${act.type==='lead'?'rgba(59,130,246,.1)':'rgba(16,185,129,.1)'};color:${act.type==='lead'?'#60a5fa':'#34d399'};border:1px solid ${act.type==='lead'?'rgba(59,130,246,.2)':'rgba(16,185,129,.2)'};">
                    ${act.type.toUpperCase()}
                </span>
            </td>
            <td class="py-3">
                <div class="text-white small fw-medium">${escHtml(act.name)}</div>
                <div class="text-secondary smaller opacity-50">${escHtml(act.email)}</div>
            </td>
            <td class="py-3 text-secondary small">${act.date}</td>
            <td class="py-3 text-end"><i class="fas fa-check-circle text-success opacity-40"></i></td>
        </tr>`).join('');
    el.innerHTML = `
    <div class="bento-card p-4 p-md-5" style="background:rgba(15,15,15,.4);border:1px solid rgba(255,255,255,.05);border-radius:24px;">
        <h3 class="h4 text-white fw-bold mb-4 d-flex align-items-center gap-3"><i class="fas fa-history"></i> Journal d'Audit</h3>
        <div class="table-responsive">
            <table class="table admin-table border-0 m-0">
                <thead><tr style="border-bottom:2px solid rgba(255,255,255,.05)!important;">
                    <th class="py-3 border-0">Type</th><th class="py-3 border-0">Acteur / Cible</th><th class="py-3 border-0">Date</th><th class="py-3 border-0 text-end">Sync</th>
                </tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    </div>`;
}

function renderAdminPendingBilling(data) {
    const el = document.getElementById('adminPendingBilling');
    if (!el || data.pending.length === 0) return;
    const cards = data.pending.map(pb => `
        <div class="col-md-6 col-lg-4">
            <div class="d-flex align-items-center gap-3 p-3 rounded-4" style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.05);">
                <div class="rounded-circle overflow-hidden flex-shrink-0" style="width:44px;height:44px;background:#1a1a2e;">
                    <img src="/api/notion-avatar.php?id=${encodeURIComponent(pb.id)}" alt="" class="w-100 h-100" style="object-fit:cover;" loading="lazy">
                </div>
                <div class="overflow-hidden flex-grow-1">
                    <div class="text-white small fw-bold text-truncate">${escHtml(pb.name)}</div>
                    <div class="text-secondary smaller text-truncate">${escHtml(pb.email)}</div>
                    ${pb.docs.length ? '<div class="mt-1">' + pb.docs.map(f => `
                        <a href="${escHtml(f.url)}" target="_blank" class="badge bg-white bg-opacity-10 text-white text-decoration-none px-2 py-1 me-1 rounded-pill smaller">
                            <i class="fas fa-file-download me-1"></i>${escHtml(f.name)}
                        </a>`).join('') + '</div>' : ''}
                </div>
            </div>
        </div>`).join('');
    el.innerHTML = `
    <div class="bento-card p-4 mb-4" style="background:rgba(239,68,68,.05);border:1px solid rgba(239,68,68,.15);border-radius:24px;">
        <h3 class="h5 text-white fw-bold mb-4 d-flex align-items-center gap-2">
            <i class="fas fa-exclamation-circle text-danger"></i>
            File d'attente — Facturation en attente (${data.pending.length})
        </h3>
        <div class="row g-3">${cards}</div>
    </div>`;
}

function initGrowthChart(chart) {
    const el = document.getElementById('growthChart');
    if (!el || growthChartInstance || !chart) return;
    growthChartInstance = new Chart(el.getContext('2d'), {
        type: 'line',
        data: {
            labels: chart.labels,
            datasets: [{
                label: 'Utilisateurs', data: chart.users,
                borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.08)',
                fill: true, tension: .4, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#3b82f6'
            },{
                label: 'Newsletter', data: chart.news,
                borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.08)',
                fill: true, tension: .4, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#10b981'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: true, position: 'top', align: 'end',
                    labels: { color: 'rgba(255,255,255,.6)', font: { size: 11, weight: '600' }, boxWidth: 10, usePointStyle: true }
                },
                tooltip: { backgroundColor: 'rgba(10,10,10,.95)', titleColor: '#fff', bodyColor: 'rgba(255,255,255,.75)', borderColor: 'rgba(255,255,255,.1)', borderWidth: 1, padding: 12, cornerRadius: 12 }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.04)' }, ticks: { color: 'rgba(255,255,255,.35)', font: { size: 11 }, stepSize: 1 } },
                x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,.35)', font: { size: 11 } } }
            }
        }
    });
}

// ── Escape HTML helper ────────────────────────────────────────────────────────
function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

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

// ── Debounce helper ───────────────────────────────────────────────────────────
function debounce(fn, delay) {
    let timer;
    return function(...args) { clearTimeout(timer); timer = setTimeout(() => fn.apply(this, args), delay); };
}

// Profile form: debounce 300ms to avoid accidental double-submit
const profileForm = document.getElementById('profileForm');
if (profileForm) {
    const debouncedSave = debounce(() => handleUpdate('profileForm','btnSaveProfile','profileAlert'), 300);
    profileForm.addEventListener('submit', e => { e.preventDefault(); debouncedSave(); });
}

<?php if (!$isAdmin): ?>
document.getElementById('reviewForm')?.addEventListener('submit', e => { e.preventDefault(); handleUpdate('reviewForm','btnSaveReview','reviewAlert'); });
<?php endif; ?>

// ── PDF / Print invoices ──────────────────────────────────────────────────────
function printInvoices() {
    const section = document.querySelector('#content-billing .bento-card:last-child');
    if (!section) return;
    const win = window.open('', '_blank', 'width=800,height=600');
    win.document.write(`<!DOCTYPE html><html><head><title>Factures SlapIA</title>
    <style>
        body { font-family: sans-serif; background: #fff; color: #111; padding: 2rem; }
        h3 { margin-bottom: 1.5rem; }
        .invoice-row { display: flex; align-items: center; gap: 1rem; padding: .75rem 0; border-bottom: 1px solid #eee; }
        .invoice-row:last-child { border-bottom: none; }
        a { color: #3b82f6; }
        @media print { body { padding: 0; } }
    </style></head><body>`);
    win.document.write('<h3>Mes Factures — SlapIA</h3>');
    section.querySelectorAll('.invoice-card').forEach(card => {
        const name = card.querySelector('.text-white')?.textContent?.trim() || 'Document';
        const link = card.querySelector('a[href]')?.href || '#';
        win.document.write(`<div class="invoice-row"><span style="flex:1;">${name}</span><a href="${link}" target="_blank">Télécharger</a></div>`);
    });
    win.document.write('</body></html>');
    win.document.close();
    win.focus();
    setTimeout(() => win.print(), 300);
}

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

// ── On load ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    // Fetch notification count for sidebar badge
    fetch('/api/check-notifications.php')
        .then(r => r.json())
        .then(data => { if (data.success) updateSidebarBadge(data.unread_count || 0); });

    <?php if ($isAdmin): ?>
    // If an admin tab is active on load (via URL hash), trigger lazy load
    const hash = location.hash.replace('#','');
    if (ADMIN_TABS.includes(hash)) loadAdminData(hash);
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>
