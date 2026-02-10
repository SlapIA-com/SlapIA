<?php include '../includes/header.php'; ?>
<?php include '../includes/components.php'; ?>

<section class="py-5 mt-5">
    <div class="container">
        
        <!-- Header -->
        <div class="row mb-5 text-center">
            <div class="col-lg-8 mx-auto">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill border border-secondary border-opacity-25 mb-4" 
                     style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
                    <span class="badge bg-info rounded-pill" style="font-size: 0.7rem;"><?php echo t('tech_badge'); ?></span>
                    <span class="text-secondary small fw-medium"><?php echo t('built_with_passion'); ?></span>
                </div>
                <h1 class="display-title mb-3"><?php echo t('how_it_works'); ?></h1>
                <p class="text-secondary lead"><?php echo t('tech_stack_desc'); ?></p>
            </div>
        </div>

        <div class="bento-grid">
            
            <!-- Card 1: VS Code / Architecture -->
            <div class="bento-card span-6 p-4 p-md-5">
                <div class="icon-box text-white mb-4" style="background: #007ACC;">
                    <i class="fas fa-code"></i>
                </div>
                <h3 class="text-white mb-3"><?php echo t('tech_stack_title'); ?></h3>
                <p class="text-secondary mb-4"><?php echo t('vscode_desc'); ?></p>
                <!-- VS Code Image -->
                <div class="rounded-3 overflow-hidden border border-secondary border-opacity-10 mt-3" style="min-height: 200px; background: #1e1e1e;">
                    <img src="/assets/img/vscode-stack.png" alt="VS Code Architecture" class="img-fluid w-100 h-100 object-fit-cover" loading="lazy">
                </div>
            </div>

            <!-- Card 2: Notion CMS -->
            <div class="bento-card span-6 p-4 p-md-5">
                <div class="icon-box text-black mb-4" style="background: #ffffff;">
                    <i class="fas fa-database"></i>
                </div>
                <h3 class="text-white mb-3"><?php echo t('notion_cms_title'); ?></h3>
                <p class="text-secondary mb-4"><?php echo t('notion_cms_desc'); ?></p>
                <!-- Notion Image -->
                <div class="rounded-3 overflow-hidden border border-secondary border-opacity-10 mt-3" style="min-height: 200px; background: #191919;">
                     <img src="/assets/img/notion-cms.png" alt="Notion Database" class="img-fluid w-100 h-100 object-fit-cover" loading="lazy">
                </div>
            </div>

            <!-- Card 3: AI Agent n8n -->
            <div class="bento-card span-12 p-4 p-md-5">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4 mb-lg-0">
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
                        <!-- n8n Image -->
                        <div class="rounded-3 overflow-hidden border border-secondary border-opacity-10" style="min-height: 250px; background: #222;">
                            <img src="/assets/img/n8n-agent.png" alt="n8n Workflow" class="img-fluid w-100 h-100 object-fit-cover" loading="lazy">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Reviews System -->
             <div class="bento-card span-12 p-4 p-md-5">
                <div class="row align-items-center">
                    <div class="col-lg-7">
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

            <!-- Matrix Secret Hint -->
            <div class="bento-card span-12 p-4 text-center mt-4" style="background: rgba(0, 255, 0, 0.05); border-color: rgba(0, 255, 0, 0.2);">
                <i class="fas fa-user-secret fs-1 text-success mb-3"></i>
                <h4 class="text-success mb-2"><?php echo t('matrix_secret_title'); ?></h4>
                <p class="text-secondary mx-auto mb-0" style="max-width: 600px;">
                    <?php echo t('matrix_hint'); ?>
                </p>
            </div>

        </div>

    </div>
</section>

<?php include '../includes/footer.php'; ?>
