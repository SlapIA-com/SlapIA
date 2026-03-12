// contact-form.js
// Handles Turnstile and Contact Form Submission, compatible with Swup SPA navigation

let toastTimeout;
let contactFormStartTime;

// Initialize Turnstile for this form
window.initContactTurnstile = function () {
    const container = document.getElementById('cf-turnstile-container');
    if (!container) return;
    if (typeof turnstile === 'undefined') return;

    const sitekey = container.getAttribute('data-sitekey');
    if (!sitekey) return;

    try {
        // Remove any existing widget first (important for Swup re-navigation)
        turnstile.remove(container);
    } catch (e) { /* no widget to remove */ }

    try {
        turnstile.render(container, {
            sitekey: sitekey,
            theme: 'dark'
        });
    } catch (e) { console.error('Turnstile render error', e); }
};

// The global callback used by Turnstile script in header (first page load)
window.onloadTurnstileCallback = function () {
    window.initContactTurnstile();
};

function showToast(message, isError = false) {
    const toast = document.getElementById('toastNotification');
    const toastMessage = document.getElementById('toastMessage');
    const progressBar = document.getElementById('toastProgressBar');

    if (!toast || !toastMessage || !progressBar) return;

    // Clear any existing timeout
    if (toastTimeout) {
        clearTimeout(toastTimeout);
    }

    // Reset state
    toast.classList.remove('show', 'error');
    progressBar.classList.remove('animate');

    // Set message and style
    toastMessage.textContent = message;
    if (isError) {
        toast.classList.add('error');
    }

    // Force reflow to reset animation
    void progressBar.offsetWidth;

    // Show toast
    requestAnimationFrame(() => {
        toast.classList.add('show');
        progressBar.classList.add('animate');
    });

    // Auto-hide after 5 seconds
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
    const msgProfanity = form.getAttribute('data-msg-profanity') || 'Un texte inapproprié a été détecté.';
    const msgShort = form.getAttribute('data-msg-short') || 'Le message est trop court (min 20 caractères).';
    const msgSuccess = form.getAttribute('data-msg-success') || 'Message envoyé avec succès !';
    const msgGeneric = form.getAttribute('data-msg-generic') || 'Une erreur est survenue.';
    const msgConn = form.getAttribute('data-msg-conn') || 'Erreur de connexion. Veuillez réessayer.';

    // Anti-Spam Checks
    const honeycomb = document.getElementById('website_check').value;
    if (honeycomb) {
        console.log('Spam detected (honeycomb)');
        return; // Silent fail for bots
    }

    // Content Security Check
    const messageInput = document.getElementById('message');
    const messageContent = messageInput ? messageInput.value.toLowerCase() : '';
    const forbiddenWords = ['caca', 'connard', 'pute', 'salope', 'batard', 'encule', 'merde', 'chiotte', 'bite', 'couille'];
    const hasForbiddenWord = forbiddenWords.some(word => messageContent.includes(word));

    if (hasForbiddenWord) {
        showToast(msgProfanity, true);
        return;
    }

    if (messageContent.length < 20) {
        showToast(msgShort, true);
        return;
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnLoader = document.getElementById('btnLoader');

    // Activer le loader
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
        'cf-turnstile-response': formData.get('cf-turnstile-response') // Important: Send the token!
    };

    try {
        const response = await fetch('/api/notion-contact.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast(msgSuccess);
            form.reset();
            // Reset character count
            const charCountDisplay = document.getElementById('charCount');
            if (charCountDisplay) {
                charCountDisplay.textContent = '0 / 20 min';
                charCountDisplay.classList.add('text-secondary');
                charCountDisplay.classList.remove('text-success');
                charCountDisplay.style.fontWeight = 'normal';
            }
        } else {
            showToast(result.error || msgGeneric, true);
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast(msgConn, true);
    } finally {
        // Désactiver le loader
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

// Global Re-Init function called by Swup and on DOMContentLoaded
window.initContactFormHelpers = function () {
    const form = document.getElementById('contactForm');
    const messageInput = document.getElementById('message');

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
