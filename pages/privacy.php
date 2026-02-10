<?php include '../includes/header.php'; ?>

<section class="py-5" style="padding-top: 120px !important;">
    <div class="container">
        
        <h1 class="display-title mb-5 text-center"><?php echo t('legal_meta_title'); ?></h1>

        <div class="row g-4">
            <!-- Sidebar Navigation -->
            <div class="col-lg-3">
                <div class="d-flex flex-column gap-2 sticky-top" style="top: 120px;">
                    <a href="formation" class="btn btn-apple-glass text-start mb-3"><i class="fas fa-arrow-left me-2"></i> <?php echo t('formations'); ?></a>
                    
                    <a href="mentions-legales" class="btn btn-apple-glass text-start"><?php echo t('legal_notice_title'); ?></a>
                    <a href="politique-confidentialite" class="btn btn-apple-glass text-start active"><?php echo t('privacy_policy_title'); ?></a>
                    <a href="cgv" class="btn btn-apple-glass text-start"><?php echo t('terms_title'); ?></a>
                </div>
            </div>

            <!-- Content -->
            <div class="col-lg-9">
                <!-- PRIVACY POLICY -->
                <div id="privacy" class="bento-card mb-5 scroll-reveal">
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
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
