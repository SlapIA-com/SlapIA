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

// Appels Notion
$stats = getSatisfactionStats(true);
$reviews = getNotionReviews(20, $lang ?? 'fr');


?>

<!-- Subtle Grid Background -->
<div class="grid-bg"></div>

<!-- ============================================
     HERO SECTION - Premium Enterprise
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
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="container position-relative" style="z-index: 2;">

        <!-- Floating Badge -->
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill border border-secondary border-opacity-25 mb-4 fade-in-up"
            style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
            <span class="badge bg-primary rounded-pill shimmer-badge"
                style="font-size: 0.7rem; border: 1px solid rgba(255,255,255,0.2);"><?php echo t('3_courses'); ?></span>
            <span class="text-secondary small fw-medium"><?php echo t('complete_training'); ?></span>
        </div>

        <!-- Main Title -->
        <h1 class="hero-title-xl mb-4 fade-in-up delay-200">
            <?php echo t('master_ai'); ?> <br>
            <span class="text-gradient-animated" id="typewriter-text"
                data-phrases='["<?php echo t('typewriter_1'); ?>", "<?php echo t('typewriter_2'); ?>", "<?php echo t('typewriter_3'); ?>"]'>
                <?php echo t('typewriter_1'); ?>
            </span>
            <span class="cursor-blink">|</span>
        </h1>

        <!-- Sub-description -->
        <p class="hero-description mx-auto mb-0 fade-in-up delay-400">
            <?php echo t('structured_paths'); ?>
        </p>

        <!-- Dual CTA -->
        <div class="hero-cta-group fade-in-up delay-600">
            <a href="formation" class="btn-primary-glow">
                <?php echo t('discover_trainings'); ?> <i class="fas fa-arrow-right"></i>
            </a>
            <a href="entreprises" class="btn-outline-glass">
                <i class="fas fa-building"></i> <?php echo t('companies'); ?>
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
     INFINITE MARQUEE - Tech Stack Logos
     ============================================ -->
