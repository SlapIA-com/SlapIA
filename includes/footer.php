</main>
<footer class="text-center text-lg-start">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <h4 class="text-white mb-4">SlapIA</h4>
                <p class="text-secondary">
                    <?php echo t('footer_desc'); ?>
                </p>
                <div class="d-flex gap-3 mt-4">
                    <a href="https://www.linkedin.com/company/slapia/" target="_blank" class="text-secondary hover-white"><i class="fab fa-linkedin fs-5"></i></a>
                    <a href="https://github.com/SlapIA-com" target="_blank" class="text-secondary hover-white"><i class="fab fa-github fs-5"></i></a>
                </div>
                
            </div>
            
            <div class="col-6 col-lg-2 offset-lg-2">
                <h6 class="text-white fw-bold mb-4"><?php echo t('footer_explore'); ?></h6>
                <ul class="list-unstyled">
                    <li class="mb-3"><a href="/formation" class="text-secondary text-decoration-none"><?php echo t('formations'); ?></a></li>
                    <li class="mb-3"><a href="/entreprises" class="text-secondary text-decoration-none"><?php echo t('companies'); ?></a></li>
                    <li class="mb-3"><a href="/how-it-works" class="text-secondary text-decoration-none"><?php echo t('how_it_works'); ?></a></li>
                </ul>
            </div>
            
            <div class="col-6 col-lg-2">
                <h6 class="text-white fw-bold mb-4"><?php echo t('footer_legal'); ?></h6>
                <ul class="list-unstyled">
                    <li class="mb-3"><a href="/mentions-legales" class="text-secondary text-decoration-none"><?php echo t('nav_mentions'); ?></a></li>
                </ul>
            </div>
        </div>
        
        <div class="border-top border-secondary border-opacity-10 mt-5 pt-4 text-center">
            <p class="text-secondary small mb-0">&copy; 2026 SlapIA Inc. <?php echo t('designed_with_precision'); ?></p>
        </div>
    </div>
</footer>

<!-- Cookie Banner -->
<!-- Cookie Banner (GDPR Regulatory) -->
<div id="cookie-banner" class="cookie-overlay">
   <!-- ... (cookie banner content implied/kept) ... -->
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global Scripts -->
<script src="/assets/js/carousel.js"></script>
<script src="/assets/js/typewriter.js"></script>

<!-- Cookie Banner Inner Content (Moved from inside script) -->
<div id="cookie-banner-content" style="display:none;"> <!-- Hidden by default, JS moves it or usage implies it's inside #cookie-banner -->
    <!-- Actually, looking at the code structure, this seems to be the content intended for #cookie-banner container defined above -->
    
    <!-- View 1: Main -->
    <div id="cookie-view-main" class="cookie-view">
        <div class="cookie-icon">🍪</div>
        <h3 class="cookie-title"><?php echo t('cookies_title'); ?></h3>
        <p class="cookie-text"><?php echo t('cookie_banner_text'); ?></p>
        
        <div class="cookie-actions">
            <button id="cookie-accept-all" class="cookie-btn cookie-btn-primary"><?php echo t('cookie_accept_all'); ?></button>
            <button id="cookie-deny-all" class="cookie-btn cookie-btn-secondary"><?php echo t('cookie_deny_all'); ?></button>
            <button id="cookie-customize" class="cookie-link-btn"><?php echo t('cookie_customize'); ?></button>
        </div>
        
        <div class="mt-3">
            <a href="/mentions-legales#privacy" class="text-white-50 small text-decoration-none hover-white"><?php echo t('cookie_policy_link'); ?></a>
        </div>
    </div>

    <!-- View 2: Preferences -->
    <div id="cookie-view-preferences" class="cookie-view hidden">
        <div class="d-flex align-items-center mb-4">
            <button id="cookie-back" class="btn btn-sm btn-circle text-white me-3" style="background: rgba(255,255,255,0.1); width: 32px; height: 32px; border-radius: 50%; border: none;"><i class="fas fa-arrow-left"></i></button>
            <h3 class="cookie-title mb-0 text-start"><?php echo t('cookie_preferences_title'); ?></h3>
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Javascript logic for animations/etc used to be here or is below.
        // We removed the invalid HTML.
        
        // Inject the content into the main banner if needed, or if the JS expects it there.
        // The cookie-banner.js likely handles the logic if elements exist.
        // We'll leave the HTML in the DOM (above).
        
        // Move content into #cookie-banner if it's empty?
        const bannerContainer = document.getElementById('cookie-banner');
        const contentSource = document.getElementById('cookie-banner-content');
        if (bannerContainer && contentSource && bannerContainer.innerHTML.trim() === '') {
             bannerContainer.innerHTML = contentSource.innerHTML;
        }
        
    });
        
        // --- Page transitions handled by CSS View Transitions API ---
        
        // --- 1. Liquid Menu Logic ---
        const activeBg = document.querySelector('.nav-active-bg');
        const activeLink = document.querySelector('.dock-link.active');
        const navContainer = document.querySelector('.dock-links-container');

        function movePill(element) {
            if (!element || !activeBg || !navContainer) return;

            // Get coordinates relative to the container
            const containerRect = navContainer.getBoundingClientRect();
            const linkRect = element.getBoundingClientRect();

            const left = linkRect.left - containerRect.left;
            const top = linkRect.top - containerRect.top;
            const width = linkRect.width;
            const height = linkRect.height;

            activeBg.style.opacity = '1';
            activeBg.style.width = `${width}px`;
            activeBg.style.height = `${height}px`; // Match height (minus padding usually handled by CSS, but exact is fine)
            activeBg.style.transform = `translate(${left}px, ${top}px)`;
        }

        // Initialize position
        if (activeLink) {
            // Need a slight timeout to ensure fonts/layout are stable
            setTimeout(() => movePill(activeLink), 50);
        }

        // --- 2. Spotlight Effect ---
        const cards = document.querySelectorAll('.bento-card');
        
        document.addEventListener('mousemove', (e) => {
            cards.forEach(card => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                card.style.setProperty('--mouse-x', `${x}px`);
                card.style.setProperty('--mouse-y', `${y}px`);
            });
        });

        // --- 3. Staggered Entry ---
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    // Add delay class based on index logic or fixed
                    entry.target.classList.add('animate-enter');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.bento-card, .display-title, .hero-section p').forEach((el, i) => {
            el.style.opacity = '0'; // Ensure hidden initially
            el.classList.add('animate-enter');
            // Stagger delays manually using style to avoid messy classes
            el.style.animationDelay = `${i * 0.1}s`;
        });
    });
