<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

$page_title = t('contact_title') . " - SlapIA";
$page_description = t('contact_subtitle');
$page_image = '/assets/img/logo.png';
$page_needs_turnstile = true;
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
        border-color: #10b981 !important;
        /* Emerald 500 */
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    }

    /* Invalid State - Red Border (Only if user has typed something) */
    .form-control:invalid:not(:placeholder-shown) {
        border-color: #ef4444 !important;
        /* Red 500 */
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        animation: shake 0.4s ease-in-out;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-5px);
        }

        75% {
            transform: translateX(5px);
        }
    }
</style>

<section class="py-5 mt-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            
            <!-- Left Column: Info & Text -->
            <div class="col-lg-5 mb-5 mb-lg-0">
                <div class="pe-lg-4">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-white bg-opacity-10 border border-white border-opacity-10 mb-4">
                        <span class="text-primary"><i class="fas fa-paper-plane"></i></span>
                        <span class="text-white small fw-bold text-uppercase tracking-wider">Contactez-nous</span>
                    </div>
                    
                    <h1 class="display-4 fw-bold mb-4 text-white" style="letter-spacing: -1px;">
                        <?php echo t('contact_title'); ?>
                    </h1>
                    
                    <p class="text-secondary lead mb-5" style="font-size: 1.15rem; line-height: 1.7;">
                        <?php echo t('contact_subtitle'); ?>
                    </p>
                    
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex align-items-center gap-4 bento-card p-3 rounded-4" style="background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.05);">
                            <div class="d-flex justify-content-center align-items-center rounded-circle bg-primary bg-opacity-10 text-primary flex-shrink-0" style="width: 56px; height: 56px; font-size: 1.25rem;">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h6 class="text-white mb-1 fw-bold">Email</h6>
                                <a href="mailto:contact@slapia.com" class="text-secondary text-decoration-none transition-all hover-white">contact@slapia.com</a>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center gap-4 bento-card p-3 rounded-4" style="background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.05);">
                            <div class="d-flex justify-content-center align-items-center rounded-circle bg-info bg-opacity-10 text-info flex-shrink-0" style="width: 56px; height: 56px; font-size: 1.25rem;">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h6 class="text-white mb-1 fw-bold">Localisation</h6>
                                <span class="text-secondary">Paris, France (Intervention globale)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Form -->
            <div class="col-lg-7">
                <form id="contactForm" class="bento-card bento-card-glow p-4 p-md-5 h-100 d-flex flex-column contact-main-card rounded-5"
                          data-msg-profanity="<?php echo htmlspecialchars(t('toast_profanity'), ENT_QUOTES, 'UTF-8'); ?>"
                          data-msg-short="<?php echo htmlspecialchars(t('toast_too_short'), ENT_QUOTES, 'UTF-8'); ?>"
                          data-msg-success="<?php echo htmlspecialchars(t('toast_success_long'), ENT_QUOTES, 'UTF-8'); ?>"
                          data-msg-generic="<?php echo htmlspecialchars(t('toast_error_generic'), ENT_QUOTES, 'UTF-8'); ?>"
                          data-msg-conn="<?php echo htmlspecialchars(t('toast_error_connection'), ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="prenom"
                                    class="text-secondary small fw-bold mb-2 text-uppercase"><?php echo t('form_firstname'); ?></label>
                                <input type="text" id="prenom" name="prenom" required placeholder=" "
                                    class="form-control bg-transparent text-white border-secondary border-opacity-25 rounded-3 py-3"
                                    style="background: rgba(255,255,255,0.02) !important;">
                            </div>
                            <div class="col-md-6">
                                <label for="nom"
                                    class="text-secondary small fw-bold mb-2 text-uppercase"><?php echo t('form_lastname'); ?></label>
                                <input type="text" id="nom" name="nom" required placeholder=" "
                                    class="form-control bg-transparent text-white border-secondary border-opacity-25 rounded-3 py-3"
                                    style="background: rgba(255,255,255,0.02) !important;">
                            </div>
                            <div class="col-12">
                                <label for="email"
                                    class="text-secondary small fw-bold mb-2 text-uppercase"><?php echo t('form_email'); ?></label>
                                <input type="email" id="email" name="email" required placeholder=" "
                                    class="form-control bg-transparent text-white border-secondary border-opacity-25 rounded-3 py-3"
                                    style="background: rgba(255,255,255,0.02) !important;">
                            </div>
                            <div class="col-12">
                                <label for="message"
                                    class="text-secondary small fw-bold mb-2 text-uppercase"><?php echo t('message'); ?></label>
                                <textarea id="message" name="message" required minlength="20" placeholder=" "
                                    class="form-control bg-transparent text-white border-secondary border-opacity-25 rounded-3 py-3"
                                    rows="4" style="background: rgba(255,255,255,0.02) !important;"></textarea>
                                <div class="d-flex justify-content-end mt-1">
                                    <small id="charCount" class="text-secondary"
                                        style="font-size: 0.8rem; opacity: 0.7;">0 / 20 min</small>
                                </div>
                            </div>
                            <div class="col-12 mt-4">
                                <!-- Cloudflare Turnstile (Visible Security) -->
                                <div class="d-flex justify-content-center mb-3">
                                    <div id="cf-turnstile-container"
                                        data-sitekey="<?php echo config('TURNSTILE_SITE_KEY'); ?>"></div>
                                </div>

                                <!-- Honeypot Field (Backup Hidden) -->
                                <div style="display:none;">
                                    <label for="website_check">Website Check</label>
                                    <input type="text" id="website_check" name="website_check" tabindex="-1"
                                        autocomplete="off">
                                </div>

                                <button type="submit" id="submitBtn" class="btn-apple w-100 justify-content-center py-3">
                                    <span id="btnText" class="fw-bold"><?php echo t('form_send_btn'); ?></span>
                                    <span id="btnLoader" class="d-none">
                                        <span class="spinner-border spinner-border-sm me-2" role="status"
                                            aria-hidden="true"></span>
                                        <?php echo t('form_sending'); ?>
                                    </span>
                                </button>
                            </div>
                        </div>
                </form>
            </div>
        </div>
    </div>
</section>



<?php include '../includes/footer.php'; ?>