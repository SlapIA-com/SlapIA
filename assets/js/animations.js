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

    // Safety fallback: Force visible after 500ms if something blocked the observer
    setTimeout(() => {
        hiddenElements.forEach(el => {
            // Check if still hidden
            const style = window.getComputedStyle(el);
            if (style.opacity === '0' || style.visibility === 'hidden') {
                el.classList.add('is-visible');
                el.style.opacity = '1'; // Brute force
                el.style.transform = 'translateY(0) scale(1)';
            }
        });
    }, 500);
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
