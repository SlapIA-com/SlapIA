</main>
<?php
include_once 'github-release.php';
$footerToolCommit = fetchLatestCommitSubject();
?>
<footer class="text-center text-lg-start">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <h4 class="text-white mb-4 d-flex align-items-center">
                    <img src="/assets/img/brand/logo.svg" alt="SlapIA Logo" style="height: 28px; width: auto; margin-right: 10px;">
                    SlapIA
                </h4>
                <p class="text-secondary">
                    <?php echo t('footer_desc'); ?>
                </p>
                <div class="d-flex gap-3 mt-4">
                    <a href="https://www.linkedin.com/company/slapia/" target="_blank"
                        class="text-secondary hover-white"><i class="fab fa-linkedin fs-5"></i></a>
                    <a href="https://github.com/SlapIA-com" target="_blank" class="text-secondary hover-white"><i
                            class="fab fa-github fs-5"></i></a>
                </div>

            </div>

            <div class="col-6 col-lg-2 offset-lg-2">
                <h6 class="text-white fw-bold mb-4"><?php echo t('footer_explore'); ?></h6>
                <ul class="list-unstyled">
                    <li class="mb-3"><a href="/formation"
                            class="text-secondary text-decoration-none"><?php echo t('formations'); ?></a></li>
                    <li class="mb-3"><a href="/entreprises"
                            class="text-secondary text-decoration-none"><?php echo t('companies'); ?></a></li>
                    <li class="mb-3"><a href="/how-it-works"
                            class="text-secondary text-decoration-none"><?php echo t('how_it_works'); ?></a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h6 class="text-white fw-bold mb-4"><?php echo t('footer_legal'); ?></h6>
                <ul class="list-unstyled">
                    <li class="mb-3"><a href="/mentions-legales"
                            class="text-secondary text-decoration-none"><?php echo t('nav_mentions'); ?></a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h6 class="text-white fw-bold mb-4"><?php echo t('solution_title'); ?></h6>
                <ul class="list-unstyled">
                    <li class="mb-3"><a href="/solution"
                            class="text-secondary text-decoration-none"><?php echo t('solution'); ?></a></li>
                    <?php if (!empty($footerToolCommit)): ?>
                        <li class="mb-3">
                            <span class="badge bg-white bg-opacity-10 text-secondary fw-normal text-truncate d-inline-block"
                                style="max-width: 100%; vertical-align: bottom;"
                                title="<?php echo htmlspecialchars($footerToolCommit, ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="fas fa-code-commit me-1"></i><?php echo htmlspecialchars($footerToolCommit, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="border-top border-secondary border-opacity-10 mt-5 pt-4 text-center">
            <p class="text-secondary small mb-0">&copy; 2026 SlapIA Inc. <?php echo t('designed_with_precision'); ?></p>
        </div>
    </div>
</footer>

<!-- Cookie Banner (GDPR) -->
<div id="cookie-banner" class="cookie-overlay">
    <div class="cookie-modal">
        <!-- View 1: Main -->
        <div id="cookie-view-main" class="cookie-view">
            <div class="cookie-icon">🍪</div>
            <h3 class="cookie-title"><?php echo t('cookies_title'); ?></h3>
            <p class="cookie-text"><?php echo t('cookie_banner_text'); ?></p>

            <div class="cookie-actions">
                <button id="cookie-accept-all"
                    class="cookie-btn cookie-btn-primary"><?php echo t('cookie_accept_all'); ?></button>
                <button id="cookie-deny-all"
                    class="cookie-btn cookie-btn-secondary"><?php echo t('cookie_deny_all'); ?></button>
                <button id="cookie-customize" class="cookie-link-btn"><?php echo t('cookie_customize'); ?></button>
            </div>

            <div class="mt-3">
                <a href="/mentions-legales#privacy"
                    class="text-white-50 small text-decoration-none hover-white"><?php echo t('cookie_policy_link'); ?></a>
            </div>
        </div>

        <!-- View 2: Preferences -->
        <div id="cookie-view-preferences" class="cookie-view hidden">
            <div class="d-flex align-items-center mb-4">
                <button id="cookie-back" class="btn btn-sm btn-circle text-white me-3"
                    style="background: rgba(255,255,255,0.1); width: 32px; height: 32px; border-radius: 50%; border: none; display: flex; align-items: center; justify-content: center;"><i
                        class="fas fa-arrow-left"></i></button>
                <h3 class="cookie-title mb-0 text-start" style="font-size: 1.25rem;">
                    <?php echo t('cookie_preferences_title'); ?>
                </h3>
            </div>

            <div class="cookie-preferences-list">
                <!-- Necessary -->
                <div class="cookie-preference-item">
                    <div class="cookie-pref-info text-start">
                        <h4><?php echo t('cookie_necessary_title'); ?></h4>
                        <p><?php echo t('cookie_necessary_desc'); ?></p>
                    </div>
                    <label class="cookie-toggle">
                        <input type="checkbox" checked disabled>
                        <span class="cookie-slider"></span>
                    </label>
                </div>

                <!-- Preferences (Language) -->
                <div class="cookie-preference-item">
                    <div class="cookie-pref-info text-start">
                        <h4><?php echo t('cookie_preferences_category_title'); ?></h4>
                        <p><?php echo t('cookie_preferences_category_desc'); ?></p>
                    </div>
                    <label class="cookie-toggle">
                        <input type="checkbox" id="cookie-toggle-preferences" checked>
                        <span class="cookie-slider"></span>
                    </label>
                </div>

            </div>

            <button id="cookie-save" class="cookie-btn cookie-btn-primary mt-2"><?php echo t('cookie_save'); ?></button>
        </div>
    </div>
