<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

$page_title = t('dash_title') . ' - SlapIA';
$page_description = t('login_meta_desc');
include '../includes/header.php';
include '../includes/components.php';

// Auth Protection
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /login');
    exit;
}

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

// Fetch Invoices (Files)
$invoices = $props['Factures']['files'] ?? [];
?>

<style>
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
                <h1 class="display-5 fw-bold text-white mb-2"><?php echo t('dash_title'); ?></h1>
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
                        <button class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 rounded-4 d-flex align-items-center" id="tab-reviews" onclick="switchTab('reviews')">
                            <i class="fas fa-star me-3 text-primary"></i> <span class="fw-medium"><?php echo t('dash_tab_reviews'); ?></span>
                        </button>
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
                            <table class="table text-white border-white border-opacity-10" style="--bs-table-bg: transparent; --bs-table-color: white;">
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

                <!-- REVIEWS TAB -->
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

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn-primary-glow px-5 py-3 rounded-pill fw-bold border-0" id="btnSaveReview">
                                    <i class="fas fa-paper-plane me-2"></i> <?php echo t('dash_review_save'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

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
