<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';
include_once '../api/notion-satisfaction.php';

$page_title = t('formations') . " - SlapIA";
$page_description = t('formation_meta_desc');
$page_image = '/assets/img/logo.png';
include '../includes/header.php';
include '../includes/components.php';

// Notion Data
$stats = getSatisfactionStats(true);
$reviews = getNotionReviews(12, $lang ?? 'fr');
?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Course",
  "name": "Formation IA & Automatisation — SlapIA",
  "description": "Formation complète en intelligence artificielle et automatisation avec n8n. 3 niveaux : collaborateur augmenté, entreprise augmentée, expert automatisation.",
  "provider": {
    "@type": "EducationalOrganization",
    "name": "SlapIA",
    "url": "https://www.slapia.com",
    "logo": "https://www.slapia.com/assets/img/logo.svg"
  },
  "url": "https://www.slapia.com/formation",
  "educationalLevel": ["Beginner", "Intermediate", "Advanced"],
  "teaches": ["Intelligence Artificielle", "Automatisation", "n8n", "Prompting", "AI Agents"],
  "inLanguage": "fr",
  "offers": {
    "@type": "Offer",
    "category": "Paid"
  }
}
</script>

<!-- Subtle Grid Background -->
<div class="grid-bg"></div>

<!-- ============================================
     HERO SECTION - Formation Premium
     ============================================ -->
<section class="hero-premium text-center position-relative">
    <!-- Animated gradient mesh -->
    <div class="hero-gradient-mesh"></div>

    <!-- Floating particles -->
    <div class="hero-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="container position-relative" style="z-index: 2;">

        <!-- Floating Badge -->
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill border border-secondary border-opacity-25 mb-4 fade-in-up"
            style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
            <span class="badge bg-success rounded-pill shimmer-badge"
                style="font-size: 0.7rem; border: 1px solid rgba(255,255,255,0.2);"><?php echo t('complete'); ?></span>
            <span class="text-secondary small fw-medium"><?php echo t('beginner_to_expert'); ?></span>
        </div>

        <!-- Main Title -->
        <h1 class="hero-title-xl mb-4 fade-in-up delay-200">
            <?php echo t('training_courses'); ?><br>
            <span class="text-gradient-animated"><?php echo t('formation_hero_gradient'); ?></span>
        </h1>

        <!-- Sub-description -->
        <p class="hero-description mx-auto mb-0 fade-in-up delay-400">
            <?php echo t('fundamentals_to_expertise'); ?>
        </p>

        <!-- Quick Navigation Pills -->
        <div class="hero-cta-group fade-in-up delay-600">
            <a href="#niveau1" class="btn-outline-glass" style="padding: 12px 28px; font-size: 0.95rem;">
                <span class="badge bg-primary rounded-circle me-1" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;">1</span>
                <?php echo t('level_1'); ?>
            </a>
            <a href="#niveau2" class="btn-outline-glass" style="padding: 12px 28px; font-size: 0.95rem;">
                <span class="badge bg-info rounded-circle me-1" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;">2</span>
                <?php echo t('level_2'); ?>
            </a>
            <a href="#niveau3" class="btn-outline-glass" style="padding: 12px 28px; font-size: 0.95rem;">
                <span class="badge rounded-circle me-1" style="width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;background:linear-gradient(135deg,#f59e0b,#ef4444);">3</span>
                <?php echo t('level_3'); ?>
            </a>
            <a href="#formation" class="btn-primary-glow" style="padding: 12px 28px; font-size: 0.95rem;">
                <i class="fas fa-crown"></i> <?php echo t('vip_coaching'); ?>
            </a>
        </div>

        <!-- Hero Stats Bar -->
        <div class="hero-stats-bar fade-in-up delay-600">
            <div class="hero-stat">
                <div class="hero-stat-number"><?php echo ($stats['nombre'] > 0) ? '+' . (int)$stats['nombre'] : '50+'; ?></div>
                <div class="hero-stat-label"><?php echo t('hero_stat_trained'); ?></div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-number"><?php echo ($stats['pourcentage'] !== 'N.A') ? $stats['pourcentage'] . '%' : '98%'; ?></div>
                <div class="hero-stat-label"><?php echo t('satisfaction'); ?></div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-number">3</div>
                <div class="hero-stat-label"><?php echo t('hero_stat_levels'); ?></div>
            </div>
        </div>
    </div>
