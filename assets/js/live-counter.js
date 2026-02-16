/**
 * Live Visitor Counter
 * Fetches REAL active user count from server.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Target both desktop (floating) and mobile (static) counters
    const counterElements = [
        document.getElementById('live-visitor-count'),
        document.getElementById('static-live-visitor-count')
    ];

    async function fetchCount() {
        try {
            // Add timestamp to prevent browser caching
            // Note: In a real scenario, correct API endpoint needed. Assuming /api/live-counter.php exists.
            const response = await fetch('/api/live-counter.php?t=' + Date.now());

            if (!response.ok) return;

            const data = await response.json();

            if (data.count !== undefined) {
                counterElements.forEach(el => {
                    if (el) {
                        // Simple animation effect
                        if (el.textContent !== data.count.toString()) {
                            el.style.opacity = '0';
                            setTimeout(() => {
                                el.textContent = data.count;
                                el.style.opacity = '1';
                            }, 300);
                        }
                    }
                });
            }
        } catch (e) {
            console.error('Counter error', e);
        }
    }

    // Initial fetch
    fetchCount();

    // Poll every 5 seconds (reduced from 2s to save resources)
    setInterval(fetchCount, 5000);
});
