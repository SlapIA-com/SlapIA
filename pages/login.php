<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

$page_title = t('login_title');
$page_description = t('login_meta_desc');
$page_needs_turnstile = true;
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
                        <div class="login-logo-container mb-4">
                            <img src="/assets/img/brand/logo.svg" alt="SlapIA" class="login-brand-logo">
                        </div>
                        <h1 class="h2 text-white fw-bold mb-2"><?php echo t('login_welcome_back'); ?></h1>
                        <p class="text-secondary small"><?php echo t('login_subtitle'); ?></p>
                    </div>

                    <form id="loginForm">
                        <div id="loginAlert" class="alert-premium-error d-none mb-4 fade-in" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <span class="alert-text"></span>
                        </div>

                        <div class="input-group-premium mb-4">
                            <label class="premium-label"><?php echo t('login_email_label'); ?></label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" class="premium-input" id="email" name="email" required placeholder="nom@entreprise.com">
                            </div>
                        </div>

                        <div class="input-group-premium mb-4">
                            <div class="d-flex justify-content-between">
                                <label class="premium-label"><?php echo t('login_password_label'); ?></label>
                            </div>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="premium-input" id="password" name="password" required placeholder="••••••••">
                            </div>
                        </div>

                        <!-- Cloudflare Turnstile -->
                        <div class="d-flex justify-content-center mb-4">
                            <div id="cf-turnstile-login" data-sitekey="<?php echo config('TURNSTILE_SITE_KEY'); ?>"></div>
                        </div>

                        <button type="submit" class="btn-premium-action w-100" id="btnLogin">
                            <span><?php echo t('login_btn'); ?></span>
                            <i class="fas fa-arrow-right ms-2 fs-small"></i>
                        </button>

                        <div class="onboarding-notice mt-5">
                            <div class="notice-content">
                                <i class="fas fa-user-shield notice-icon"></i>
                                <p class="notice-text">
                                    <?php echo t('login_notice_prefix'); ?> 
                                    <a href="/contact" class="notice-link"><?php echo t('login_contact_form'); ?></a>.
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

.login-logo-container {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 15px;
    box-shadow: 0 0 30px rgba(59, 130, 246, 0.15);
}

.login-brand-logo {
    width: 100%;
    height: auto;
    filter: drop-shadow(0 0 10px rgba(41, 151, 255, 0.3));
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

/* ALERTS */
.alert-premium-error {
    background: rgba(220, 38, 38, 0.1);
    border: 1px solid rgba(220, 38, 38, 0.2);
    border-left: 4px solid #ef4444;
    color: #fca5a5;
    padding: 1rem;
    border-radius: 12px;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
}

.alert-text {
    flex: 1;
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
    btn.innerHTML = '<span><?php echo t('login_connecting'); ?></span>';
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
            const alertText = alert.querySelector('.alert-text');
            alertText.textContent = result.error || '<?php echo t('login_error_fail'); ?>';
            alert.classList.remove('d-none');
            // Shake effect
            const card = document.querySelector('.login-glass');
            card.style.animation = 'shake 0.5s cubic-bezier(.36,.07,.19,.97) both';
            setTimeout(() => card.style.animation = '', 500);
            
            // Reset turnstile if exists
            if (typeof turnstile !== 'undefined') {
                try { turnstile.reset('#cf-turnstile-login'); } catch(e) {}
            }
        }
    } catch (err) {
        alert.textContent = '<?php echo t('login_error_server'); ?>';
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
