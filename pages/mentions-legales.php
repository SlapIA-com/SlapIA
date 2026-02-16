<?php include '../includes/header.php'; ?>

<section class="py-5" style="padding-top: 120px !important;">
    <div class="container">
        
        <h1 class="display-title mb-5 text-center"><?php echo t('legal_meta_title'); ?></h1>

        <div class="row g-4 d-flex align-items-stretch">
            <!-- Sidebar Navigation -->
            <div class="col-lg-3">
                <div class="sticky-top" style="top: 120px;">
                    <div class="bento-card p-3">
                        <div class="d-flex flex-column gap-1">
                            <a href="formation" class="btn text-start text-secondary hover-white mb-2 d-flex align-items-center py-2 px-3 rounded-3 transition-all">
                                <i class="fas fa-arrow-left me-2"></i> <?php echo t('formations'); ?>
                            </a>
                            
                            <hr class="border-secondary opacity-25 my-2">
                            
                            <button class="btn text-start text-white py-2 px-3 rounded-3 transition-all nav-item-legal active" id="btn-mentions" onclick="showSection('mentions')">
                                <?php echo t('legal_notice_title'); ?>
                            </button>
                            <button class="btn text-start text-secondary hover-white py-2 px-3 rounded-3 transition-all nav-item-legal" id="btn-privacy" onclick="showSection('privacy')">
                                <?php echo t('privacy_policy_title'); ?>
                            </button>
                            <button class="btn text-start text-secondary hover-white py-2 px-3 rounded-3 transition-all nav-item-legal" id="btn-terms" onclick="showSection('terms')">
                                <?php echo t('terms_title'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="col-lg-9">
                <!-- MENTIONS LEGALES -->
                <div id="mentions" class="legal-section bento-card mb-5 scroll-reveal" style="display: block;">
                    <h3 class="text-white mb-4"><?php echo t('legal_notice_title'); ?></h3>
                    
                    <!-- 1. EDITEUR -->
                    <h5 class="text-white mb-3 mt-4"><?php echo t('legal_section_editor'); ?></h5>
                    <div class="text-secondary mb-4">
                        <ul class="list-unstyled">
                            <li class="mb-2"><strong><?php echo t('legal_label_name'); ?></strong> <?php echo t('legal_value_name'); ?></li>
                            <li class="mb-2"><strong><?php echo t('legal_label_address'); ?></strong> <?php echo t('legal_value_address'); ?></li>
                            <li class="mb-2"><strong><?php echo t('legal_label_email'); ?></strong> <?php echo t('legal_value_email'); ?></li>
                            <li class="mb-2"><strong><?php echo t('legal_label_phone'); ?></strong> <?php echo t('legal_value_phone'); ?></li>
                        </ul>
                    </div>

                    <!-- 2. ENTREPRISE -->
                    <h5 class="text-white mb-3"><?php echo t('legal_section_company'); ?></h5>
                    <div class="text-secondary mb-4">
                        <ul class="list-unstyled">
                            <li class="mb-2"><strong><?php echo t('legal_label_form'); ?></strong> <?php echo t('legal_value_form'); ?></li>
                            <li class="mb-2"><strong><?php echo t('legal_label_siren'); ?></strong> 100 946 722</li>
                            <li class="mb-2"><strong><?php echo t('legal_label_siret'); ?></strong> 100 946 722 00012</li>
                            <li class="mb-2"><strong><?php echo t('legal_label_ape'); ?></strong> 85.59A (<?php echo t('legal_value_activity'); ?>)</li>
                            <li class="mb-2"><strong><?php echo t('legal_label_tva'); ?></strong> <?php echo t('legal_value_tva_none'); ?></li>
                        </ul>
                    </div>

                    <!-- 3. HEBERGEMENT -->
                    <h5 class="text-white mb-3"><?php echo t('legal_section_hosting'); ?></h5>
                    <div class="text-secondary">
                        <p>
                            <strong><?php echo t('hosting_provider'); ?></strong><br>
                            GNL Solution<br>
                            <?php echo t('infrastructure_by'); ?>
                        </p>
                    </div>
                </div>

                <!-- PRIVACY POLICY -->
                <div id="privacy" class="legal-section bento-card mb-5 scroll-reveal" style="display: none;">
                    <h3 class="text-white mb-4"><?php echo t('privacy_policy_title'); ?></h3>
                    <div class="text-secondary">
                        <div class="alert alert-dark border border-secondary border-opacity-25" role="alert">
                            <i class="fas fa-shield-alt text-primary me-2"></i> <strong><?php echo t('privacy_priority'); ?></strong>
                        </div>
                        
                        <h5 class="text-white mt-4"><?php echo t('data_storage_title'); ?></h5>
                        <p><?php echo t('data_storage_text'); ?></p>
                        
                        <h5 class="text-white mt-4"><?php echo t('cookies_title'); ?></h5>
                        <p><?php echo t('cookies_text'); ?></p>
                        
                        <h5 class="text-white mt-4"><?php echo t('data_usage_title'); ?></h5>
                        <p><?php echo t('data_usage_intro'); ?></p>
                        <ul>
                            <li><?php echo t('data_usage_1'); ?></li>
                            <li><?php echo t('data_usage_2'); ?></li>
                            <li><?php echo t('data_usage_3'); ?></li>
                        </ul>
                    </div>
                </div>

                <!-- TERMS -->
                <div id="terms" class="legal-section bento-card mb-5 scroll-reveal" style="display: none;">
                    <h3 class="text-white mb-4"><?php echo t('terms_title'); ?></h3>
                    <div class="text-secondary">
                        <p><strong><?php echo t('terms_services_label'); ?></strong> <?php echo t('terms_services_text'); ?></p>
                        <p><strong><?php echo t('terms_payment_label'); ?></strong> <?php echo t('terms_payment_text'); ?></p>
                        <p><strong><?php echo t('terms_cancellation_label'); ?></strong> <?php echo t('terms_cancellation_text'); ?></p>
                        <p><strong><?php echo t('terms_ip_label'); ?></strong> <?php echo t('terms_ip_text'); ?></p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<script>
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.legal-section').forEach(el => {
        el.style.display = 'none';
        el.classList.remove('fade-in-up'); // Reset animation
    });
    
    // Deactivate all buttons
    document.querySelectorAll('.nav-item-legal').forEach(btn => {
        btn.classList.remove('active', 'text-white', 'bg-white', 'bg-opacity-10');
        btn.classList.add('text-secondary');
    });
    
    // Show selected section
    const selectedSection = document.getElementById(sectionId);
    if (selectedSection) {
        selectedSection.style.display = 'block';
        // Force reflow for animation restart
        void selectedSection.offsetWidth; 
        selectedSection.classList.add('fade-in-up');
    }
    
    // Activate button
    const activeBtn = document.getElementById('btn-' + sectionId);
    if (activeBtn) {
        activeBtn.classList.remove('text-secondary');
        activeBtn.classList.add('active', 'text-white', 'bg-white', 'bg-opacity-10');
    }
    
    // Update URL hash without scrolling
    history.pushState(null, null, '#' + sectionId);
}

// Handle hash on load
document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.substring(1); // Remove #
    if (hash && ['mentions', 'privacy', 'terms'].includes(hash)) {
        showSection(hash);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
