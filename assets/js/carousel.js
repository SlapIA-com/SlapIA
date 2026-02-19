/**
 * Infinite Loop Carousel for Testimonials
 * Handles left/right navigation and seamless looping.
 */

function initCarousels() {
    const track = document.querySelector('.reviews-track');
    const container = document.querySelector('.reviews-inner');
    const items = document.querySelectorAll('.review-item');
    const prevBtn = document.getElementById('prev-review');
    const nextBtn = document.getElementById('next-review');

    if (!track || !container || items.length === 0) return;

    // cleanup previous listeners if any (simple approach: clone and replace nodes to strip listeners, or just leave them if they don't leak much)
    // For now, since Swup replaces the DOM, we don't need to cleanup old listeners on elements that are gone.
    // BUT if we re-init on the same page (rare), we might double up. 
    // Since we only call this on contentReplaced (new DOM), it's fine.

    // Configuration
    const itemWidth = 320;
    const gap = 36;
    const totalWidth = itemWidth + gap;
    let isTransitioning = false;
    let autoPlayInterval;

    // Verify if clones already exist (to prevent double cloning on re-init if keeping DOM)
    // Swup replaces DOM, so we are fresh.

    // Clone items
    const itemsToClone = items.length;
    const clonesStart = [];
    const clonesEnd = [];

    items.forEach(item => {
        const cloneS = item.cloneNode(true);
        cloneS.classList.add('clone');
        clonesStart.push(cloneS);

        const cloneE = item.cloneNode(true);
        cloneE.classList.add('clone');
        clonesEnd.push(cloneE);
    });

    clonesEnd.forEach(clone => track.appendChild(clone));
    clonesStart.reverse().forEach(clone => track.insertBefore(clone, track.firstChild));

    let currentIndex = items.length;

    const updatePosition = (index, animate = true) => {
        if (!animate) {
            track.style.transition = 'none';
        } else {
            track.style.transition = 'transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1)';
        }
        const translateX = -(index * totalWidth);
        track.style.transform = `translateX(${translateX}px)`;
    };

    updatePosition(currentIndex, false);

    const move = (direction) => {
        if (isTransitioning) return;
        isTransitioning = true;

        if (direction === 'next') {
            currentIndex++;
        } else {
            currentIndex--;
        }

        updatePosition(currentIndex, true);
    };

    // Use named function for event listener to allow removal if needed
    const handleTransitionEnd = () => {
        isTransitioning = false;
        const totalOriginalItems = items.length;
        if (currentIndex >= totalOriginalItems * 2) {
            currentIndex = totalOriginalItems;
            updatePosition(currentIndex, false);
        }
        if (currentIndex < totalOriginalItems) {
            currentIndex = totalOriginalItems * 2 - 1;
            updatePosition(currentIndex, false);
        }
    };

    track.addEventListener('transitionend', handleTransitionEnd);

    if (nextBtn) {
        // Clone to strip old listeners
        const newNext = nextBtn.cloneNode(true);
        nextBtn.parentNode.replaceChild(newNext, nextBtn);
        newNext.addEventListener('click', () => {
            stopAutoPlay();
            move('next');
            startAutoPlay();
        });
    }

    if (prevBtn) {
        const newPrev = prevBtn.cloneNode(true);
        prevBtn.parentNode.replaceChild(newPrev, prevBtn);
        newPrev.addEventListener('click', () => {
            stopAutoPlay();
            move('prev');
            startAutoPlay();
        });
    }

    const startAutoPlay = () => {
        clearInterval(autoPlayInterval);
        autoPlayInterval = setInterval(() => {
            move('next');
        }, 3000);
    };

    const stopAutoPlay = () => {
        clearInterval(autoPlayInterval);
    };

    startAutoPlay();

    container.addEventListener('mouseenter', stopAutoPlay);
    container.addEventListener('mouseleave', startAutoPlay);
}

// Auto-init
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCarousels);
} else {
    initCarousels();
}

window.initCarousels = initCarousels;
