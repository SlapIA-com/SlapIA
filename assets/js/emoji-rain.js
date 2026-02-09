/**
 * Emoji Flight Easter Egg - GIANT VERSION
 * Triggers one giant emoji crossing the screen.
 */

console.log('🍆 Giant Emoji Script Loaded.');

document.addEventListener('DOMContentLoaded', () => {
    let keyBuffer = '';

    const triggers = {
        'bite': '🍆',
        'fesse': '🍑'
    };

    document.addEventListener('keydown', (e) => {
        // Backspace support
        if (e.key === 'Backspace') {
            keyBuffer = keyBuffer.slice(0, -1);
            return;
        }

        // Add key
        if (e.key.length === 1) {
            keyBuffer += e.key.toLowerCase();
        }

        // Clip buffer
        if (keyBuffer.length > 20) {
            keyBuffer = keyBuffer.slice(-20);
        }

        // Check triggers
        for (const [word, emoji] of Object.entries(triggers)) {
            if (keyBuffer.endsWith(word)) {
                console.log(`🚀 Giant Emoji Triggered: ${emoji}`);
                launchGiantEmoji(emoji);
                keyBuffer = ''; // Reset
            }
        }
    });

    function launchGiantEmoji(emojiChar) {
        const el = document.createElement('div');
        el.innerText = emojiChar;

        // GIANT Styles
        el.style.position = 'fixed';
        el.style.top = '50%';
        el.style.left = '-300px'; // Start far left
        el.style.transform = 'translateY(-50%)';
        el.style.fontSize = '300px'; // MASSIVE
        el.style.zIndex = '999999'; // Top of the world
        el.style.pointerEvents = 'none';
        el.style.whiteSpace = 'nowrap';
        el.style.transition = 'left 3s cubic-bezier(0.2, 0.8, 0.2, 1), transform 3s linear';

        document.body.appendChild(el);

        // Force browser to recognize start position
        // Reading offsetWidth forces a reflow
        void el.offsetWidth;

        // Animate
        setTimeout(() => {
            el.style.left = '120vw'; // Clean cross
            el.style.transform = 'translateY(-50%) rotate(360deg)'; // Spin
        }, 50);

        // Cleanup
        setTimeout(() => {
            el.remove();
        }, 3500);
    }
});