</div>

<!-- RSS Subscribe Modal -->
<div class="modal fade" id="rssSubscribeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: rgba(18, 18, 18, 0.95); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(20px); border-radius: 20px;">
      <div class="modal-header border-bottom border-light border-opacity-10 position-relative p-4">
        <h5 class="modal-title text-warning fw-bold"><i class="fas fa-rss me-2"></i> <?php echo function_exists('t') ? t('rss_subscribe') : "S'abonner aux alertes"; ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; right: 20px; top: 20px;"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <p class="text-white-50 mb-4">Laissez votre e-mail pour être prévenu de nos prochains articles et actualités exclusives.</p>
        <form id="rss-subscribe-form">
          <input type="email" id="rss-email-input" class="form-control mb-3 text-white fw-medium" placeholder="votre.email@exemple.com" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff !important;">
          <style>
              #rss-email-input::placeholder { color: rgba(255,255,255,0.4); }
              #rss-email-input:-webkit-autofill,
              #rss-email-input:-webkit-autofill:hover, 
              #rss-email-input:-webkit-autofill:focus {
                  -webkit-text-fill-color: white;
                  -webkit-box-shadow: 0 0 0px 1000px rgba(18,18,18,0.95) inset;
                  transition: background-color 5000s ease-in-out 0s;
              }
          </style>
          
          <!-- Cloudflare Turnstile -->
          <div class="d-flex justify-content-center mb-3">
              <div id="cf-turnstile-rss" data-sitekey="<?php echo config('TURNSTILE_SITE_KEY'); ?>"></div>
          </div>

          <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold" id="rss-submit-btn">
             M'inscrire
          </button>
        </form>
        <div id="rss-subscribe-msg" class="mt-3 small" style="display:none;"></div>
      </div>
    </div>
  </div>
</div>

<!-- RSS Unsubscribe Modal -->
<div class="modal fade" id="rssUnsubscribeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: rgba(18, 18, 18, 0.95); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(20px); border-radius: 20px;">
      <div class="modal-header border-bottom border-light border-opacity-10 position-relative p-4">
        <h5 class="modal-title text-secondary fw-bold"><i class="fas fa-bell-slash me-2"></i> Se désabonner</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; right: 20px; top: 20px;"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <p class="text-white-50 mb-4">Entrez l'adresse e-mail avec laquelle vous vous êtes inscrit pour vous désabonner de nos alertes.</p>
        <form id="rss-unsubscribe-form">
          <input type="email" id="rss-unsub-email-input" class="form-control mb-3 text-white fw-medium" placeholder="votre.email@exemple.com" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff !important;">
          <style>
              #rss-unsub-email-input::placeholder { color: rgba(255,255,255,0.4); }
              #rss-unsub-email-input:-webkit-autofill,
              #rss-unsub-email-input:-webkit-autofill:hover,
              #rss-unsub-email-input:-webkit-autofill:focus {
                  -webkit-text-fill-color: white;
                  -webkit-box-shadow: 0 0 0px 1000px rgba(18,18,18,0.95) inset;
                  transition: background-color 5000s ease-in-out 0s;
              }
          </style>

          <!-- Cloudflare Turnstile -->
          <div class="d-flex justify-content-center mb-3">
              <div id="cf-turnstile-unsub" data-sitekey="<?php echo config('TURNSTILE_SITE_KEY'); ?>"></div>
          </div>

          <button type="submit" class="btn btn-outline-secondary w-100 rounded-pill fw-bold" id="rss-unsub-btn">
            Me désabonner
          </button>
        </form>
        <div id="rss-unsub-msg" class="mt-3 small" style="display:none;"></div>
      </div>
    </div>
  </div>
