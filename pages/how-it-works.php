<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

$page_title = t('how_it_works') . " - SlapIA";
$page_description = t('tech_stack_desc');
$page_image = '/assets/img/logo.png';
include '../includes/header.php'; ?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "Comment fonctionne SlapIA — Stack Technique",
  "description": "Découvrez la stack technique de SlapIA : PHP, Notion comme CMS, n8n pour l'automatisation IA, et les agents autonomes.",
  "url": "https://www.slapia.com/how-it-works",
  "step": [
    {
      "@type": "HowToStep",
      "name": "Stack Technique PHP & VS Code",
      "text": "Le site est construit avec PHP vanilla, structuré et déployé via VS Code et Git."
    },
    {
      "@type": "HowToStep",
      "name": "Notion comme CMS headless",
      "text": "Les avis clients et données de satisfaction sont stockés dans Notion et récupérés via l'API Notion en temps réel."
    },
    {
      "@type": "HowToStep",
      "name": "Agent IA Autonome n8n",
      "text": "Un agent IA (Gemini Pro) orchestre les automatisations via n8n : qualification des prospects, réponses automatiques et synchronisation CRM."
    },
    {
      "@type": "HowToStep",
      "name": "Gestion des Emails Automatiques",
      "text": "Chaque interaction client déclenche un flux d'emails intelligents automatisés via n8n : notification, bienvenue, demande d'avis et remerciement."
    }
  ]
}
</script>

<?php include '../includes/components.php'; ?>



