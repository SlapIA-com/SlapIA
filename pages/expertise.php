<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

$page_title = t('expertise') . " - SlapIA";
$page_description = t('comprehensive_expertise') . " - " . t('technical_expertise_desc');
$page_image = '/assets/img/brand/logo.png';
include '../includes/header.php'; ?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Thomas Lapierre",
  "jobTitle": "Expert IA & Consultant IT",
  "url": "https://www.slapia.com/expertise",
  "sameAs": "https://www.linkedin.com/in/lapierre-thomas/",
  "worksFor": {
    "@type": "Organization",
    "name": "SlapIA",
    "url": "https://www.slapia.com"
  },
  "knowsAbout": ["Intelligence Artificielle", "Automatisation", "n8n", "PHP", "Consulting IT", "Formation IA"],
  "hasCredential": [
    {
      "@type": "EducationalOccupationalCredential",
      "name": "Certification Hoshin Niveau 1 — Le Collaborateur Augmenté",
      "dateCreated": "2025-12"
    },
    {
      "@type": "EducationalOccupationalCredential",
      "name": "Certification Hoshin Niveau 2 — L'Entreprise Augmentée",
      "dateCreated": "2025-12"
    }
  ]
}
</script>



<section class="py-5 mt-5">
    <div class="container">

        <!-- Profile Hero -->
        <div class="row align-items-center mb-5 pb-5 border-bottom border-light border-opacity-10">
            <div class="col-lg-5 mb-4 mb-lg-0 text-center text-lg-start">
                <div class="position-relative d-inline-block">
                    <!-- Placeholder for profile picture -->
                    <div class="rounded-circle p-1 bg-gradient-to-br from-primary to-purple d-inline-block mb-4">
                        <img src="../assets/img/team/Thomas-Lapierre.jpg" alt="Thomas Lapierre"
                            class="rounded-circle shadow-lg"
                            style="width: 160px; height: 160px; border: 4px solid var(--bg-deep);">
                    </div>
                </div>
                <h1 class="display-title mb-2" style="font-size: 3rem;">Thomas Lapierre</h1>
                <p class="text-gradient-purple fw-bold fs-4 mb-3"><?php echo t('expert_subtitle'); ?></p>
                <div class="d-flex gap-3 justify-content-center justify-content-lg-start mt-4">
                    <a href="https://www.linkedin.com/in/lapierre-thomas/" target="_blank"
                        class="btn btn-outline-light rounded-pill px-4">
                        <i class="fab fa-linkedin me-2"></i> LinkedIn
                    </a>
                    <a href="https://synologynasthomas.synology.me/" target="_blank"
                        class="btn btn-outline-primary rounded-pill px-4">
                        <i class="fas fa-briefcase me-2"></i> Portfolio
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

        <!-- Professional Summary -->
        <div class="row mb-5 pb-5 border-bottom border-light border-opacity-10">
            <div class="col-lg-12 text-center mb-4">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill mb-3"><?php echo t('about_me_badge'); ?></span>
                <h2 class="display-4 fw-bold text-white"><?php echo t('summary_title'); ?></h2>
            </div>
            <div class="col-lg-8 offset-lg-2">
                <div class="bento-card p-4 p-md-5">
                    <p class="text-secondary lh-lg mb-0"><?php echo t('summary_text'); ?></p>
                </div>
            </div>
        </div>

        <!-- Certifications Hoshin Section -->
        <div class="row align-items-center mb-5 pb-5">
            <div class="col-lg-12 text-center mb-5">
                <span
                    class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill mb-3"><?php echo t('certified_excellence'); ?></span>
                <h2 class="display-4 fw-bold text-white"><?php echo t('hoshin_cert_title'); ?></h2>
                <p class="text-secondary mx-auto mt-3" style="max-width: 700px;">
                    <?php echo t('hoshin_cert_desc'); ?>
                </p>
            </div>

            <div class="col-md-6 mb-4 mb-md-0">
                <div class="bento-card p-5 h-100 text-center">
                    <div class="icon-box mx-auto text-white mb-4"
                        style="width: 70px; height: 70px; border-radius: 50%; background: rgba(255,255,255,0.04);">
                        <i class="fas fa-user-astronaut fs-2"></i>
                    </div>
                    <div class="cert-image mb-3">
                        <img src="../assets/img/certifications/Formation_iA_Niveau_1_Entreprise.jpg" alt="Certification Niveau 1"
                            style="max-width:100%; height:auto; border-radius:8px;">
                    </div>
                    <h3 class="text-white mb-2"><?php echo t('cert_level_1_title'); ?></h3>
                    <p class="text-primary fw-bold text-uppercase small tracking-wide mb-4">
                        <?php echo t('cert_level_1_subtitle'); ?>
                    </p>
                    <p class="text-secondary mb-4">
                        <?php echo t('cert_level_1_desc'); ?>
                    </p>
                    <div class="d-inline-block px-3 py-1 rounded-pill bg-white bg-opacity-10 text-white small">
                        <i class="fas fa-check-circle me-2 text-success"></i>Nyon, Dec 2025
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="bento-card p-5 h-100 text-center"
                    style="background: linear-gradient(145deg, rgba(20,20,20,0.6), rgba(40,40,40,0.4));">
                    <div class="icon-box mx-auto text-white bg-gradient-to-br from-purple to-pink mb-4"
                        style="width: 70px; height: 70px; border-radius: 50%; background-color: #bf5af2;">
                        <i class="fas fa-rocket fs-2"></i>
                    </div>
                    <div class="cert-image mb-3">
                        <img src="../assets/img/certifications/Formation_iA_Niveau_2_Entreprise.jpg" alt="Certification Niveau 2"
                            style="max-width:100%; height:auto; border-radius:8px;">
                    </div>
                    <h3 class="text-white mb-2"><?php echo t('cert_level_2_title'); ?></h3>
                    <p class="text-white-50 fw-bold text-uppercase small tracking-wide mb-4">
                        <?php echo t('cert_level_2_subtitle'); ?>
                    </p>
                    <p class="text-secondary mb-4">
                        <?php echo t('cert_level_2_desc'); ?>
                    </p>
                    <div class="d-inline-block px-3 py-1 rounded-pill bg-white bg-opacity-10 text-white small">
                        <i class="fas fa-check-circle me-2 text-success"></i>Nyon, Dec 2025
                    </div>
                </div>
            </div>
        </div>

        <!-- Experience & Education -->
        <div class="row mb-5 pb-5 border-bottom border-light border-opacity-10">

            <!-- Experience -->
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h2 class="text-white mb-4 fs-3"><i class="fas fa-briefcase text-primary me-2"></i><?php echo t('experience_title'); ?></h2>

                <div class="bento-card p-4 mb-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0" style="width:40px;height:40px;border-radius:50%;background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-rocket text-primary"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1 h6"><?php echo t('exp_slapia_title'); ?></h5>
                            <p class="text-primary small fw-bold mb-1">SlapIA</p>
                            <p class="text-secondary small mb-0"><i class="fas fa-calendar-alt me-1"></i>Fév. 2026 — <?php echo t('present'); ?> · Besançon, France</p>
                        </div>
                    </div>
                </div>

                <div class="bento-card p-4 mb-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0" style="width:40px;height:40px;border-radius:50%;background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-server text-primary"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1 h6"><?php echo t('exp_micromega_title1'); ?></h5>
                            <p class="text-primary small fw-bold mb-1">Micro-Mega SA (COLTENE)</p>
                            <p class="text-secondary small mb-0"><i class="fas fa-calendar-alt me-1"></i>Nov. 2024 — <?php echo t('present'); ?> · Besançon</p>
                        </div>
                    </div>
                </div>

                <div class="bento-card p-4 mb-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0" style="width:40px;height:40px;border-radius:50%;background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-tools text-primary"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1 h6"><?php echo t('exp_micromega_title2'); ?></h5>
                            <p class="text-primary small fw-bold mb-1">Micro-Mega SA (COLTENE)</p>
                            <p class="text-secondary small mb-0"><i class="fas fa-calendar-alt me-1"></i>Oct. 2022 — Oct. 2024 · Besançon</p>
                        </div>
                    </div>
                </div>

                <div class="bento-card p-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0" style="width:40px;height:40px;border-radius:50%;background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-user-graduate text-primary"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1 h6"><?php echo t('exp_adeo_title'); ?></h5>
                            <p class="text-primary small fw-bold mb-1">ADEO INFORMATIQUE</p>
                            <p class="text-secondary small mb-0"><i class="fas fa-calendar-alt me-1"></i>Mai 2022 — Juin 2022 · Besançon</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Education -->
            <div class="col-lg-6">
                <h2 class="text-white mb-4 fs-3"><i class="fas fa-graduation-cap text-primary me-2"></i><?php echo t('education_title'); ?></h2>

                <div class="bento-card p-4 mb-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0" style="width:40px;height:40px;border-radius:50%;background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-laptop-code text-primary"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1 h6"><?php echo t('edu_bts_title'); ?></h5>
                            <p class="text-primary small fw-bold mb-1"><?php echo t('edu_bts_school'); ?></p>
                            <p class="text-secondary small mb-0"><i class="fas fa-calendar-alt me-1"></i>Sept. 2024 — Août 2026</p>
                        </div>
                    </div>
                </div>

                <div class="bento-card p-4 mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0" style="width:40px;height:40px;border-radius:50%;background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-microchip text-primary"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1 h6"><?php echo t('edu_bac_title'); ?></h5>
                            <p class="text-primary small fw-bold mb-1"><?php echo t('edu_bac_school'); ?></p>
                            <p class="text-secondary small mb-0"><i class="fas fa-calendar-alt me-1"></i>Sept. 2021 — Août 2024</p>
                        </div>
                    </div>
                </div>

                <!-- Skills, Languages & Additional Certifications -->
                <h2 class="text-white mb-4 fs-3"><i class="fas fa-layer-group text-primary me-2"></i><?php echo t('skills_section_title'); ?></h2>

                <div class="bento-card p-4 mb-3">
                    <h6 class="text-white mb-3"><i class="fas fa-code text-primary me-2"></i><?php echo t('technical_expertise'); ?></h6>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2"><?php echo t('skill_ai_tools'); ?></span>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2">Visual Studio Code</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2">.htaccess</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2">PHP</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2">n8n</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2">Make</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2">Notion API</span>
                    </div>
                </div>

                <div class="bento-card p-4 mb-3">
                    <h6 class="text-white mb-3"><i class="fas fa-globe text-primary me-2"></i><?php echo t('languages_title'); ?></h6>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary small">🇫🇷 Français</span>
                            <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 px-2 py-1 small"><?php echo t('lang_native_bilingual'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary small">🇬🇧 English</span>
                            <span class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-25 px-2 py-1 small"><?php echo t('lang_limited_working'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary small">🇪🇸 Español</span>
                            <span class="badge bg-secondary bg-opacity-30 text-secondary border border-secondary border-opacity-25 px-2 py-1 small"><?php echo t('lang_elementary'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="bento-card p-4">
                    <h6 class="text-white mb-3"><i class="fas fa-certificate text-primary me-2"></i><?php echo t('additional_certs_title'); ?></h6>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-check-circle text-success small flex-shrink-0"></i>
                            <span class="text-secondary small">PIX — Certificat Pix</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-check-circle text-success small flex-shrink-0"></i>
                            <span class="text-secondary small">Les Essentiels de Microsoft Azure</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-check-circle text-success small flex-shrink-0"></i>
                            <span class="text-secondary small">AI : Charges de travail & Machine Learning sur Azure</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="text-center pb-5">
            <div class="bento-card d-inline-block p-5 text-center">
                <h3 class="text-white mb-3"><?php echo t('interested_collab'); ?></h3>
                <p class="text-secondary mb-4"><?php echo t('lets_discuss_hoshin'); ?></p>
                <a href="/contact" class="btn-apple"><?php echo t('get_in_touch_btn'); ?> <i
                        class="fas fa-arrow-right ms-2"></i></a>
            </div>
        </div>

    </div>
</section>

<?php include '../includes/footer.php'; ?>