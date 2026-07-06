<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

$page_title = t('solution_title') . " - SlapIA";
$page_description = t('solution_subtitle');
$page_image = '/assets/img/brand/logo.png';
include '../includes/header.php'; ?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "SlapIA Tool",
  "operatingSystem": "Windows 10/11",
  "applicationCategory": "UtilitiesApplication",
  "url": "https://www.slapia.com/solution",
  "downloadUrl": "https://github.com/ThomasLap13/SlapIA-Tool/releases/latest",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "EUR"
  }
}
</script>

<section class="py-5 mt-5">
    <div class="container">

        <!-- Hero -->
        <div class="row mb-5 text-center scroll-reveal">
            <div class="col-lg-8 mx-auto">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill border border-secondary border-opacity-25 mb-4 shimmer-badge"
                    style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
                    <span class="badge bg-primary rounded-pill" style="font-size: 0.7rem;"><i class="fab fa-windows me-1"></i> <?php echo t('solution_badge'); ?></span>
                </div>
                <h1 class="display-title mb-3"><?php echo t('solution_title'); ?></h1>
                <p class="text-secondary lead mx-auto" style="max-width: 650px;"><?php echo t('solution_subtitle'); ?></p>

                <!-- Download CTA -->
                <div class="d-flex flex-column align-items-center gap-3 mt-4">
                    <a href="/downloads/latest/SlapIA-Tool-Setup.exe" id="solutionDownloadBtn" class="btn-primary-glow" data-no-swup>
                        <i class="fas fa-download"></i> <span id="solutionDownloadLabel"><?php echo t('solution_download_btn'); ?></span>
                    </a>
                    <div class="text-secondary small" id="solutionReleaseMeta" style="min-height: 1.2em;"></div>
                    <div class="text-secondary small opacity-75"><?php echo t('solution_requirements'); ?></div>
                    <a href="https://github.com/ThomasLap13/SlapIA-Tool" target="_blank" rel="noopener" data-no-swup class="text-secondary small text-decoration-none">
                        <i class="fab fa-github me-1"></i> <?php echo t('solution_view_github'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="why-grid">
            <div class="why-card scroll-reveal">
                <div class="why-icon icon-blue"><i class="fas fa-gauge-high"></i></div>
                <h4 class="why-card-title"><?php echo t('solution_feature_overview_title'); ?></h4>
                <p class="why-card-desc"><?php echo t('solution_feature_overview_desc'); ?></p>
            </div>

            <div class="why-card scroll-reveal delay-100">
                <div class="why-icon icon-purple"><i class="fas fa-microchip"></i></div>
                <h4 class="why-card-title"><?php echo t('solution_feature_hardware_title'); ?></h4>
                <p class="why-card-desc"><?php echo t('solution_feature_hardware_desc'); ?></p>
            </div>

            <div class="why-card scroll-reveal delay-200">
                <div class="why-icon icon-green"><i class="fas fa-chart-line"></i></div>
                <h4 class="why-card-title"><?php echo t('solution_feature_monitoring_title'); ?></h4>
                <p class="why-card-desc"><?php echo t('solution_feature_monitoring_desc'); ?></p>
            </div>

            <div class="why-card scroll-reveal">
                <div class="why-icon icon-blue"><i class="fas fa-box-open"></i></div>
                <h4 class="why-card-title"><?php echo t('solution_feature_apps_title'); ?></h4>
                <p class="why-card-desc"><?php echo t('solution_feature_apps_desc'); ?></p>
            </div>

            <div class="why-card scroll-reveal delay-100">
                <div class="why-icon icon-purple"><i class="fas fa-arrows-rotate"></i></div>
                <h4 class="why-card-title"><?php echo t('solution_feature_updates_title'); ?></h4>
                <p class="why-card-desc"><?php echo t('solution_feature_updates_desc'); ?></p>
            </div>

            <div class="why-card scroll-reveal delay-200">
                <div class="why-icon icon-green"><i class="fas fa-circle-half-stroke"></i></div>
                <h4 class="why-card-title"><?php echo t('solution_feature_theme_title'); ?></h4>
                <p class="why-card-desc"><?php echo t('solution_feature_theme_desc'); ?></p>
            </div>

            <div class="why-card scroll-reveal">
                <div class="why-icon icon-blue"><i class="fas fa-download"></i></div>
                <h4 class="why-card-title"><?php echo t('solution_feature_installpilot_title'); ?></h4>
                <p class="why-card-desc"><?php echo t('solution_feature_installpilot_desc'); ?></p>
            </div>

            <div class="why-card scroll-reveal delay-100">
                <div class="why-icon icon-purple"><i class="fas fa-sliders"></i></div>
                <h4 class="why-card-title"><?php echo t('solution_feature_preferences_title'); ?></h4>
                <p class="why-card-desc"><?php echo t('solution_feature_preferences_desc'); ?></p>
            </div>

            <div class="why-card scroll-reveal delay-200">
                <div class="why-icon icon-green"><i class="fas fa-language"></i></div>
                <h4 class="why-card-title"><?php echo t('solution_feature_languages_title'); ?></h4>
                <p class="why-card-desc"><?php echo t('solution_feature_languages_desc'); ?></p>
            </div>
        </div>

    </div>
</section>

<script>
(function () {
    const metaEl = document.getElementById('solutionReleaseMeta');
    const labelEl = document.getElementById('solutionDownloadLabel');
    if (!metaEl || !labelEl) return;

    function formatSize(bytes) {
        if (!bytes) return '';
        const mb = bytes / (1024 * 1024);
        return mb.toFixed(1) + ' MB';
    }

    labelEl.textContent = <?php echo json_encode(t('solution_download_btn_loading')); ?>;

    fetch('/api/download-latest.php?info=1')
        .then(res => res.json())
        .then(data => {
            labelEl.textContent = <?php echo json_encode(t('solution_download_btn')); ?>;
            if (data && data.success) {
                const parts = [];
                if (data.version) parts.push(<?php echo json_encode(t('solution_version_label')); ?> + ' ' + data.version);
                if (data.exe_size) parts.push(<?php echo json_encode(t('solution_size_label')); ?> + ' ' + formatSize(data.exe_size));
                metaEl.textContent = parts.join(' · ');
            } else {
                metaEl.textContent = <?php echo json_encode(t('solution_download_error')); ?>;
            }
        })
        .catch(() => {
            labelEl.textContent = <?php echo json_encode(t('solution_download_btn')); ?>;
            metaEl.textContent = <?php echo json_encode(t('solution_download_error')); ?>;
        });
})();
</script>

<?php include '../includes/footer.php'; ?>
