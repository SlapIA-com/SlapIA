<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

// Auth Protection (MUST be before any output)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /login');
    exit;
}

$page_title = t('dash_title') . ' - SlapIA';
$page_description = t('login_meta_desc');
include '../includes/header.php';
include '../includes/components.php';

// Fetch Latest User Data from Notion Server-side
$notionApiKey = config('NOTION_API_KEY');
$userId = $_SESSION['user_id'];

$ch = curl_init('https://api.notion.com/v1/pages/' . $userId);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $notionApiKey,
        'Notion-Version: 2022-06-28'
    ]
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
unset($ch);

$userPage = json_decode($response, true);
$props = $userPage['properties'] ?? [];
$icon = $userPage['icon'] ?? null;

// Helper function to get profile image URL or emoji
$getUserIcon = function() use ($icon) {
    if (!$icon) return null;
    if ($icon['type'] === 'emoji') return $icon['emoji'];
    if ($icon['type'] === 'external') return $icon['external']['url'];
    if ($icon['type'] === 'file') return $icon['file']['url'];
    return null;
};

// Sync session with Notion photo/name
$_SESSION['user_name'] = $props['Prenom NOM']['title'][0]['text']['content'] ?? $_SESSION['user_name'];
$photoProperty = $props['Photo']['files'] ?? [];
$photoUrl = '';
if (count($photoProperty) > 0) {
    $photoUrl = $photoProperty[0]['file']['url'] ?? $photoProperty[0]['external']['url'] ?? '';
}

// Fallback to Icon if Photo property is empty
if (empty($photoUrl)) {
    if ($icon && ($icon['type'] === 'external' || $icon['type'] === 'file')) {
        $photoUrl = ($icon['type'] === 'external') ? $icon['external']['url'] : $icon['file']['url'];
    }
}
$_SESSION['user_photo'] = $photoUrl;

// Helper functions to safely extract data
$getUserName = fn() => $props['Prenom NOM']['title'][0]['text']['content'] ?? '';
$getUserEmail = fn() => $props['Email']['email'] ?? '';
$getUserPhone = fn() => $props['Téléphone']['phone_number'] ?? '';
$getUserCompany = fn() => $props['Nom d\'entreprise']['rich_text'][0]['text']['content'] ?? '';
$getUserJob = fn() => $props['Job']['rich_text'][0]['text']['content'] ?? '';
$getUserLocation = fn() => $props['Location']['rich_text'][0]['text']['content'] ?? '';
$getUserLinkedin = fn() => $props['Linkedin']['url'] ?? '';
$getFacturationStatus = fn() => $props['Facturation']['select']['name'] ?? 'Non disponible';
$getSatisfaction = fn() => $props['Satisfaction']['select']['name'] ?? '';
$getAvis = fn() => $props['Avis clients']['rich_text'][0]['text']['content'] ?? '';
$getUserStatus = fn() => $props['Status']['select']['name'] ?? $props['Status']['rich_text'][0]['text']['content'] ?? 'Client';

// Admin Property Helper
$getNotionProp = function($p) {
    if (!$p) return '';
    $type = $p['type'] ?? '';
    if (($type === 'title' || $type === 'rich_text') && !empty($p[$type])) {
        return $p[$type][0]['plain_text'] ?? '';
    }
    if ($type === 'email') return $p['email'] ?? '';
    if ($type === 'select') return $p['select']['name'] ?? '';
    return '';
};

// Fetch Invoices (Files)
$invoices = $props['Factures']['files'] ?? [];

// --- ADMIN LOGIC ---
$isAdmin = ($getUserStatus() === 'Admin');
$adminData = ['users' => 0, 'leads' => 0, 'newsletter' => 0, 'recent' => []];

