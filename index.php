<?php

include 'includes/header.php';
include 'includes/components.php';
include 'api/notion-satisfaction.php';

// Si l'URL contient encore ?refresh_stats (anciennes bookmarks), rediriger vers l'URL propre
if (isset($_GET['refresh_stats'])) {
    // Eviter la propagation du paramètre dans la console et les outils de tracking
    $cleanUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $cleanUrl);
    exit;
}

// Récupérer les stats (forcer le refresh à chaque fois, pas de cache)
$stats = getSatisfactionStats(true);
?>

<!-- Hero Section -->
<section class="text-center position-relative" style="padding: 60px 0 100px;">
    <div class="container">
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
            <div class="bento-card span-8 d-flex flex-column justify-content-between scroll-reveal" style="min-height: 400px; background: linear-gradient(to bottom right, rgba(20,20,20,0.8), rgba(0,0,0,0.9));">
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
            <div class="bento-card span-4 d-flex flex-column justify-content-center align-items-center text-center position-relative scroll-reveal delay-200">
                <div class="position-absolute top-2 end-2">
                </div>
                <div class="position-relative mb-3">
                    <?php
$strokeOffset = $stats['pourcentage'] !== 'N.A' ? (339.292 - (339.292 * $stats['pourcentage'] / 100)) : 339.292;
?>
                    <svg width="120" height="120" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="8"/>
                        <circle cx="60" cy="60" r="54" fill="none" stroke="var(--accent-purple)" stroke-width="8" stroke-dasharray="339.292" stroke-dashoffset="<?php echo $strokeOffset; ?>" stroke-linecap="round" transform="rotate(-90 60 60)"/>
                    </svg>
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <span class="h2 fw-bold m-0 d-block text-white"><?php echo $stats['pourcentage'] !== 'N.A' ? $stats['pourcentage'] . '%' : 'N.A'; ?></span>
                    </div>
                </div>
                <h5 class="text-white"><?php echo t('satisfaction'); ?></h5>
                <p class="text-secondary small mb-2">
                    <?php
// Language-aware display for number of responses
$na_display = ($lang === 'en') ? 'N/A' : 'N.A';
if ($stats['nombre'] > 0) {
    $nombre = (int)$stats['nombre'];
    if ($lang === 'en') {
        $formatted = $nombre >= 500 ? '+' . number_format($nombre, 0, '.', ',') . ' learners trained' : 'Based on ' . number_format($nombre, 0, '.', ',') . ' of our trained customer' . ($nombre > 1 ? 's' : '');
    }
    else {
        $formatted = $nombre >= 500 ? '+' . number_format($nombre, 0, ',', ' ') . ' apprenant' . ($nombre > 1 ? 's' : '') . ' formés' : 'Basé sur ' . number_format($nombre, 0, ',', ' ') . ' de nos clients formé' . ($nombre > 1 ? 's' : '');
    }
    echo $formatted;
}
else {
    echo($lang === 'en') ? t('based_on_learners') : t('based_on_learners');
}
?>
                </p>
                <?php if (isset($stats['error']) && $stats['error']): ?>
                    <small class="text-warning d-block mt-2">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo isset($stats['message']) ? htmlspecialchars($stats['message']) : 'Erreur'; ?>
                    </small>
                <?php
