/**
 * Typewriter Effect
 * Cycles through text phrases with a typing animation.
 */

function initTypewriter() {
    const typewriterElement = document.getElementById('typewriter-text');
    if (!typewriterElement) return;

    // Clear any previous interval/timeout if we had a global reference (optional but good practice)
    // For now, simple re-init is fine as long as we don't have multiple instances fighting.
    // Ideally we should stop the old one, but since the DOM element is replaced, the old one just updates a detached node.

    const phrases = JSON.parse(typewriterElement.getAttribute('data-phrases') || '[]');
    if (phrases.length === 0) return;

    let phraseIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    let typeSpeed = 100;

    function type() {
        // Safety check if element still exists
        if (!document.body.contains(typewriterElement)) return;

        const currentPhrase = phrases[phraseIndex];

        if (isDeleting) {
            typewriterElement.textContent = currentPhrase.substring(0, charIndex - 1);
            charIndex--;
            typeSpeed = 50;
        } else {
            typewriterElement.textContent = currentPhrase.substring(0, charIndex + 1);
            charIndex++;
            typeSpeed = 100;
        }

        if (!isDeleting && charIndex === currentPhrase.length) {
            isDeleting = true;
            typeSpeed = 2000;
        } else if (isDeleting && charIndex === 0) {
            isDeleting = false;
            phraseIndex = (phraseIndex + 1) % phrases.length;
            typeSpeed = 500;
        }

        setTimeout(type, typeSpeed);
    }

    type();
}

// Auto-init
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTypewriter);
} else {
    initTypewriter();
}

// Expose
window.initTypewriter = initTypewriter;
