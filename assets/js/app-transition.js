document.addEventListener('DOMContentLoaded', () => {
    // Basic Swup Initialization
    const swup = new Swup({
        containers: ['#swup'],
        plugins: [
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
        try {
            if (window.initScrollReveal) window.initScrollReveal();
        } catch (e) { console.error("ScrollReveal init failed", e); }

        // 3. Re-init Aurora if needed (it is fixed bg, so maybe remains?)
        // Aurora is outside #swup likely, so it stays.

        // 4. Re-init Typewriter if on home
        try {
            if (document.getElementById('typewriter-text') && window.initTypewriter) {
                window.initTypewriter();
            }
        } catch (e) { console.error("Typewriter init failed", e); }

        // 5. Re-init Carousels
        try {
            if (window.initCarousels) window.initCarousels();
        } catch (e) { console.error("Carousel init failed", e); }

        // 6. Update Active Links
        const currentPath = window.location.pathname.replace(/\/$/, "").replace("/index.php", "") || "/";
        const links = document.querySelectorAll('.dock-link');
        let activeLink = null;

        links.forEach(link => {
            link.classList.remove('active');
            // Normalize link href too
            const linkHref = link.getAttribute('href').replace(/\/$/, "").replace("/index.php", "") || "/";

            if (linkHref === currentPath) {
                link.classList.add('active');
                activeLink = link;
            }
        });

        // 7. Move Pill
        const pill = document.querySelector('.nav-liquid-pill');
        const container = document.querySelector('.dock-links-container');

        if (activeLink && pill && container) {
            const containerRect = container.getBoundingClientRect();
            const elementRect = activeLink.getBoundingClientRect();
            const left = elementRect.left - containerRect.left;
            const width = elementRect.width;

            pill.style.left = left + 'px';
            pill.style.width = width + 'px';
            pill.style.opacity = '1';
        } else if (pill) {
            pill.style.opacity = '0';
        }

    });
});
