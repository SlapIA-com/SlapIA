<?php include '../includes/header.php'; ?>

<section class="py-5" style="padding-top: 120px !important;">
    <div class="container">
        
        <h1 class="display-title mb-5 text-center"><?php echo t('legal_meta_title'); ?></h1>

        <div class="row g-4">
            <!-- Sidebar Navigation -->
            <div class="col-lg-3">
                <div class="d-flex flex-column gap-2 sticky-top" style="top: 120px;">
                    <a href="formation" class="btn btn-apple-glass text-start mb-3"><i class="fas fa-arrow-left me-2"></i> <?php echo t('formations'); ?></a>
                    
                    <a href="mentions-legales" class="btn btn-apple-glass text-start active"><?php echo t('legal_notice_title'); ?></a>
                    <a href="politique-confidentialite" class="btn btn-apple-glass text-start"><?php echo t('privacy_policy_title'); ?></a>
                    <a href="cgv" class="btn btn-apple-glass text-start"><?php echo t('terms_title'); ?></a>
                </div>
            </div>

            <!-- Content -->
            <div class="col-lg-9">
                <!-- SECTIONS 1-3: MENTIONS LEGALES -->
                <div id="mentions" class="bento-card mb-5 scroll-reveal">
                    
                    <!-- 1. EDITITEUR -->
                    <h3 class="text-white mb-4"><?php echo t('legal_section_editor'); ?></h3>
                    <div class="text-secondary mb-5">
                        <ul class="list-unstyled">
                            <li class="mb-2"><strong><?php echo t('legal_label_name'); ?></strong> Thomas Lapierre</li>
                            <li class="mb-2"><strong><?php echo t('legal_label_address'); ?></strong> <?php echo t('legal_value_address_placeholder'); ?></li>
                            <li class="mb-2"><strong><?php echo t('legal_label_email'); ?></strong> contact@javabien.ovh</li>
                        </ul>
                    </div>

                    <!-- 2. ENTREPRISE -->
                    <h3 class="text-white mb-4"><?php echo t('legal_section_company'); ?></h3>
                    <div class="text-secondary mb-5">
                        <ul class="list-unstyled">
                            <li class="mb-2"><strong><?php echo t('legal_label_form'); ?></strong> <?php echo t('legal_value_form'); ?></li>
                            <li class="mb-2"><strong><?php echo t('legal_label_siren'); ?></strong> 100 946 722</li>
                            <li class="mb-2"><strong><?php echo t('legal_label_siret'); ?></strong> 100 946 722 00012</li>
                            <li class="mb-2"><strong><?php echo t('legal_label_ape'); ?></strong> 85.59A (<?php echo t('legal_value_activity'); ?>)</li>
                            <li class="mb-2"><strong><?php echo t('legal_label_tva'); ?></strong> <?php echo t('legal_value_tva_none'); ?></li>
                        </ul>
                    </div>

                    <!-- 3. HEBERGEMENT -->
                    <h3 class="text-white mb-4"><?php echo t('legal_section_hosting'); ?></h3>
                    <div class="text-secondary">
                        <p>
                            <strong><?php echo t('hosting_provider'); ?></strong><br>
                            Synology NAS / Local Host<br>
                            <?php echo t('infrastructure_by'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
