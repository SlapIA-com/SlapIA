document.addEventListener('DOMContentLoaded', () => {
    initCustomCursor();
});

function initCustomCursor() {
    // Only on desktop
    if (window.innerWidth <= 768) return;

    const cursor = document.createElement('div');
    cursor.classList.add('custom-cursor');
    document.body.appendChild(cursor);

    let mouseX = 0;
    let mouseY = 0;
    let cursorX = 0;
    let cursorY = 0;

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;

        // Show cursor if hidden (first movement)
        if (cursor.style.opacity === '') {
            cursor.style.opacity = '1';
        }
    });

    const loop = () => {
        cursorX += (mouseX - cursorX) * 0.15;
        cursorY += (mouseY - cursorY) * 0.15;
        cursor.style.transform = `translate(${cursorX}px, ${cursorY}px)`;
        requestAnimationFrame(loop);
    };
    requestAnimationFrame(loop);

    // Delegation for hover effects since elements might change via Swup
    document.body.addEventListener('mouseover', (e) => {
        const target = e.target.closest('a, button, .bento-card, input, textarea');
        if (target) {
            cursor.classList.add('cursor-hover');
        }
    });

    document.body.addEventListener('mouseout', (e) => {
        const target = e.target.closest('a, button, .bento-card, input, textarea');
        if (target) {
            cursor.classList.remove('cursor-hover');
        }
    });
}

function initStaggeredText() {
    const staggeredElements = document.querySelectorAll('.stagger-text:not(.initialized)');

    staggeredElements.forEach(el => {
        el.classList.add('initialized');

        const text = el.textContent.trim();
        el.textContent = '';

        const words = text.split(' ');

        words.forEach((word, wordIndex) => {
            const wordSpan = document.createElement('span');
            wordSpan.classList.add('stagger-word');
            wordSpan.style.display = 'inline-block';
            wordSpan.style.whiteSpace = 'nowrap';

            const chars = word.split('');
            chars.forEach((char, charIndex) => {
                const charSpan = document.createElement('span');
                charSpan.textContent = char;
                charSpan.classList.add('stagger-char');
                charSpan.style.display = 'inline-block';
                charSpan.style.opacity = '0';
                charSpan.style.transform = 'translateY(15px)';
                charSpan.style.animation = `simpleFadeInUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards`;

                const totalIndex = wordIndex * 4 + charIndex;
                charSpan.style.animationDelay = `${(totalIndex * 0.03) + 0.1}s`;

                wordSpan.appendChild(charSpan);
            });

            el.appendChild(wordSpan);

            if (wordIndex < words.length - 1) {
                const space = document.createTextNode(' ');
                el.appendChild(space);
            }
        });
    });
}