<section class="py-5 mt-5">
    <div class="container">
        
        <!-- Header -->
        <div class="row mb-5 text-center scroll-reveal">
            <div class="col-lg-8 mx-auto">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill border border-secondary border-opacity-25 mb-4 shimmer-badge" 
                     style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
                    <span class="badge bg-info rounded-pill" style="font-size: 0.7rem;"><?php echo t('tech_badge'); ?></span>
                    <span class="text-secondary small fw-medium"><?php echo t('built_with_passion'); ?></span>
                </div>
                <h1 class="display-title mb-3"><?php echo t('how_it_works'); ?></h1>
                <p class="text-secondary lead"><?php echo t('tech_stack_desc'); ?></p>
            </div>
        </div>

        <!-- ========== STEP 1 & 2: Side by side ========== -->
        <div class="bento-grid">
            
            <!-- Card 1: VS Code / Architecture -->
            <div class="bento-card span-6 p-4 p-md-5 scroll-scale" style="position: relative;">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold" 
                         style="width: 36px; height: 36px; background: linear-gradient(135deg, #007ACC, #005A9E); color: white; font-size: 0.85rem; flex-shrink: 0;">
                        1
                    </div>
                    <span class="text-secondary small text-uppercase fw-semibold" style="letter-spacing: 0.1em;"><?php echo t('hiw_step'); ?> 1</span>
                </div>
                <div class="icon-box text-white mb-4" style="background: #007ACC;">
                    <i class="fas fa-code"></i>
                </div>
                <h3 class="text-white mb-3"><?php echo t('tech_stack_title'); ?></h3>
                <p class="text-secondary mb-4"><?php echo t('vscode_desc'); ?></p>
                <div class="rounded-3 overflow-hidden border border-secondary border-opacity-10 mt-3" style="min-height: 200px; background: #1e1e1e;">
                    <img src="/assets/img/vscode-stack.png" alt="VS Code Architecture" class="img-fluid w-100 h-100 object-fit-cover" loading="lazy">
                </div>
            </div>

            <!-- Card 2: Notion CMS -->
            <div class="bento-card span-6 p-4 p-md-5 scroll-scale delay-200" style="position: relative;">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold" 
                         style="width: 36px; height: 36px; background: linear-gradient(135deg, #ffffff, #d4d4d4); color: black; font-size: 0.85rem; flex-shrink: 0;">
                        2
                    </div>
                    <span class="text-secondary small text-uppercase fw-semibold" style="letter-spacing: 0.1em;"><?php echo t('hiw_step'); ?> 2</span>
                </div>
                <div class="icon-box text-black mb-4" style="background: #ffffff;">
                    <i class="fas fa-database"></i>
                </div>
                <h3 class="text-white mb-3"><?php echo t('notion_cms_title'); ?></h3>
                <p class="text-secondary mb-4"><?php echo t('notion_cms_desc'); ?></p>
                <div class="rounded-3 overflow-hidden border border-secondary border-opacity-10 mt-3" style="min-height: 200px; background: #191919;">
                     <img src="/assets/img/notion-cms.png" alt="Notion Database" class="img-fluid w-100 h-100 object-fit-cover" loading="lazy">
                </div>
            </div>
        </div>

        <!-- Transition 1 -->
        <div class="text-center py-5 scroll-reveal">
            <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                <div style="height: 1px; width: 60px; background: linear-gradient(to right, transparent, rgba(255,255,255,0.2));"></div>
                <i class="fas fa-chevron-down text-secondary" style="opacity: 0.4; animation: floatDown 2s ease-in-out infinite;"></i>
                <div style="height: 1px; width: 60px; background: linear-gradient(to left, transparent, rgba(255,255,255,0.2));"></div>
            </div>
            <p class="text-secondary fst-italic mb-0" style="font-size: 1.1rem;"><?php echo t('hiw_transition_2'); ?></p>
        </div>

        <!-- ========== STEP 3: AI Agent (full width, text left / image right) ========== -->
        <div class="bento-grid">
            <div class="bento-card span-12 p-4 p-md-5 scroll-scale" style="position: relative;">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold" 
                                 style="width: 36px; height: 36px; background: linear-gradient(135deg, #EA4B71, #c4224e); color: white; font-size: 0.85rem; flex-shrink: 0;">
                                3
                            </div>
                            <span class="text-secondary small text-uppercase fw-semibold" style="letter-spacing: 0.1em;"><?php echo t('hiw_step'); ?> 3</span>
                        </div>
                        <div class="icon-box text-white mb-4" style="background: #EA4B71;">
                            <i class="fas fa-robot"></i>
                        </div>
                        <h3 class="text-white mb-3"><?php echo t('ai_agent_title'); ?></h3>
                        <p class="text-secondary mb-4"><?php echo t('ai_agent_desc'); ?></p>
                        <ul class="list-unstyled text-secondary mb-0">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <?php echo t('tech_list_1'); ?></li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <?php echo t('tech_list_2'); ?></li>
                            <li class="mb-0"><i class="fas fa-check text-success me-2"></i> <?php echo t('tech_list_3'); ?></li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <div class="rounded-3 overflow-hidden border border-secondary border-opacity-10" style="min-height: 250px; background: #222;">
                            <img src="/assets/img/n8n-agent.png" alt="n8n Workflow" class="img-fluid w-100 h-100 object-fit-cover" loading="lazy">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transition 2 -->
        <div class="text-center py-5 scroll-reveal">
            <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                <div style="height: 1px; width: 60px; background: linear-gradient(to right, transparent, rgba(255,255,255,0.2));"></div>
                <i class="fas fa-chevron-down text-secondary" style="opacity: 0.4; animation: floatDown 2s ease-in-out infinite;"></i>
                <div style="height: 1px; width: 60px; background: linear-gradient(to left, transparent, rgba(255,255,255,0.2));"></div>
            </div>
            <p class="text-secondary fst-italic mb-0" style="font-size: 1.1rem;"><?php echo t('hiw_transition_3'); ?></p>
        </div>

        <!-- ========== STEP 4: Email Automation (full width, image left / text right) ========== -->
        <div class="bento-grid">
            <div class="bento-card span-12 p-4 p-md-5 scroll-scale" style="position: relative;">
                <div class="row align-items-center flex-lg-row-reverse">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold" 
                                 style="width: 36px; height: 36px; background: linear-gradient(135deg, #10B981, #059669); color: white; font-size: 0.85rem; flex-shrink: 0;">
                                4
                            </div>
                            <span class="text-secondary small text-uppercase fw-semibold" style="letter-spacing: 0.1em;"><?php echo t('hiw_step'); ?> 4</span>
                        </div>
                        <div class="icon-box text-white mb-4" style="background: #10B981;">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                        <h3 class="text-white mb-3"><?php echo t('auto_email_title'); ?></h3>
                        <p class="text-secondary mb-4"><?php echo t('auto_email_desc'); ?></p>
                        <ul class="list-unstyled text-secondary mb-0">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <?php echo t('auto_email_step1'); ?></li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <?php echo t('auto_email_step2'); ?></li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> <?php echo t('auto_email_step3'); ?></li>
                            <li class="mb-0"><i class="fas fa-check text-success me-2"></i> <?php echo t('auto_email_step4'); ?></li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <div class="rounded-3 overflow-hidden border border-secondary border-opacity-10" style="min-height: 250px; background: #222;">
                            <img src="/assets/img/n8n-agent2.png" alt="n8n Email Automation Workflow" class="img-fluid w-100 h-100 object-fit-cover" loading="lazy">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transition 3 -->
        <div class="text-center py-5 scroll-reveal">
            <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                <div style="height: 1px; width: 60px; background: linear-gradient(to right, transparent, rgba(255,255,255,0.2));"></div>
                <i class="fas fa-chevron-down text-secondary" style="opacity: 0.4; animation: floatDown 2s ease-in-out infinite;"></i>
                <div style="height: 1px; width: 60px; background: linear-gradient(to left, transparent, rgba(255,255,255,0.2));"></div>
            </div>
            <p class="text-secondary fst-italic mb-0" style="font-size: 1.1rem;"><?php echo t('hiw_transition_4'); ?></p>
        </div>

        <!-- ========== STEP 5: Reviews System ========== -->
        <div class="bento-grid">
            <div class="bento-card span-12 p-4 p-md-5 scroll-scale" style="position: relative;">
                <div class="row align-items-center">
                    <div class="col-lg-7">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold" 
                                 style="width: 36px; height: 36px; background: linear-gradient(135deg, #F59E0B, #D97706); color: white; font-size: 0.85rem; flex-shrink: 0;">
                                5
                            </div>
                            <span class="text-secondary small text-uppercase fw-semibold" style="letter-spacing: 0.1em;"><?php echo t('hiw_step'); ?> 5</span>
                        </div>
                         <div class="icon-box text-white mb-4" style="background: #F59E0B;">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="text-white mb-3"><?php echo t('reviews_system_title'); ?></h3>
                        <p class="text-secondary mb-0"><?php echo t('reviews_system_desc'); ?></p>
                    </div>
                     <div class="col-lg-5 text-center mt-4 mt-lg-0">
                         <div class="p-4 rounded-3 border border-secondary border-opacity-10" style="background: rgba(255,255,255,0.03);">
                            <div class="display-4 fw-bold text-white mb-2">100%</div>
                            <p class="text-secondary small mb-3"><?php echo t('verified_by_notion'); ?></p>
                            <div class="d-flex justify-content-center gap-1 text-warning fs-5">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== CTA Section ========== -->
        <div class="text-center py-5 mt-4 scroll-reveal">
            <div class="p-5 rounded-4 border border-secondary border-opacity-10" 
                 style="background: linear-gradient(135deg, rgba(41,151,255,0.08), rgba(191,90,242,0.08)); backdrop-filter: blur(20px);">
                <i class="fas fa-rocket fs-1 mb-3" style="background: linear-gradient(135deg, #2997ff, #bf5af2); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;"></i>
                <h3 class="text-white mb-3"><?php echo t('hiw_cta_text'); ?></h3>
                <a href="/contact" class="btn-apple mt-2">
                    <?php echo t('get_in_touch_btn'); ?> <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Matrix Secret Hint -->
        <div class="bento-grid mt-3">
            <div class="bento-card span-12 p-4 text-center scroll-scale" style="background: rgba(0, 255, 0, 0.05); border-color: rgba(0, 255, 0, 0.2);">
                <i class="fas fa-user-secret fs-1 text-success mb-3"></i>
                <h4 class="text-success mb-2"><?php echo t('matrix_secret_title'); ?></h4>
                <p class="text-secondary mx-auto mb-0" style="max-width: 600px;">
                    <?php echo t('matrix_hint'); ?>
                </p>
            </div>
        </div>

    </div>
</section>

<style>
@keyframes floatDown {
    0%, 100% { transform: translateY(-3px); opacity: 0.3; }
    50% { transform: translateY(3px); opacity: 0.7; }
}
</style>

<?php include '../includes/footer.php'; ?>
