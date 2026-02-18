<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

$page_title = t('companies') . " - SlapIA";
$page_description = t('training_for_companies') . " - " . t('level_2_title');
$page_image = '/assets/img/logo.png';
include '../includes/header.php';

include '../includes/components.php';
?>

<section class="py-5 mt-5">
    <div class="container">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill border border-secondary border-opacity-25 mb-4" 
                     style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
                    <span class="badge bg-success rounded-pill" style="font-size: 0.7rem;"><?php echo t('b2b'); ?></span>
                    <span class="text-secondary small fw-medium"><?php echo t('custom_trainings'); ?></span>
                </div>
                <h1 class="display-title mb-3" style="font-size: 3rem;"><?php echo t('training_for_companies'); ?></h1>
                <p class="text-secondary lead"><?php echo t('train_teams'); ?></p>
                 <!-- Language Note -->
                 <div class="mt-4 p-3 rounded-3 d-inline-block text-start border border-secondary border-opacity-10" style="background: rgba(255,255,255,0.03); max-width: 600px;">
                    <div class="d-flex gap-3">
                        <i class="fas fa-globe text-primary mt-1"></i>
                        <div>
                            <h6 class="text-white mb-1 small text-uppercase fw-bold"><?php echo t('lang_disclaimer_title'); ?></h6>
                            <p class="text-secondary small mb-0"><?php echo t('lang_disclaimer_text'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Offres B2B -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <?php render_b2b_card('fas fa-users', 'text-primary', 'background: rgba(41, 151, 255, 0.1);', 'team_training', 'group_sessions'); ?>
            </div>
            <div class="col-md-4">
                <?php render_b2b_card('fas fa-search', 'text-warning', 'background: rgba(255, 193, 7, 0.1);', 'ai_audit', 'process_analysis'); ?>
            </div>
            <div class="col-md-4">
                <?php render_b2b_card('fas fa-cogs', 'text-success', 'background: rgba(16, 185, 129, 0.1);', 'deployment', 'tool_implementation'); ?>
            </div>
        </div>

        <!-- ROI Calculator (Embedded) -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-12">
                <iframe src="/Calcule-ROI-IA?embed=true" width="100%" height="750" frameborder="0" scrolling="no" style="border-radius: 24px; overflow: hidden; background: transparent;"></iframe>
            </div>
        </div>

        <!-- Section Logos Entreprises -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-10">
                <div class="bento-card p-5">
                    <h3 class="text-white text-center mb-4"><?php echo t('trusted_by'); ?></h3>
                    <div class="row g-4 align-items-center justify-content-center">
                        <div class="col-6 col-md-4 col-lg-3 text-center">
                            <div class="p-4 rounded-3 h-100" style="background: rgba(255,255,255,0.05);">
                                <div class="d-flex align-items-center justify-content-center mb-3" style="height: 100px;">
                                    <img src="/assets/img/hoshin.png" alt="Hoshin Partners" class="img-fluid rounded-circle" style="max-height: 80px; width: auto;">
                                </div>
                                <a href="https://www.linkedin.com/company/hoshin-partners/" target="_blank" class="text-white text-decoration-none fw-bold d-block mb-2 hover-text-primary">Hoshin Partners</a>
                                <p class="text-secondary small mb-0"><?php echo t('hoshin_desc'); ?></p>
                            </div>
                        </div>
                        <!-- Placeholders removed -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Témoignages / Projets -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h2 class="text-white text-center mb-4"><?php echo t('projects_realized'); ?></h2>
                <div class="row g-4">
                    <!-- Projet : Automatisation ERP -->
                    <div class="col-md-6">
                        <div class="bento-card p-4 h-100">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <i class="fas fa-cogs text-white"></i>
                                </div>
                                <div>
                                    <h5 class="text-white mb-0"><?php echo t('project_workflow_title'); ?></h5>
                                    <span class="text-secondary small"><?php echo t('project_saas_startup'); ?></span>
                                </div>
                            </div>
                            <p class="text-secondary"><?php echo t('project_workflow_desc'); ?></p>
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="badge bg-primary bg-opacity-25 text-primary">Make</span>
                                <span class="badge bg-success bg-opacity-25 text-success">Perplexity</span>
                                <span class="badge bg-warning bg-opacity-25 text-warning">Airtable</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="row justify-content-center mt-5">
            <div class="col-lg-8 text-center">
                <div class="bento-card p-5" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);">
                    <h3 class="text-white mb-3"><?php echo t('ready_to_train'); ?></h3>
                    <p class="text-secondary mb-4"><?php echo t('lets_discuss'); ?></p>
                    <a href="/contact" class="btn-apple">
                        <?php echo t('request_quote'); ?>
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M4 8a.5.5 0 0 1 .5-.5h5.793L8.146 5.354a.5.5 0 1 1 .708-.708l3 3a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708-.708L10.293 8.5H4.5A.5.5 0 0 1 4 8"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