if ($isAdmin) {
    // 1. Fetch KPI Counts (Simplification: just get the count of items in databases)
    $dbIds = [
        'users' => config('NOTION_SATISFACTION_DATABASE_ID'),
        'leads' => config('NOTION_CONTACT_DATABASE_ID'),
        'newsletter' => config('NOTION_Newsletter_DATABASE_ID')
    ];

    foreach ($dbIds as $key => $dbId) {
        if (!$dbId) continue;
        $ch = curl_init('https://api.notion.com/v1/databases/' . $dbId . '/query');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['page_size' => 1]), // We only need the total (if API provided it) but Notion doesn't return total count easily. We'll fetch more or handle as needed.
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $notionApiKey,
                'Notion-Version: 2022-06-28'
            ]
        ]);
        $res = curl_exec($ch);
        $data = json_decode($res, true);
        // Notion doesn't give a 'total' count in query results. For a real dashboard, we'd use a search or a specifically maintained counter.
        // For now, we'll fetch a small batch to show "Live" state.
        $adminData[$key] = count($data['results'] ?? []); 
    }

    // 2. Fetch Recent Activity (Latest Leads & Subscribers)
    $chRecent = curl_init('https://api.notion.com/v1/databases/' . config('NOTION_CONTACT_DATABASE_ID') . '/query');
    curl_setopt_array($chRecent, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'page_size' => 5,
            'sorts' => [['timestamp' => 'created_time', 'direction' => 'descending']]
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $notionApiKey,
            'Notion-Version: 2022-06-28',
            'Content-Type: application/json'
        ]
    ]);
    $resRecent = curl_exec($chRecent);
    $recentLeads = json_decode($resRecent, true)['results'] ?? [];
    foreach($recentLeads as $l) {
        $name = $l['properties']['Prenom NOM']['title'][0]['text']['content'] ?? 'Anonyme';
        $email = $l['properties']['Email']['email'] ?? ($l['properties']['Email']['email'] ?? '');
        $date = date('d/m H:i', strtotime($l['created_time']));
        $adminData['recent'][] = ['type' => 'lead', 'name' => $name, 'email' => $email, 'date' => $date, 'ts' => strtotime($l['created_time'])];
    }

    // 2.2 Recent Newsletter
    $chNews = curl_init('https://api.notion.com/v1/databases/' . config('NOTION_Newsletter_DATABASE_ID') . '/query');
    curl_setopt_array($chNews, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'page_size' => 5,
            'sorts' => [['timestamp' => 'created_time', 'direction' => 'descending']]
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $notionApiKey,
            'Notion-Version: 2022-06-28',
            'Content-Type: application/json'
        ]
    ]);
    $resNews = curl_exec($chNews);
    $recentNews = json_decode($resNews, true)['results'] ?? [];
    foreach($recentNews as $n) {
        $email = $n['properties']['Email']['title'][0]['text']['content'] ?? 'Newsletter';
        $date = date('d/m H:i', strtotime($n['created_time']));
        $adminData['recent'][] = ['type' => 'newsletter', 'name' => 'Subscriber', 'email' => $email, 'date' => $date, 'ts' => strtotime($n['created_time'])];
    }

    // Sort combined recent activity
    usort($adminData['recent'], fn($a, $b) => $b['ts'] <=> $a['ts']);
    $adminData['recent'] = array_slice($adminData['recent'], 0, 10);

    // 3. Fetch Full Admin Lists (Newsletter & Users)
    // 3.1 Newsletter List
    $chFullNews = curl_init('https://api.notion.com/v1/databases/' . config('NOTION_Newsletter_DATABASE_ID') . '/query');
    curl_setopt_array($chFullNews, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['page_size' => 100]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $notionApiKey,
            'Notion-Version: 2022-06-28',
            'Content-Type: application/json'
        ]
    ]);
    $resFullNews = curl_exec($chFullNews);
    $adminData['list_newsletter'] = json_decode($resFullNews, true)['results'] ?? [];

    // 3.2 Users List
    $chFullUsers = curl_init('https://api.notion.com/v1/databases/' . config('NOTION_SATISFACTION_DATABASE_ID') . '/query');
    curl_setopt_array($chFullUsers, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['page_size' => 100]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $notionApiKey,
            'Notion-Version: 2022-06-28',
            'Content-Type: application/json'
        ]
    ]);
    $resFullUsers = curl_exec($chFullUsers);
    $adminData['list_users'] = json_decode($resFullUsers, true)['results'] ?? [];

    // 3.3 Fetch Pending Billing Users
    $queryPending = [
        'filter' => [
            'property' => 'Facturation',
            'select' => [
                'equals' => 'En attente'
            ]
        ]
    ];
    $chPending = curl_init('https://api.notion.com/v1/databases/' . config('NOTION_SATISFACTION_DATABASE_ID') . '/query');
    curl_setopt_array($chPending, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($queryPending),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $notionApiKey,
            'Notion-Version: 2022-06-28',
            'Content-Type: application/json'
        ]
    ]);
    $resPending = curl_exec($chPending);
    $adminData['list_pending_billing'] = json_decode($resPending, true)['results'] ?? [];
}
?>

