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

// Helper functions to safely extract data
$getUserName = fn() => $props['Prenom NOM']['title'][0]['text']['content'] ?? '';
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

<div class="grid-bg"></div>

<!-- Dashboard Layout -->
<section class="py-5" style="min-height: 80vh;">
    <div class="container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-5 fade-in-up">
            <div class="d-flex align-items-center">
                <?php $userIcon = $getUserIcon(); ?>
                <?php if($userIcon): ?>
                    <div class="profile-pic-container me-3">
                        <?php if(strpos($userIcon, 'http') === 0): ?>
                            <img src="<?php echo $userIcon; ?>" alt="Profile" class="profile-pic-img">
                        <?php else: ?>
                            <div class="profile-pic-emoji"><?php echo $userIcon; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div>
                    <h1 class="h2 text-white fw-bold mb-1"><?php echo t('dash_title'); ?></h1>
                    <p class="text-secondary mb-0"><?php echo t('dash_welcome'); ?>, <?php echo htmlspecialchars($_SESSION['user_name']); ?> !</p>
                </div>
            </div>
            <div>
                <a href="/api/auth-logout.php" class="btn btn-outline-danger btn-sm px-3 rounded-pill" style="border-color: rgba(220,53,69,0.5);">
                    <i class="fas fa-sign-out-alt me-1"></i> <?php echo t('dash_logout'); ?>
                </a>
            </div>
        </div>

        <div class="row g-4">
            
            <!-- Sidebar Navigation -->
            <div class="col-lg-3 fade-in-up delay-100">
                <div class="bento-card bento-card-glow p-3 w-100 h-auto position-relative sticky-top" style="top: 120px;">
                    <div class="list-group list-group-flush bg-transparent">
                        <button class="list-group-item list-group-item-action bg-transparent text-white border-bottom border-secondary border-opacity-25 py-3 active" id="tab-profile" onclick="switchTab('profile')">
                            <i class="fas fa-user-circle me-2 text-primary"></i> <?php echo t('dash_tab_profile'); ?>
                        </button>
                        <button class="list-group-item list-group-item-action bg-transparent text-white border-bottom border-secondary border-opacity-25 py-3" id="tab-billing" onclick="switchTab('billing')">
                            <i class="fas fa-file-invoice-dollar me-2 text-primary"></i> <?php echo t('dash_tab_billing'); ?>
                        </button>
                        <button class="list-group-item list-group-item-action bg-transparent text-white py-3" id="tab-reviews" onclick="switchTab('reviews')">
                            <i class="fas fa-star me-2 text-primary"></i> <?php echo t('dash_tab_reviews'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="col-lg-9 fade-in-up delay-200">
                
                <!-- PROFILE TAB -->
                <div id="content-profile" class="tab-content fade-in">
                    <div class="bento-card bento-card-glow p-4 p-md-5">
                        <h3 class="h4 text-white fw-bold mb-4 d-flex align-items-center">
                            <i class="fas fa-id-card me-3 text-primary"></i> <?php echo t('dash_profile_info'); ?>
                        </h3>
                        
                        <form id="profileForm">
                            <div id="profileAlert" class="alert alert-success d-none small py-2 fade-in" role="alert"></div>

                            <div class="row g-3">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-white opacity-75 small"><?php echo t('dash_full_name'); ?></label>
                                    <input type="text" class="form-control bg-dark border-secondary text-white" name="name" value="<?php echo htmlspecialchars($getUserName()); ?>" required placeholder="Prénom NOM">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-white opacity-75 small"><?php echo t('dash_email_readonly'); ?></label>
                                    <input type="email" class="form-control bg-dark border-secondary text-white" value="<?php echo htmlspecialchars($getUserEmail()); ?>" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-white opacity-75 small"><?php echo t('dash_new_password'); ?> (<?php echo t('dash_password_hint'); ?>)</label>
                                    <input type="password" class="form-control bg-dark border-secondary text-white" name="password" placeholder="••••••••">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-white opacity-75 small"><?php echo t('dash_phone'); ?></label>
                                    <input type="text" class="form-control bg-dark border-secondary text-white" name="phone" value="<?php echo htmlspecialchars($getUserPhone()); ?>" placeholder="+33 6 00 00 00 00">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-white opacity-75 small"><?php echo t('dash_linkedin_link'); ?></label>
                                    <input type="url" class="form-control bg-dark border-secondary text-white" name="linkedin" value="<?php echo htmlspecialchars($getUserLinkedin()); ?>" placeholder="https://linkedin.com/in/...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-white opacity-75 small"><?php echo t('dash_location_city'); ?></label>
                                    <input type="text" class="form-control bg-dark border-secondary text-white" name="location" value="<?php echo htmlspecialchars($getUserLocation()); ?>" placeholder="Paris, France">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-white opacity-75 small"><?php echo t('dash_company'); ?></label>
                                    <input type="text" class="form-control bg-dark border-secondary text-white" name="company" value="<?php echo htmlspecialchars($getUserCompany()); ?>" placeholder="Ma Super Entreprise">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-white opacity-75 small"><?php echo t('dash_job_title'); ?></label>
                                    <input type="text" class="form-control bg-dark border-secondary text-white" name="job" value="<?php echo htmlspecialchars($getUserJob()); ?>" placeholder="CEO / Consultant">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary btn-primary-glow px-4 py-2 rounded-pill fw-bold" id="btnSaveProfile">
                                    <?php echo t('dash_update_btn'); ?> <i class="fas fa-save ms-2"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- BILLING TAB -->
                <div id="content-billing" class="tab-content d-none fade-in">
                    <div class="bento-card bento-card-glow p-4 p-md-5">
                        <h3 class="h4 text-white fw-bold mb-1"><?php echo t('dash_billing_history'); ?></h3>
                        <p class="text-secondary small mb-4"><?php echo t('dash_billing_subtitle'); ?></p>

                        <div class="table-responsive">
                            <table class="table text-white border-secondary border-opacity-10">
                                <thead>
                                    <tr class="text-secondary small">
                                        <th class="border-0"><?php echo t('dash_table_status'); ?></th>
                                        <th class="border-0"><?php echo t('dash_table_doc'); ?></th>
                                        <th class="border-0"><?php echo t('dash_table_date'); ?></th>
                                        <th class="border-0 text-end"><?php echo t('dash_table_actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($formattedInvoices)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-secondary py-4">
                                                <i class="fas fa-folder-open mb-2 fs-4"></i><br>
                                                <?php echo t('dash_no_documents'); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($formattedInvoices as $f): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                        $status = $f['status'];
                                                        $statusClass = 'text-secondary';
                                                        $statusIcon = 'fa-info-circle';
                                                        $badgeColor = '#71717a';
                                                        
                                                        switch(mb_strtolower($status)) {
                                                            case 'payé':
                                                                $statusClass = 'text-success';
                                                                $statusIcon = 'fa-check-circle';
                                                                $badgeColor = '#10b981';
                                                                break;
                                                            case 'facturé':
                                                                $statusClass = 'text-info';
                                                                $statusIcon = 'fa-file-invoice';
                                                                $badgeColor = '#06b6d4';
                                                                break;
                                                            case 'en cours':
                                                                $statusClass = 'text-primary';
                                                                $statusIcon = 'fa-spinner fa-spin';
                                                                $badgeColor = '#3b82f6';
                                                                break;
                                                            case 'en attente':
                                                                $statusClass = 'text-warning';
                                                                $statusIcon = 'fa-clock';
                                                                $badgeColor = '#f59e0b';
                                                                break;
                                                            case 'dispensé':
                                                                $statusClass = 'text-muted';
                                                                $statusIcon = 'fa-minus-circle';
                                                                $badgeColor = '#71717a';
                                                                break;
                                                        }
                                                    ?>
                                                    <span class="badge rounded-pill px-3 py-2" style="background-color: <?php echo $badgeColor; ?>; color: #fff;">
                                                        <i class="fas <?php echo $statusIcon; ?> me-1"></i> <?php echo htmlspecialchars($status); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($f['name']); ?></td>
                                                <td><?php echo htmlspecialchars($f['date']); ?></td>
                                                <td class="text-end">
                                                    <a href="<?php echo htmlspecialchars($f['file_url']); ?>" class="btn btn-outline-primary btn-sm px-3 rounded-pill" target="_blank">
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
                    <div class="bento-card bento-card-glow p-4 p-md-5">
                        <h3 class="h4 text-white fw-bold mb-4 d-flex align-items-center">
                            <i class="fas fa-star me-3 text-warning"></i> <?php echo t('dash_review_title'); ?>
                        </h3>

                        <form id="reviewForm">
                            <div id="reviewAlert" class="alert alert-success d-none small py-2 fade-in" role="alert"></div>

                            <div class="glass-panel p-4 rounded-4 mb-4 text-center">
                                <p class="text-white opacity-75 small mb-3"><?php echo t('dash_satisfaction_note'); ?></p>
                                <div class="d-flex justify-content-center gap-3">
                                    <input type="hidden" name="satisfaction" id="satisfactionValue" value="<?php echo htmlspecialchars($getNote()); ?>">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="<?php echo ($i <= (int)$getNote() ? 'fas' : 'far'); ?> fa-star text-warning fa-2x cursor-pointer star-item" data-value="<?php echo $i; ?>" onclick="setStars(<?php echo $i; ?>)"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-white opacity-75 small fw-bold text-uppercase mb-3"><?php echo t('dash_review_label'); ?></label>
                                <textarea class="form-control premium-textarea" name="avis" rows="6" placeholder="<?php echo t('dash_review_placeholder'); ?>"><?php echo htmlspecialchars($getAvis()); ?></textarea>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary btn-primary-glow px-4 py-2 rounded-pill fw-bold" id="btnSaveReview">
                                    <?php echo t('dash_review_save'); ?> <i class="fas fa-paper-plane ms-2"></i>
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
    // Buttons
    document.getElementById('tab-profile').classList.remove('active', 'text-primary');
    document.getElementById('tab-billing').classList.remove('active', 'text-primary');
    document.getElementById('tab-reviews').classList.remove('active', 'text-primary');
    
    // Content target
    document.getElementById('content-profile').classList.add('d-none');
    document.getElementById('content-billing').classList.add('d-none');
    document.getElementById('content-reviews').classList.add('d-none');
    
    // Activate target
    const btn = document.getElementById('tab-' + tabId);
    btn.classList.add('active');
    
    const content = document.getElementById('content-' + tabId);
    content.classList.remove('d-none');
}

function setStars(count) {
    const input = document.getElementById('satisfactionInput');
    const stars = '⭐'.repeat(count);
    input.value = stars;
    
    for(let i=1; i<=5; i++) {
        const star = document.getElementById('star-' + i);
        if(i <= count) {
            star.classList.remove('text-secondary', 'opacity-25');
            star.classList.add('text-warning');
        } else {
            star.classList.add('text-secondary', 'opacity-25');
            star.classList.remove('text-warning');
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

<style>
.smaller { font-size: 0.75rem; }
.cursor-pointer { cursor: pointer; }
.star-item { transition: none; }
.star-item:hover { transform: none; }

.bento-card:hover, .btn-primary:hover, .btn-outline-danger:hover, .btn-outline-glass:hover {
    transform: none !important;
}

.profile-pic-container {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.05);
    border: 2px solid rgba(255,255,255,0.1);
    box-shadow: 0 0 20px rgba(0,0,0,0.3);
}
.profile-pic-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.profile-pic-emoji {
    font-size: 2rem;
}

/* Disable default validation styles for dashboard */
#profileForm .form-control:valid, 
#reviewForm .form-control:valid,
#profileForm .form-control:invalid,
#reviewForm .form-control:invalid {
    border-color: rgba(255, 255, 255, 0.1) !important;
    background: rgba(255, 255, 255, 0.03) !important;
}

#profileForm .form-control:focus, 
#reviewForm .form-control:focus {
    border-color: var(--accent-blue) !important;
    background: rgba(255, 255, 255, 0.06) !important;
    box-shadow: 0 0 15px rgba(41, 151, 255, 0.1) !important;
}

/* Premium Textarea for Review */
.premium-textarea {
    background: rgba(10, 10, 10, 0.8) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 18px !important;
    color: white !important;
    padding: 1.25rem !important;
    line-height: 1.6 !important;
    resize: none;
    transition: all 0.3s ease !important;
}

.premium-textarea:focus {
    background: rgba(15, 15, 15, 0.9) !important;
    border-color: var(--accent-blue) !important;
}

/* Custom Scrollbar for Textarea */
.premium-textarea::-webkit-scrollbar {
    width: 6px;
}
.premium-textarea::-webkit-scrollbar-track {
    background: transparent;
}
.premium-textarea::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
.premium-textarea::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>

<?php include '../includes/footer.php'; ?>
