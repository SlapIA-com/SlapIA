/**
 * Mandatory Cookie Consent Modal
 * IOS 26 Glass Style
 * Blocks interaction until accepted.
 */

document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('cookie-banner');
    const btn = document.getElementById('cookie-accept');

    if (!overlay || !btn) return;

    // Check if user already accepted
    const hasConsented = localStorage.getItem('cookie_consent');

    if (!hasConsented) {
        // Show banner immediately and block scroll
        blockAccess();
    } else {
        // Ensure hidden
        overlay.classList.remove('show');
    }

    function blockAccess() {
        document.body.style.overflow = 'hidden'; // Lock scroll
        // document.body.style.height = '100vh'; // Prevent mobile scroll (optional, can be glitchy)

        // Slight delay for specific animation effect or just instant
        requestAnimationFrame(() => {
            overlay.classList.add('show');
        });
    }

    function grantAccess() {
        // Animation out
        overlay.classList.remove('show');

        // Restore scroll after transition
        setTimeout(() => {
            document.body.style.overflow = '';
            // document.body.style.height = ''; 
        }, 400);

        // Save consent
        localStorage.setItem('cookie_consent', 'true');
    }

    btn.addEventListener('click', grantAccess);
});
