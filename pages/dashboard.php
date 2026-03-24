<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

$page_title = 'Mon Espace - SlapIA';
$page_description = 'Gérez votre profil, vos formations et vos factures.';
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
                    <h1 class="h2 text-white fw-bold mb-1">Espace Personnel</h1>
                    <p class="text-secondary mb-0">Ravi de vous revoir, <?php echo htmlspecialchars($_SESSION['user_name']); ?> !</p>
                </div>
            </div>
            <div>
                <a href="/api/auth-logout.php" class="btn btn-outline-danger btn-sm px-3 rounded-pill" style="border-color: rgba(220,53,69,0.5);">
                    <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
                </a>
            </div>
        </div>

        <div class="row g-4">
            
            <!-- Sidebar Navigation -->
            <div class="col-lg-3 fade-in-up delay-100">
                <div class="bento-card bento-card-glow p-3 w-100 h-100 position-relative">
                    <div class="list-group list-group-flush bg-transparent">
                        <button class="list-group-item list-group-item-action bg-transparent text-white border-bottom border-secondary border-opacity-25 py-3 active" id="tab-profile" onclick="switchTab('profile')">
                            <i class="fas fa-user-circle me-2 text-primary"></i> Mon Profil
                        </button>
                        <button class="list-group-item list-group-item-action bg-transparent text-white border-bottom border-secondary border-opacity-25 py-3" id="tab-billing" onclick="switchTab('billing')">
                            <i class="fas fa-file-invoice-dollar me-2" style="color: var(--accent-purple);"></i> Facturation
                        </button>
                        <button class="list-group-item list-group-item-action bg-transparent text-white border-bottom border-secondary border-opacity-25 py-3" id="tab-reviews" onclick="switchTab('reviews')">
                            <i class="fas fa-star me-2 text-warning"></i> Mon Avis
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="col-lg-9 fade-in-up delay-200">
                
                <!-- PROFILE TAB -->
                <div id="content-profile" class="dashboard-tab">
                    <div class="bento-card bento-card-glow p-4 p-lg-5 position-relative">
                        <div class="card-glow-orb orb-blue" style="top:-30px; right:-30px; width:200px; height:200px; opacity:0.1;"></div>
                        
                        <h4 class="text-white mb-4"><i class="fas fa-id-card text-primary me-2"></i> Informations Personnelles</h4>
                        
                        <form id="profileForm">
                            <div id="profileAlert" class="alert d-none small py-2" role="alert"></div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-75 small">Prénom NOM (Format strict)</label>
                                    <input type="text" class="form-control bg-dark border-secondary text-white" name="name" value="<?php echo htmlspecialchars($getUserName()); ?>" required placeholder="Prenom NOM">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-75 small">Email (Non modifiable)</label>
                                    <input type="email" class="form-control bg-dark border-secondary text-secondary" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" disabled>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-75 small">Nouveau mot de passe (Laissez vide pour ne pas changer)</label>
                                    <input type="password" class="form-control bg-dark border-secondary text-white" name="password" autocomplete="new-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-75 small">Téléphone</label>
                                    <input type="tel" class="form-control bg-dark border-secondary text-white" name="phone" value="<?php echo htmlspecialchars($getUserPhone()); ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-75 small">Lien LinkedIn</label>
                                    <input type="url" class="form-control bg-dark border-secondary text-white" name="linkedin" value="<?php echo htmlspecialchars($getUserLinkedin()); ?>" placeholder="https://linkedin.com/in/...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-75 small">Localisation (Ville)</label>
                                    <input type="text" class="form-control bg-dark border-secondary text-white" name="location" value="<?php echo htmlspecialchars($getUserLocation()); ?>" placeholder="Ex: Paris, France">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-75 small">Entreprise</label>
                                    <input type="text" class="form-control bg-dark border-secondary text-white" name="company" value="<?php echo htmlspecialchars($getUserCompany()); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-75 small">Fonction / Job</label>
                                    <input type="text" class="form-control bg-dark border-secondary text-white" name="job" value="<?php echo htmlspecialchars($getUserJob()); ?>">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary btn-primary-glow px-4" id="btnSaveProfile">
                                    Mettre à jour le profil <i class="fas fa-save ms-2"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- BILLING TAB -->
                <div id="content-billing" class="dashboard-tab d-none">
                    <div class="bento-card bento-card-glow p-4 p-lg-5 position-relative">
                        <div class="card-glow-orb orb-purple" style="bottom:-30px; left:-30px; width:200px; height:200px; opacity:0.1;"></div>
                        
                        <h4 class="text-white mb-4"><i class="fas fa-receipt me-2" style="color: var(--accent-purple);"></i> État de votre facturation</h4>

                        <div class="d-flex align-items-center p-3 rounded bg-dark border border-secondary border-opacity-25 mt-3 mb-4">
                            <div class="me-3">
                                <?php 
                                    $status = $getFacturationStatus(); 
                                    $statusClass = 'text-secondary';
                                    $statusIcon = 'fa-info-circle';
                                    
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
                                    echo '<i class="fas '.$statusIcon.' '.$statusClass.' fs-3"></i>';
                                ?>
                            </div>
                            <div>
                                <h6 class="text-white m-0">Statut Financier Général</h6>
                                <p class="m-0 small fw-bold" style="color: <?php echo $badgeColor ?? '#71717a'; ?>;">
                                    <?php echo htmlspecialchars($status); ?>
                                </p>
                            </div>
                        </div>

                        <h5 class="text-white mb-3 small fw-bold text-uppercase opacity-75">Vos Factures & Documents</h5>
                        <div class="row g-3">
                            <?php if(empty($invoices)): ?>
                                <div class="col-12 text-center py-4 opacity-50">
                                    <i class="fas fa-folder-open mb-2 fs-4"></i>
                                    <p class="small mb-0">Aucun document n'a été partagé pour le moment.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($invoices as $file): ?>
                                    <div class="col-md-6">
                                        <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 d-flex align-items-center">
                                            <i class="fas fa-file-pdf text-danger me-3 fs-4"></i>
                                            <div class="flex-grow-1 overflow-hidden">
                                                <div class="text-white text-truncate small fw-bold"><?php echo htmlspecialchars($file['name']); ?></div>
                                                <div class="text-secondary smaller">PDF • Facture</div>
                                            </div>
                                            <a href="<?php echo $file['file']['url'] ?? $file['external']['url'] ?? '#'; ?>" target="_blank" class="btn btn-sm btn-outline-glass ms-2">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- REVIEWS TAB -->
                <div id="content-reviews" class="dashboard-tab d-none">
                    <div class="bento-card bento-card-glow p-4 p-lg-5 position-relative">
                        <div class="card-glow-orb orb-blue" style="top:-30px; left:-30px; width:200px; height:200px; opacity:0.1;"></div>
                        
                        <h4 class="text-white mb-4"><i class="fas fa-star text-warning me-2"></i> Mon Avis Client</h4>
                        
                        <form id="reviewForm">
                            <div id="reviewAlert" class="alert d-none small py-2" role="alert"></div>

                            <div class="mb-4 text-center p-3 rounded bg-dark border border-secondary border-opacity-25">
                                <label class="d-block text-white opacity-75 small mb-3">Votre Note de Satisfaction</label>
                                <div class="star-rating fs-2">
                                    <?php 
                                        $currentStars = $getSatisfaction();
                                        $starOptions = [
                                            '⭐' => 1,
                                            '⭐⭐' => 2,
                                            '⭐⭐⭐' => 3,
                                            '⭐⭐⭐⭐' => 4,
                                            '⭐⭐⭐⭐⭐' => 5
                                        ];
                                        $currentStarValue = $starOptions[$currentStars] ?? 0;
                                    ?>
                                    <div class="d-inline-flex gap-2">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="fas fa-star star-item cursor-pointer <?php echo ($i <= $currentStarValue) ? 'text-warning' : 'text-secondary opacity-25'; ?>" 
                                               data-value="<?php echo str_repeat('⭐', $i); ?>" 
                                               onclick="setStars(<?php echo $i; ?>)"
                                               id="star-<?php echo $i; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="satisfaction" id="satisfactionInput" value="<?php echo htmlspecialchars($currentStars); ?>">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-white opacity-75 small">Votre Témoignage / Avis</label>
                                <textarea class="form-control bg-dark border-secondary text-white" name="avis" rows="5" placeholder="Rédigez votre avis ici..."><?php echo htmlspecialchars($getAvis()); ?></textarea>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary btn-primary-glow px-4" id="btnSaveReview">
                                    Enregistrer l'avis <i class="fas fa-paper-plane ms-2"></i>
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
    
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sauvegarde...';
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
            alertBox.textContent = result.message || 'Mise à jour réussie !';
            alertBox.classList.remove('d-none');
            // Reload page if name changed to update header
            if(data.name) setTimeout(() => location.reload(), 1500);
        } else {
            alertBox.className = 'alert alert-danger small py-2';
            alertBox.textContent = result.error || 'Erreur de mise à jour.';
            alertBox.classList.remove('d-none');
        }
    } catch (err) {
        alertBox.className = 'alert alert-danger small py-2';
        alertBox.textContent = 'Une erreur serveur est survenue.';
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
</style>

<?php include '../includes/footer.php'; ?>
