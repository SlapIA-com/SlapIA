<?php

include_once 'config.php';
include_once 'lang.php';

?>
<!DOCTYPE html>
<html lang="<?php echo $lang ?? 'fr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- DNS Prefetch & Preconnect for external domains -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://challenges.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://unpkg.com">
    <link rel="dns-prefetch" href="https://api.notion.com">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/img/logo.svg">
    <link rel="apple-touch-icon" href="/assets/img/logo.svg">

    <!-- Alternate Languages (SE0) -->
    <link rel="alternate" hreflang="fr" href="https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>?lang=fr" />
    <link rel="alternate" hreflang="en" href="https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>?lang=en" />
    <link rel="alternate" hreflang="x-default" href="https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>" />

    <!-- Open Graph / Facebook -->
    <?php
$meta_title = isset($page_title) ? $page_title : t('meta_title');
$meta_description = isset($page_description) ? $page_description : t('meta_description');
$meta_image = isset($page_image) ? $page_image : "https://" . $_SERVER['HTTP_HOST'] . "/assets/img/logo.png";

// Ensure image URL is absolute
if (strpos($meta_image, 'http') === false) {
    $meta_image = "https://" . $_SERVER['HTTP_HOST'] . $meta_image;
}

// Build canonical URL (strip query params except lang)
$canonical_path = strtok($_SERVER['REQUEST_URI'], '?');
$canonical_url = "https://" . $_SERVER['HTTP_HOST'] . $canonical_path;
?>
    <!-- Standard Meta Description (SEO) -->
    <meta name="description" content="<?php echo htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8'); ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($meta_image, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($meta_image, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- n8n Chat Widget -->
    <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />
    <script type="module">
        import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';
        createChat({
            webhookUrl: '<?php echo config('N8N_CHAT_WEBHOOK'); ?>'
        });
    </script>

    <!-- Cloudflare Turnstile (contact page only) -->
    <?php if (isset($page_needs_turnstile) && $page_needs_turnstile): ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=onloadTurnstileCallback" async defer></script>
    <?php endif; ?>

    <title><?php echo $meta_title; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Design System (auto cache-busting) -->
    <?php
    // Helper for auto cache-busting based on file modification time
    function css_url($path) {
        $file = $_SERVER['DOCUMENT_ROOT'] . $path;
        $v = file_exists($file) ? filemtime($file) : time();
        return $path . '?v=' . $v;
    }
    ?>
    <link href="<?php echo css_url('/assets/css/header.css'); ?>" rel="stylesheet">
    <link href="<?php echo css_url('/assets/css/footer.css'); ?>" rel="stylesheet">
    <link href="<?php echo css_url('/assets/css/animations.css'); ?>" rel="stylesheet">
    <link href="<?php echo css_url('/assets/css/reviews.css'); ?>" rel="stylesheet">
    <link href="<?php echo css_url('/assets/css/theme-matrix.css'); ?>" rel="stylesheet">
    <link href="<?php echo css_url('/assets/css/style.css'); ?>" rel="stylesheet">
    <link href="<?php echo css_url('/assets/css/lightbox.css'); ?>" rel="stylesheet">
    <?php
    // Page-specific CSS: only load on pages that need them
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    if ($current_page === 'index' || !isset($page_title)) {
        echo '<link href="' . css_url('/assets/css/homepage.css') . '" rel="stylesheet">';
    }
    if (isset($page_css) && is_array($page_css)) {
        foreach ($page_css as $css) {
            echo '<link href="' . css_url($css) . '" rel="stylesheet">';
        }
    }
    ?>

    <!-- Schema.org Organization -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "SlapIA",
      "url": "https://<?php echo $_SERVER["HTTP_HOST"]; ?>",
      "logo": "https://<?php echo $_SERVER["HTTP_HOST"]; ?>/assets/img/logo.svg",
      "contactPoint": {
        "@type": "ContactPoint",
        "email": "contact@slapia.com",
        "contactType": "customer service"
      }
    }
    </script>
</head>
<body>

<!-- The Aurora Background -->
<div class="aurora-bg">
    <div class="aurora-orb orb-1"></div>
    <div class="aurora-orb orb-2"></div>
    <div class="aurora-orb orb-3"></div>
</div>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" onclick="toggleMobileMenu()"></div>

<!-- Mobile Menu -->
<nav class="mobile-menu">
    <a href="/" class="mobile-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i> <?php echo t('home'); ?>
    </a>
    <a href="/formation" class="mobile-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'formation.php' ? 'active' : ''; ?>">
        <i class="fas fa-graduation-cap"></i> <?php echo t('formations'); ?>
    </a>
    <a href="/entreprises" class="mobile-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'entreprises.php' ? 'active' : ''; ?>">
        <i class="fas fa-building"></i> <?php echo t('companies'); ?>
    </a>
    <a href="/expertise" class="mobile-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'expertise.php' ? 'active' : ''; ?>">
        <i class="fas fa-star"></i> <?php echo t('expertise'); ?>
    </a>
    <a href="#" onclick="switchLanguage('<?php echo $lang === 'en' ? 'fr' : 'en'; ?>'); return false;" class="mobile-menu-link">
        <i class="fas fa-flag"></i> <?php echo $lang === 'en' ? 'FR' : 'EN'; ?>
    </a>
    <div class="mobile-contact-btn">
        <a href="/contact" class="btn-apple w-100 justify-content-center">
            <i class="fas fa-envelope"></i> <?php echo t('contact'); ?>
        </a>
    </div>
