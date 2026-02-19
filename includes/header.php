<?php

include_once 'config.php';
include_once 'lang.php';

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
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
?>
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo $meta_title; ?>">
    <meta property="og:description" content="<?php echo $meta_description; ?>">
    <meta property="og:image" content="<?php echo $meta_image; ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="twitter:title" content="<?php echo $meta_title; ?>">
    <meta property="twitter:description" content="<?php echo $meta_description; ?>">
    <meta property="twitter:image" content="<?php echo $meta_image; ?>">

    <!-- n8n Chat Widget -->
    <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />
    <script type="module">
        import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';
        createChat({
            webhookUrl: '<?php echo config('N8N_CHAT_WEBHOOK'); ?>'
        });
    </script>
    <title><?php echo $meta_title; ?></title>
    
    <!-- Bootstrap 5 (Grid Only primarily) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Design System -->
    <!-- Custom Design System -->
    <!-- Custom Design System -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/cookie-banner.css" rel="stylesheet">

    <!-- Mobile Menu Styles -->
    <style>
        .mobile-menu-btn {
            display: none;
            background: transparent;
            border: none;
            padding: 8px;
            cursor: pointer;
            z-index: 1001;
        }

        .hamburger {
            display: flex;
            flex-direction: column;
            gap: 5px;
            width: 24px;
        }

        .hamburger span {
            display: block;
            height: 2px;
            background: white;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .mobile-menu-btn.active .hamburger span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .mobile-menu-btn.active .hamburger span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-btn.active .hamburger span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 280px;
            max-width: 85%;
            height: 100vh;
            background: rgba(10, 10, 10, 0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 999;
            padding: 100px 30px 40px;
            transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .mobile-menu.active {
            right: 0;
        }

        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .mobile-menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .mobile-menu-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            padding: 14px 20px;
            border-radius: 12px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .mobile-menu-link:hover,
        .mobile-menu-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .mobile-menu-link.active {
            background: linear-gradient(135deg, var(--accent-purple), #7000ff);
        }

        .mobile-menu-link i {
            width: 24px;
            text-align: center;
            font-size: 1rem;
        }

        .mobile-contact-btn {
            margin-top: auto;
            text-align: center;
        }

        @media (max-width: 767px) {
            .mobile-menu-btn {
                display: block;
            }

            .dock-nav .d-none.d-md-flex {
                display: none !important;
            }

            .dock-nav .btn-apple {
                display: none;
            }
        }
    </style>
</head>
</head>
<body>

<?php // Loader removed as per user request ?>



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
    <a href="?lang=<?php echo $lang === 'en' ? 'fr' : 'en'; ?>" class="mobile-menu-link" onclick="toggleMobileMenu()">
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
        <a href="?lang=<?php echo $lang === 'en' ? 'fr' : 'en'; ?>" class="btn btn-sm btn-apple px-3 py-2 fw-bold" style="font-size: 0.8rem;" title="<?php echo $lang === 'en' ? t('lang_fr') : t('lang_en'); ?>">
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
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.dock-links-container');
    const pill = document.querySelector('.nav-liquid-pill');
    const links = document.querySelectorAll('.dock-links-container .dock-link');
    
    if (!container || !pill || links.length === 0) return;
    
    // Position pill on active link
    function positionPill(element, animate = true) {
        if (!element) return;
        
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
    }
    
    // Initialize on active link
    const activeLink = document.querySelector('.dock-links-container .dock-link.active');
    if (activeLink) {
        setTimeout(() => positionPill(activeLink, false), 50);
    }
    
    // Click animation - slide to clicked link before navigation
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!this.classList.contains('active')) {
                positionPill(this, true);
            }
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', () => {
        if (activeLink) {
            positionPill(activeLink, false);
        }
    });
});
</script>

<!-- Spacer for fixed nav -->
<div class="nav-spacer" style="height: 120px;"></div>

<!-- Main Content Container for Swup -->
<main id="swup" class="transition-fade">


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
        "email": "contact@javabien.ovh",
        "contactType": "customer service"
      }
    }
    </script>

