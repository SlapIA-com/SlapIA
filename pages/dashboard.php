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
curl_close($ch);

$userPage = json_decode($response, true);
$props = $userPage['properties'] ?? [];

// Helper functions to safely extract data
$getUserName = fn() => $props['Prenom NOM']['title'][0]['text']['content'] ?? '';
$getUserPhone = fn() => $props['Téléphone']['phone_number'] ?? '';
$getUserCompany = fn() => $props['Nom d\'entreprise']['rich_text'][0]['text']['content'] ?? '';
$getUserJob = fn() => $props['Job']['rich_text'][0]['text']['content'] ?? '';
$getFacturationStatus = fn() => $props['Facturation']['select']['name'] ?? $props['Facturation']['status']['name'] ?? 'Non disponible';
?>

<div class="grid-bg"></div>

<!-- Dashboard Layout -->
<section class="py-5" style="min-height: 80vh;">
    <div class="container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-5 fade-in-up">
            <div>
                <h1 class="h2 text-white fw-bold mb-1">Espace Personnel</h1>
                <p class="text-secondary mb-0">Ravi de vous revoir, <?php echo htmlspecialchars($_SESSION['user_name']); ?> !</p>
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
                        <button class="list-group-item list-group-item-action bg-transparent text-white border-bottom border-secondary border-opacity-25 pb-3 active" id="tab-profile" onclick="switchTab('profile')">
                            <i class="fas fa-user-circle me-2 text-primary"></i> Mon Profil
                        </button>
                        <button class="list-group-item list-group-item-action bg-transparent text-white border-bottom border-secondary border-opacity-25 py-3" id="tab-billing" onclick="switchTab('billing')">
                            <i class="fas fa-file-invoice-dollar me-2" style="color: var(--accent-purple);"></i> Facturation
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
                                    <label class="form-label text-white opacity-75 small">Prénom et Nom</label>
                                    <input type="text" class="form-control bg-dark border-secondary text-white" name="name" value="<?php echo htmlspecialchars($getUserName()); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white opacity-75 small">Email (Non modifiable)</label>
                                    <input type="email" class="form-control bg-dark border-secondary text-secondary" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" disabled>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label text-white opacity-75 small">Téléphone</label>
                                    <input type="tel" class="form-control bg-dark border-secondary text-white" name="phone" value="<?php echo htmlspecialchars($getUserPhone()); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-white opacity-75 small">Entreprise</label>
                                    <input type="text" class="form-control bg-dark border-secondary text-white" name="company" value="<?php echo htmlspecialchars($getUserCompany()); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-white opacity-75 small">Fonction / Job</label>
                                    <input type="text" class="form-control bg-dark border-secondary text-white" name="job" value="<?php echo htmlspecialchars($getUserJob()); ?>">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary btn-primary-glow px-4" id="btnSaveProfile">
                                    Mettre à jour <i class="fas fa-save ms-2"></i>
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

                        <div class="d-flex align-items-center p-3 rounded bg-dark border border-secondary border-opacity-25 mt-3">
                            <div class="me-3">
                                <?php 
                                    $status = $getFacturationStatus(); 
                                    if(strtolower($status) === 'payé') {
                                        echo '<i class="fas fa-check-circle text-success fs-3"></i>';
                                    } else if(strtolower($status) === 'en attente') {
                                        echo '<i class="fas fa-clock text-warning fs-3"></i>';
                                    } else {
                                        echo '<i class="fas fa-info-circle text-secondary fs-3"></i>';
                                    }
                                ?>
                            </div>
                            <div>
                                <h6 class="text-white m-0">Statut Financier Général</h6>
                                <p class="text-secondary small m-0 fw-bold">
                                    <?php echo htmlspecialchars($status); ?>
                                </p>
                            </div>
                            <!-- Future extension: Button to Request Invoice PDF / n8n trigger -->
                            <?php if(strtolower($status) === 'payé' || strtolower($status) === 'en attente'): ?>
                                <div class="ms-auto">
                                    <button class="btn btn-outline-glass btn-sm" onclick="alert('Vos documents seront bientôt téléchargeables depuis cette interface.');">
                                        <i class="fas fa-download"></i> PDF
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

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
    
    // Content target
    document.getElementById('content-profile').classList.add('d-none');
    document.getElementById('content-billing').classList.add('d-none');
    
    // Activate target
    const btn = document.getElementById('tab-' + tabId);
    btn.classList.add('active');
    
    // Make active tab text primary slightly depending on original color, for simplicity just add 'active' bootrap styling
    
    const content = document.getElementById('content-' + tabId);
    content.classList.remove('d-none');
}

// Profile Form Submission
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveProfile');
    const alertBox = document.getElementById('profileAlert');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sauvegarde...';
    btn.disabled = true;
    alertBox.classList.add('d-none');
    
    const formData = new FormData(this);
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
        
        // Hide success message after 3 seconds
        if(alertBox.classList.contains('alert-success')) {
            setTimeout(() => {
                alertBox.classList.add('fade');
                setTimeout(() => alertBox.classList.add('d-none'), 150);
            }, 3000);
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
