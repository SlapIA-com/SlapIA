function initScrollReveal() {
    const allRevealElements = document.querySelectorAll('.scroll-reveal, .fade-in-up, .scroll-scale, .stagger-row');

    // Create new observer
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -20px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    allRevealElements.forEach((el) => {
        // Prepare element for animation
        el.classList.remove('is-visible');
        observer.observe(el);

        // IMMEDIATE CHECK: If element is already in viewport (e.g. top of page), reveal it NOW.
        // Waiting for observer callback can sometimes delay "above fold" content too much.
        const rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight - 50) { // Slight buffer
            el.classList.add('is-visible');
            observer.unobserve(el);
        }
    });

    // Safety fallback: Force visible after 500ms
    setTimeout(() => {
        allRevealElements.forEach(el => {
            if (!el.classList.contains('is-visible')) {
                // If reasonably close to viewport or simply standard fallback
                el.classList.add('is-visible');
            }
        });
    }, 500);
}

function initAllAnimations() {
    initScrollReveal();
    if (window.initCounters) window.initCounters();
    if (window.initWhyCards) window.initWhyCards();
}

// Auto-init logic
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllAnimations);
} else {
    initAllAnimations();
}

// Expose to window for Swup
window.initScrollReveal = initScrollReveal;

// Global function so Swup can re-init after page transitions (moved from index.php)
window.initCounters = function() {
    const counters = document.querySelectorAll('.number-value');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const targetStr = el.getAttribute('data-count');
                if (!targetStr) return;
                
                const target = parseInt(targetStr);
                const suffix = el.getAttribute('data-suffix') || '';
                // Dynamic prefix: + for satisfaction/people if > 10, or empty
                const prefix = (target > 10 && !suffix.includes('%')) ? '+' : '';
                
                const duration = 2000;
                const start = performance.now();

                function animate(currentTime) {
                    const elapsed = currentTime - start;
                    const progress = Math.min(elapsed / duration, 1);
                    // Ease out cubic
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = Math.round(eased * target);
                    el.textContent = prefix + current + suffix;
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    }
                }
                requestAnimationFrame(animate);
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.2 });

    counters.forEach(counter => {
        // Reset to 0 before observing to ensure animation triggers on re-entry
        if (counter.hasAttribute('data-count')) {
            counter.textContent = '0';
            observer.observe(counter);
        }
    });
};

// Global function for why-card spotlight (moved from index.php)
window.initWhyCards = function() {
    const whyCards = document.querySelectorAll('.why-card');
    whyCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            card.style.setProperty('--mouse-x', `${e.clientX - rect.left}px`);
            card.style.setProperty('--mouse-y', `${e.clientY - rect.top}px`);
        });
    });
};