</script>
</body>
</html>

<!-- Live Visitor Counter Badge (Floating) -->
<div class="floating-visitor-counter position-fixed bottom-0 start-0 m-3 p-2 rounded-pill bg-dark border border-secondary border-opacity-25 shadow-lg d-flex align-items-center gap-2 fade-in" style="z-index: 9999; backdrop-filter: blur(10px);">
    <span class="position-relative d-flex" style="width: 8px; height: 8px;">
      <span class="position-absolute rounded-circle bg-success opacity-75" style="width: 100%; height: 100%; animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;"></span>
      <span class="position-relative d-inline-block rounded-circle bg-success" style="width: 100%; height: 100%;"></span>
    </span>
    <span class="text-white small fw-bold" style="font-size: 0.75rem;">
        <span id="live-visitor-count">...</span> <?php echo t('live_counter_viewing'); ?>
    </span>
</div>
<script src="/assets/js/live-counter.js"></script>

<style>
@keyframes ping {
    75%, 100% {
        transform: scale(2);
        opacity: 0;
    }
}
</style>

<script src="/assets/js/matrix.js"></script>
<script src="/assets/js/cookie-banner.js"></script>
<script src="/assets/js/tilt.js"></script>
<script src="/assets/js/console-egg.js"></script>
<!-- Scroll Indicator (Static) -->
<div id="scroll-indicator" class="scroll-indicator-glass">
    <svg width="100%" height="100%" viewBox="0 0 100 100" style="transform: rotate(-90deg);">
        <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="5" />
        <circle id="scroll-progress" cx="50" cy="50" r="45" fill="none" stroke="#bf5af2" stroke-width="5" stroke-dasharray="283" stroke-dashoffset="283" stroke-linecap="round" />
    </svg>
    <div class="arrow-icon">↑</div>
</div>

<style>
    .scroll-indicator-glass {
        position: fixed;
        bottom: 110px; /* Adjusted spacing */
        right: 24px;   /* Standard widget alignment to match chat */
        width: 72px;   /* Increased to match chat bubble size */
        height: 72px;
        z-index: 10000;
        cursor: pointer;
        opacity: 0; 
        transform: translateY(20px);
        transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
        border-radius: 50%;
        background: rgba(15, 15, 15, 0.8);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }
    .scroll-indicator-glass.visible {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
    .scroll-indicator-glass:hover {
        border-color: #bf5af2;
        box-shadow: 0 0 20px rgba(191, 90, 242, 0.4);
        transform: scale(1.05); /* Subtle scale */
    }
    .arrow-icon {
        color: white;
        font-size: 28px; /* Larger icon */
        font-weight: bold;
    }
</style>

<script src="/assets/js/scroll-indicator.js?v=FIXED"></script>
<script src="/assets/js/emoji-rain.js"></script>

<!-- Swup.js for App-like Navigation -->
<script src="https://unpkg.com/swup@4"></script>
<script src="https://unpkg.com/@swup/scroll-plugin@3"></script>
<script src="/assets/js/animations.js"></script>
<script src="/assets/js/app-transition.js"></script>
