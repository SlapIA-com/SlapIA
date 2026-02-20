<?php
include_once 'includes/config.php';
include_once 'includes/lang.php';
$page_title = t('meta_title');
$page_description = t('meta_description');
$page_image = '/assets/img/logo.png';
include 'includes/header.php';
include 'includes/components.php';
include_once 'api/notion-satisfaction.php';

// Redirection si URL avec anciens paramètres
if (isset($_GET['refresh_stats'])) {
    $cleanUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $cleanUrl);
    exit;
}

// Appels Notion en parallèle (curl_multi) — ~50% plus rapide
$notionData = fetchBothNotionData($lang ?? 'fr', 20);
$stats = $notionData['stats'];
$reviews = $notionData['reviews'];
?>

<!-- Hero Section -->
<section class="text-center position-relative py-5">
    <div class="container" style="padding-top: 20px; padding-bottom: 60px;">
        <!-- Floating Badge -->
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill border border-secondary border-opacity-25 mb-4 fade-in-up"
             style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
            <span class="badge bg-primary rounded-pill" style="font-size: 0.7rem;"><?php echo t('3_courses'); ?></span>
            <span class="text-secondary small fw-medium"><?php echo t('complete_training'); ?></span>
        </div>

        <h1 class="display-title mb-4 fade-in-up delay-200">
            <?php echo t('master_ai'); ?> <br>
            <span class="text-gradient-purple" id="typewriter-text" data-phrases='["<?php echo t('typewriter_1'); ?>", "<?php echo t('typewriter_2'); ?>", "<?php echo t('typewriter_3'); ?>"]'>
                <?php echo t('typewriter_1'); ?>
            </span>
            <span class="cursor-blink">|</span>
        </h1>

        <p class="text-secondary mx-auto mb-5 fade-in-up delay-400" style="max-width: 600px; font-size: 1.25rem; line-height: 1.6;">
            <?php echo t('structured_paths'); ?>
        </p>

        <div class="d-flex justify-content-center gap-3 fade-in-up delay-600">
            <a href="formation" class="btn-apple">
                <?php echo t('discover_trainings'); ?> <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Main Bento Grid Interface -->
<section class="pb-5">
    <div class="container fade-in-up delay-600">
        <div class="bento-grid">

            <!-- Main Feature Card - Formation IA (Large) -->
            <div class="bento-card span-8 d-flex flex-column justify-content-between scroll-scale" style="min-height: 400px; background: linear-gradient(to bottom right, rgba(20,20,20,0.8), rgba(0,0,0,0.9));">
                <div>
                    <div class="icon-box text-white border-0" style="background: var(--accent-blue);">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3 class="text-white mb-2"><?php echo t('ai_training'); ?></h3>
                    <p class="text-secondary" style="max-width: 400px;"><?php echo t('learn_ai_tools'); ?></p>
                </div>
                <!-- Abstract visual element mimicking an interface -->
                <div class="mt-4 rounded-3 border border-secondary border-opacity-10 p-3" style="background: rgba(0,0,0,0.5);">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle bg-success" style="width: 8px; height: 8px;"></div>
                        <span class="text-secondary small"><?php echo t('3_levels_available'); ?></span>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="h-1 rounded bg-secondary opacity-25 flex-grow-1"></div>
                        <div class="h-1 rounded bg-secondary opacity-25 w-25"></div>
                    </div>
                    <div class="h-1 rounded bg-secondary opacity-25 w-50 mt-2"></div>
                </div>
            </div>

            <!-- Side Card (Stats) -->
            <?php render_satisfaction_card($stats, $lang); ?>

            <!-- Testimonials / Avis déroulants (from Notion) -->
            <?php
$reviews = [];
if (function_exists('getNotionReviews')) {
    try {
        $reviews = getNotionReviews(12, $lang);
    }
    catch (Exception $e) {
        $reviews = [];
    }
}
render_reviews_section($reviews);
?>

            <!-- Bottom Row Cards - 3 Parcours -->
            <?php
