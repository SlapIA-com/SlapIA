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

<div class="login-page-wrapper">
    <!-- Animated Background Elements -->
    <div class="glow-system">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    
    <div class="grid-overlay"></div>

    <section class="login-section">
        <div class="container d-flex justify-content-center align-items-center min-vh-100">
            <div class="login-card-container fade-in-up">
                <div class="glass-card login-glass">
                    <div class="text-center mb-5">
                        <div class="login-logo mb-3">
                            <span class="slap-dot"></span>
                        </div>
                        <h1 class="h2 text-white fw-bold mb-2">Bon retour</h1>
                        <p class="text-secondary small">Accédez à votre intelligence SlapIA</p>
                    </div>

                    <form id="loginForm">
                        <div id="loginAlert" class="alert alert-danger d-none small py-2 mb-4" role="alert"></div>

                        <div class="input-group-premium mb-4">
                            <label class="premium-label">Adresse Email</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" class="premium-input" id="email" name="email" required placeholder="nom@entreprise.com">
                            </div>
                        </div>

                        <div class="input-group-premium mb-5">
                            <div class="d-flex justify-content-between">
                                <label class="premium-label">Mot de passe</label>
                            </div>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="premium-input" id="password" name="password" required placeholder="••••••••">
                            </div>
                        </div>

                        <button type="submit" class="btn-premium-action w-100" id="btnLogin">
                            <span>Se connecter</span>
                            <i class="fas fa-arrow-right ms-2 fs-small"></i>
                        </button>

                        <div class="onboarding-notice mt-5">
                            <div class="notice-content">
                                <i class="fas fa-user-shield notice-icon"></i>
                                <p class="notice-text">
                                    L'accès est réservé aux comptes validés via notre 
                                    <a href="/contact" class="notice-link">formulaire de contact</a>.
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
/* PREMIUM LOGIN STYLES */
.login-page-wrapper {
    position: relative;
    background: #050505;
    min-height: 100vh;
    overflow: hidden;
    margin-top: -100px; /* Offset for header if needed, but relative works best */
    padding-top: 100px;
}

.grid-overlay {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    mask-image: radial-gradient(circle at center, black, transparent 80%);
    pointer-events: none;
}

/* ORBS ANIMATION */
.glow-system {
    position: absolute;
    inset: 0;
    filter: blur(80px);
    opacity: 0.5;
    pointer-events: none;
}

.orb {
    position: absolute;
    border-radius: 50%;
    animation: orbFloat 20s infinite alternate;
}

.orb-1 {
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, var(--accent-blue) 0%, transparent 70%);
    top: 10%;
    left: -100px;
}

.orb-2 {
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, var(--accent-purple) 0%, transparent 70%);
    bottom: 10%;
    right: -100px;
    animation-delay: -5s;
}

.orb-3 {
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, #3b82f6 0%, transparent 70%);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation: orbPulse 15s infinite;
}

@keyframes orbFloat {
    from { transform: translate(0, 0) scale(1); }
    to { transform: translate(100px, 50px) scale(1.1); }
}

@keyframes orbPulse {
    0%, 100% { opacity: 0.3; transform: translate(-50%, -50%) scale(1); }
    50% { opacity: 0.6; transform: translate(-50%, -50%) scale(1.3); }
}

/* GLASS CARD */
.login-card-container {
    width: 100%;
    max-width: 450px;
    perspective: 1000px;
}

.login-glass {
    background: rgba(15, 15, 15, 0.7);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 28px;
    padding: 3rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    transition: transform 0.3s ease;
}

.login-glass:hover {
    border-color: rgba(255, 255, 255, 0.15);
}

.login-logo {
    display: inline-block;
}

.slap-dot {
    width: 40px;
    height: 40px;
    background: var(--accent-gradient);
    border-radius: 12px;
    display: inline-block;
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
    position: relative;
    transform: rotate(45deg);
}

/* INPUTS */
.input-group-premium {
    position: relative;
}

.premium-label {
    display: block;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.75rem;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 1rem;
    color: rgba(255, 255, 255, 0.3);
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.premium-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    padding: 0.85rem 1rem 0.85rem 2.8rem;
    color: white;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.premium-input:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.5);
    background: rgba(255, 255, 255, 0.06);
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
}

.premium-input:focus + .input-icon {
    color: var(--accent-blue);
}

/* BUTTON */
.btn-premium-action {
    background: white;
    color: black;
    border: none;
    border-radius: 14px;
    padding: 1rem;
    font-weight: 700;
    font-size: 1rem;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 10px 15px -3px rgba(255, 255, 255, 0.1);
}

.btn-premium-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(255, 255, 255, 0.2);
    filter: brightness(1.1);
}

.btn-premium-action:active {
    transform: translateY(0);
}

/* NOTICE */
.onboarding-notice {
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    padding-top: 2rem;
}

.notice-content {
    display: flex;
    gap: 1rem;
    background: rgba(255, 255, 255, 0.03);
    padding: 1rem;
    border-radius: 16px;
}

.notice-icon {
    color: var(--accent-blue);
    font-size: 1.25rem;
    margin-top: 0.25rem;
}

.notice-text {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.8rem;
    margin: 0;
    line-height: 1.5;
}

.notice-link {
    color: white;
    text-decoration: none;
    font-weight: 600;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.notice-link:hover {
    color: var(--accent-blue);
    border-color: var(--accent-blue);
}

/* RESPONSIVE */
@media (max-width: 576px) {
    .login-glass {
        padding: 2rem;
    }
    .login-section {
        padding: 1rem;
    }
}
</style>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnLogin');
    const alert = document.getElementById('loginAlert');
    const originalText = btn.innerHTML;
    
    // UI Loading state
    btn.innerHTML = '<span>Connexion en cours...</span>';
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
            // Shake effect
            const card = document.querySelector('.login-glass');
            card.style.animation = 'shake 0.5s cubic-bezier(.36,.07,.19,.97) both';
            setTimeout(() => card.style.animation = '', 500);
        }
    } catch (err) {
        alert.textContent = 'Une erreur serveur est survenue.';
        alert.classList.remove('d-none');
    } finally {
        if (!window.location.href.includes('/dashboard')) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
});
</script>

<style>
@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}
</style>

<?php include '../includes/footer.php'; ?>
