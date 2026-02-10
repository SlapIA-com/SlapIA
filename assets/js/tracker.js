/**
 * SlapIA Advanced Tracker
 * Collects usage data and sends it to Notion via local API.
 * Respects GDPR Consent ("analytics" category).
 */

(function () {
    // 1. Helper to generate UUIDs
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // 2. Get Cookie
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    // 3. Check Consent
    const consentCookie = getCookie('slapia_consent');
    let consent = null;
    if (consentCookie) {
        try {
            consent = JSON.parse(decodeURIComponent(consentCookie));
        } catch (e) { }
    }

    // STOP if no consent for analytics
    if (!consent || !consent.analytics) {
        // console.log('Tracker: No analytics consent.');
        return;
    }

    // 4. Identify User (Visitor ID - Persistent)
    let visitorId = localStorage.getItem('slapia_visitor_id');
    if (!visitorId) {
        visitorId = generateUUID();
        localStorage.setItem('slapia_visitor_id', visitorId);
    }

    // 5. Identify Session (Session ID - Temporary)
    let sessionId = sessionStorage.getItem('slapia_session_id');
    if (!sessionId) {
        sessionId = generateUUID();
        sessionStorage.setItem('slapia_session_id', sessionId);
    }

    // 6. Collect Data
    const payload = {
        url: window.location.href,
        visitor_id: visitorId,
        session_id: sessionId,
        referrer: document.referrer || 'Direct',
        screen_width: window.screen.width,
        language: navigator.language
    };

    // 7. Send to Backend
    // Use Beacon API if available for reliability on unload, otherwise Fetch
    if (navigator.sendBeacon) {
        const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
        navigator.sendBeacon('/api/track.php', blob);
    } else {
        fetch('/api/track.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        }).catch(err => console.error('Tracker Error:', err));
    }

    // console.log('Tracker: Event sent', payload);

})();