render_feature_card(
    'span-4 scroll-scale',
    'fas fa-seedling',
    'text-primary',
    'background: rgba(41, 151, 255, 0.1);',
    'level_1_foundations',
    'discover_ai',
['url' => 'formation#niveau1', 'text_key' => 'learn_more']
);

render_feature_card(
    'span-4 scroll-scale delay-100',
    'fas fa-rocket',
    'text-info',
    'background: rgba(13, 202, 240, 0.1);',
    'level_2_expert',
    'advanced_connections',
['url' => 'formation#niveau2', 'text_key' => 'learn_more']
);
?>

            <div class="bento-card span-4 d-flex flex-column justify-content-between scroll-scale delay-200" style="background: linear-gradient(135deg, var(--accent-purple), #7000ff);">
                <div>
                    <div class="icon-box text-white border-0 mb-3" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h5 class="text-white mb-1"><?php echo t('personal_coaching'); ?></h5>
                    <p class="text-white opacity-75 small mb-3"><?php echo t('vip_mentoring'); ?></p>
                </div>
                <a href="formation#formation" class="text-white text-decoration-none small fw-bold">
                    <?php echo t('book_slot'); ?> <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>

        </div>
    </div>
</section>

<!-- "OS" Style Section - Outils et Ecosystème -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            
            <!-- Features List -->
            <div class="col-lg-5 mb-5 mb-lg-0">
                <h2 class="display-6 fw-bold mb-4"><?php echo t('ecosystem_title'); ?></h2>
                <div class="d-flex flex-column gap-4 scroll-reveal">
                    <?php
$features = [
    ['title' => 'progressive_training', 'desc' => 'progressive_training_desc'],
    ['title' => 'immediate_practice', 'desc' => 'immediate_practice_desc'],
    ['title' => 'continuous_support', 'desc' => 'continuous_support_desc']
];

foreach ($features as $feature):
?>
                    <div class="d-flex gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: rgba(255,255,255,0.1);">
                            <i class="fas fa-check text-success"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1"><?php echo t($feature['title']); ?></h5>
                            <p class="text-secondary small m-0"><?php echo t($feature['desc']); ?></p>
                        </div>
                    </div>
                    <?php
endforeach; ?>
                </div>
            </div>
            
            <!-- Tools Grid (App Icons) -->
            <div class="col-lg-7">
                <div class="position-relative">
                    <!-- Glow behind -->
                    <div class="position-absolute top-50 start-50 translate-middle" style="width: 300px; height: 300px; background: var(--accent-blue); filter: blur(100px); opacity: 0.2;"></div>
                    
                    <div class="row g-3">
                        <?php
$tools = [
    ['icon' => 'fab fa-python', 'label' => 'tool_chatbot', 'delay' => ''],
    ['icon' => 'fas fa-project-diagram', 'label' => 'tool_automation', 'delay' => 'delay-100'],
    ['icon' => 'fas fa-server', 'label' => 'tool_logic', 'delay' => 'delay-200'],
    ['icon' => 'fas fa-database', 'label' => 'tool_database', 'delay' => 'delay-300'],
    ['icon' => 'fas fa-laptop-code', 'label' => 'tool_code', 'delay' => 'delay-400'],
    ['icon' => 'fas fa-network-wired', 'label' => 'tool_api', 'delay' => 'delay-500'],
];

foreach ($tools as $tool):
?>
                        <div class="col-4">
                            <div class="bento-card p-3 text-center h-100 border-0 scroll-reveal <?php echo $tool['delay']; ?>" style="background: rgba(255,255,255,0.03);">
                                <i class="<?php echo $tool['icon']; ?> fs-1 text-white mb-3 d-block"></i>
                                <h6 class="text-white small m-0"><?php echo t($tool['label']); ?></h6>
                            </div>
                        </div>
                        <?php
endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
