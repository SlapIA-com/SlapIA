<!-- ROI Calculator Component -->
<div class="row mb-5 pb-5">
    <div class="col-lg-12">
        <div class="bento-card p-4 p-md-5 position-relative overflow-hidden">
            <!-- Background Glow -->
            <div class="position-absolute top-50 start-50 translate-middle" style="width: 50%; height: 50%; background: var(--accent-purple); filter: blur(120px); opacity: 0.15; pointer-events: none;"></div>
            
            <!-- Top Right Actions -->
            <div class="position-absolute top-0 end-0 p-4 z-3 d-flex gap-2">
                <!-- Embed Button -->
                <button class="btn btn-sm btn-outline-light rounded-pill d-flex align-items-center gap-2 share-btn-hover" onclick="openShareModal('embed')" style="border-color: rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
                    <i class="fas fa-code"></i> <span class="d-none d-sm-inline"><?php echo t('share_embed'); ?></span>
                </button>

                <!-- Share Button -->
                <button class="btn btn-sm btn-outline-light rounded-pill d-flex align-items-center gap-2 share-btn-hover" onclick="openShareModal('share')" style="border-color: rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white;">
                    <i class="fas fa-share-alt"></i> <span class="d-none d-sm-inline"><?php echo t('share'); ?></span>
                </button>
            </div>
            <style>
                .share-btn-hover:hover {
                    background: rgba(255,255,255,0.2) !important;
                    color: white !important;
                    border-color: rgba(255,255,255,0.3) !important;
                }
            </style>

            <div class="row align-items-center position-relative z-1">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <h2 class="text-white mb-2"><?php echo t('roi_title'); ?></h2>
                    <p class="text-secondary mb-4"><?php echo t('roi_subtitle'); ?></p>
                    
                    <div class="d-flex flex-column gap-4">
                        <!-- Team Size -->
                        <div>
                            <div class="d-flex justify-content-between mb-2">
                                <label for="roi-employees" class="text-white small fw-bold"><?php echo t('roi_label_team'); ?></label>
                                <span class="text-primary fw-bold" id="roi-employees-value">5</span>
                            </div>
                            <input type="range" class="form-range" id="roi-employees" min="1" max="50" value="5" step="1">
                        </div>

                        <!-- Salary -->
                        <div>
                            <div class="d-flex justify-content-between mb-2">
                                <label for="roi-salary" class="text-white small fw-bold"><?php echo t('roi_label_salary'); ?></label>
                                <span class="text-primary fw-bold" id="roi-salary-value">3 500€</span>
                            </div>
                            <input type="range" class="form-range" id="roi-salary" min="2000" max="10000" value="3500" step="100">
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 offset-lg-1">
                    <div class="p-4 rounded-4" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                        <!-- Time Savings (Hero Metric) -->
                        <div class="text-center mb-4 pb-4 border-bottom border-secondary border-opacity-25">
                            <div class="icon-box mx-auto text-white bg-gradient-to-br from-primary to-info mb-3" style="width: 60px; height: 60px; border-radius: 50%;">
                                <i class="fas fa-clock fs-3"></i>
                            </div>
                            <h3 class="display-4 fw-bold text-white mb-0" id="roi-result-hours">0</h3>
                            <p class="text-secondary text-uppercase small tracking-wide fw-bold"><?php echo t('roi_result_time'); ?></p>
                        </div>

                        <!-- Money Savings -->
                        <div class="text-center">
                            <h4 class="text-success fw-bold mb-0" id="roi-result-money">0€</h4>
                            <p class="text-secondary text-uppercase small tracking-wide fw-bold mb-0"><?php echo t('roi_result_money'); ?></p>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <small class="text-secondary opacity-50 fst-italic" style="font-size: 0.7rem;">
                                <i class="fas fa-info-circle me-1"></i> <?php echo t('roi_disclaimer'); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal (YouTube Style) -->
