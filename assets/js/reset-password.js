/**
 * reset-password.js
 * Handles Password Reset forms (request & exec) with Swup SPA compatibility.
 */

window.initResetPasswordForm = function () {
    // ── Step 1: Request reset ──
    const requestForm = document.getElementById('resetRequestForm');
    if (requestForm && !requestForm.dataset.initialized) {
        requestForm.dataset.initialized = 'true';
        requestForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn   = document.getElementById('btnResetRequest');
            const email = document.getElementById('resetEmail').value.trim();
            const turnstileResponse = typeof turnstile !== 'undefined' ? turnstile.getResponse('#cf-turnstile-reset') : '';
            showResetAlert(null);

            if (!turnstileResponse) {
                showResetAlert('Veuillez compléter la sécurité Cloudflare.');
                return;
            }

            btn.disabled  = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Envoi...';

            try {
                const res  = await fetch('/api/auth-reset-request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, 'cf-turnstile-response': turnstileResponse }),
                });
                const data = await res.json();

                if (data.success) {
                    requestForm.innerHTML = '';
                    showResetSuccess('Si cette adresse est associée à un compte, vous recevrez un lien de réinitialisation dans quelques minutes.');
                } else {
                    showResetAlert(data.error || 'Erreur serveur.');
                    btn.disabled  = false;
                    btn.innerHTML = '<span>Envoyer le lien</span><i class="fas fa-paper-plane ms-2"></i>';
                    if (typeof turnstile !== 'undefined') turnstile.reset('#cf-turnstile-reset');
                }
            } catch (err) {
                showResetAlert('Erreur de connexion.');
                btn.disabled  = false;
                btn.innerHTML = '<span>Envoyer le lien</span><i class="fas fa-paper-plane ms-2"></i>';
                if (typeof turnstile !== 'undefined') turnstile.reset('#cf-turnstile-reset');
            }
        });
    }

    // ── Step 2: Set new password ──
    const execForm = document.getElementById('resetExecForm');
    if (execForm && !execForm.dataset.initialized) {
        execForm.dataset.initialized = 'true';
        const pwInput = document.getElementById('newPassword');
        if (pwInput) {
            pwInput.addEventListener('input', () => updatePwStrength(pwInput.value));
        }

        execForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn      = document.getElementById('btnResetExec');
            const pw       = document.getElementById('newPassword').value;
            const confirm  = document.getElementById('confirmPassword').value;
            const token    = document.getElementById('resetToken').value;
            const email    = document.getElementById('resetEmailHidden').value;
            showResetAlert(null);

            if (pw !== confirm) { showResetAlert('Les mots de passe ne correspondent pas.'); return; }
            if (pw.length < 8)  { showResetAlert('Le mot de passe doit contenir au moins 8 caractères.'); return; }

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
                    showResetSuccess('Mot de passe mis à jour ! <a href="/login" class="text-white fw-bold ms-1">Se connecter →</a>');
                } else {
                    showResetAlert(data.error || 'Erreur serveur.');
                    btn.disabled  = false;
                    btn.innerHTML = '<span>Confirmer</span><i class="fas fa-check ms-2"></i>';
                }
            } catch (err) {
                showResetAlert('Erreur de connexion.');
                btn.disabled  = false;
                btn.innerHTML = '<span>Confirmer</span><i class="fas fa-check ms-2"></i>';
            }
        });
    }

    // ── Helpers ──
    function showResetAlert(msg) {
        const el   = document.getElementById('resetAlert');
        const span = el?.querySelector('.alert-text');
        if (!el) return;
        if (!msg) { el.classList.add('d-none'); return; }
        if (span) span.innerHTML = msg; else el.innerHTML = msg;
        el.classList.remove('d-none');
    }

    function showResetSuccess(msg) {
        const el   = document.getElementById('resetSuccess');
        const span = el?.querySelector('.alert-text');
        if (!el) return;
        if (span) span.innerHTML = msg; else el.innerHTML = msg;
        el.classList.remove('d-none');
    }

    function updatePwStrength(pw) {
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
};

// Initial call
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.initResetPasswordForm);
} else {
    window.initResetPasswordForm();
}
