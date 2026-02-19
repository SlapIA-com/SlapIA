function initScrollReveal() {
    const allRevealElements = document.querySelectorAll('.scroll-reveal, .fade-in-up, .scroll-scale');

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

// Auto-init logic
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initScrollReveal);
} else {
    initScrollReveal();
}

// Expose to window for Swup
window.initScrollReveal = initScrollReveal;
