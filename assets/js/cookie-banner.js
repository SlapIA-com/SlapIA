/**
 * Simple Cookie Consent Banner
 * "Purist" approach: No tracking cookies used, just functional.
 * This banner is purely informational and records "consent" in localStorage.
 */

document.addEventListener('DOMContentLoaded', () => {
    const banner = document.getElementById('cookie-banner');
    const btn = document.getElementById('cookie-accept');

    if (!banner || !btn) return;

    // Check if user already accepted
    const hasConsented = localStorage.getItem('cookie_consent');

    if (!hasConsented) {
        // Show banner after short delay
        setTimeout(() => {
            banner.classList.add('show');
        }, 1500);
    }

    btn.addEventListener('click', () => {
        // Save consent
        localStorage.setItem('cookie_consent', 'true');

        // Hide banner
        banner.classList.remove('show');
    });
});
