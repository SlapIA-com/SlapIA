// contact-form.js
// Handles Turnstile and Contact Form Submission, compatible with Swup SPA navigation

let toastTimeout;
let contactFormStartTime;

// Initialize Turnstile for the Contact Form
window.initContactTurnstile = function () {
    const container = document.getElementById('cf-turnstile-container');
    if (!container || typeof turnstile === 'undefined') return;

    if (container.dataset.rendered === 'true' || container.querySelector('iframe')) return;

    const sitekey = container.getAttribute('data-sitekey');
    if (!sitekey) return;

    container.dataset.rendered = 'true';
    try {
        turnstile.render(container, {
            sitekey: sitekey,
            theme: 'dark'
        });
    } catch (e) {
        container.dataset.rendered = 'false';
        console.warn('Turnstile render retry pending...');
    }
};

// Initialize Turnstile for the Login Form
window.initLoginTurnstile = function () {
    const container = document.getElementById('cf-turnstile-login');
    if (!container || typeof turnstile === 'undefined') return;

    if (container.dataset.rendered === 'true' || container.querySelector('iframe')) return;

    const sitekey = container.getAttribute('data-sitekey');
    if (!sitekey) return;

    container.dataset.rendered = 'true';
    try {
        turnstile.render(container, {
            sitekey: sitekey,
            theme: 'dark'
        });
    } catch (e) {
        container.dataset.rendered = 'false';
    }
};

// Initialize Turnstile for the RSS Modal
window.initRssTurnstile = function (force = false) {
    const container = document.getElementById('cf-turnstile-rss');
    if (!container || typeof turnstile === 'undefined') return;
    
    if (container.dataset.rendered === 'true' && !force) return;

    const sitekey = container.getAttribute('data-sitekey');
    if (!sitekey) return;

    try {
        if (force) {
            try { turnstile.remove(container); } catch(e) {}
        }
        
        container.dataset.rendered = 'true';
        turnstile.render(container, {
            sitekey: sitekey,
            theme: 'dark'
        });
    } catch (e) {
        container.dataset.rendered = 'false';
    }
};

// The global callback used by Turnstile script in header
window.onloadTurnstileCallback = function () {
    window.initContactTurnstile();
    window.initLoginTurnstile();
    window.initRssTurnstile();
};

function showToast(message, isError = false) {
    const toast = document.getElementById('toastNotification');
    const toastMessage = document.getElementById('toastMessage');
    const progressBar = document.getElementById('toastProgressBar');

    if (!toast || !toastMessage || !progressBar) return;

    if (toastTimeout) {
        clearTimeout(toastTimeout);
    }

    toast.classList.remove('show', 'error');
    progressBar.classList.remove('animate');

    toastMessage.textContent = message;
    if (isError) {
        toast.classList.add('error');
    }

    void progressBar.offsetWidth;

    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });

    requestAnimationFrame(() => {
        toast.classList.add('show');
        progressBar.classList.add('animate');
    });

    toastTimeout = setTimeout(() => {
        closeToast();
    }, 5000);
}

function closeToast() {
    const toast = document.getElementById('toastNotification');
    if (!toast) return;
    toast.classList.remove('show');
    if (toastTimeout) {
        clearTimeout(toastTimeout);
    }
}