<style>
    .admin-table {
        --bs-table-bg: transparent !important;
        --bs-table-color: #fff !important;
    }
    .admin-table td, .admin-table th {
        background: transparent !important;
        color: inherit !important;
        border-bottom: 1px solid rgba(255,255,255,0.05) !important;
    }
    .admin-table thead th {
        color: #71717a !important;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.05em;
    }

    .pulse-green {
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 2s infinite;
    }
    @keyframes pulse-green {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

/* Dashboard Mobile Enhancements */
@media (max-width: 767px) {
    .dashboard-section {
        padding-top: 2rem !important;
    }
    .dashboard-section .row.align-items-center {
        text-align: center;
        flex-direction: column;
    }
    .dashboard-section .col-md-auto {
        margin-bottom: 20px;
    }
    .dashboard-section .col-md-auto div[style*="width: 140px"] {
        width: 100px !important;
        height: 100px !important;
        margin: 0 auto;
    }
    .dashboard-section .col-md-auto div[style*="font-size: 4.5rem"] {
        font-size: 3rem !important;
    }
    .dashboard-section .display-5 {
        font-size: 2rem;
    }
    
    /* Sidebar Tabs for Mobile */
    .dashboard-section .col-lg-3 .bento-card {
        margin-bottom: 20px;
    }
    .dashboard-section .list-group {
        flex-direction: row;
        overflow-x: auto;
        padding-bottom: 5px;
        scrollbar-width: none;
    }
    .dashboard-section .list-group::-webkit-scrollbar {
        display: none;
    }
    .dashboard-section .list-group-item {
        margin-bottom: 0 !important;
        margin-right: 8px;
        white-space: nowrap;
        padding: 10px 20px !important;
        background: rgba(255,255,255,0.03) !important;
    }
    .dashboard-section .list-group-item.active {
        background: var(--accent-purple) !important;
        border: 1px solid rgba(255,255,255,0.2) !important;
    }
    
    /* Form Adjustments */
    .bento-card.p-md-5 {
        padding: 25px !important;
    }
    .bento-card .h4 {
        font-size: 1.25rem;
        justify-content: center;
    }
    
    /* Billing Table */
    .table thead th {
        font-size: 0.7rem;
    }
    .table td {
        font-size: 0.85rem;
    }
}

/* Tab Content Transitions */
.tab-content.fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="grid-bg"></div>
<!-- Dashboard Layout -->
<section class="dashboard-section py-5 mt-4" style="min-height: 80vh;">
    <div class="container">
        
        <!-- Header: Large Avatar & Welcome -->
        <div class="row mb-5 align-items-center fade-in-up">
            <div class="col-md-auto mb-4 mb-md-0">
                <?php $userIcon = $getUserIcon(); ?>
                <?php if($userIcon): ?>
                    <div class="rounded-4 overflow-hidden border border-white border-opacity-10 shadow-lg" style="width: 140px; height: 140px; background: rgba(255,255,255,0.03);">
                        <?php if(strpos($userIcon, 'http') === 0): ?>
                            <img src="<?php echo $userIcon; ?>" alt="Profile" class="w-100 h-100" style="object-fit: cover;">
                        <?php else: ?>
                            <div class="w-100 h-100 d-flex align-items-center justify-content-center" style="font-size: 4.5rem;"><?php echo $userIcon; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md">
                <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
                    <h1 id="dash-title" class="display-5 fw-bold text-white mb-0"><?php echo t('dash_title'); ?></h1>
                    
                    <?php 
                    $status = $getUserStatus();
                    $statusIcon = 'fa-user';
                    $statusLabel = 'Espace Client';
                    $statusColor = 'rgba(255, 255, 255, 0.1)';
                    $statusText = '#fff';

                    if ($isAdmin) {
                        $statusIcon = 'fa-user-shield';
                        $statusLabel = 'Espace Admin';
                        $statusColor = 'rgba(239, 68, 68, 0.15)';
                        $statusText = '#ef4444';
                    } elseif (stripos($status, 'Entreprise') !== false) {
                        $statusIcon = 'fa-building';
                        $statusLabel = 'Espace Entreprise';
                        $statusColor = 'rgba(59, 130, 246, 0.15)';
                        $statusText = '#3b82f6';
                    } elseif (stripos($status, 'Particulier') !== false) {
                        $statusIcon = 'fa-user-tie';
                        $statusLabel = 'Espace Particulier';
                        $statusColor = 'rgba(168, 85, 247, 0.15)';
                        $statusText = '#a855f7';
                    }
                    ?>
                    <div class="badge rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2" 
                         style="background: <?php echo $statusColor; ?>; border: 1px solid rgba(255,255,255,0.1); color: <?php echo $statusText; ?>; font-size: 0.75rem; font-weight: 600; text-uppercase: uppercase; letter-spacing: 0.5px;">
                        <i class="fas <?php echo $statusIcon; ?>"></i> <?php echo $statusLabel; ?>
                    </div>
                </div>
                <p class="text-secondary lead mb-0" style="opacity: 0.8;"><?php echo t('dash_welcome'); ?>, <?php echo htmlspecialchars($_SESSION['user_name']); ?> !</p>
            </div>
            <div class="col-md-auto mt-4 mt-md-0">
                <a href="/api/auth-logout.php" data-no-swup class="btn btn-sm px-4 py-2 rounded-pill d-inline-flex align-items-center gap-2" 
                   style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; text-decoration: none; transition: all 0.3s ease;">
                    <i class="fas fa-sign-out-alt"></i> <?php echo t('dash_logout'); ?>
                </a>
            </div>
        </div>

        <div class="row g-4">
            
            <!-- Sidebar Navigation -->
            <div class="col-lg-3 fade-in-up delay-100">
                <div class="bento-card p-3 w-100 h-auto" style="background: rgba(15, 15, 15, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px;">
                    <div class="list-group list-group-flush bg-transparent">
                        <button class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 rounded-4 mb-2 active d-flex align-items-center" id="tab-profile" onclick="switchTab('profile')">
                            <i class="fas fa-user-circle me-3 text-primary"></i> <span class="fw-medium"><?php echo t('dash_tab_profile'); ?></span>
                        </button>
                        <button class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 rounded-4 mb-2 d-flex align-items-center" id="tab-billing" onclick="switchTab('billing')">
                            <i class="fas fa-file-invoice-dollar me-3 text-primary"></i> <span class="fw-medium"><?php echo t('dash_tab_billing'); ?></span>
                        </button>
                        <?php if($isAdmin): ?>
                            <button class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 rounded-4 mb-2 d-flex align-items-center" id="tab-admin" onclick="switchTab('admin')">
                                <i class="fas fa-shield-alt me-3 text-danger"></i> <span class="fw-medium"><?php echo t('dash_tab_admin'); ?></span>
                            </button>
                            <button class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 rounded-4 mb-2 d-flex align-items-center" id="tab-admin-newsletter" onclick="switchTab('admin-newsletter')">
                                <i class="fas fa-mail-bulk me-3 text-success"></i> <span class="fw-medium"><?php echo 'List ' . t('admin_total_subscribers'); ?></span>
                            </button>
                            <button class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 rounded-4 d-flex align-items-center" id="tab-admin-emails" onclick="switchTab('admin-emails')">
                                <i class="fas fa-users-cog me-3 text-warning"></i> <span class="fw-medium"><?php echo 'List ' . t('admin_total_users'); ?></span>
                            </button>
                        <?php else: ?>
                            <button class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 rounded-4 d-flex align-items-center" id="tab-reviews" onclick="switchTab('reviews')">
                                <i class="fas fa-star me-3 text-primary"></i> <span class="fw-medium"><?php echo t('dash_tab_reviews'); ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="col-lg-9 fade-in-up delay-200">
                
                <!-- PROFILE TAB -->
                <div id="content-profile" class="tab-content fade-in">
                    <div class="bento-card p-4 p-md-5" style="background: rgba(15, 15, 15, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px;">
                        <h3 class="h4 text-white fw-bold mb-5 d-flex align-items-center">
                            <i class="fas fa-id-card me-3 text-primary"></i> <?php echo t('dash_profile_info'); ?>
                        </h3>
                        
                        <form id="profileForm">
                            <div id="profileAlert" class="alert d-none small py-3 mb-4 rounded-4 fade-in" role="alert"></div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-50 small fw-bold text-uppercase mb-2"><?php echo t('dash_full_name'); ?></label>
                                    <input type="text" class="form-control bg-dark border-white border-opacity-10 text-white p-3 rounded-3" name="name" value="<?php echo htmlspecialchars($getUserName()); ?>" required style="background: rgba(255,255,255,0.05) !important; color: white !important;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-50 small fw-bold text-uppercase mb-2"><?php echo t('dash_email_readonly'); ?></label>
                                    <input type="email" class="form-control bg-dark border-white border-opacity-10 text-white p-3 rounded-3" value="<?php echo htmlspecialchars($getUserEmail()); ?>" disabled style="background: rgba(255,255,255,0.03) !important; color: rgba(255,255,255,0.6) !important; cursor: not-allowed; border-color: rgba(255,255,255,0.05) !important;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-50 small fw-bold text-uppercase mb-2"><?php echo t('dash_new_password'); ?></label>
                                    <input type="password" class="form-control bg-dark border-white border-opacity-10 text-white p-3 rounded-3" name="password" placeholder="<?php echo t('dash_password_hint'); ?>" style="background: rgba(255,255,255,0.05) !important; color: white !important;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-50 small fw-bold text-uppercase mb-2"><?php echo t('dash_phone'); ?></label>
                                    <input type="text" class="form-control bg-dark border-white border-opacity-10 text-white p-3 rounded-3" name="phone" value="<?php echo htmlspecialchars($getUserPhone()); ?>" style="background: rgba(255,255,255,0.05) !important; color: white !important;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-50 small fw-bold text-uppercase mb-2"><?php echo t('dash_linkedin_link'); ?></label>
                                    <input type="url" class="form-control bg-dark border-white border-opacity-10 text-white p-3 rounded-3" name="linkedin" value="<?php echo htmlspecialchars($getUserLinkedin()); ?>" style="background: rgba(255,255,255,0.05) !important; color: white !important;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-50 small fw-bold text-uppercase mb-2"><?php echo t('dash_location_city'); ?></label>
                                    <input type="text" class="form-control bg-dark border-white border-opacity-10 text-white p-3 rounded-3" name="location" value="<?php echo htmlspecialchars($getUserLocation()); ?>" style="background: rgba(255,255,255,0.05) !important; color: white !important;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-50 small fw-bold text-uppercase mb-2"><?php echo t('dash_company'); ?></label>
                                    <input type="text" class="form-control bg-dark border-white border-opacity-10 text-white p-3 rounded-3" name="company" value="<?php echo htmlspecialchars($getUserCompany()); ?>" style="background: rgba(255,255,255,0.05) !important; color: white !important;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-50 small fw-bold text-uppercase mb-2"><?php echo t('dash_job_title'); ?></label>
                                    <input type="text" class="form-control bg-dark border-white border-opacity-10 text-white p-3 rounded-3" name="job" value="<?php echo htmlspecialchars($getUserJob()); ?>" style="background: rgba(255,255,255,0.05) !important; color: white !important;">
                                </div>
                            </div>

                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <div class="d-flex justify-content-end mt-5">
                                <button type="submit" class="btn-primary-glow px-4 py-3 rounded-pill fw-bold border-0" id="btnSaveProfile">
                                    <i class="fas fa-save me-2"></i> <?php echo t('dash_update_btn'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- BILLING TAB -->
                <div id="content-billing" class="tab-content d-none fade-in">
                    <?php if ($isAdmin && !empty($adminData['list_pending_billing'])): ?>
                        <div class="bento-card p-4 p-md-5 mb-4" style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.1); border-radius: 24px;">
                            <h3 class="h4 text-white fw-bold mb-4 d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-3 text-danger"></i> File d'attente Facturation (Admin)
                            </h3>
                            <div class="row g-3">
                                <?php foreach($adminData['list_pending_billing'] as $pb): 
                                    $pbProps = $pb['properties'] ?? [];
                                    $pbName = $getNotionProp($pbProps['Prenom NOM']) ?: 'N.A';
                                    $pbEmail = $getNotionProp($pbProps['Email']);
                                    $pbPhoto = '';
                                    $pbPhotoProp = $pbProps['Photo']['files'] ?? [];
                                    if (count($pbPhotoProp) > 0) {
                                        $pbPhoto = $pbPhotoProp[0]['file']['url'] ?? $pbPhotoProp[0]['external']['url'] ?? '';
                                    }
                                    if (empty($pbPhoto) && isset($pb['icon'])) {
                                        $pIcon = $pb['icon'];
                                        if ($pIcon['type'] !== 'emoji') {
                                            $pbPhoto = $pIcon[$pIcon['type']]['url'] ?? '';
                                        }
                                    }
                                ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="d-flex align-items-center p-3 rounded-4" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                            <div class="rounded-circle me-3 overflow-hidden" style="width: 48px; height: 48px; min-width: 48px; background: #333;">
                                                <?php if($pbPhoto): ?>
                                                    <img src="<?php echo $pbPhoto; ?>" alt="" class="w-100 h-100 object-fit-cover">
                                                <?php else: ?>
                                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-white-50">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="overflow-hidden">
                                                <h6 class="text-white mb-0 text-truncate"><?php echo htmlspecialchars($pbName); ?></h6>
                                                <p class="text-secondary small mb-0 text-truncate opacity-75"><?php echo htmlspecialchars($pbEmail); ?></p>
                                                <?php 
                                                $pbInvoices = $pbProps['Factures']['files'] ?? [];
                                                if (!empty($pbInvoices)): ?>
                                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                                        <?php foreach($pbInvoices as $f): 
                                                            $fUrl = $f['file']['url'] ?? $f['external']['url'] ?? '#';
                                                            $fName = $f['name'] ?? 'Doc';
                                                        ?>
                                                            <a href="<?php echo $fUrl; ?>" target="_blank" class="btn btn-sm btn-outline-glass px-2 py-1 rounded-pill" style="font-size: 10px; border: 1px solid rgba(255,255,255,0.1);">
                                                                <i class="fas fa-file-download me-1"></i> <?php echo htmlspecialchars($fName); ?>
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

                    <div class="bento-card p-4 p-md-5" style="background: rgba(15, 15, 15, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px;">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h3 class="h4 text-white fw-bold mb-1"><?php echo t('dash_billing_history'); ?></h3>
                                <p class="text-secondary small mb-0"><?php echo t('dash_billing_subtitle'); ?></p>
                            </div>
                            <div class="badge rounded-pill px-3 py-2 border border-white border-opacity-10" style="background: rgba(255,255,255,0.03); color: #a1a1aa;">
                                <span class="opacity-75"><?php echo t('dash_table_status'); ?>:</span> <span class="text-white fw-bold ms-1"><?php echo htmlspecialchars($getFacturationStatus()); ?></span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table admin-table m-0">
                                <thead>
                                    <tr class="text-secondary small text-uppercase" style="border-bottom: 2px solid rgba(255,255,255,0.05) !important;">
                                        <th class="py-3" style="color: #71717a; border: 0;"><?php echo t('dash_table_doc'); ?></th>
                                        <th class="py-3" style="color: #71717a; border: 0;"><?php echo t('dash_table_date'); ?></th>
                                        <th class="py-3 text-end" style="color: #71717a; border: 0;"><?php echo t('dash_table_actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($invoices)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-secondary py-5 border-0">
                                                <i class="fas fa-folder-open mb-3 d-block" style="font-size: 2.5rem; opacity: 0.3;"></i>
                                                <?php echo t('dash_no_documents'); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($invoices as $inv): ?>
                                            <tr class="align-middle" style="border-bottom: 1px solid rgba(255,255,255,0.05) !important;">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="far fa-file-pdf text-danger fs-4 me-3"></i>
                                                        <span class="text-white fw-medium"><?php echo htmlspecialchars($inv['name']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="py-3 text-secondary opacity-75"><?php echo date('d/m/Y'); ?></td>
                                                <td class="py-3 text-end">
                                                    <a href="<?php echo $inv['file']['url'] ?? $inv['external']['url'] ?? '#'; ?>" target="_blank" class="btn btn-outline-glass btn-sm px-3 rounded-pill text-white" style="border-color: rgba(255,255,255,0.15);">
                                                        <i class="fas fa-download me-1"></i> <?php echo t('dash_download'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- REVIEWS TAB (Only for customers) -->
                <?php if(!$isAdmin): ?>
                <div id="content-reviews" class="tab-content d-none fade-in">
                    <div class="bento-card p-4 p-md-5" style="background: rgba(15, 15, 15, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px;">
                        <h3 class="h4 text-white fw-bold mb-4 d-flex align-items-center">
                            <i class="fas fa-star me-3 text-warning"></i> <?php echo t('dash_review_title'); ?>
                        </h3>

                        <form id="reviewForm">
                            <div id="reviewAlert" class="alert d-none small py-3 mb-4 rounded-4 fade-in" role="alert"></div>

                            <div class="p-4 rounded-4 mb-4 text-center" style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1);">
                                <p class="text-secondary small mb-3 text-uppercase fw-bold"><?php echo t('dash_satisfaction_note'); ?></p>
                                <div class="d-flex justify-content-center gap-3 mb-2">
                                    <?php 
                                    $currentStars = 0;
                                    $sat = (string)($props['Satisfaction']['select']['name'] ?? '');
                                    // Robust star counting from Select (e.g. "⭐⭐⭐⭐⭐" or "5 étoiles" or "5/5")
                                    if(preg_match('/[1-5]/', $sat, $matches)) {
                                        $currentStars = (int)$matches[0];
                                    } elseif(strpos($sat, '⭐') !== false) {
                                        $currentStars = mb_substr_count($sat, '⭐');
                                    }
                                    ?>
                                    <input type="hidden" name="satisfaction" id="satisfactionInput" value="<?php echo str_repeat('⭐', $currentStars); ?>">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="<?php echo ($i <= $currentStars ? 'fas' : 'far'); ?> fa-star text-warning fa-2x cursor-pointer star-item" id="star-<?php echo $i; ?>" onclick="setStars(<?php echo $i; ?>)"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="text-warning small" id="starText"><?php echo $currentStars ? $currentStars.'/5' : ''; ?></div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase mb-3"><?php echo t('dash_review_label'); ?></label>
                                <textarea class="form-control bg-white bg-opacity-5 border-white border-opacity-10 text-white p-4 rounded-4" name="avis" rows="12" placeholder="<?php echo t('dash_review_placeholder'); ?>" style="background: rgba(255,255,255,0.03) !important; min-height: 250px; line-height: 1.6;"><?php echo htmlspecialchars($getAvis()); ?></textarea>
                            </div>

                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn-primary-glow px-5 py-3 rounded-pill fw-bold border-0" id="btnSaveReview">
                                    <i class="fas fa-paper-plane me-2"></i> <?php echo t('dash_review_save'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ADMIN CONTROL TAB -->
                <?php if($isAdmin): ?>
                <div id="content-admin" class="tab-content d-none fade-in">
                    <!-- Admin Hub: Status & Quick Actions -->
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="bento-card p-4" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px;">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="pulse-green"></div>
                                            <span class="text-white small fw-bold text-uppercase opacity-75">Notion API:</span>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-3 py-1 rounded-pill">Connecté</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-database text-primary small"></i>
                                            <span class="text-white small fw-bold text-uppercase opacity-75">DB:</span>
                                            <span class="text-white small fw-medium">CRM-SlapIA</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 border-start border-white border-opacity-10 ps-4">
                                            <i class="fas fa-clock text-secondary small"></i>
                                            <span class="text-secondary small">Dernière synchro : <?php echo date('H:i:s'); ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="/admin-reset-pwd" class="btn btn-sm btn-outline-danger px-4 py-2 rounded-pill">
                                            <i class="fas fa-key me-2"></i> Reset Tool
                                        </a>
                                        <a href="https://www.notion.so" target="_blank" class="btn btn-sm btn-outline-glass px-4 py-2 rounded-pill">
                                            <i class="fas fa-external-link-alt me-2"></i> Notion CRM
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <!-- KPI Card: Users -->
                        <div class="col-md-4">
                            <div class="bento-card p-4 h-100" style="background: linear-gradient(135deg, rgba(88, 86, 214, 0.1), rgba(15, 15, 15, 0.4)); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px;">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-3 p-2 me-3" style="background: rgba(88, 86, 214, 0.2);"><i class="fas fa-users text-primary"></i></div>
                                    <span class="text-secondary small fw-bold text-uppercase"><?php echo t('admin_total_users'); ?></span>
                                </div>
                                <h2 class="text-white mb-0"><?php echo count($adminData['list_users']); ?></h2>
                            </div>
                        </div>
                        <!-- KPI Card: Leads -->
                        <div class="col-md-4">
                            <div class="bento-card p-4 h-100" style="background: linear-gradient(135deg, rgba(255, 149, 0, 0.1), rgba(15, 15, 15, 0.4)); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px;">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-3 p-2 me-3" style="background: rgba(255, 149, 0, 0.2);"><i class="fas fa-bolt text-warning"></i></div>
                                    <span class="text-secondary small fw-bold text-uppercase"><?php echo t('admin_total_leads'); ?></span>
                                </div>
                                <h2 class="text-white mb-0"><?php echo $adminData['leads']; ?></h2>
                            </div>
                        </div>
                        <!-- KPI Card: Newsletter -->
                        <div class="col-md-4">
                            <div class="bento-card p-4 h-100" style="background: linear-gradient(135deg, rgba(52, 199, 89, 0.1), rgba(15, 15, 15, 0.4)); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px;">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-3 p-2 me-3" style="background: rgba(52, 199, 89, 0.2);"><i class="fas fa-paper-plane text-success"></i></div>
                                    <span class="text-secondary small fw-bold text-uppercase"><?php echo t('admin_total_subscribers'); ?></span>
                                </div>
                                <h2 class="text-white mb-0"><?php echo count($adminData['list_newsletter']); ?></h2>
                            </div>
                        </div>
                    </div>

                    <div class="bento-card p-4 p-md-5" style="background: rgba(15, 15, 15, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px;">
                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <h3 class="h4 text-white fw-bold mb-0">
                                <i class="fas fa-history me-3 text-primary"></i> <?php echo t('admin_recent_activity'); ?>
                            </h3>
                            <a href="https://www.notion.so" target="_blank" class="btn btn-sm btn-outline-glass rounded-pill px-4">
                                <i class="fas fa-external-link-alt me-2"></i> <?php echo t('admin_view_crm'); ?>
                            </a>
                        </div>

                        <div class="activity-feed">
                            <?php if(empty($adminData['recent'])): ?>
                                <p class="text-secondary py-4 text-center">Aucune activité récente détectée.</p>
                            <?php else: ?>
                                <?php foreach($adminData['recent'] as $act): ?>
                                    <div class="d-flex align-items-start mb-4 pb-4 border-bottom border-white border-opacity-5">
                                        <div class="rounded-circle p-2 me-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                            <?php if($act['type'] === 'lead'): ?>
                                                <i class="fas fa-envelope text-warning"></i>
                                            <?php else: ?>
                                                <i class="fas fa-rss text-success"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between mb-1">
                                                <h6 class="text-white mb-0 fw-bold"><?php echo htmlspecialchars($act['name'] ?: 'Inscrit'); ?></h6>
                                                <span class="text-secondary x-small"><?php echo $act['date']; ?></span>
                                            </div>
                                            <p class="text-secondary small mb-0 opacity-75"><?php echo htmlspecialchars($act['email']); ?></p>
                                            <span class="badge bg-opacity-10 text-uppercase x-small mt-2 <?php echo $act['type'] === 'lead' ? 'bg-warning text-warning' : 'bg-success text-success'; ?>" style="font-size: 10px;">
                                                <?php echo $act['type']; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="mt-5 pt-4 border-top border-white border-opacity-10">
                            <h5 class="text-white small fw-bold text-uppercase mb-4"><i class="fas fa-tools me-2 opacity-50"></i> Configuration & Maintenance</h5>
                            <div class="d-flex flex-wrap gap-3">
                                <button class="btn btn-sm px-4 py-2 rounded-pill" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                                    Rénitialiser le Cache
                                </button>
                                <button class="btn btn-sm px-4 py-2 rounded-pill" style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444;" onclick="switchTab('admin-emails')">
                                    Réinitialiser un mot de passe
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ADMIN NEWSLETTER LIST TAB -->
                <div id="content-admin-newsletter" class="tab-content d-none fade-in">
                    <div class="bento-card p-4 p-md-5" style="background: rgba(15, 15, 15, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px;">
                        <h3 class="h4 text-white fw-bold mb-4 d-flex align-items-center">
                            <i class="fas fa-mail-bulk me-3 text-success"></i> <?php echo t('admin_total_subscribers'); ?>
                        </h3>
                        <div class="table-responsive">
                            <table class="table admin-table border-0 m-0">
                                <thead>
                                    <tr class="text-secondary small text-uppercase" style="border-bottom: 2px solid rgba(255,255,255,0.05) !important;">
                                        <th class="py-3 border-0">Email</th>
                                        <th class="py-3 border-0">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($adminData['list_newsletter'] as $n): ?>
                                        <tr class="align-middle" style="border-bottom: 1px solid rgba(255,255,255,0.05) !important;">
                                            <td class="py-3 text-white"><?php echo htmlspecialchars($getNotionProp($n['properties']['Email'])); ?></td>
                                            <td class="py-3 text-secondary small"><?php echo date('d/m/Y H:i', strtotime($n['created_time'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ADMIN EMAILS LIST TAB -->
                <div id="content-admin-emails" class="tab-content d-none fade-in">
                    <div class="bento-card p-4 p-md-5" style="background: rgba(15, 15, 15, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px;">
                        <h3 class="h4 text-white fw-bold mb-4 d-flex align-items-center">
                            <i class="fas fa-users-cog me-3 text-warning"></i> <?php echo t('admin_total_users'); ?>
                        </h3>
                        <div class="table-responsive">
                            <table class="table admin-table border-0 m-0">
                                <thead>
                                    <tr class="text-secondary small text-uppercase" style="border-bottom: 2px solid rgba(255,255,255,0.05) !important;">
                                        <th class="py-3 border-0">Nom</th>
                                        <th class="py-3 border-0">Email</th>
                                        <th class="py-3 border-0">Entreprise</th>
                                        <th class="py-3 border-0 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($adminData['list_users'] as $u): ?>
                                        <tr class="align-middle" style="border-bottom: 1px solid rgba(255,255,255,0.05) !important;">
                                            <td class="py-3 text-white fw-medium"><?php echo htmlspecialchars($getNotionProp($u['properties']['Prenom NOM']) ?: 'N.A'); ?></td>
                                            <td class="py-3 text-secondary"><?php echo htmlspecialchars($getNotionProp($u['properties']['Email'])); ?></td>
                                            <td class="py-3 small text-secondary opacity-75"><?php echo htmlspecialchars($getNotionProp($u['properties']['Nom d\'entreprise'])); ?></td>
                                            <td class="py-3 text-end">
                                                <a href="mailto:<?php echo htmlspecialchars($getNotionProp($u['properties']['Email'])); ?>" class="btn btn-sm btn-outline-glass px-3 py-1 rounded-pill me-2" style="font-size: 11px; border: 1px solid rgba(255,255,255,0.1);">
                                                    <i class="fas fa-envelope me-1"></i> Email
                                                </a>
                                                <a href="/admin-reset-pwd?email=<?php echo urlencode($getNotionProp($u['properties']['Email'])); ?>" class="btn btn-sm btn-outline-danger px-3 py-1 rounded-pill" style="font-size: 11px;">
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
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<script>
// Tab Switching functionality
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('d-none'));
    document.querySelectorAll('.list-group-item').forEach(b => b.classList.remove('active'));
    
    document.getElementById('content-' + tabId).classList.remove('d-none');
    document.getElementById('tab-' + tabId).classList.add('active');

    // Dynamic Title
    const titleMap = {
        'profile': '<?php echo t('dash_tab_profile'); ?>',
        'billing': '<?php echo t('dash_tab_billing'); ?>',
        'reviews': '<?php echo t('dash_tab_reviews'); ?>',
        'admin': 'Administration SlapIA',
        'admin-newsletter': 'Liste Newsletter',
        'admin-emails': 'Gestion Utilisateurs'
    };
    if(titleMap[tabId]) document.getElementById('dash-title').textContent = titleMap[tabId];
}

function setStars(count) {
    const input = document.getElementById('satisfactionInput');
    const starText = document.getElementById('starText');
    const stars = '⭐'.repeat(count);
    input.value = stars;
    if(starText) starText.textContent = count + '/5';
    
    for(let i=1; i<=5; i++) {
        const star = document.getElementById('star-' + i);
        if(i <= count) {
            star.classList.replace('far', 'fas');
        } else {
            star.classList.replace('fas', 'far');
        }
    }
}

// Generic update function
async function handleUpdate(formId, btnId, alertId) {
    const form = document.getElementById(formId);
    const btn = document.getElementById(btnId);
    const alertBox = document.getElementById(alertId);
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?php echo t('dash_saving'); ?>';
    btn.disabled = true;
    alertBox.classList.add('d-none');
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('/api/auth-update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alertBox.className = 'alert alert-success small py-2';
            alertBox.textContent = result.message || '<?php echo t('dash_update_success_default'); ?>';
            alertBox.classList.remove('d-none');
            // Reload page if name changed to update header
            if(data.name) setTimeout(() => location.reload(), 1500);
        } else {
            alertBox.className = 'alert alert-danger small py-2';
            alertBox.textContent = result.error || '<?php echo t('dash_update_error_default'); ?>';
            alertBox.classList.remove('d-none');
        }
    } catch (err) {
        alertBox.className = 'alert alert-danger small py-2';
        alertBox.textContent = '<?php echo t('login_error_server'); ?>';
        alertBox.classList.remove('d-none');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
        if(alertBox.classList.contains('alert-success')) {
            setTimeout(() => {
                alertBox.classList.add('fade');
                setTimeout(() => alertBox.classList.add('d-none'), 150);
            }, 3000);
        }
    }
}

document.getElementById('profileForm').addEventListener('submit', e => {
    e.preventDefault();
    handleUpdate('profileForm', 'btnSaveProfile', 'profileAlert');
});

document.getElementById('reviewForm').addEventListener('submit', e => {
    e.preventDefault();
    handleUpdate('reviewForm', 'btnSaveReview', 'reviewAlert');
});
</script>



<?php include '../includes/footer.php'; ?>
