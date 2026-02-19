<?php
/**
 * Component Helper Functions
 * 
 * Functions to generate standard UI components like Bento cards to reduce HTML duplication.
 */

// Basic Bento Card
function render_bento_card($classes, $content, $styles = '')
{
    // If $classes is an array, join it
    if (is_array($classes)) {
        $classes = implode(' ', $classes);
    }

    echo '<div class="bento-card ' . htmlspecialchars($classes) . '" style="' . htmlspecialchars($styles) . '">';
    echo $content;
    echo '</div>';
}

// Icon Box Component
function render_icon_box($iconClass, $colorClass = 'text-white', $bgStyle = '', $extraBoxClasses = '')
{
    echo '<div class="icon-box ' . htmlspecialchars($extraBoxClasses) . ' ' . htmlspecialchars($colorClass) . ' mb-3" style="' . htmlspecialchars($bgStyle) . '">';
    echo '<i class="' . htmlspecialchars($iconClass) . '"></i>';
    echo '</div>';
}

// Standard Feature Card (Icon + Title + Text)
function render_feature_card($spanClass, $iconClass, $iconColorClass, $iconBgStyle, $titleKey, $descKey, $link = null)
{
    ob_start();
?>
    <div class="h-100 text-center">
        <?php render_icon_box($iconClass, $iconColorClass, $iconBgStyle, 'mx-auto'); ?>
        <h4 class="text-white"><?php echo t($titleKey); ?></h4>
        <p class="text-secondary small"><?php echo t($descKey); ?></p>
        <?php if ($link): ?>
            <a href="<?php echo htmlspecialchars($link['url']); ?>" class="text-primary small text-decoration-none">
                <?php echo t($link['text_key']); ?> <i class="fas fa-arrow-right ms-1"></i>
            </a>
        <?php
    endif; ?>
    </div>
    <?php
    $content = ob_get_clean();
    render_bento_card($spanClass . ' p-4', $content, 'height: 100%;');
}

/**
 * Render a simple B2B Offer Card
 */
function render_b2b_card($iconClass, $iconColorClass, $iconBgStyle, $titleKey, $descKey)
{
    ob_start();
?>
    <div class="h-100 text-center">
        <?php render_icon_box($iconClass, $iconColorClass, $iconBgStyle, 'mx-auto'); ?>
        <h4 class="text-white"><?php echo t($titleKey); ?></h4>
        <p class="text-secondary small"><?php echo t($descKey); ?></p>
    </div>
    <?php
    $content = ob_get_clean();
    render_bento_card('p-4 h-100', $content);
}

/**
 * Render Project Card
 */
function render_project_card($title, $client, $desc, $badges, $iconColorStart, $iconColorEnd, $iconClass)
{
    ob_start();
?>
    <div class="d-flex align-items-center gap-3 mb-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $iconColorStart; ?> 0%, <?php echo $iconColorEnd; ?> 100%);">
            <i class="<?php echo $iconClass; ?> text-white"></i>
        </div>
        <div>
            <h5 class="text-white mb-0"><?php echo htmlspecialchars($title); ?></h5>
            <span class="text-secondary small"><?php echo htmlspecialchars($client); ?></span>
        </div>
    </div>
    <p class="text-secondary"><?php echo htmlspecialchars($desc); ?></p>
    <div class="d-flex gap-2 flex-wrap">
        <?php foreach ($badges as $badge): ?>
            <span class="badge <?php echo $badge['class']; ?>"><?php echo htmlspecialchars($badge['text']); ?></span>
        <?php
    endforeach; ?>
    </div>
    <?php
    $content = ob_get_clean();
    render_bento_card('p-4 h-100', $content);
}

/**
 * Render Star Rating HTML
 */
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
/**
 * Renders the Satisfaction Stats Card
 */
