<?php include '../includes/header.php'; ?>

<section class="py-5" style="padding-top: 120px !important;">
    <div class="container">
        
        <h1 class="display-title mb-5 text-center"><?php echo t('legal_meta_title'); ?></h1>

        <div class="row g-4 justify-content-center">
            
            <!-- Content -->
            <div class="col-lg-8">
                <!-- MENTIONS LEGALES -->
                <div id="mentions" class="legal-section bento-card mb-5 scroll-reveal">
                    <h3 class="text-white mb-4"><?php echo t('legal_notice_title'); ?></h3>
                    
                    <!-- 1. EDITEUR -->
                    <h5 class="text-white mb-3 mt-4"><?php echo t('legal_section_editor'); ?></h5>
                    <div class="text-secondary mb-4">
                        <ul class="list-unstyled">
                            <li class="mb-2"><strong><?php echo t('legal_label_name'); ?></strong> SlapIA</li>
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
                            GNL Solution, 20 rue Gustave Courbet 25000 Besançon.<br>
                            contact@gnl-solution.fr<br>
                            03.65.67.01.69<br>
                            <?php echo t('infrastructure_by'); ?>
                        </p>
                    </div>
                </div>

                <!-- PRIVACY POLICY -->
                <div id="privacy" class="legal-section bento-card mb-5 scroll-reveal">
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
                <div id="terms" class="legal-section bento-card mb-5 scroll-reveal">
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
// Handle hash on load to scroll to correct section
document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.substring(1); // Remove #
    if (hash && ['mentions', 'privacy', 'terms'].includes(hash)) {
        const element = document.getElementById(hash);
        if(element) {
            // Small delay to ensure layout is ready
            setTimeout(() => {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