</section>


<!-- ============================================
     LANGUAGE DISCLAIMER
     ============================================ -->
<section class="pb-4">
    <div class="container fade-in-up delay-600">
        <div class="bento-card p-3 d-inline-flex gap-3 align-items-start mx-auto d-block" 
            style="background: rgba(41,151,255,0.05); border-color: rgba(41,151,255,0.15); max-width: 650px;">
            <i class="fas fa-globe text-info mt-1" style="font-size: 1.1rem;"></i>
            <div>
                <h6 class="text-white mb-1 small text-uppercase fw-bold"><?php echo t('lang_disclaimer_title'); ?></h6>
                <p class="text-secondary small mb-0"><?php echo t('lang_disclaimer_text'); ?></p>
            </div>
        </div>
    </div>
</section>


<!-- ============================================
     VISUAL LEARNING PATH - Progression Overview
     ============================================ -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-badge scroll-reveal">
                <i class="fas fa-route"></i> <?php echo t('formation_path_badge'); ?>
            </div>
            <h2 class="section-title-lg scroll-reveal delay-100"><?php echo t('formation_path_title'); ?></h2>
            <p class="text-secondary mx-auto scroll-reveal delay-200" style="max-width: 600px; font-size: 1.1rem;">
                <?php echo t('formation_path_subtitle'); ?>
            </p>
        </div>

        <!-- 3-Column Path Cards -->
        <div class="why-grid">
            <!-- Level 1 Card -->
            <a href="#niveau1" class="why-card scroll-reveal text-decoration-none" style="cursor:pointer;">
                <div class="why-icon icon-blue">
                    <i class="fas fa-seedling"></i>
                </div>
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                    <span class="badge bg-primary rounded-pill" style="font-size:0.7rem;"><?php echo t('level_1'); ?></span>
                </div>
                <h4 class="why-card-title"><?php echo t('formation_path_l1_title'); ?></h4>
                <p class="why-card-desc"><?php echo t('formation_path_l1_desc'); ?></p>
                <div class="mt-3 d-flex gap-2 justify-content-center flex-wrap">
                    <span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25" style="font-size:0.7rem;">ChatGPT</span>
                    <span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25" style="font-size:0.7rem;">Notion</span>
                </div>
            </a>

            <!-- Level 2 Card -->
            <a href="#niveau2" class="why-card scroll-reveal delay-100 text-decoration-none" style="cursor:pointer;">
                <div class="why-icon icon-purple">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                    <span class="badge bg-info rounded-pill" style="font-size:0.7rem;"><?php echo t('level_2'); ?></span>
                </div>
                <h4 class="why-card-title"><?php echo t('formation_path_l2_title'); ?></h4>
                <p class="why-card-desc"><?php echo t('formation_path_l2_desc'); ?></p>
                <div class="mt-3 d-flex gap-2 justify-content-center flex-wrap">
                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25" style="font-size:0.7rem;">ChatGPT</span>
                    <span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25" style="font-size:0.7rem;">Notion</span>
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-10" style="font-size:0.7rem;">Logic</span>
                </div>
            </a>

            <!-- Level 3 Card -->
            <a href="#niveau3" class="why-card scroll-reveal delay-200 text-decoration-none" style="cursor:pointer;">
                <div class="why-icon" style="background: linear-gradient(135deg, rgba(245,158,11,0.2), rgba(239,68,68,0.05)); color: #f59e0b; box-shadow: 0 0 30px rgba(245,158,11,0.1);">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                    <span class="badge rounded-pill" style="font-size:0.7rem;background:linear-gradient(135deg,#f59e0b,#ef4444);"><?php echo t('level_3'); ?></span>
                </div>
                <h4 class="why-card-title"><?php echo t('formation_path_l3_title'); ?></h4>
                <p class="why-card-desc"><?php echo t('formation_path_l3_desc'); ?></p>
                <div class="mt-3 d-flex gap-2 justify-content-center flex-wrap">
                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25" style="font-size:0.7rem;">n8n</span>
                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25" style="font-size:0.7rem;">Make</span>
                </div>
            </a>
        </div>
    </div>