endif; ?>
                <small class="text-secondary-50 mt-2 d-block opacity-50" style="font-size: 0.7rem;">
                    <i class="fas fa-clock"></i> <?php echo t('last_updated'); ?> <?php echo date('H:i'); ?>
                </small>
            </div>

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
if (!empty($reviews)):
?>
            <div class="bento-card span-12 scroll-reveal" style="padding:18px 24px;">
                <div class="d-flex align-items-center justify-content-end mb-3">
                     <div class="reviews-navigation">
                         <button id="prev-review" class="nav-btn" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
                         <button id="next-review" class="nav-btn" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
                     </div>
                </div>
                <div class="reviews-marquee">
                    <div class="reviews-inner">
                        <div class="reviews-track">
                        <?php
    function render_stars_html($note)
    {
        $note = floatval($note);
        if ($note <= 0)
            return '';
        $full = floor($note);
        $half = ($note - $full) >= 0.25 && ($note - $full) < 0.75 ? 1 : 0;
        if (($note - $full) >= 0.75) {
            $full += 1;
            $half = 0;
        }
        $empty = 5 - $full - $half;
        $out = '';
        for ($i = 0; $i < $full; $i++)
            $out .= '<i class="fas fa-star"></i>';
        if ($half)
            $out .= '<i class="fas fa-star-half-alt"></i>';
        for ($i = 0; $i < $empty; $i++)
            $out .= '<i class="far fa-star"></i>';
        return $out;
    }
    foreach ($reviews as $r) {
        $prenom = htmlspecialchars($r['prenom'] ?? '');
        $nom = htmlspecialchars($r['nom'] ?? '');
        $prof = htmlspecialchars($r['profession'] ?? '');
        $avis = htmlspecialchars($r['avis'] ?? '');
        $note = isset($r['note']) ? floatval($r['note']) : 0;
        $photo = $r['photo'] ?? null;
        $linkedin = $r['linkedin'] ?? '';
        $name = trim($prenom . ' ' . $nom);
        $initials = strtoupper(($prenom ? $prenom[0] : '') . ($nom ? $nom[0] : ''));
        $stars = render_stars_html($note);

        // Si pas d'avis, on n'affiche pas la carte du tout
        if (empty($avis)) {
            continue;
        }
?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="review-avatar"><?php if ($photo): ?><img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo $name; ?>"/><?php
        else:
            echo $initials;
        endif; ?></div>
                                    <div class="review-info">
                                        <?php if (!empty($linkedin)): ?>
                                            <a href="<?php echo htmlspecialchars($linkedin); ?>" target="_blank" rel="noopener noreferrer" class="text-white text-decoration-none hover-underline">
                                                <strong><?php echo $name; ?></strong>
                                            </a>
                                        <?php
        else: ?>
                                            <strong><?php echo $name; ?></strong>
                                        <?php
        endif; ?>
                                        
                                        <?php
        $displayProf = $prof;
        if (isset($r['status']) && $r['status'] === 'Entreprise' && !empty($r['entreprise'])) {
            $displayProf .= ' <span class="company-name">chez ' . htmlspecialchars($r['entreprise']) . '</span>';
        }
        if (!empty($displayProf)): ?>
                                        <div class="profession"><?php echo $displayProf; ?></div>
                                        <?php
        endif; ?>
                                    </div>
                                </div>
                                <div class="review-content-scroll">
                                    <p class="review-text"><?php echo $avis; ?></p>
                                </div>
                                <div class="review-stars"><?php echo $stars; ?></div>
                            </div>
                            <?php
    }
?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
endif; ?>

            <!-- Bottom Row Cards - 3 Parcours -->
            <!-- Bottom Row Cards - 3 Parcours -->
            <?php
render_feature_card(
    'span-4 scroll-reveal',
    'fas fa-seedling',
    'text-primary',
    'background: rgba(41, 151, 255, 0.1);',
    'level_1_foundations',
    'discover_ai',
['url' => 'formation#niveau1', 'text_key' => 'learn_more']
);
?>

            <?php
render_feature_card(
    'span-4 scroll-reveal delay-200',
    'fas fa-rocket',
    'text-info',
    'background: rgba(13, 202, 240, 0.1);',
    'level_2_expert',
    'advanced_connections',
['url' => 'formation#niveau2', 'text_key' => 'learn_more']
);
?>

            <div class="bento-card span-4 d-flex flex-column justify-content-between scroll-reveal delay-400" style="background: linear-gradient(135deg, var(--accent-purple), #7000ff);">
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