</nav>

<!-- Floating Dock Navbar -->
<nav class="dock-nav">
    <a href="/" class="dock-brand">
        <img src="/assets/img/logo.svg" alt="SlapIA Logo" style="height: 24px; width: auto;" loading="lazy"> SlapIA
    </a>
    
    <!-- Right: Navigation & Action -->
    <div class="d-flex align-items-center gap-4">
        <div class="dock-links-container d-none d-md-flex">
            <div class="nav-liquid-pill"></div>
            <a href="/" class="dock-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"><?php echo t('home'); ?></a>
            <a href="/formation" class="dock-link <?php echo basename($_SERVER['PHP_SELF']) == 'formation.php' ? 'active' : ''; ?>"><?php echo t('formations'); ?></a>
            <a href="/entreprises" class="dock-link <?php echo basename($_SERVER['PHP_SELF']) == 'entreprises.php' ? 'active' : ''; ?>"><?php echo t('companies'); ?></a>
            <a href="/expertise" class="dock-link <?php echo basename($_SERVER['PHP_SELF']) == 'expertise.php' ? 'active' : ''; ?>"><?php echo t('expertise'); ?></a>
        </div>
        <a href="#" onclick="switchLanguage('<?php echo $lang === 'en' ? 'fr' : 'en'; ?>'); return false;" class="btn btn-sm btn-apple px-3 py-2 fw-bold" style="font-size: 0.8rem;" title="<?php echo $lang === 'en' ? t('lang_fr') : t('lang_en'); ?>">
            <i class="fas fa-flag" style="margin-right: 6px;"></i> <span><?php echo $lang === 'en' ? 'FR' : 'EN'; ?></span>
        </a>
        <a href="/contact" class="btn btn-sm btn-apple px-3 py-2 fw-bold" style="font-size: 0.8rem;">
            <?php echo t('contact'); ?>
        </a>
        
        <!-- Mobile Menu Button -->
        <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Menu">
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>
    </div>
</nav>

<script>
function switchLanguage(lang) {
    // Force reload with new query param to ensure PHP session updates
    const url = new URL(window.location.href);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
}

function toggleMobileMenu() {
    const btn = document.querySelector('.mobile-menu-btn');
    const menu = document.querySelector('.mobile-menu');
    const overlay = document.querySelector('.mobile-menu-overlay');
    
    btn.classList.toggle('active');
    menu.classList.toggle('active');
    overlay.classList.toggle('active');
    
    // Prevent body scroll when menu is open
    document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';
}

// Close menu on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const menu = document.querySelector('.mobile-menu');
        if (menu.classList.contains('active')) {
            toggleMobileMenu();
        }
    }
});

// Liquid Glass Navigation Pill Effect (iOS 26 style)
// Liquid Glass Navigation Pill Effect (iOS 26 style)
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.dock-links-container');
    const pill = document.querySelector('.nav-liquid-pill');
    const links = document.querySelectorAll('.dock-links-container .dock-link');
    
    if (!container || !pill) return;
    
    // Position pill on active link (Exposed globally for Swup)
    window.moveNavPill = function(element, animate = true) {
        if (!element) return;
        
        // Ensure container and pill are fresh references if needed, 
        // though typically they are outside swup and static.
        
        const containerRect = container.getBoundingClientRect();
        const elementRect = element.getBoundingClientRect();
        
        const left = elementRect.left - containerRect.left;
        const width = elementRect.width;
        
        if (animate) {
            pill.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.1, 1)';
        } else {
            pill.style.transition = 'none';
        }
        
        pill.style.left = left + 'px';
        pill.style.width = width + 'px';
        pill.style.opacity = '1';
    };
    
    // Initialize on active link
    const activeLink = document.querySelector('.dock-links-container .dock-link.active');
    if (activeLink) {
        setTimeout(() => window.moveNavPill(activeLink, false), 50);
    }
    
    // Pill moves only on click / page navigation
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            links.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            window.moveNavPill(this, true);
        });
    });
    

    // Handle window resize
    window.addEventListener('resize', () => {
        const currentActive = document.querySelector('.dock-links-container .dock-link.active');
        if (currentActive) {
            window.moveNavPill(currentActive, false);
        }
    });
});
</script>

<!-- Spacer for fixed nav -->
<div class="nav-spacer" style="height: 120px;"></div>

<!-- Main Content Container for Swup -->
<main id="swup" class="swup-transition-main">



