<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

$page_title = 'Connexion - SlapIA';
$page_description = 'Connectez-vous à votre espace personnel SlapIA';
include '../includes/header.php';
include '../includes/components.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: /dashboard');
    exit;
}
?>

<div class="grid-bg"></div>

<section class="py-5" style="min-height: 80vh; display: flex; align-items: center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="bento-card bento-card-glow p-4 p-md-5 position-relative fade-in-up">
                    <div class="card-glow-orb orb-blue" style="top: -20px; right: -20px; width: 150px; height: 150px;"></div>
                    
                    <div class="text-center mb-4 position-relative" style="z-index: 2;">
                        <h1 class="h3 text-white fw-bold mb-2">Bienvenue</h1>
                        <p class="text-secondary small">Connectez-vous à votre espace membre</p>
                    </div>

                    <form id="loginForm" class="position-relative" style="z-index: 2;">
                        <!-- Alert Component -->
                        <div id="loginAlert" class="alert alert-danger d-none small py-2" role="alert"></div>

                        <div class="mb-3">
                            <label class="form-label text-white opacity-75 small">Adresse Email</label>
                            <input type="email" class="form-control bg-dark border-secondary text-white" id="email" name="email" required placeholder="nom@entreprise.com">
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label text-white opacity-75 small mb-0">Mot de passe</label>
                                <!-- <a href="/forgot-password" class="text-primary small text-decoration-none" style="font-size: 0.8rem;">Oublié ?</a> -->
                            </div>
                            <input type="password" class="form-control bg-dark border-secondary text-white mt-1" id="password" name="password" required placeholder="••••••••">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-primary-glow mb-3" id="btnLogin">
                            Se connecter <i class="fas fa-sign-in-alt ms-2"></i>
                        </button>

                        <div class="text-center mt-4">
                            <div class="p-3 rounded border border-secondary border-opacity-25" style="background: rgba(255,255,255,0.02);">
                                <p class="text-secondary small mb-0 lh-sm">
                                    <i class="fas fa-info-circle text-primary mb-2 fs-5"></i><br>
                                    L'accès à l'espace personnel est **strictement réservé** aux personnes ayant déjà effectué une demande et validé leur inscription via notre 
                                    <a href="/contact" class="text-white fw-bold text-decoration-none border-bottom border-primary">page de contact</a>.
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnLogin');
    const alert = document.getElementById('loginAlert');
    const originalText = btn.innerHTML;
    
    // UI Loading state
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Connexion...';
    btn.disabled = true;
    alert.classList.add('d-none');
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('/api/auth-login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = result.redirect || '/dashboard';
        } else {
            alert.textContent = result.error || 'Erreur de connexion.';
            alert.classList.remove('d-none');
        }
    } catch (err) {
        alert.textContent = 'Une erreur serveur est survenue.';
        alert.classList.remove('d-none');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});
</script>

<?php include '../includes/footer.php'; ?>
