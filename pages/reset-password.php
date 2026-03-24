<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

// Already logged in? No need to reset
if (!empty($_SESSION['logged_in'])) {
    header('Location: /dashboard');
    exit;
}

$token = trim($_GET['token'] ?? '');
$email = strtolower(trim($_GET['email'] ?? ''));
$mode  = ($token && $email) ? 'reset' : 'request'; // which step to show

$page_title       = t('reset_password_title');
$page_description = t('reset_password_meta_desc');
include '../includes/header.php';
?>

<div class="login-page-wrapper">
    <div class="glow-system">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>
    <div class="grid-overlay"></div>

    <section class="login-section">
        <div class="container d-flex justify-content-center align-items-center min-vh-100">
            <div class="login-card-container fade-in-up">
                <div class="glass-card login-glass">

                    <div class="text-center mb-4">
                        <div class="login-logo-container mb-4">
                            <img src="/assets/img/brand/logo.svg" alt="SlapIA" class="login-brand-logo">
                        </div>
                        <?php if ($mode === 'request'): ?>
                            <h1 class="h2 text-white fw-bold mb-2"><?php echo t('reset_request_title'); ?></h1>
                            <p class="text-secondary small"><?php echo t('reset_request_subtitle'); ?></p>
                        <?php else: ?>
                            <h1 class="h2 text-white fw-bold mb-2"><?php echo t('reset_new_password_title'); ?></h1>
                            <p class="text-secondary small"><?php echo t('reset_new_password_subtitle'); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Shared alert -->
                    <div id="resetAlert" class="alert-premium-error d-none mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span class="alert-text"></span>
                    </div>
                    <div id="resetSuccess" class="alert-premium-success d-none mb-4" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <span class="alert-text"></span>
                    </div>

                    <?php if ($mode === 'request'): ?>
                    <!-- ── Step 1: request reset ── -->
                    <form id="resetRequestForm">
                        <div class="input-group-premium mb-4">
                            <label class="premium-label"><?php echo t('login_email_label'); ?></label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" class="premium-input" id="resetEmail" name="email" required
                                       placeholder="nom@entreprise.com" autocomplete="email">
                            </div>
                        </div>

                        <button type="submit" class="btn-premium-action w-100" id="btnResetRequest">
                            <span><?php echo t('reset_send_link_btn'); ?></span>
                            <i class="fas fa-paper-plane ms-2"></i>
                        </button>
                    </form>

                    <?php else: ?>
                    <!-- ── Step 2: set new password ── -->
                    <form id="resetExecForm">
                        <input type="hidden" id="resetToken" value="<?php echo htmlspecialchars($token); ?>">
                        <input type="hidden" id="resetEmailHidden" value="<?php echo htmlspecialchars($email); ?>">

                        <div class="input-group-premium mb-4">
                            <label class="premium-label"><?php echo t('reset_new_password_label'); ?></label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="premium-input" id="newPassword" required
                                       placeholder="••••••••" minlength="8" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="input-group-premium mb-4">
                            <label class="premium-label"><?php echo t('reset_confirm_password_label'); ?></label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="premium-input" id="confirmPassword" required
                                       placeholder="••••••••" minlength="8" autocomplete="new-password">
                            </div>
                        </div>

                        <!-- Password strength bar -->
                        <div class="pw-strength-wrap mb-4">
                            <div class="pw-strength-bar">
                                <div class="pw-strength-fill" id="pwStrengthFill"></div>
                            </div>
                            <span class="pw-strength-label" id="pwStrengthLabel"></span>
                        </div>

                        <button type="submit" class="btn-premium-action w-100" id="btnResetExec">
                            <span><?php echo t('reset_confirm_btn'); ?></span>
                            <i class="fas fa-check ms-2"></i>
                        </button>
                    </form>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <a href="/login" class="forgot-link"><?php echo t('reset_back_to_login'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
/* Reuse login page styles — must be duplicated here since it's a separate page */
.login-page-wrapper { position:relative; background:#050505; min-height:100vh; overflow:hidden; margin-top:-100px; padding-top:100px; }
.grid-overlay { position:absolute; inset:0; background-image:radial-gradient(rgba(255,255,255,0.03) 1px,transparent 1px); background-size:40px 40px; mask-image:radial-gradient(circle at center,black,transparent 80%); pointer-events:none; }
.glow-system { position:absolute; inset:0; filter:blur(80px); opacity:0.5; pointer-events:none; }
.orb { position:absolute; border-radius:50%; animation:orbFloat 20s infinite alternate; }
.orb-1 { width:400px; height:400px; background:radial-gradient(circle,var(--accent-blue) 0%,transparent 70%); top:10%; left:-100px; }
.orb-2 { width:350px; height:350px; background:radial-gradient(circle,var(--accent-purple) 0%,transparent 70%); bottom:10%; right:-100px; animation-delay:-5s; }
@keyframes orbFloat { from{transform:translate(0,0) scale(1)} to{transform:translate(100px,50px) scale(1.1)} }
.login-card-container { width:100%; max-width:450px; }
.login-glass { background:rgba(15,15,15,0.7); backdrop-filter:blur(25px); border:1px solid rgba(255,255,255,0.08); border-radius:28px; padding:3rem; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); }
.login-logo-container { display:inline-flex; justify-content:center; align-items:center; width:80px; height:80px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.1); border-radius:20px; padding:15px; }
.login-brand-logo { width:100%; height:auto; filter:drop-shadow(0 0 10px rgba(41,151,255,0.3)); }
.input-group-premium { position:relative; }
.premium-label { display:block; color:rgba(255,255,255,0.6); font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.75rem; }
.input-wrapper { position:relative; display:flex; align-items:center; }
.input-icon { position:absolute; left:1rem; color:rgba(255,255,255,0.3); font-size:0.9rem; }
.premium-input { width:100%; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.1); border-radius:14px; padding:0.85rem 1rem 0.85rem 2.8rem; color:white; font-size:0.95rem; transition:all 0.3s; }
.premium-input:focus { outline:none; border-color:rgba(59,130,246,0.5); background:rgba(255,255,255,0.06); box-shadow:0 0 0 4px rgba(59,130,246,0.15); }
.btn-premium-action { background:white; color:black; border:none; border-radius:14px; padding:1rem; font-weight:700; font-size:1rem; display:flex; justify-content:center; align-items:center; transition:all 0.3s; box-shadow:0 10px 15px -3px rgba(255,255,255,0.1); width:100%; cursor:pointer; }
.btn-premium-action:hover { transform:translateY(-2px); filter:brightness(1.1); }
.btn-premium-action:disabled { opacity:0.6; cursor:not-allowed; transform:none; }
.alert-premium-error { background:rgba(220,38,38,0.1); border:1px solid rgba(220,38,38,0.2); border-left:4px solid #ef4444; color:#fca5a5; padding:1rem; border-radius:12px; font-size:0.85rem; display:flex; align-items:center; }
.alert-premium-success { background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.2); border-left:4px solid #22c55e; color:#86efac; padding:1rem; border-radius:12px; font-size:0.85rem; display:flex; align-items:center; }
.forgot-link { color:rgba(255,255,255,0.4); font-size:0.82rem; text-decoration:none; transition:color 0.2s; }
.forgot-link:hover { color:white; }
/* Password strength */
.pw-strength-wrap { display:flex; align-items:center; gap:0.75rem; }
.pw-strength-bar { flex:1; height:4px; background:rgba(255,255,255,0.1); border-radius:4px; overflow:hidden; }
.pw-strength-fill { height:100%; width:0; border-radius:4px; transition:width 0.3s, background 0.3s; }
.pw-strength-label { font-size:0.75rem; color:rgba(255,255,255,0.5); min-width:70px; }
@media(max-width:576px){ .login-glass{padding:2rem;} }
</style>

<script>
(function () {
    // ── Step 1: Request reset ──────────────────────────────────────────────
    const requestForm = document.getElementById('resetRequestForm');
    if (requestForm) {
        requestForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn   = document.getElementById('btnResetRequest');
            const email = document.getElementById('resetEmail').value.trim();
            showAlert(null);

            btn.disabled  = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Envoi...';

            try {
                const res  = await fetch('/api/auth-reset-request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email }),
                });
                const data = await res.json();

                if (data.success) {
                    requestForm.innerHTML = '';
                    showSuccess('Si cette adresse est associée à un compte, vous recevrez un lien de réinitialisation dans quelques minutes.');
                } else {
                    showAlert(data.error || 'Erreur serveur.');
                    btn.disabled  = false;
                    btn.innerHTML = '<span><?php echo t('reset_send_link_btn'); ?></span><i class="fas fa-paper-plane ms-2"></i>';
                }
            } catch {
                showAlert('Erreur de connexion.');
                btn.disabled  = false;
                btn.innerHTML = '<span><?php echo t('reset_send_link_btn'); ?></span><i class="fas fa-paper-plane ms-2"></i>';
            }
        });
    }

    // ── Step 2: Set new password ───────────────────────────────────────────
    const execForm = document.getElementById('resetExecForm');
    if (execForm) {
        const pwInput = document.getElementById('newPassword');
        if (pwInput) {
            pwInput.addEventListener('input', () => updateStrength(pwInput.value));
        }

        execForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn      = document.getElementById('btnResetExec');
            const pw       = document.getElementById('newPassword').value;
            const confirm  = document.getElementById('confirmPassword').value;
            const token    = document.getElementById('resetToken').value;
            const email    = document.getElementById('resetEmailHidden').value;
            showAlert(null);

            if (pw !== confirm) { showAlert('Les mots de passe ne correspondent pas.'); return; }
            if (pw.length < 8)  { showAlert('Le mot de passe doit contenir au moins 8 caractères.'); return; }

            btn.disabled  = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Mise à jour...';

            try {
                const res  = await fetch('/api/auth-reset-exec.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token, email, password: pw }),
                });
                const data = await res.json();

                if (data.success) {
                    execForm.innerHTML = '';
                    showSuccess('Mot de passe mis à jour ! <a href="/login" class="text-white fw-bold ms-1">Se connecter →</a>');
                } else {
                    showAlert(data.error || 'Erreur serveur.');
                    btn.disabled  = false;
                    btn.innerHTML = '<span><?php echo t('reset_confirm_btn'); ?></span><i class="fas fa-check ms-2"></i>';
                }
            } catch {
                showAlert('Erreur de connexion.');
                btn.disabled  = false;
                btn.innerHTML = '<span><?php echo t('reset_confirm_btn'); ?></span><i class="fas fa-check ms-2"></i>';
            }
        });
    }

    // ── Helpers ─────────────────────────────────────────────────────────────
    function showAlert(msg) {
        const el   = document.getElementById('resetAlert');
        const span = el?.querySelector('.alert-text');
        if (!el) return;
        if (!msg) { el.classList.add('d-none'); return; }
        if (span) span.innerHTML = msg; else el.innerHTML = msg;
        el.classList.remove('d-none');
    }

    function showSuccess(msg) {
        const el   = document.getElementById('resetSuccess');
        const span = el?.querySelector('.alert-text');
        if (!el) return;
        if (span) span.innerHTML = msg; else el.innerHTML = msg;
        el.classList.remove('d-none');
    }

    function updateStrength(pw) {
        const fill  = document.getElementById('pwStrengthFill');
        const label = document.getElementById('pwStrengthLabel');
        if (!fill || !label) return;

        let score = 0;
        if (pw.length >= 8)  score++;
        if (pw.length >= 12) score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;

        const levels = [
            { w: '20%', bg: '#ef4444', text: 'Très faible' },
            { w: '40%', bg: '#f97316', text: 'Faible' },
            { w: '60%', bg: '#eab308', text: 'Moyen' },
            { w: '80%', bg: '#3b82f6', text: 'Fort' },
            { w: '100%', bg: '#22c55e', text: 'Très fort' },
        ];
        const l = levels[Math.max(0, score - 1)] || levels[0];
        fill.style.width      = pw.length ? l.w : '0';
        fill.style.background = l.bg;
        label.textContent     = pw.length ? l.text : '';
    }
}());
</script>

<?php include '../includes/footer.php'; ?>