<div class="marquee-container border-top border-bottom border-light border-opacity-10 mb-5 fade-in-up delay-600"
    style="background: rgba(0,0,0,0.3); backdrop-filter: blur(10px);">
    <div class="marquee-content">
        <?php
        // Logo data for DRY marquee generation
        $marquee_logos = [
            ['src' => 'https://nxus.fr/wp-content/uploads/2025/02/logo-n8n.png', 'alt' => 'n8n', 'label' => 'n8n', 'style' => 'height:22px; filter: brightness(0) invert(1); opacity:0.8;'],
            ['src' => 'https://raw.githubusercontent.com/lobehub/lobe-icons/refs/heads/master/packages/static-png/dark/make-color.png', 'alt' => 'make', 'label' => 'Make', 'style' => 'height:22px; filter: brightness(0) invert(1); opacity:0.8;'],
            ['src' => 'https://upload.wikimedia.org/wikipedia/commons/4/45/Notion_app_logo.png', 'alt' => 'Notion', 'label' => 'Notion', 'style' => 'height:28px; border-radius: 4px;'],
            ['src' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1d/Google_Gemini_icon_2025.svg/960px-Google_Gemini_icon_2025.svg.png?20250728014952', 'alt' => 'Gemini', 'label' => 'Gemini', 'style' => 'height:28px;'],
            ['src' => 'https://upload.wikimedia.org/wikipedia/commons/4/4d/OpenAI_Logo.svg', 'alt' => 'ChatGPT', 'label' => 'ChatGPT', 'style' => 'height:28px; filter: brightness(0) invert(1); opacity:0.8;'],
            ['src' => 'https://upload.wikimedia.org/wikipedia/fr/0/02/Microsoft_365_Copilot.svg', 'alt' => 'Copilot', 'label' => 'Copilot', 'style' => 'height:28px;'],
            ['icon' => 'fas fa-code', 'label' => 'API & Webhooks'],
            ['src' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/vscode/vscode-original.svg', 'alt' => 'VS Code', 'label' => 'Visual Studio Code', 'style' => 'height:28px;'],
        ];

        // Repeat 4 sets for seamless loop
        for ($set = 0; $set < 4; $set++):
            foreach ($marquee_logos as $logo):
                echo '<div class="marquee-item">';
                if (isset($logo['src'])) {
                    echo '<img src="' . $logo['src'] . '" alt="' . $logo['alt'] . '" style="' . $logo['style'] . '"> ';
                } elseif (isset($logo['icon'])) {
                    echo '<i class="' . $logo['icon'] . ' text-secondary" style="font-size: 1.2rem;"></i> ';
                }
                echo $logo['label'];
                echo '</div>';
            endforeach;
        endfor;
        ?>
    </div>
</div>
</div>

<!-- ============================================
     MAIN BENTO GRID - Enhanced
     ============================================ -->
<section class="pb-5">
    <div class="container fade-in-up delay-600">
        <div class="bento-grid">

            <!-- Main Feature Card - Formation IA (Large) -->
            <div class="bento-card bento-card-glow card-feature-main span-8 d-flex flex-column justify-content-between scroll-scale">
                <!-- Internal glow orbs -->
                <div class="card-glow-orb orb-blue"></div>
                <div class="card-glow-orb orb-purple"></div>

                <div style="position: relative; z-index: 2;">
                    <div class="icon-box text-white border-0" style="background: var(--accent-blue);">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3 class="text-white mb-2"><?php echo t('ai_training'); ?></h3>
                    <p class="text-secondary" style="max-width: 400px;"><?php echo t('learn_ai_tools'); ?></p>
                </div>

                <!-- Mini Terminal Mockup -->
                <div class="mini-terminal" style="position: relative; z-index: 2;">
                    <div class="mini-terminal-header">
                        <div class="terminal-dot red"></div>
                        <div class="terminal-dot yellow"></div>
                        <div class="terminal-dot green"></div>
                        <span class="text-secondary small ms-2" style="font-size: 0.7rem;">slapia-training.sh</span>
                    </div>
                    <div class="mini-terminal-body">
                        <div class="terminal-line">
                            <span class="terminal-prompt">$</span>
                            <span class="terminal-command"><?php echo t('terminal_line_1'); ?></span>
                        </div>
                        <div class="terminal-line">
                            <span class="terminal-success">✓</span>
                            <span class="terminal-command"><?php echo t('terminal_line_2'); ?></span>
                        </div>
                        <div class="terminal-line">
                            <span class="terminal-success">✓</span>
                            <span class="terminal-command"><?php echo t('terminal_line_3'); ?></span>
                        </div>
                        <div class="terminal-line">
                            <span class="terminal-success">✓</span>
                            <span class="terminal-command"><?php echo t('terminal_line_4'); ?></span>
                        </div>
                        <div class="terminal-line">
                            <span class="terminal-info">→</span>
                            <span class="terminal-success"><?php echo t('terminal_line_5'); ?></span>
                        </div>
                    </div>
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
                } catch (Exception $e) {
                    $reviews = [];
                }
            }
            render_reviews_section($reviews);
            ?>

            <!-- Enterprise Card -->
            <div class="bento-card bento-card-glow card-enterprise span-6 d-flex flex-column justify-content-between scroll-scale">
                <div>
                    <div class="icon-box text-primary border-0 mb-3" style="background: rgba(41, 151, 255, 0.1);">
                        <i class="fas fa-building"></i>
                    </div>
                    <h4 class="text-white"><?php echo t('training_for_companies'); ?></h4>
                    <p class="text-secondary small"><?php echo t('train_teams'); ?></p>

                    <!-- Mini pills showing capabilities -->
                    <div class="enterprise-logos">
                        <div class="enterprise-logo-pill"><i class="fas fa-users"></i> <?php echo t('hero_stat_teams'); ?></div>
                        <div class="enterprise-logo-pill"><i class="fas fa-chart-line"></i> ROI</div>
                        <div class="enterprise-logo-pill"><i class="fas fa-certificate"></i> <?php echo t('hero_stat_certification'); ?></div>
                    </div>
                </div>
                <a href="entreprises" class="text-primary small text-decoration-none mt-3">
                    <?php echo t('learn_more'); ?> <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            <!-- Coaching Card - Premium Purple -->
            <div class="bento-card bento-card-glow card-coaching span-6 d-flex flex-column justify-content-between scroll-scale delay-100">
                <div style="position: relative; z-index: 2;">
                    <div class="card-coaching-badge">
                        <i class="fas fa-crown"></i> VIP
                    </div>
                    <div class="icon-box text-white border-0 mb-3" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h5 class="text-white mb-1"><?php echo t('personal_coaching'); ?></h5>
                    <p class="text-white opacity-75 small mb-3"><?php echo t('vip_mentoring'); ?></p>
                </div>
                <a href="formation#formation" class="text-white text-decoration-none small fw-bold" style="position: relative; z-index: 2;">
                    <?php echo t('book_slot'); ?> <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>

        </div>
    </div>
</section>


<!-- ============================================
     WHY SLAPIA SECTION
     ============================================ -->
