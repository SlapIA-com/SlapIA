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
?>
