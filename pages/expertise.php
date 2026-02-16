<?php include '../includes/header.php'; ?>

<section class="py-5 mt-5">
    <div class="container">
        
        <!-- Profile Hero -->
        <div class="row align-items-center mb-5 pb-5 border-bottom border-light border-opacity-10">
            <div class="col-lg-5 mb-4 mb-lg-0 text-center text-lg-start">
                <div class="position-relative d-inline-block">
                    <!-- Placeholder for profile picture -->
                    <div class="rounded-circle p-1 bg-gradient-to-br from-primary to-purple d-inline-block mb-4">
                        <img src="https://media.licdn.com/dms/image/v2/D4E03AQH5AN2bQQZUmg/profile-displayphoto-scale_200_200/B4EZxB5vFqHgAY-/0/1770632181103?e=1772064000&v=beta&t=HlsFwMEbWP2R-5vASKRrxq2ElWNQqAHU7JJKXaIwOH4" alt="Thomas Lapierre" class="rounded-circle shadow-lg" style="width: 160px; height: 160px; border: 4px solid var(--bg-deep);">
                    </div>
                </div>
                <h1 class="display-title mb-2" style="font-size: 3rem;">Thomas Lapierre</h1>
                <p class="text-gradient-purple fw-bold fs-4 mb-3"><?php echo t('expert_subtitle'); ?></p>
                <div class="d-flex gap-3 justify-content-center justify-content-lg-start mt-4">
                    <a href="https://www.linkedin.com/in/lapierre-thomas/" target="_blank" class="btn btn-outline-light rounded-pill px-4">
                        <i class="fab fa-linkedin me-2"></i> LinkedIn
                    </a>
                </div>
            </div>
            
            <div class="col-lg-6 offset-lg-1">
                <div class="bento-card p-4 p-md-5">
                    <h3 class="text-white mb-4"><?php echo t('my_background'); ?></h3>
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <i class="fas fa-check-circle text-primary"></i>
                            </div>
                            <div>
                                <h5 class="text-white h6 mb-1"><?php echo t('technical_expertise'); ?></h5>
                                <p class="text-secondary small m-0"><?php echo t('technical_expertise_desc'); ?></p>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <i class="fas fa-check-circle text-primary"></i>
                            </div>
                            <div>
                                <h5 class="text-white h6 mb-1"><?php echo t('practical_pedagogy'); ?></h5>
                                <p class="text-secondary small m-0"><?php echo t('practical_pedagogy_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certifications Hoshin Section -->
        <div class="row align-items-center mb-5 pb-5">
            <div class="col-lg-12 text-center mb-5">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill mb-3"><?php echo t('certified_excellence'); ?></span>
                <h2 class="display-4 fw-bold text-white"><?php echo t('hoshin_cert_title'); ?></h2>
                <p class="text-secondary mx-auto mt-3" style="max-width: 700px;">
                    <?php echo t('hoshin_cert_desc'); ?>
                </p>
            </div>

            <div class="col-md-6 mb-4 mb-md-0">
                <div class="bento-card p-5 h-100 text-center shadow-lg" style="border-width: 1px; --glass-border: rgba(41, 151, 255, 0.3);">
                    <div class="icon-box mx-auto text-white mb-4" style="width: 70px; height: 70px; border-radius: 50%; background: rgba(255,255,255,0.04);">
                        <i class="fas fa-user-astronaut fs-2"></i>
                    </div>
                    <div class="cert-image mb-3">
                        <img src="../assets/img/Formation_iA_Niveau_1_Entreprise.jpg" alt="Certification Niveau 1" style="max-width:100%; height:auto; border-radius:8px;">
                    </div>
                    <h3 class="text-white mb-2"><?php echo t('cert_level_1_title'); ?></h3>
                    <p class="text-primary fw-bold text-uppercase small tracking-wide mb-4"><?php echo t('cert_level_1_subtitle'); ?></p>
                    <p class="text-secondary mb-4">
                        <?php echo t('cert_level_1_desc'); ?>
                    </p>
                    <div class="d-inline-block px-3 py-1 rounded-pill bg-white bg-opacity-10 text-white small">
                        <i class="fas fa-check-circle me-2 text-success"></i>Nyon, Dec 2025
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="bento-card p-5 h-100 text-center" style="background: linear-gradient(145deg, rgba(20,20,20,0.6), rgba(40,40,40,0.4));">
                    <div class="icon-box mx-auto text-white bg-gradient-to-br from-purple to-pink mb-4" style="width: 70px; height: 70px; border-radius: 50%; background-color: #bf5af2;">
                        <i class="fas fa-rocket fs-2"></i>
                    </div>
                    <div class="cert-image mb-3">
                        <img src="../assets/img/Formation_iA_Niveau_2_Entreprise.jpg" alt="Certification Niveau 2" style="max-width:100%; height:auto; border-radius:8px;">
                    </div>
                    <h3 class="text-white mb-2"><?php echo t('cert_level_2_title'); ?></h3>
                    <p class="text-white-50 fw-bold text-uppercase small tracking-wide mb-4"><?php echo t('cert_level_2_subtitle'); ?></p>
                    <p class="text-secondary mb-4">
                        <?php echo t('cert_level_2_desc'); ?>
                    </p>
                    <div class="d-inline-block px-3 py-1 rounded-pill bg-white bg-opacity-10 text-white small">
                        <i class="fas fa-check-circle me-2 text-success"></i>Nyon, Dec 2025
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="text-center pb-5">
            <div class="bento-card d-inline-block p-5 text-center">
                <h3 class="text-white mb-3"><?php echo t('interested_collab'); ?></h3>
                <p class="text-secondary mb-4"><?php echo t('lets_discuss_hoshin'); ?></p>
                <a href="/contact" class="btn-apple"><?php echo t('get_in_touch_btn'); ?> <i class="fas fa-arrow-right ms-2"></i></a>
            </div>
        </div>

    </div>
</section>

<?php include '../includes/footer.php'; ?>
