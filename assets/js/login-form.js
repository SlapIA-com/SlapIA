// login-form.js
// Handles Login Form Submission, compatible with Swup SPA navigation

window.initLoginForm = function() {
    const loginForm = document.getElementById('loginForm');
    if (!loginForm || loginForm.dataset.initialized === 'true') return;
    
    loginForm.dataset.initialized = 'true';
    
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnLogin');
        const alert = document.getElementById('loginAlert');
        if (!btn || !alert) return;
        
        const originalText = btn.innerHTML;
        
        // UI Loading state
        btn.innerHTML = '<span><i class="fas fa-spinner fa-spin me-2"></i> Connexion...</span>';
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
                // Success: Redirect
                window.location.href = result.redirect || '/dashboard';
            } else {
                // Handle Error
                const alertText = alert.querySelector('.alert-text');
                if (alertText) {
                    alertText.textContent = result.error || 'Identifiants invalides.';
                } else {
                    alert.textContent = result.error || 'Identifiants invalides.';
                }
                alert.classList.remove('d-none');
                
                // Shake effect
                const card = document.querySelector('.login-glass');
                if (card) {
                    card.style.animation = 'shake 0.5s cubic-bezier(.36,.07,.19,.97) both';
                    setTimeout(() => card.style.animation = '', 500);
                }
                
                // Reset turnstile if exists
                if (typeof turnstile !== 'undefined') {
                    try { turnstile.reset('#cf-turnstile-login'); } catch(ex) {}
                }
            }
        } catch (err) {
            console.error("Login Error:", err);
            const alertText = alert.querySelector('.alert-text');
            if (alertText) {
                alertText.textContent = 'Erreur de connexion au serveur.';
            } else {
                alert.textContent = 'Erreur de connexion au serveur.';
            }
            alert.classList.remove('d-none');
        } finally {
            if (!window.location.href.includes('/dashboard')) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    });
};

// Initialize on first load if script is loaded at bottom
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.initLoginForm);
} else {
    window.initLoginForm();
}
