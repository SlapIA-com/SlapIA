<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

$page_title = t('contact_title') . " - SlapIA";
$page_description = t('contact_subtitle');
$page_image = '/assets/img/logo.png';
include '../includes/header.php'; ?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ContactPage",
  "name": "Contact SlapIA",
  "description": "Contactez SlapIA pour une formation IA, un audit ou un projet d'automatisation.",
  "url": "https://www.slapia.com/contact",
  "mainEntity": {
    "@type": "Organization",
    "name": "SlapIA",
    "url": "https://www.slapia.com",
    "contactPoint": {
      "@type": "ContactPoint",
      "contactType": "customer support",
      "availableLanguage": ["fr", "en"]
    }
  }
}
</script>



<!-- Toast Notification -->
<div id="toastNotification" class="toast-notification">
    <div class="toast-content">
        <i class="fas fa-check-circle toast-icon"></i>
        <span id="toastMessage"><?php echo t('contact_toast_success'); ?></span>
        <button type="button" class="toast-close" onclick="closeToast()">&times;</button>
    </div>
    <div class="toast-progress">
        <div id="toastProgressBar" class="toast-progress-bar"></div>
    </div>
</div>

<style>
.toast-notification {
    position: fixed;
    top: -100px;
    left: 50%;
    transform: translateX(-50%);
    min-width: 350px;
    max-width: 90%;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(16, 185, 129, 0.4);
    z-index: 9999;
    opacity: 0;
    transition: top 0.4s ease, opacity 0.4s ease;
    overflow: hidden;
}

.toast-notification.show {
    top: 100px;
    opacity: 1;
}

.toast-notification.error {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 10px 40px rgba(239, 68, 68, 0.4);
}

.toast-content {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    gap: 12px;
}

.toast-icon {
    font-size: 1.4rem;
    color: white;
}

.toast-notification.error .toast-icon::before {
    content: "\f071";
}

#toastMessage {
    flex: 1;
    color: white;
    font-weight: 500;
    font-size: 0.95rem;
}

.toast-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 1.4rem;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
    line-height: 1;
}

.toast-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

.toast-progress {
    height: 4px;
    background: rgba(255, 255, 255, 0.3);
}

.toast-progress-bar {
    height: 100%;
    background: white;
    width: 100%;
    transition: width 5s linear;
}

.toast-progress-bar.animate {
    width: 0%;
}

/* Smart Validation Styling */
/* Default / Empty State - Neutral Border */
.form-control {
    border-color: rgba(255, 255, 255, 0.25) !important;
    transition: all 0.3s ease;
}

.form-control:focus {
    background: rgba(255, 255, 255, 0.05) !important;
    box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
}

/* Valid State - Green Border */
.form-control:valid {
    border-color: #10b981 !important; /* Emerald 500 */
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}

