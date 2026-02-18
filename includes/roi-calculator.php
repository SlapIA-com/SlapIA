<!-- ROI Calculator Component -->
<div class="row mb-5 pb-5">
    <div class="col-lg-12">
        <div class="bento-card p-4 p-md-5 position-relative overflow-hidden">
            <!-- Background Glow -->
            <div class="position-absolute top-50 start-50 translate-middle" style="width: 50%; height: 50%; background: var(--accent-purple); filter: blur(120px); opacity: 0.15; pointer-events: none;"></div>
            
            <!-- Share Button (Top Right) -->
            <div class="position-absolute top-0 end-0 p-4">
                <button class="btn btn-sm btn-outline-light rounded-pill d-flex align-items-center gap-2" onclick="openShareModal()" style="border-color: rgba(255,255,255,0.1); background: rgba(255,255,255,0.05);">
                    <i class="fas fa-share-alt"></i> <span class="d-none d-sm-inline"><?php echo t('share'); ?></span>
                </button>
            </div>

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

<!-- Share Modal (Hidden by default) -->
<div id="shareModal" class="share-modal-overlay" style="display: none;">
    <div class="share-modal-content bento-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="text-white m-0"><?php echo t('share_roi_title'); ?></h5>
            <button class="btn btn-sm btn-icon text-secondary" onclick="closeShareModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-pills mb-3" id="shareTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active btn-sm" id="link-tab" data-bs-toggle="tab" data-bs-target="#link" type="button" role="tab" aria-selected="true">
                    <i class="fas fa-link me-2"></i> <?php echo t('share_link'); ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link btn-sm" id="embed-tab" data-bs-toggle="tab" data-bs-target="#embed" type="button" role="tab" aria-selected="false">
                    <i class="fas fa-code me-2"></i> <?php echo t('share_embed'); ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="shareTabsContent">
            <!-- Link -->
            <div class="tab-pane fade show active" id="link" role="tabpanel">
                <div class="input-group">
                    <input type="text" class="form-control bg-dark border-secondary text-white" id="share-link-input" readonly value="https://slapia.com/pages/roi.php">
                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('share-link-input')">
                        <i class="far fa-copy"></i>
                    </button>
                </div>
            </div>
            <!-- Embed -->
            <div class="tab-pane fade" id="embed" role="tabpanel">
                <div class="position-relative">
                    <textarea class="form-control bg-dark border-secondary text-white" id="share-embed-input" rows="4" readonly><iframe src="https://slapia.com/pages/roi.php" width="100%" height="600" frameborder="0" style="border-radius: 12px;"></iframe></textarea>
                    <button class="btn btn-sm btn-outline-secondary position-absolute bottom-0 end-0 m-2" type="button" onclick="copyToClipboard('share-embed-input')">
                        <i class="far fa-copy"></i> <?php echo t('copy'); ?>
                    </button>
                </div>
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
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.2s ease-out;
}
.share-modal-content {
    width: 90%;
    max-width: 500px;
    background: #1a1a1a;
    border: 1px solid rgba(255, 255, 255, 0.1);
    animation: slideUp 0.3s ease-out;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<script>
function openShareModal() {
    document.getElementById('shareModal').style.display = 'flex';
    // Update input values with current domain logic if needed
    const currentHost = window.location.protocol + '//' + window.location.host;
    const roiPath = '/pages/roi.php'; // Adjust based on deployment
    document.getElementById('share-link-input').value = currentHost + roiPath;
    document.getElementById('share-embed-input').value = `<iframe src="${currentHost}${roiPath}" width="100%" height="600" frameborder="0" style="border-radius: 12px; overflow: hidden;"></iframe>`;
}
function closeShareModal() {
    document.getElementById('shareModal').style.display = 'none';
}
function copyToClipboard(elementId) {
    const copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999); // Mobile
    navigator.clipboard.writeText(copyText.value).then(() => {
        // Simple visual feedback
        const btn = copyText.nextElementSibling || copyText.parentElement.querySelector('button');
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-success"></i>';
        setTimeout(() => { btn.innerHTML = originalIcon; }, 2000);
    });
}
// Close on outside click
document.getElementById('shareModal').addEventListener('click', function(e) {
    if (e.target === this) closeShareModal();
});
</script>
