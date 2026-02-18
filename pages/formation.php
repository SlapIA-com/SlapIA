<?php

$page_title = t('formations') . " - SlapIA";
$page_description = t('cert_level_1_desc') . " " . t('cert_level_2_desc');
$page_image = '/assets/img/Formation_iA_Niveau_1_Entreprise.jpg';
include '../includes/header.php'; ?>

<section class="py-5 mt-5">
    <div class="container">
        
        <!-- Header -->
        <div class="row mb-5 text-center">
            <div class="col-lg-8 mx-auto">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill border border-secondary border-opacity-25 mb-4" 
                     style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
                    <span class="badge bg-success rounded-pill" style="font-size: 0.7rem;"><?php echo t('complete'); ?></span>
                    <span class="text-secondary small fw-medium"><?php echo t('beginner_to_expert'); ?></span>
                </div>
                <h1 class="display-title mb-3"><?php echo t('training_courses'); ?></h1>
                <p class="text-secondary lead"><?php echo t('fundamentals_to_expertise'); ?></p>
                <!-- Language Note -->
                <div class="mt-4 p-3 rounded-3 d-inline-block text-start border border-secondary border-opacity-10" style="background: rgba(255,255,255,0.03); max-width: 600px;">
                    <div class="d-flex gap-3">
                        <i class="fas fa-info-circle text-info mt-1"></i>
                        <div>
                            <h6 class="text-white mb-1 small text-uppercase fw-bold"><?php echo t('lang_disclaimer_title'); ?></h6>
                            <p class="text-secondary small mb-0"><?php echo t('lang_disclaimer_text'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Formation IA Globale -->
        <div class="bento-card p-4 p-md-5 mb-5 text-center" style="background: linear-gradient(135deg, rgba(41, 151, 255, 0.1) 0%, rgba(102, 126, 234, 0.1) 100%);">
            <div class="icon-box mx-auto text-white border-0 mb-4" style="background: var(--accent-blue); width: 70px; height: 70px;">
                <i class="fas fa-brain fs-3"></i>
            </div>
            <h2 class="text-white mb-3"><?php echo t('global_ai_training'); ?></h2>
            <p class="text-secondary mx-auto mb-4" style="max-width: 600px;">
                <?php echo t('complete_program'); ?>
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="#niveau1" class="btn btn-outline-primary rounded-pill px-4"><?php echo t('level_1'); ?></a>
                <a href="#niveau2" class="btn btn-outline-info rounded-pill px-4"><?php echo t('level_2'); ?></a>
                <a href="#formation" class="btn btn-outline-light rounded-pill px-4"><?php echo t('vip_coaching'); ?></a>
            </div>
        </div>

        <!-- Niveau 1 -->
        <div id="niveau1" class="bento-card p-4 p-md-5 mb-5">
            <div class="d-flex align-items-center gap-4 mb-4">
                <div class="d-flex justify-content-center align-items-center bg-primary text-white rounded-3 fw-bold" style="width: 60px; height: 60px; font-size: 2rem;">1</div>
                <div>
                    <h2 class="text-white mb-1"><?php echo t('acculturation_foundations'); ?></h2>
                    <p class="text-secondary mb-0"><?php echo t('understand_why'); ?></p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table text-white mb-0" style="--bs-table-bg: transparent; --bs-table-color: var(--text-secondary); border-color: var(--glass-border);">
                    <thead>
                        <tr class="text-uppercase small text-secondary border-bottom border-light border-opacity-10">
                            <th class="py-3 ps-0">Module</th>
                            <th class="py-3">Thématique</th>
                            <th class="py-3">Contenu Clé</th>
                            <th class="py-3 text-end pe-0">Outil(s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- M1 -->
                        <tr class="align-middle border-bottom border-light border-opacity-10">
                            <td class="py-4 ps-0 fw-bold text-white">M1</td>
                            <td class="py-4 text-white"><?php echo t('m1_level1_theme'); ?></td>
                            <td class="py-4"><?php echo t('m1_level1_desc'); ?></td>
                            <td class="py-4 text-end pe-0"><span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25">ChatGPT</span></td>
                        </tr>
                        <!-- M2 -->
                        <tr class="align-middle border-bottom border-light border-opacity-10">
                            <td class="py-4 ps-0 fw-bold text-white">M2</td>
                            <td class="py-4 text-white"><?php echo t('m2_level1_theme'); ?></td>
                            <td class="py-4"><?php echo t('m2_level1_desc'); ?></td>
                            <td class="py-4 text-end pe-0">
                                <span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25">Notion</span>
                                <span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25 ms-1">Airtable</span>
                            </td>
                        </tr>
                        <!-- M3 -->
                        <tr class="align-middle border-bottom border-light border-opacity-10">
                            <td class="py-4 ps-0 fw-bold text-white">M3</td>
                            <td class="py-4 text-white"><?php echo t('m3_level1_theme'); ?></td>
                            <td class="py-4"><?php echo t('m3_level1_desc'); ?></td>
                            <td class="py-4 text-end pe-0"><span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25">Make</span></td>
                        </tr>
                         <!-- M4 -->
                         <tr class="align-middle border-bottom border-light border-opacity-10">
                            <td class="py-4 ps-0 fw-bold text-white">M4</td>
                            <td class="py-4 text-white"><?php echo t('m4_level1_theme'); ?></td>
                            <td class="py-4"><?php echo t('m4_level1_desc'); ?></td>
                            <td class="py-4 text-end pe-0"><span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25">n8n</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Niveau 2 -->
        <div id="niveau2" class="bento-card p-4 p-md-5 mb-5" style="border-color: rgba(41, 151, 255, 0.3);">
            <div class="d-flex align-items-center gap-4 mb-4">
                <div class="d-flex justify-content-center align-items-center bg-info text-white rounded-3 fw-bold" style="width: 60px; height: 60px; font-size: 2rem;">2</div>
                <div>
                    <h2 class="text-white mb-1"><?php echo t('level_2_title'); ?></h2>
                    <p class="text-secondary mb-0"><?php echo t('level_2_subtitle'); ?></p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table text-white mb-0" style="--bs-table-bg: transparent; --bs-table-color: var(--text-secondary); border-color: var(--glass-border);">
                    <thead>
                        <tr class="text-uppercase small text-secondary border-bottom border-light border-opacity-10">
                            <th class="py-3 ps-0"><?php echo t('module'); ?></th>
                            <th class="py-3">Thématique</th>
                            <th class="py-3"><?php echo t('what_you_will_do'); ?></th>
                            <th class="py-3 text-end pe-0">Outil(s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- M1 -->
                        <tr class="align-middle border-bottom border-light border-opacity-10">
                            <td class="py-4 ps-0 fw-bold text-white">M1</td>
                            <td class="py-4 text-white"><?php echo t('m1_level2_theme'); ?></td>
                            <td class="py-4"><?php echo t('m1_level2_desc'); ?></td>
                            <td class="py-4 text-end pe-0">
                                <span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25">Airtable</span>
                                <span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25 ms-1">Notion</span>
                            </td>
                        </tr>
                        <!-- M2 -->
                        <tr class="align-middle border-bottom border-light border-opacity-10">
                            <td class="py-4 ps-0 fw-bold text-white">M2</td>
                            <td class="py-4 text-white"><?php echo t('m2_level2_theme'); ?></td>
                            <td class="py-4"><?php echo t('m2_level2_desc'); ?></td>
                            <td class="py-4 text-end pe-0">
                                <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25">Make</span>
                                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 ms-1">n8n</span>
                            </td>
                        </tr>
                        <!-- M3 -->
                        <tr class="align-middle border-bottom border-light border-opacity-10">
                            <td class="py-4 ps-0 fw-bold text-white">M3</td>
                            <td class="py-4 text-white"><?php echo t('m3_level2_theme'); ?></td>
                            <td class="py-4"><?php echo t('m3_level2_desc'); ?></td>
                            <td class="py-4 text-end pe-0"><span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">ChatGPT + Auto</span></td>
                        </tr>
                         <!-- M4 -->
                         <tr class="align-middle border-bottom border-light border-opacity-10">
                            <td class="py-4 ps-0 fw-bold text-white">M4</td>
                            <td class="py-4 text-white"><?php echo t('m4_level2_theme'); ?></td>
                            <td class="py-4"><?php echo t('m4_level2_desc'); ?></td>
                            <td class="py-4 text-end pe-0"><span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-10">Logic</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 3: Accompagnement Personnalisé -->
        <div id="formation" class="row align-items-center mb-5">
            <div class="col-lg-5 mb-4 mb-lg-0">
                <h2 class="display-6 fw-bold mb-3"><?php echo t('vip_title_prefix'); ?><br><span class="text-gradient-purple"><?php echo t('vip_coaching'); ?></span></h2>
                <p class="text-secondary mb-4"><?php echo t('vip_desc'); ?></p>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex gap-3">
                        <div class="icon-box rounded-circle bg-success bg-opacity-10 text-success border-success border-opacity-25 mb-0" style="width:40px; height:40px; font-size:1rem;">
                            <i class="fas fa-video"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1"><?php echo t('daily_visio'); ?></h5>
                            <p class="text-secondary small m-0"><?php echo t('daily_visio_desc'); ?></p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="icon-box rounded-circle bg-info bg-opacity-10 text-info border-info border-opacity-25 mb-0" style="width:40px; height:40px; font-size:1rem;">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1"><?php echo t('tech_help'); ?></h5>
                            <p class="text-secondary small m-0"><?php echo t('tech_help_desc'); ?></p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="icon-box rounded-circle bg-warning bg-opacity-10 text-warning border-warning border-opacity-25 mb-0" style="width:40px; height:40px; font-size:1rem;">
                            <i class="fas fa-road"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1"><?php echo t('custom_path'); ?></h5>
                            <p class="text-secondary small m-0"><?php echo t('custom_path_desc'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 offset-lg-1">
                <div class="bento-card p-4 text-center">
                    <div class="mb-4">
                        <i class="fas fa-rocket text-warning fs-1"></i>
                    </div>
                    <h3 class="text-white"><?php echo t('intensive_mentoring'); ?></h3>
                    <p class="text-secondary mb-4 small"><?php echo t('intensive_mentoring_desc'); ?></p>
                    <div class="display-4 fw-bold text-white mb-0">60€<span class="fs-5 text-secondary fw-normal">/session</span></div>
                    <p class="text-secondary small mb-4 fw-bold text-uppercase tracking-wider"><?php echo t('one_hour_per_day'); ?></p>
                    <a href="/contact" class="btn-apple w-100 justify-content-center"><?php echo t('book_slot'); ?></a>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- FAQ Section -->
<section class="pb-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h3 class="text-white text-center mb-4"><?php echo t('faq_title'); ?></h3>
                <div class="accordion" id="faqAccordion">
                    
                    <!-- Q1 -->
                    <div class="bento-card p-0 mb-3 overflow-hidden">
                        <div class="p-0" id="headingOne">
                            <button class="accordion-button collapsed bg-transparent text-white shadow-none p-4 w-100 d-flex justify-content-between align-items-center border-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                <span class="fw-bold"><?php echo t('faq_q1'); ?></span>
                                <i class="fas fa-chevron-down text-secondary transition-icon"></i>
                            </button>
                        </div>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary p-4 pt-0 border-top border-secondary border-opacity-10">
                                <?php echo t('faq_a1'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Q2 -->
                    <div class="bento-card p-0 mb-3 overflow-hidden">
                        <div class="p-0" id="headingTwo">
                            <button class="accordion-button collapsed bg-transparent text-white shadow-none p-4 w-100 d-flex justify-content-between align-items-center border-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                <span class="fw-bold"><?php echo t('faq_q2'); ?></span>
                                <i class="fas fa-chevron-down text-secondary transition-icon"></i>
                            </button>
                        </div>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary p-4 pt-0 border-top border-secondary border-opacity-10">
                                <?php echo t('faq_a2'); ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