/* Invalid State - Red Border (Only if user has typed something) */
.form-control:invalid:not(:placeholder-shown) {
    border-color: #ef4444 !important; /* Red 500 */
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
    animation: shake 0.4s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
</style>

<section class="py-5 mt-5">
    <div class="container">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-8">
                <h1 class="display-title mb-3" style="font-size: 3rem;"><?php echo t('contact_title'); ?></h1>
                <p class="text-secondary lead"><?php echo t('contact_subtitle'); ?></p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="bento-card p-5">
                    <form id="contactForm">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="prenom" class="text-secondary small fw-bold mb-2 text-uppercase"><?php echo t('form_firstname'); ?></label>
                                <input type="text" id="prenom" name="prenom" required placeholder=" " class="form-control bg-transparent text-white border-secondary border-opacity-25 rounded-3 py-3" style="background: rgba(255,255,255,0.02) !important;">
                            </div>
                            <div class="col-md-6">
                                <label for="nom" class="text-secondary small fw-bold mb-2 text-uppercase"><?php echo t('form_lastname'); ?></label>
                                <input type="text" id="nom" name="nom" required placeholder=" " class="form-control bg-transparent text-white border-secondary border-opacity-25 rounded-3 py-3" style="background: rgba(255,255,255,0.02) !important;">
                            </div>
                            <div class="col-12">
                                <label for="email" class="text-secondary small fw-bold mb-2 text-uppercase"><?php echo t('form_email'); ?></label>
                                <input type="email" id="email" name="email" required placeholder=" " class="form-control bg-transparent text-white border-secondary border-opacity-25 rounded-3 py-3" style="background: rgba(255,255,255,0.02) !important;">
                            </div>
                            <div class="col-12">
                                <label for="message" class="text-secondary small fw-bold mb-2 text-uppercase"><?php echo t('message'); ?></label>
                                <textarea id="message" name="message" required minlength="20" placeholder=" " class="form-control bg-transparent text-white border-secondary border-opacity-25 rounded-3 py-3" rows="4" style="background: rgba(255,255,255,0.02) !important;"></textarea>
                                <div class="d-flex justify-content-end mt-1">
                                    <small id="charCount" class="text-secondary" style="font-size: 0.8rem; opacity: 0.7;">0 / 20 min</small>
                                </div>
                            </div>
                            <div class="col-12 mt-4">
                                <!-- Cloudflare Turnstile (Visible Security) -->
                                <div class="d-flex justify-content-center mb-3">
                                    <div class="cf-turnstile" data-sitekey="<?php echo config('TURNSTILE_SITE_KEY'); ?>" data-theme="dark"></div>
                                </div>
                                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

                                <!-- Honeypot Field (Backup Hidden) -->
                                <div style="display:none;">
                                    <label for="website_check">Website Check</label>
                                    <input type="text" id="website_check" name="website_check" tabindex="-1" autocomplete="off">
                                </div>

                                <button type="submit" id="submitBtn" class="btn-apple w-100 justify-content-center">
                                    <span id="btnText"><?php echo t('form_send_btn'); ?></span>
                                    <span id="btnLoader" class="d-none">
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        <?php echo t('form_sending'); ?>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
let toastTimeout;

function showToast(message, isError = false) {
    const toast = document.getElementById('toastNotification');
    const toastMessage = document.getElementById('toastMessage');
    const progressBar = document.getElementById('toastProgressBar');
    
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
    toast.classList.remove('show');
    if (toastTimeout) {
        clearTimeout(toastTimeout);
    }
}

const startTime = Date.now();

document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Anti-Spam Checks
    const honeycomb = document.getElementById('website_check').value;
    if (honeycomb) {
        console.log('Spam detected (honeycomb)');
        return; // Silent fail for bots
    }

    const timeDiff = Date.now() - startTime;
    if (timeDiff < 3000) {
        // Submitted too fast (< 3 seconds)
        console.log('Spam detected (too fast)');
        showToast(<?php echo json_encode(t('toast_wait')); ?>, true);
        return;
    }

    // Content Security Check
    const messageContent = document.getElementById('message').value.toLowerCase();
    const forbiddenWords = ['caca', 'connard', 'pute', 'salope', 'batard', 'encule', 'merde', 'chiotte', 'bite', 'couille'];
    const hasForbiddenWord = forbiddenWords.some(word => messageContent.includes(word));

    if (hasForbiddenWord) {
        showToast(<?php echo json_encode(t('toast_profanity')); ?>, true);
        return;
    }

    if (messageContent.length < 20) {
        showToast(<?php echo json_encode(t('toast_too_short')); ?>, true);
        return;
    }

    const form = this;
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
            showToast(<?php echo json_encode(t('toast_success_long')); ?>);
            form.reset();
        } else {
            showToast(result.error || <?php echo json_encode(t('toast_error_generic')); ?>, true);
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast(<?php echo json_encode(t('toast_error_connection')); ?>, true);
    } finally {
        // Désactiver le loader
        submitBtn.disabled = false;
        btnText.classList.remove('d-none');
        btnLoader.classList.add('d-none');
    }
});

// Character Counter Logic
const messageInput = document.getElementById('message');
const charCountDisplay = document.getElementById('charCount');
const minLength = 20;

if (messageInput && charCountDisplay) {
    messageInput.addEventListener('input', function() {
        const currentLength = this.value.length;
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
    });
}
</script>

<?php include '../includes/footer.php'; ?>