</section>


<!-- ============================================
     NIVEAU 1 - Detailed Curriculum
     ============================================ -->
<section id="niveau1" class="py-5">
    <div class="container">
        <div class="bento-card bento-card-glow p-4 p-md-5 scroll-scale" style="border-color: rgba(41,151,255,0.2);">
            <!-- Header -->
            <div class="d-flex align-items-center gap-4 mb-4">
                <div class="d-flex justify-content-center align-items-center bg-primary text-white rounded-3 fw-bold flex-shrink-0"
                    style="width: 60px; height: 60px; font-size: 2rem; box-shadow: 0 0 30px rgba(41,151,255,0.3);">1</div>
                <div>
                    <h2 class="text-white mb-1"><?php echo t('acculturation_foundations'); ?></h2>
                    <p class="text-secondary mb-0"><?php echo t('understand_why'); ?></p>
                </div>
            </div>

            <!-- Curriculum Table -->
            <div class="table-responsive">
                <table class="table text-white mb-0"
                    style="--bs-table-bg: transparent; --bs-table-color: var(--text-secondary); border-color: var(--glass-border);">
                    <thead>
                        <tr class="text-uppercase small text-secondary border-bottom border-light border-opacity-10">
                            <th class="py-3 ps-0"><?php echo t('module'); ?></th>
                            <th class="py-3"><?php echo t('thematique'); ?></th>
                            <th class="py-3"><?php echo t('what_you_will_do'); ?></th>
                            <th class="py-3 text-end pe-0"><?php echo t('tools'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="align-middle border-bottom border-light border-opacity-10 stagger-row">
                            <td class="py-4 ps-0 fw-bold text-white">M1</td>
                            <td class="py-4 text-white"><?php echo t('m1_level1_theme'); ?></td>
                            <td class="py-4"><?php echo t('m1_level1_desc'); ?></td>
                            <td class="py-4 text-end pe-0"><span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25">N.A</span></td>
                        </tr>
                        <tr class="align-middle border-bottom border-light border-opacity-10 stagger-row">
                            <td class="py-4 ps-0 fw-bold text-white">M2</td>
                            <td class="py-4 text-white"><?php echo t('m2_level1_theme'); ?></td>
                            <td class="py-4"><?php echo t('m2_level1_desc'); ?></td>
                            <td class="py-4 text-end pe-0"><span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25">Notion</span></td>
                        </tr>
                        <tr class="align-middle border-bottom border-light border-opacity-10 stagger-row">
                            <td class="py-4 ps-0 fw-bold text-white">M3</td>
                            <td class="py-4 text-white"><?php echo t('m3_level1_theme'); ?></td>
                            <td class="py-4"><?php echo t('m3_level1_desc'); ?></td>
                            <td class="py-4 text-end pe-0"><span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25">ChatGPT</span></td>
                        </tr>
                        <tr class="align-middle stagger-row">
                            <td class="py-4 ps-0 fw-bold text-white">M4</td>
                            <td class="py-4 text-white"><?php echo t('m4_level1_theme'); ?></td>
                            <td class="py-4"><?php echo t('m4_level1_desc'); ?></td>
                            <td class="py-4 text-end pe-0"><span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25">N.A</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>


<!-- ============================================
     NIVEAU 2 - Detailed Curriculum
     ============================================ -->
<section id="niveau2" class="py-5">
    <div class="container">
        <div class="bento-card bento-card-glow p-4 p-md-5 scroll-scale" style="border-color: rgba(191,90,242,0.2);">
            <!-- Header -->
            <div class="d-flex align-items-center gap-4 mb-4">
                <div class="d-flex justify-content-center align-items-center bg-info text-white rounded-3 fw-bold flex-shrink-0"
                    style="width: 60px; height: 60px; font-size: 2rem; box-shadow: 0 0 30px rgba(191,90,242,0.3);">2</div>
                <div>
                    <h2 class="text-white mb-1"><?php echo t('level_2_title'); ?></h2>
                    <p class="text-secondary mb-0"><?php echo t('level_2_subtitle'); ?></p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table text-white mb-0"
                    style="--bs-table-bg: transparent; --bs-table-color: var(--text-secondary); border-color: var(--glass-border);">
                    <thead>
                        <tr class="text-uppercase small text-secondary border-bottom border-light border-opacity-10">
                            <th class="py-3 ps-0"><?php echo t('module'); ?></th>
                            <th class="py-3"><?php echo t('thematique'); ?></th>
                            <th class="py-3"><?php echo t('what_you_will_do'); ?></th>
                            <th class="py-3 text-end pe-0"><?php echo t('tools'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="align-middle border-bottom border-light border-opacity-10 stagger-row">
                            <td class="py-4 ps-0 fw-bold text-white">M1</td>
                            <td class="py-4 text-white"><?php echo t('m1_level2_theme'); ?></td>
                            <td class="py-4"><?php echo t('m1_level2_desc'); ?></td>
                            <td class="py-4 text-end pe-0"><span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25">N.A</span></td>
                        </tr>
                        <tr class="align-middle border-bottom border-light border-opacity-10 stagger-row">
                            <td class="py-4 ps-0 fw-bold text-white">M2</td>
                            <td class="py-4 text-white"><?php echo t('m2_level2_theme'); ?></td>
                            <td class="py-4"><?php echo t('m2_level2_desc'); ?></td>
                            <td class="py-4 text-end pe-0">
                                <span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25">Notion</span>
                                <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25">ChatGPT</span>
                            </td>
                        </tr>
                        <tr class="align-middle border-bottom border-light border-opacity-10 stagger-row">
                            <td class="py-4 ps-0 fw-bold text-white">M3</td>
                            <td class="py-4 text-white"><?php echo t('m3_level2_theme'); ?></td>
                            <td class="py-4"><?php echo t('m3_level2_desc'); ?></td>
                            <td class="py-4 text-end pe-0"><span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25">N.A</span></td>
                        </tr>
                        <tr class="align-middle stagger-row">
                            <td class="py-4 ps-0 fw-bold text-white">M4</td>
                            <td class="py-4 text-white"><?php echo t('m4_level2_theme'); ?></td>
                            <td class="py-4"><?php echo t('m4_level2_desc'); ?></td>
                            <td class="py-4 text-end pe-0"><span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-10">Logic</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>


<!-- ============================================
     NIVEAU 3 - Detailed Curriculum
     ============================================ -->
<section id="niveau3" class="py-5">
    <div class="container">
        <div class="bento-card bento-card-glow p-4 p-md-5 scroll-scale" style="border-color: rgba(245,158,11,0.2);">
            <!-- Header with gradient -->
            <div class="d-flex align-items-center gap-4 mb-4">
                <div class="d-flex justify-content-center align-items-center text-white rounded-3 fw-bold flex-shrink-0"
                    style="width: 60px; height: 60px; font-size: 2rem; background: linear-gradient(135deg, #f59e0b, #ef4444); box-shadow: 0 0 30px rgba(245,158,11,0.3);">
                    <?php echo t('level_3_number'); ?></div>
                <div>
                    <h2 class="text-white mb-1"><?php echo t('level_3_title'); ?></h2>
                    <p class="text-secondary mb-0"><?php echo t('level_3_subtitle'); ?></p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table text-white mb-0"
                    style="--bs-table-bg: transparent; --bs-table-color: var(--text-secondary); border-color: var(--glass-border);">
                    <thead>
                        <tr class="text-uppercase small text-secondary border-bottom border-light border-opacity-10">
                            <th class="py-3 ps-0"><?php echo t('module'); ?></th>
                            <th class="py-3"><?php echo t('thematique'); ?></th>
                            <th class="py-3"><?php echo t('what_you_will_do'); ?></th>
                            <th class="py-3 text-end pe-0"><?php echo t('tools'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="align-middle border-bottom border-light border-opacity-10 stagger-row">
                            <td class="py-4 ps-0 fw-bold text-white">M1</td>
                            <td class="py-4 text-white"><?php echo t('m1_level3_theme'); ?></td>
                            <td class="py-4"><?php echo t('m1_level3_desc'); ?></td>
                            <td class="py-4 text-end pe-0">
                                <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25">n8n</span>
                                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25">Make</span>
                            </td>
                        </tr>
                        <tr class="align-middle border-bottom border-light border-opacity-10 stagger-row">
                            <td class="py-4 ps-0 fw-bold text-white">M2</td>
                            <td class="py-4 text-white"><?php echo t('m2_level3_theme'); ?></td>
                            <td class="py-4"><?php echo t('m2_level3_desc'); ?></td>
                            <td class="py-4 text-end pe-0">
                                <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25">n8n</span>
                            </td>
                        </tr>
                        <tr class="align-middle border-bottom border-light border-opacity-10 stagger-row">
                            <td class="py-4 ps-0 fw-bold text-white">M3</td>
                            <td class="py-4 text-white"><?php echo t('m3_level3_theme'); ?></td>
                            <td class="py-4"><?php echo t('m3_level3_desc'); ?></td>
                            <td class="py-4 text-end pe-0">
                                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25">Make</span>
                            </td>
                        </tr>
                        <tr class="align-middle stagger-row">
                            <td class="py-4 ps-0 fw-bold text-white">M4</td>
                            <td class="py-4 text-white"><?php echo t('m4_level3_theme'); ?></td>
                            <td class="py-4"><?php echo t('m4_level3_desc'); ?></td>
                            <td class="py-4 text-end pe-0">
                                <span class="badge bg-secondary bg-opacity-25 text-white border border-secondary border-opacity-25">Synthèse</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>


<!-- ============================================
     VIP COACHING SECTION - Split Layout Premium
     ============================================ -->
<section id="formation" class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-4 mb-lg-0 scroll-reveal">
                <div class="section-badge">
                    <i class="fas fa-crown"></i> VIP
                </div>
                <h2 class="display-6 fw-bold mb-3"><?php echo t('vip_title_prefix'); ?><br><span
                        class="text-gradient-animated"><?php echo t('vip_coaching'); ?></span></h2>
                <p class="text-secondary mb-4"><?php echo t('vip_desc'); ?></p>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex gap-3">
                        <div class="icon-box rounded-circle bg-success bg-opacity-10 text-success border-success border-opacity-25 mb-0 flex-shrink-0"
                            style="width:40px; height:40px; font-size:1rem;">
                            <i class="fas fa-video"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1"><?php echo t('daily_visio'); ?></h5>
                            <p class="text-secondary small m-0"><?php echo t('daily_visio_desc'); ?></p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="icon-box rounded-circle bg-info bg-opacity-10 text-info border-info border-opacity-25 mb-0 flex-shrink-0"
                            style="width:40px; height:40px; font-size:1rem;">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1"><?php echo t('tech_help'); ?></h5>
                            <p class="text-secondary small m-0"><?php echo t('tech_help_desc'); ?></p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="icon-box rounded-circle bg-warning bg-opacity-10 text-warning border-warning border-opacity-25 mb-0 flex-shrink-0"
                            style="width:40px; height:40px; font-size:1rem;">
                            <i class="fas fa-road"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1"><?php echo t('custom_path'); ?></h5>
                            <p class="text-secondary small m-0"><?php echo t('custom_path_desc'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 offset-lg-1 scroll-reveal delay-200">
                <div class="bento-card bento-card-glow p-4 text-center position-relative overflow-hidden"
                    style="background: linear-gradient(135deg, rgba(191,90,242,0.08), rgba(112,0,255,0.05)); border-color: rgba(191,90,242,0.2);">
                    <!-- Glow orb -->
                    <div class="position-absolute" style="top:-80px;right:-80px;width:200px;height:200px;background:var(--accent-purple);filter:blur(100px);opacity:0.15;pointer-events:none;"></div>
                    
                    <div class="mb-3" style="position:relative;z-index:2;">
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3"
                            style="background: rgba(191,90,242,0.15); border: 1px solid rgba(191,90,242,0.3); color: var(--accent-purple); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                            <i class="fas fa-crown"></i> <?php echo t('intensive_mentoring'); ?>
                        </div>
                    </div>
                    <div style="position:relative;z-index:2;">
                        <i class="fas fa-rocket text-warning fs-1 mb-3 d-block"></i>
                        <h3 class="text-white mb-2"><?php echo t('intensive_mentoring'); ?></h3>
                        <p class="text-secondary mb-4 small"><?php echo t('intensive_mentoring_desc'); ?></p>
                        <div class="display-4 fw-bold text-white mb-0">60€<span
                                class="fs-5 text-secondary fw-normal">/session</span></div>
                        <p class="text-secondary small mb-4 fw-bold text-uppercase tracking-wider">
                            <?php echo t('one_hour_per_day'); ?></p>
                        <a href="/contact" class="btn-primary-glow w-100 justify-content-center" style="display:flex;">
                            <?php echo t('book_slot'); ?> <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ============================================
     REVIEWS SECTION (from Notion)
     ============================================ -->
<?php if (!empty($reviews)): ?>
<section class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <div class="section-badge scroll-reveal">
                <i class="fas fa-star"></i> <?php echo t('formation_reviews_badge'); ?>
            </div>
            <h2 class="section-title-lg scroll-reveal delay-100"><?php echo t('formation_reviews_title'); ?></h2>
        </div>
        <?php render_reviews_section($reviews); ?>
    </div>
</section>
<?php endif; ?>


<!-- ============================================
     FAQ SECTION
     ============================================ -->
<section class="pb-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <div class="section-badge scroll-reveal">
                        <i class="fas fa-question-circle"></i> FAQ
                    </div>
                    <h3 class="text-white scroll-reveal delay-100"><?php echo t('faq_title'); ?></h3>
                </div>
                <div class="accordion" id="faqAccordion">

                    <!-- Q1 -->
                    <div class="bento-card p-0 mb-3 overflow-hidden scroll-reveal delay-200">
                        <div class="p-0" id="headingOne">
                            <button
                                class="accordion-button collapsed bg-transparent text-white shadow-none p-4 w-100 d-flex justify-content-between align-items-center border-0"
                                type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne"
                                aria-expanded="false" aria-controls="collapseOne">
                                <span class="fw-bold"><?php echo t('faq_q1'); ?></span>
                                <i class="fas fa-chevron-down text-secondary transition-icon"></i>
                            </button>
                        </div>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne"
                            data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary p-4 pt-0 border-top border-secondary border-opacity-10">
                                <?php echo t('faq_a1'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Q2 -->
                    <div class="bento-card p-0 mb-3 overflow-hidden scroll-reveal delay-300">
                        <div class="p-0" id="headingTwo">
                            <button
                                class="accordion-button collapsed bg-transparent text-white shadow-none p-4 w-100 d-flex justify-content-between align-items-center border-0"
                                type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo"
                                aria-expanded="false" aria-controls="collapseTwo">
                                <span class="fw-bold"><?php echo t('faq_q2'); ?></span>
                                <i class="fas fa-chevron-down text-secondary transition-icon"></i>
                            </button>
                        </div>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                            data-bs-parent="#faqAccordion">
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


<!-- ============================================
     FINAL CTA SECTION
     ============================================ -->
<section class="final-cta">
    <div class="final-cta-bg"></div>
    <div class="container position-relative" style="z-index: 2;">
        <div class="section-badge mx-auto scroll-reveal">
            <i class="fas fa-rocket"></i> <?php echo t('cta_badge'); ?>
        </div>
        <h2 class="section-title-lg scroll-reveal delay-100"><?php echo t('cta_title'); ?></h2>
        <p class="final-cta-desc scroll-reveal delay-200"><?php echo t('cta_description'); ?></p>
        <div class="hero-cta-group scroll-reveal delay-300">
            <a href="/contact" class="btn-primary-glow">
                <?php echo t('book_slot'); ?> <i class="fas fa-arrow-right"></i>
            </a>
            <a href="/entreprises" class="btn-outline-glass">
                <i class="fas fa-building"></i> <?php echo t('companies'); ?>
            </a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>