/**
 * Infinite Loop Carousel for Testimonials
 * Handles left/right navigation and seamless looping.
 */

document.addEventListener('DOMContentLoaded', () => {
    const track = document.querySelector('.reviews-track');
    const container = document.querySelector('.reviews-inner');
    const items = document.querySelectorAll('.review-item');
    const prevBtn = document.getElementById('prev-review');
    const nextBtn = document.getElementById('next-review');

    if (!track || !container || items.length === 0) return;

    // Configuration
    const itemWidth = 320; // Width of card
    const gap = 36; // Gap between cards
    const totalWidth = itemWidth + gap;
    let isTransitioning = false;
    let autoPlayInterval;

    // Clone items for infinite loop
    // We need enough clones to fill the screen width at least once
    const itemsToClone = items.length;

    // Clone Start (for going left from start) and End (for going right from end)
    const clonesStart = [];
    const clonesEnd = [];

    // Simple cloning strategy: clone all items and append/prepend
    // Ideally we only need a few, but cloning all is safer for small counts
    items.forEach(item => {
        const cloneS = item.cloneNode(true);
        cloneS.classList.add('clone');
        clonesStart.push(cloneS);

        const cloneE = item.cloneNode(true);
        cloneE.classList.add('clone');
        clonesEnd.push(cloneE);
    });

    // Append/Prepend clones
    clonesEnd.forEach(clone => track.appendChild(clone));
    // For prepend, we need to reverse the order to keep sequence correct if we were doing specific logic, 
    // but here we just want a block of items before. 
    // Actually, to seemingly go "left" from item 1 to item N, we need item N, N-1... before item 1.
    clonesStart.reverse().forEach(clone => track.insertBefore(clone, track.firstChild));

    // Current Index (visual). 
    // Original items start at index = items.length (since we prepended items.length clones)
    let currentIndex = items.length;

    // Initial Position
    const updatePosition = (index, animate = true) => {
        if (!animate) {
            track.style.transition = 'none';
        } else {
            track.style.transition = 'transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1)';
        }
        const translateX = -(index * totalWidth);
        track.style.transform = `translateX(${translateX}px)`;
    };

    // Set initial position without animation
    updatePosition(currentIndex, false);

    // Navigation Logic
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

    // Handle Transition End for Loop Reset
    track.addEventListener('transitionend', () => {
        isTransitioning = false;

        const totalOriginalItems = items.length;

        // If we moved past the last original item (into end clones)
        if (currentIndex >= totalOriginalItems * 2) {
            // Jump back to the first original item
            currentIndex = totalOriginalItems;
            updatePosition(currentIndex, false);
        }

        // If we moved before the first original item (into start clones)
        if (currentIndex < totalOriginalItems) {
            // Jump forward to the last original item
            currentIndex = totalOriginalItems * 2 - 1;
            updatePosition(currentIndex, false);
        }
    });

    // Event Listeners
    if (nextBtn) nextBtn.addEventListener('click', () => {
        stopAutoPlay();
        move('next');
        startAutoPlay();
    });

    if (prevBtn) prevBtn.addEventListener('click', () => {
        stopAutoPlay();
        move('prev');
        startAutoPlay();
    });

    // Auto Play
    const startAutoPlay = () => {
        clearInterval(autoPlayInterval);
        autoPlayInterval = setInterval(() => {
            move('next');
        }, 3000);
    };

    const stopAutoPlay = () => {
        clearInterval(autoPlayInterval);
    };

    // Start
    startAutoPlay();

    // Pause on hover
    container.addEventListener('mouseenter', stopAutoPlay);
    container.addEventListener('mouseleave', startAutoPlay);
});
