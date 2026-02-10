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
                    <a href="politique-confidentialite" class="btn btn-apple-glass text-start"><?php echo t('privacy_policy_title'); ?></a>
                    <a href="cgv" class="btn btn-apple-glass text-start active"><?php echo t('terms_title'); ?></a>
                </div>
            </div>

            <!-- Content -->
            <div class="col-lg-9">
                <!-- TERMS OF SALE (CGV) -->
                <div id="terms" class="bento-card mb-5 scroll-reveal">
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

<?php include '../includes/footer.php'; ?>
