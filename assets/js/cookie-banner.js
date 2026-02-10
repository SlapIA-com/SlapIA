/**
 * Regulatory Compliant Cookie Consent Manager (GDPR)
 * IOS 26 Glass Style
 * Blocks interaction until choice is made.
 * Supports granular consent: Necessary (Always), Analytics, Marketing, Preferences (Language).
 * Integration: Saves 'slapia_consent' cookie for PHP backend.
 */

document.addEventListener('DOMContentLoaded', () => {
    const overlay = document.getElementById('cookie-banner');

    // Buttons
    const btnAcceptAll = document.getElementById('cookie-accept-all');
    const btnDenyAll = document.getElementById('cookie-deny-all');
    const btnCustomize = document.getElementById('cookie-customize');
    const btnSave = document.getElementById('cookie-save');
    const btnBack = document.getElementById('cookie-back');

    // Views
    const viewMain = document.getElementById('cookie-view-main');
    const viewPreferences = document.getElementById('cookie-view-preferences');

    // Toggles
    const toggleAnalytics = document.getElementById('cookie-toggle-analytics');
    const toggleMarketing = document.getElementById('cookie-toggle-marketing');
    const togglePreferences = document.getElementById('cookie-toggle-preferences');

    if (!overlay) return;

    // Check if user already accepted
    const consentData = getCookie('slapia_consent'); // Read from cookie instead of localStorage
    let consent = null;

    if (consentData) {
        try {
            consent = JSON.parse(decodeURIComponent(consentData));
        } catch (e) {
            consent = null;
        }
    }

    if (!consent) {
        // Show banner immediately and block scroll
        blockAccess();
    } else {
        // Ensure hidden
        overlay.classList.remove('show');
        // Apply consent (init scripts)
        applyConsent(consent);
    }

    // --- Actions ---

    // 1. Accept All
    if (btnAcceptAll) {
        btnAcceptAll.addEventListener('click', () => {
            const fullConsent = {
                necessary: true,
                preferences: true,
                analytics: true,
                marketing: true,
                timestamp: new Date().toISOString()
            };
            saveAndClose(fullConsent);
        });
    }

    // 2. Deny All
    if (btnDenyAll) {
        btnDenyAll.addEventListener('click', () => {
            const minConsent = {
                necessary: true,
                preferences: false,
                analytics: false,
                marketing: false,
                timestamp: new Date().toISOString()
            };
            saveAndClose(minConsent);
        });
    }

    // 3. Customize (Switch View)
    if (btnCustomize) {
        btnCustomize.addEventListener('click', () => {
            viewMain.classList.add('hidden');
            viewPreferences.classList.remove('hidden');
        });
    }

    // 4. Back (Switch View)
    if (btnBack) {
        btnBack.addEventListener('click', () => {
            viewPreferences.classList.add('hidden');
            viewMain.classList.remove('hidden');
        });
    }

    // 5. Save Preferences
    if (btnSave) {
        btnSave.addEventListener('click', () => {
            const customConsent = {
                necessary: true,
                preferences: togglePreferences ? togglePreferences.checked : false,
                analytics: toggleAnalytics ? toggleAnalytics.checked : false,
                marketing: toggleMarketing ? toggleMarketing.checked : false,
                timestamp: new Date().toISOString()
            };
            saveAndClose(customConsent);
        });
    }

    // --- Helper Functions ---

    function saveAndClose(consentObj) {
        // Save to Cookie (valid for 1 year)
        const cookieValue = encodeURIComponent(JSON.stringify(consentObj));
        setCookie('slapia_consent', cookieValue, 365);

        // Also save to localStorage for easy JS access (redundancy)
        localStorage.setItem('cookie_consent_data', JSON.stringify(consentObj));

        applyConsent(consentObj);
        grantAccess();
    }

    function applyConsent(consent) {
        // Logic to initialize scripts based on consent
        if (consent.analytics) {
            // Init Google Analytics / Matomo
        }
        if (consent.marketing) {
            // Init Facebook Pixel / Ads
        }
        // Preferences (Language) are handled by PHP on next request based on cookie
    }

    function blockAccess() {
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => {
            overlay.classList.add('show');
        });
    }

    function grantAccess() {
        overlay.classList.remove('show');
        setTimeout(() => {
            document.body.style.overflow = '';
        }, 400);
    }

    // Cookie Utilities
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
    }

    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
});
