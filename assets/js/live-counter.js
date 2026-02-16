/**
 * Live Visitor Counter
 * Fetches REAL active user count from server.
 */

document.addEventListener('DOMContentLoaded', () => {
    const counterElement = document.getElementById('live-visitor-count');
    if (!counterElement) return;

    async function fetchCount() {
        try {
            // Add timestamp to prevent browser caching
            // Note: In a real scenario, correct API endpoint needed. Assuming /api/live-counter.php exists.
            const response = await fetch('/api/live-counter.php?t=' + Date.now());

            if (!response.ok) return;

            const data = await response.json();

            if (data.count !== undefined) {
                // Simple animation effect
                if (counterElement.textContent !== data.count.toString()) {
                    counterElement.style.opacity = '0';
                    setTimeout(() => {
                        counterElement.textContent = data.count;
                        counterElement.style.opacity = '1';
                    }, 300);
                }
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
