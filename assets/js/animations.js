function initScrollReveal() {
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

    const hiddenElements = document.querySelectorAll('.scroll-reveal, .fade-in-up, .scroll-scale');
    hiddenElements.forEach((el) => observer.observe(el));

    // Safety fallback: Force visible after 2 seconds if something blocked the observer
    setTimeout(() => {
        hiddenElements.forEach(el => {
            if (getComputedStyle(el).opacity === '0') {
                el.classList.add('is-visible');
            }
        });
    }, 2000);
}

// Auto-init logic
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initScrollReveal);
} else {
    // DOM already loaded, run immediately
    initScrollReveal();
}

// Global fallback to force visibility if script loads late
window.onload = function () {
    setTimeout(initScrollReveal, 100);
};

// Expose to window for Swup
window.initScrollReveal = initScrollReveal;
