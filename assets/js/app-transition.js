document.addEventListener('DOMContentLoaded', () => {
    // Basic Swup Initialization
    const swup = new Swup({
        containers: ['#swup'],
        plugins: [
            new SwupSlideTheme(),
            new SwupScrollPlugin({
                doScrollingRightAway: false,
                animateScroll: true,
                scrollFriction: 0.3,
                scrollAcceleration: 0.04,
            })
        ]
    });

    // Re-initialize scripts after content replacement
    swup.on('contentReplaced', function () {
        // 1. Re-init Mobile Menu
        const menuBtn = document.querySelector('.mobile-menu-btn');
        if (menuBtn) {
            // Remove old listeners to be safe (though DOM is replaced)
            // Re-attach listener
            document.querySelector('.mobile-menu-overlay').addEventListener('click', toggleMobileMenu);
        }

        // 2. Re-init Scroll Reveal
        if (window.initScrollReveal) {
            window.initScrollReveal();
        }

        // 3. Re-init Aurora if needed (it is fixed bg, so maybe remains?)
        // Aurora is outside #swup likely, so it stays.

        // 4. Re-init Typewriter if on home
        if (document.getElementById('typewriter-text') && window.initTypewriter) {
            window.initTypewriter();
        }

        // 5. Re-init Carousels
        if (window.initCarousels) window.initCarousels();

        // 6. Update Body Class or Active Links
        // Dock active link update
        const path = window.location.pathname;
        document.querySelectorAll('.dock-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === path || (path === '/' && link.getAttribute('href') === 'index.php')) {
                link.classList.add('active');
            }
        });

    });
});