</div>

<!-- Live Visitor Counter Badge (Floating) -->
<div class="floating-visitor-counter position-fixed bottom-0 start-0 m-3 p-2 rounded-pill bg-dark border border-secondary border-opacity-25 shadow-lg d-flex align-items-center gap-2 fade-in"
    style="z-index: 9999; backdrop-filter: blur(10px);">
    <span class="position-relative d-flex" style="width: 8px; height: 8px;">
        <span class="position-absolute rounded-circle bg-success opacity-75"
            style="width: 100%; height: 100%; animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;"></span>
        <span class="position-relative d-inline-block rounded-circle bg-success"
            style="width: 100%; height: 100%;"></span>
    </span>
    <span class="text-white small fw-bold" style="font-size: 0.75rem;">
        <span id="live-visitor-count">...</span> <?php echo t('live_counter_viewing'); ?>
    </span>
</div>


<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php
// Helper for JS/CSS auto cache-busting based on file modification time.
// Use project-relative paths instead of relying on $_SERVER['DOCUMENT_ROOT'].
if (!function_exists('asset_url')) {
    function asset_url($path) {
        // Normalize incoming path and compute absolute file path from project root
        $cleanPath = ltrim($path, '/\\');
        $projectRoot = realpath(dirname(__DIR__));
        $file = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanPath);

        $v = file_exists($file) ? filemtime($file) : time();
        return '/' . $cleanPath . '?v=' . $v;
    }
}
?>
<script src="<?php echo asset_url('/assets/js/carousel.js'); ?>"></script>
<script src="<?php echo asset_url('/assets/js/typewriter.js'); ?>"></script>
<script src="<?php echo asset_url('/assets/js/live-counter.js'); ?>"></script>
<script src="<?php echo asset_url('/assets/js/matrix.js'); ?>"></script>
<script src="<?php echo asset_url('/assets/js/cookie-banner.js'); ?>"></script>
<!-- Removed tilt.js as requested -->
<script src="<?php echo asset_url('/assets/js/console-egg.js'); ?>"></script>
<script src="<?php echo asset_url('/assets/js/emoji-rain.js'); ?>"></script>
<script src="<?php echo asset_url('/assets/js/interactions.js'); ?>"></script>
<script src="<?php echo asset_url('/assets/js/contact-form.js'); ?>"></script>
<script src="<?php echo asset_url('/assets/js/login-form.js'); ?>"></script>
<script src="<?php echo asset_url('/assets/js/reset-password.js'); ?>"></script>
<script src="<?php echo asset_url('/assets/js/lightbox.js'); ?>"></script>

<!-- Swup.js for App-like Navigation (pinned versions) -->
<script src="https://unpkg.com/swup@4.6.0/dist/Swup.umd.js"></script>
<script src="https://unpkg.com/@swup/scroll-plugin@3.3.4/dist/SwupScrollPlugin.umd.js"></script>
<script src="https://unpkg.com/@swup/head-plugin@2.2.0/dist/SwupHeadPlugin.umd.js"></script>
<script src="https://unpkg.com/@swup/scripts-plugin@2.1.1/dist/SwupScriptsPlugin.umd.js"></script>
<script src="<?php echo asset_url('/assets/js/animations.js'); ?>"></script>
<script src="<?php echo asset_url('/assets/js/blog-overlay.js'); ?>"></script>
<script src="<?php echo asset_url('/assets/js/app-transition.js'); ?>"></script>

<script>
    // Spotlight Effect on bento cards
    document.addEventListener('DOMContentLoaded', function () {
        const cards = document.querySelectorAll('.bento-card');
        document.addEventListener('mousemove', (e) => {
            cards.forEach(card => {
                const rect = card.getBoundingClientRect();
                card.style.setProperty('--mouse-x', `${e.clientX - rect.left}px`);
                card.style.setProperty('--mouse-y', `${e.clientY - rect.top}px`);
            });
        });
    });
</script>

</body>

</html>