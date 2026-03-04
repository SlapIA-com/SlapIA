/**
 * Global image lightbox implementation.
 * Attaches a click handler to all images inside main#swup.
 */

document.addEventListener('DOMContentLoaded', () => {
    initLightbox();
});

// Ensure initLightbox is globally accessible for Swup transitions
window.initLightbox = initLightbox;


function initLightbox() {
    // Attach styles and behaviors only to images in specific areas
    // Exclude images with .no-lightbox class, logos, etc.
    const images = document.querySelectorAll('main#swup img:not(.no-lightbox):not(.logo)');

    images.forEach(img => {
        if (!img.classList.contains('lightbox-trigger')) {
            img.classList.add('lightbox-trigger');
            img.addEventListener('click', function () {
                openLightbox(this.src, this.alt);
            });
        }
    });
}

function openLightbox(src, alt) {
    // Remove existing if any
    let existing = document.getElementById('global-lightbox');
    if (existing) {
        existing.remove();
    }

    // Create overlay
    const overlay = document.createElement('div');
    overlay.id = 'global-lightbox';
    overlay.className = 'lightbox-overlay';

    // Create close button
    const closeBtn = document.createElement('button');
    closeBtn.className = 'lightbox-close';
    closeBtn.innerHTML = '&times;';
    closeBtn.setAttribute('aria-label', 'Close');

    // Create image
    const img = document.createElement('img');
    img.src = src;
    img.alt = alt || 'Lightbox Image';
    img.className = 'lightbox-image';

    // Append to overlay
    overlay.appendChild(closeBtn);
    overlay.appendChild(img);

    // Append to body
    document.body.appendChild(overlay);

    // Trigger animation frame delay so it transitions
    requestAnimationFrame(() => {
        overlay.classList.add('active');
    });

    // Close on button click
    closeBtn.addEventListener('click', closeLightbox);

    // Close on background click
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closeLightbox();
        }
    });

    // Close on Escape key
    const escHandler = (e) => {
        if (e.key === 'Escape') {
            closeLightbox();
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
}

function closeLightbox() {
    const overlay = document.getElementById('global-lightbox');
    if (overlay) {
        overlay.classList.remove('active');
        // Wait for transition to finish
        setTimeout(() => {
            overlay.remove();
        }, 300);
    }
}
