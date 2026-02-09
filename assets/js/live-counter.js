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
            const response = await fetch('/api/live-counter.php?t=' + Date.now());
            const data = await response.json();

            if (data.count) {
                // Smooth transition if number changes
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

    // Poll every 2 seconds for real-time feel
    setInterval(fetchCount, 2000);
});