function render_satisfaction_card($stats, $lang = 'fr')
{
    $percent = $stats['pourcentage'];
    $strokeOffset = $percent !== 'N.A' ? (339.292 - (339.292 * $percent / 100)) : 339.292;
?>
    <div class="bento-card span-4 d-flex flex-column justify-content-center align-items-center text-center position-relative scroll-scale delay-100">
        <div class="position-relative mb-3">
            <svg width="120" height="120" viewBox="0 0 120 120">
                <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="8"/>
                <circle cx="60" cy="60" r="54" fill="none" stroke="var(--accent-purple)" stroke-width="8" stroke-dasharray="339.292" stroke-dashoffset="<?php echo $strokeOffset; ?>" stroke-linecap="round" transform="rotate(-90 60 60)"/>
            </svg>
            <div class="position-absolute top-50 start-50 translate-middle">
                <span class="h2 fw-bold m-0 d-block text-white"><?php echo $percent !== 'N.A' ? $percent . '%' : 'N.A'; ?></span>
            </div>
        </div>
        <h5 class="text-white"><?php echo t('satisfaction'); ?></h5>
        <p class="text-secondary small mb-2">
            <?php
    if ($stats['nombre'] > 0) {
        $nombre = (int)$stats['nombre'];
        if ($lang === 'en') {
            echo $nombre >= 500 ? '+' . number_format($nombre, 0, '.', ',') . ' learners trained' : 'Based on ' . number_format($nombre, 0, '.', ',') . ' reviews';
        }
        else {
            echo $nombre >= 500 ? '+' . number_format($nombre, 0, ',', ' ') . ' apprenants formés' : 'Basé sur ' . number_format($nombre, 0, ',', ' ') . ' avis';
        }
    }
    else {
        echo t('based_on_learners');
    }
?>
        </p>
        <small class="text-secondary-50 mt-2 d-block opacity-50" style="font-size: 0.7rem;">
            <i class="fas fa-clock"></i> <?php echo t('last_updated'); ?> <?php echo date('H:i'); ?>
        </small>
    </div>
    <?php
}

/**
 * Renders the Reviews Section (Marquee)
 */
function render_reviews_section($reviews)
{
    if (empty($reviews))
        return;
?>
    <div class="bento-card span-12 scroll-scale delay-200" style="padding:18px 24px;">
        <div class="d-flex align-items-center justify-content-end mb-3">
             <div class="reviews-navigation">
                 <button id="prev-review" class="nav-btn" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
                 <button id="next-review" class="nav-btn" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
             </div>
        </div>
        <div class="reviews-marquee">
            <div class="reviews-inner">
                <div class="reviews-track">
                <?php foreach ($reviews as $r):
        $prenom = htmlspecialchars($r['prenom'] ?? '');
        $nom = htmlspecialchars($r['nom'] ?? '');
        $prof = htmlspecialchars($r['profession'] ?? '');
        $avis = htmlspecialchars($r['avis'] ?? '');
        $note = isset($r['note']) ? floatval($r['note']) : 0;
        $photo = $r['photo'] ?? null;
        $linkedin = $r['linkedin'] ?? '';
        $name = trim($prenom . ' ' . $nom);
        $initials = strtoupper(($prenom ? $prenom[0] : '') . ($nom ? $nom[0] : ''));
        if (empty($avis))
            continue;
?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-avatar">
                                <?php if ($photo): ?>
                                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo $name; ?>" loading="lazy"/>
                                <?php
        else: ?>
                                    <?php echo $initials; ?>
                                <?php
        endif; ?>
                            </div>
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
                                
                                <?php if (!empty($prof)): ?>
                                    <div class="profession">
                                        <?php
            echo $prof;
            if (isset($r['status']) && $r['status'] === 'Entreprise' && !empty($r['entreprise'])) {
                echo ' <span class="company-name">chez ' . htmlspecialchars($r['entreprise']) . '</span>';
            }
?>
                                    </div>
                                <?php
        endif; ?>
                            </div>
                        </div>
                        <div class="review-content-scroll">
                            <p class="review-text"><?php echo $avis; ?></p>
                        </div>
                        <div class="review-stars"><?php echo function_exists('render_stars_html') ? render_stars_html($note) : ''; ?></div>
                    </div>
                <?php
    endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
