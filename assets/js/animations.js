function initScrollReveal() {
    // 1. Reset any stuck elements immediately (Swup might have cloned state)
    const allRevealElements = document.querySelectorAll('.scroll-reveal, .fade-in-up, .scroll-scale');

    // Create new observer
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -20px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Add class to trigger CSS transition
                entry.target.classList.add('is-visible');
                // Stop observing once visible
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    allRevealElements.forEach((el) => {
        // Ensure clean state if not already visible (Swup might preserve classes if cached)
        // If it was already visible in a previous page view (and cache usage), we might want to keep it?
        // Actually, for a "slide in" feel, we want them to animate again.
        // But Swup replaces the container, so elements are new.

        // Remove 'is-visible' just in case data was cached improperly or cloned
        el.classList.remove('is-visible');

        // Force opacity 0 via inline style to ensure it starts hidden, then remove it to let CSS take over
        // el.style.opacity = '0'; 
        // Actually, CSS handles opacity: 0 by default for these classes.

        observer.observe(el);
    });

    // Safety fallback: Ensure everything is visible after a safe delay
    // This catches cases where IntersectionObserver fails or elements are stuck
    setTimeout(() => {
        allRevealElements.forEach(el => {
            if (!el.classList.contains('is-visible')) {
                // Check if it's actually in viewport
                const rect = el.getBoundingClientRect();
                if (rect.top < window.innerHeight) {
                    el.classList.add('is-visible');
                }
            }
        });
    }, 1000); // 1s fallback
}

// Auto-init logic
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initScrollReveal);
} else {
    initScrollReveal();
}

// Expose to window for Swup
window.initScrollReveal = initScrollReveal;