<!-- "OS" Style Section - Outils enseignés -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
                <div class="col-lg-5 mb-5 mb-lg-0">
                <h2 class="display-6 fw-bold mb-4"><?php echo t('ecosystem_title'); ?></h2>
                <div class="d-flex flex-column gap-4 scroll-reveal">
                    <div class="d-flex gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: rgba(255,255,255,0.1);">
                            <i class="fas fa-check text-success"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1"><?php echo t('progressive_training'); ?></h5>
                            <p class="text-secondary small m-0"><?php echo t('progressive_training_desc'); ?></p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: rgba(255,255,255,0.1);">
                            <i class="fas fa-check text-success"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1"><?php echo t('immediate_practice'); ?></h5>
                            <p class="text-secondary small m-0"><?php echo t('immediate_practice_desc'); ?></p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: rgba(255,255,255,0.1);">
                            <i class="fas fa-check text-success"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-1"><?php echo t('continuous_support'); ?></h5>
                            <p class="text-secondary small m-0"><?php echo t('continuous_support_desc'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Graphic Element representing App Grid - Outils -->
            <div class="col-lg-7 scroll-reveal delay-200">
                <div class="position-relative">
                    <!-- Glow behind -->
                    <div class="position-absolute top-50 start-50 translate-middle" style="width: 300px; height: 300px; background: var(--accent-blue); filter: blur(100px); opacity: 0.2;"></div>
                    
                    <div class="row g-3">
                        <!-- App Icon 1 -->
                        <div class="col-4">
                            <div class="bento-card p-3 text-center h-100 border-0" style="background: rgba(255,255,255,0.03);">
                                <i class="fas fa-robot fs-1 text-white mb-3 d-block"></i>
                                <h6 class="text-white small m-0"><?php echo t('tool_chatbot'); ?></h6>
                            </div>
                        </div>
                         <!-- App Icon 2 -->
                         <div class="col-4">
                            <div class="bento-card p-3 text-center h-100 border-0" style="background: rgba(255,255,255,0.03);">
                                <i class="fas fa-code-branch fs-1 text-white mb-3 d-block"></i>
                                <h6 class="text-white small m-0"><?php echo t('tool_automation'); ?></h6>
                            </div>
                        </div>
                        <!-- App Icon 3 (Logique) -->
                         <div class="col-4">
                            <div class="bento-card p-3 text-center h-100 border-0" style="background: rgba(255,255,255,0.03);">
                                <i class="fas fa-sitemap fs-1 text-white mb-3 d-block"></i>
                                <h6 class="text-white small m-0"><?php echo t('tool_logic'); ?></h6>
                            </div>
                        </div>
                        <!-- App Icon 4 -->
                        <div class="col-4">
                            <div class="bento-card p-3 text-center h-100 border-0" style="background: rgba(255,255,255,0.03);">
                                <i class="fas fa-database fs-1 text-white mb-3 d-block"></i>
                                <h6 class="text-white small m-0"><?php echo t('tool_database'); ?></h6>
                            </div>
                        </div>
                         <!-- App Icon 5 -->
                         <div class="col-4">
                            <div class="bento-card p-3 text-center h-100 border-0" style="background: rgba(255,255,255,0.03);">
                                <i class="fas fa-code fs-1 text-white mb-3 d-block"></i>
                                <h6 class="text-white small m-0"><?php echo t('tool_code'); ?></h6>
                            </div>
                        </div>
                         <!-- App Icon 6 -->
                         <div class="col-4">
                            <div class="bento-card p-3 text-center h-100 border-0" style="background: rgba(255,255,255,0.03);">
                                <i class="fas fa-plug fs-1 text-white mb-3 d-block"></i>
                                <h6 class="text-white small m-0"><?php echo t('tool_api'); ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="assets/js/carousel.js"></script>
<script src="assets/js/typewriter.js"></script>
<?php include 'includes/footer.php'; ?>