async function handleContactFormSubmit(e) {
    e.preventDefault();

    const form = e.target;
    // Anti-Spam Check
    const honeycomb = document.getElementById('website_check').value;
    if (honeycomb) return;

    // Content Security Check
    const messageInput = document.getElementById('message');
    const messageContent = messageInput ? messageInput.value.toLowerCase() : '';
    const forbiddenWords = ['caca', 'connard', 'pute', 'salope', 'batard', 'encule', 'merde', 'chiotte', 'bite', 'couille'];
    if (forbiddenWords.some(word => messageContent.includes(word))) {
        showToast("Un texte inapproprié a été détecté.", true);
        return;
    }

    if (messageContent.length < 20) {
        showToast("Le message est trop court (min 20 caractères).", true);
        return;
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnLoader = document.getElementById('btnLoader');

    submitBtn.disabled = true;
    btnText.classList.add('d-none');
    btnLoader.classList.remove('d-none');

    const formData = new FormData(form);
    const data = {
        prenom: document.getElementById('prenom').value,
        nom: document.getElementById('nom').value,
        email: document.getElementById('email').value,
        message: document.getElementById('message').value,
        website_check: honeycomb,
        'cf-turnstile-response': formData.get('cf-turnstile-response')
    };

    try {
        const response = await fetch('/api/notion-contact.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast("Message envoyé avec succès !");
            form.reset();
            const charCountDisplay = document.getElementById('charCount');
            if (charCountDisplay) {
                charCountDisplay.textContent = '0 / 20 min';
                charCountDisplay.classList.add('text-secondary');
                charCountDisplay.classList.remove('text-success');
                charCountDisplay.style.fontWeight = 'normal';
            }
        } else {
            showToast(result.error || "Une erreur est survenue.", true);
        }
    } catch (error) {
        showToast("Erreur de connexion au serveur.", true);
    } finally {
        submitBtn.disabled = false;
        btnText.classList.remove('d-none');
        btnLoader.classList.add('d-none');
    }
}

function handleCharacterCount(e) {
    const charCountDisplay = document.getElementById('charCount');
    const minLength = 20;

    if (!charCountDisplay) return;

    const currentLength = e.target.value.length;
    charCountDisplay.textContent = `${currentLength} / ${minLength} min`;

    if (currentLength >= minLength) {
        charCountDisplay.classList.remove('text-secondary');
        charCountDisplay.classList.add('text-success');
        charCountDisplay.style.fontWeight = 'bold';
    } else {
        charCountDisplay.classList.add('text-secondary');
        charCountDisplay.classList.remove('text-success');
        charCountDisplay.style.fontWeight = 'normal';
    }
}

// RSS Modal Global Handlers
window.initRssModalHelpers = function() {
    const rssModalEl = document.getElementById('rssSubscribeModal');
    if (!rssModalEl || rssModalEl.dataset.helpersInitialized) return;
    rssModalEl.dataset.helpersInitialized = 'true';

    rssModalEl.addEventListener('show.bs.modal', function () {
        const msg = document.getElementById('rss-subscribe-msg');
        if (msg) { msg.style.display = 'none'; msg.innerHTML = ''; }
        
        if (typeof turnstile !== 'undefined') {
            window.initRssTurnstile(true); // Force re-render
        } else {
            let checkInt = setInterval(() => {
                if (typeof turnstile !== 'undefined') {
                    window.initRssTurnstile(true);
                    clearInterval(checkInt);
                }
            }, 500);
            setTimeout(() => clearInterval(checkInt), 5000);
        }
    });

    const rssForm = document.getElementById('rss-subscribe-form');
    if (rssForm) {
        rssForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('rss-email-input').value;
            const btn = document.getElementById('rss-submit-btn');
            const msg = document.getElementById('rss-subscribe-msg');
            const formData = new FormData(rssForm);
            const cfResponse = formData.get('cf-turnstile-response');

            if (!cfResponse) {
                msg.style.display = 'block';
                msg.innerHTML = '<span class="text-danger">Veuillez valider le Captcha Cloudflare.</span>';
                return;
            }
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> En cours...';
            btn.disabled = true;
            msg.style.display = 'none';

            fetch('/api/subscribe-rss.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ email: email, 'cf-turnstile-response': cfResponse })
            })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = 'M\'inscrire';
                msg.style.display = 'block';
                if(data.success) {
                    msg.innerHTML = `<span class="text-success">${data.message || 'Inscription réussie !'}</span>`;
                    rssForm.reset();
                    try { turnstile.reset('#cf-turnstile-rss'); } catch(e) {}
                    setTimeout(() => {
                        if (window.bootstrap && bootstrap.Modal) {
                            var modal = bootstrap.Modal.getInstance(rssModalEl);
                            if (modal) modal.hide();
                        }
                    }, 2000);
                } else {
                    msg.innerHTML = `<span class="text-danger">${data.error || 'Erreur inconnue'}</span>`;
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = 'M\'inscrire';
                msg.style.display = 'block';
                msg.innerHTML = '<span class="text-danger">Erreur de connexion au serveur.</span>';
            });
        });
    }
};

// Global Re-Init function called by Swup and on DOMContentLoaded
window.initContactFormHelpers = function () {
    const form = document.getElementById('contactForm');
    const messageInput = document.getElementById('message');

    // Re-init RSS and Login helpers too
    window.initRssModalHelpers();
    window.initLoginTurnstile();

    // Remove old listeners to prevent duplicates during Swup navigation
    if (form) {
        form.removeEventListener('submit', handleContactFormSubmit);
        form.addEventListener('submit', handleContactFormSubmit);
    }

    if (messageInput) {
        messageInput.removeEventListener('input', handleCharacterCount);
        messageInput.addEventListener('input', handleCharacterCount);
    }

    contactFormStartTime = Date.now();
};

document.addEventListener('DOMContentLoaded', () => {
    window.initContactFormHelpers();
    if (typeof turnstile !== 'undefined') {
        setTimeout(window.initContactTurnstile, 150);
    }
});