<div id="shareModal" class="share-modal-overlay" style="display: none;">
    <div class="share-modal-content bento-card p-0 overflow-hidden">
        <!-- Header -->
        <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
            <h5 class="text-white m-0 fs-6"><?php echo t('share'); ?></h5>
            <button class="btn btn-sm btn-icon text-secondary p-0" onclick="closeShareModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Social Icons Row -->
        <div class="p-4 overflow-auto">
            <div class="d-flex gap-4 justify-content-start" style="/* min-width: max-content; */">
                
                <!-- Embed Button -->
                <div class="text-center share-item" onclick="toggleEmbedMode(true)" style="cursor: pointer;">
                    <div class="share-icon-btn mb-2 d-flex align-items-center justify-content-center bg-dark border border-secondary border-opacity-25 rounded-circle mx-auto" style="width: 50px; height: 50px;">
                        <i class="fas fa-code text-white"></i>
                    </div>
                    <small class="text-secondary" style="font-size:0.75rem;"><?php echo t('share_embed'); ?></small>
                </div>

                <!-- WhatsApp -->
                <a href="#" id="share-whatsapp" target="_blank" class="text-decoration-none text-center share-item">
                    <div class="share-icon-btn mb-2 d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width: 50px; height: 50px; background: #25D366;">
                        <i class="fab fa-whatsapp text-white fs-4"></i>
                    </div>
                    <small class="text-secondary" style="font-size:0.75rem;">WhatsApp</small>
                </a>

                <!-- X / Twitter -->
                <a href="#" id="share-twitter" target="_blank" class="text-decoration-none text-center share-item">
                    <div class="share-icon-btn mb-2 d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width: 50px; height: 50px; background: #000;">
                        <i class="fab fa-x-twitter text-white fs-4"></i>
                    </div>
                    <small class="text-secondary" style="font-size:0.75rem;">X</small>
                </a>

                <!-- LinkedIn -->
                <a href="#" id="share-linkedin" target="_blank" class="text-decoration-none text-center share-item">
                    <div class="share-icon-btn mb-2 d-flex align-items-center justify-content-center rounded-circle mx-auto" style="width: 50px; height: 50px; background: #0077b5;">
                        <i class="fab fa-linkedin-in text-white fs-4"></i>
                    </div>
                    <small class="text-secondary" style="font-size:0.75rem;">LinkedIn</small>
                </a>

                <!-- Email -->
                <a href="#" id="share-email" class="text-decoration-none text-center share-item">
                    <div class="share-icon-btn mb-2 d-flex align-items-center justify-content-center bg-secondary bg-opacity-25 rounded-circle mx-auto" style="width: 50px; height: 50px;">
                        <i class="fas fa-envelope text-white fs-4"></i>
                    </div>
                    <small class="text-secondary" style="font-size:0.75rem;">Email</small>
                </a>

            </div>
        </div>

        <!-- Input Section -->
        <div class="p-3 bg-black bg-opacity-25 border-top border-secondary border-opacity-25">
            <div class="d-flex align-items-center gap-2 p-2 rounded-3 border border-secondary border-opacity-25" style="background: rgba(0,0,0,0.3);">
                <input type="text" class="form-control bg-transparent border-0 text-white shadow-none p-0 ps-2" id="share-input" readonly value="Loading..." style="font-size: 0.9rem; text-overflow: ellipsis;">
                <button class="btn btn-primary rounded-pill px-3 py-1 fw-bold" style="font-size: 0.85rem;" onclick="copyShareInput()">
                    <?php echo t('copy'); ?>
                </button>
            </div>
            <!-- Back to Link mode (Hidden by default) -->
            <div id="back-to-link" class="text-center mt-2" style="display: none;">
                <button class="btn btn-link text-secondary btn-sm text-decoration-none" onclick="toggleEmbedMode(false)">
                    <i class="fas fa-link me-1"></i> <?php echo t('share_link'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.share-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.2s ease-out;
}
.share-modal-content {
    width: 90%;
    max-width: 520px;
    background: #1e1e1e; /* YouTube-ish dark */
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    animation: slideUp 0.3s ease-out;
}
.share-icon-btn {
    transition: transform 0.2s, background 0.2s;
}
.share-item:hover .share-icon-btn {
    transform: scale(1.05);
    opacity: 0.9;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<script>
let currentUrl = '';
let embedCode = '';

function openShareModal(mode = 'share') {
    // Generate URL
    const host = window.location.protocol + '//' + window.location.host;
    const path = '/Calcule-ROI-IA'; // The standalone page
    currentUrl = host + path;
    const shareTitle = "<?php echo t('roi_title'); ?> - SlapIA";
    const shareText = "<?php echo t('roi_subtitle'); ?>";

    // Try Native Share API first (Mobile iOS/Android & Desktop Safari/Edge)
    // ONLY if mode is 'share' (not embed)
    if (mode === 'share' && navigator.share) {
        navigator.share({
            title: shareTitle,
            text: shareText,
            url: currentUrl
        })
        .then(() => console.log('Successful share'))
        .catch((error) => {
            console.log('Error sharing', error);
        });
        return; // Stop here if we used native share
    }

    // Fallback: Custom Modal
    const modal = document.getElementById('shareModal');
    modal.style.display = 'flex';
    
    // Embed code (Force transparent background)
    embedCode = `<iframe src="${currentUrl}?embed=true" width="100%" height="750" frameborder="0" scrolling="no" style="border-radius: 12px;"></iframe>`;

    // Set UI mode (Link or Embed)
    toggleEmbedMode(mode === 'embed');

    // Update Social Links
    const text = "Découvrez le calculateur ROI IA de SlapIA";
    document.getElementById('share-whatsapp').href = `https://wa.me/?text=${encodeURIComponent(text + ' ' + currentUrl)}`;
    document.getElementById('share-twitter').href = `https://twitter.com/intent/tweet?url=${encodeURIComponent(currentUrl)}&text=${encodeURIComponent(text)}`;
    document.getElementById('share-linkedin').href = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(currentUrl)}`;
    document.getElementById('share-email').href = `mailto:?subject=${encodeURIComponent(text)}&body=${encodeURIComponent(currentUrl)}`;
}

function closeShareModal() {
    document.getElementById('shareModal').style.display = 'none';
}

function toggleEmbedMode(isEmbed) {
    const input = document.getElementById('share-input');
    const backBtn = document.getElementById('back-to-link');
    
    if (isEmbed) {
        input.value = embedCode;
        backBtn.style.display = 'block';
    } else {
        input.value = currentUrl;
        backBtn.style.display = 'none';
    }
}

function copyShareInput() {
    const input = document.getElementById('share-input');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = input.nextElementSibling;
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Copié !';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');
        setTimeout(() => { 
            btn.innerHTML = originalText; 
            btn.classList.add('btn-primary'); 
            btn.classList.remove('btn-success');
        }, 2000);
    });
}

// Close on outside click
document.getElementById('shareModal').addEventListener('click', function(e) {
    if (e.target === this) closeShareModal();
});
</script>