<section class="section-why">
    <div class="container">
        <div class="text-center">
            <div class="section-badge scroll-reveal">
                <i class="fas fa-bolt"></i> <?php echo t('why_badge'); ?>
            </div>
            <h2 class="section-title-lg scroll-reveal delay-100"><?php echo t('why_title'); ?></h2>
            <p class="text-secondary mx-auto scroll-reveal delay-200" style="max-width: 600px; font-size: 1.1rem;">
                <?php echo t('why_subtitle'); ?>
            </p>
        </div>

        <div class="why-grid">
            <!-- Card 1 - Progressive -->
            <div class="why-card scroll-reveal">
                <div class="why-icon icon-purple">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h4 class="why-card-title"><?php echo t('progressive_training'); ?></h4>
                <p class="why-card-desc"><?php echo t('progressive_training_desc'); ?></p>
            </div>

            <!-- Card 2 - Pratique -->
            <div class="why-card scroll-reveal delay-100">
                <div class="why-icon icon-blue">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <h4 class="why-card-title"><?php echo t('immediate_practice'); ?></h4>
                <p class="why-card-desc"><?php echo t('immediate_practice_desc'); ?></p>
            </div>

            <!-- Card 3 - Support -->
            <div class="why-card scroll-reveal delay-200">
                <div class="why-icon icon-green">
                    <i class="fas fa-headset"></i>
                </div>
                <h4 class="why-card-title"><?php echo t('continuous_support'); ?></h4>
                <p class="why-card-desc"><?php echo t('continuous_support_desc'); ?></p>
            </div>
        </div>
    </div>
</section>


<!-- ============================================
     NUMBERS / IMPACT SECTION
     ============================================ -->
<section class="numbers-section">
    <div class="container">
        <div class="numbers-grid">
            <div class="number-item scroll-reveal">
                <div class="number-value" data-count="<?php echo ($stats['nombre'] > 0) ? (int)$stats['nombre'] : 50; ?>">0</div>
                <div class="number-label"><?php echo t('number_trained'); ?></div>
            </div>
            <div class="number-item scroll-reveal delay-100">
                <div class="number-value" data-count="<?php echo ($stats['pourcentage'] !== 'N.A') ? (int)$stats['pourcentage'] : 98; ?>" data-suffix="%">0</div>
                <div class="number-label"><?php echo t('satisfaction'); ?></div>
            </div>
            <div class="number-item scroll-reveal delay-200">
                <div class="number-value" data-count="3">0</div>
                <div class="number-label"><?php echo t('number_levels'); ?></div>
            </div>
            <div class="number-item scroll-reveal delay-300">
                <div class="number-value" data-count="24" data-suffix="h">0</div>
                <div class="number-label"><?php echo t('number_support'); ?></div>
            </div>
        </div>
    </div>
</section>


<!-- ============================================
     ECOSYSTEM SECTION - Tools & Approach
     ============================================ -->
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
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 40px; height: 40px; background: rgba(255,255,255,0.1);">
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
                    <div class="position-absolute top-50 start-50 translate-middle"
                        style="width: 300px; height: 300px; background: var(--accent-blue); filter: blur(100px); opacity: 0.2;">
                    </div>

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
                                <div class="bento-card p-3 text-center h-100 border-0 scroll-reveal <?php echo $tool['delay']; ?>"
                                    style="background: rgba(255,255,255,0.03);">
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
            <a href="formation" class="btn-primary-glow">
                <?php echo t('discover_trainings'); ?> <i class="fas fa-arrow-right"></i>
            </a>
            <a href="contact" class="btn-outline-glass">
                <i class="fas fa-envelope"></i> <?php echo t('contact'); ?>
            </a>
        </div>
    </div>
</section>


<!-- Counter Animation Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.number-value[data-count]');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.getAttribute('data-count'));
                const suffix = el.getAttribute('data-suffix') || '';
                const prefix = target > 10 ? '+' : '';
                const duration = 2000;
                const start = performance.now();

                function animate(currentTime) {
                    const elapsed = currentTime - start;
                    const progress = Math.min(elapsed / duration, 1);
                    // Ease out cubic
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = Math.round(eased * target);
                    el.textContent = prefix + current + suffix;
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    }
                }
                requestAnimationFrame(animate);
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => observer.observe(counter));

    // Spotlight effect for why-cards
    const whyCards = document.querySelectorAll('.why-card');
    whyCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            card.style.setProperty('--mouse-x', `${e.clientX - rect.left}px`);
            card.style.setProperty('--mouse-y', `${e.clientY - rect.top}px`);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>