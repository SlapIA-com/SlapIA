/**
 * Circular Scroll Indicator (Logic Only)
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('✅ Scroll Indicator Logic Loaded');

    const progressCircle = document.getElementById('scroll-progress');
    const indicator = document.getElementById('scroll-indicator');
    const totalLength = 283;

    if (!progressCircle || !indicator) {
        console.error('❌ Scroll Indicator elements not found in DOM');
        return;
    }

    // Click to scroll top
    indicator.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    const updateScroll = () => {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;

        let scrollPercent = 0;
        if (docHeight > 0) {
            scrollPercent = Math.max(0, Math.min(1, scrollTop / docHeight));
        }

        const drawLength = totalLength * scrollPercent;
        progressCircle.style.strokeDashoffset = totalLength - drawLength;

        // Show immediately (>10px)
        if (scrollTop > 10) {
            indicator.classList.add('visible');
        } else {
            indicator.classList.remove('visible');
        }
    };

    window.addEventListener('scroll', updateScroll);
    updateScroll(); // Init Check
});
