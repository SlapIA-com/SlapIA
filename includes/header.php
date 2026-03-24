<?php
include_once 'config.php';
include_once 'lang.php';

// Calculate the current page "slug" (filename without extension)
$slug = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang ?? 'fr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- DNS Prefetch & Preconnect for external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://challenges.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://unpkg.com">
    <link rel="dns-prefetch" href="https://api.notion.com">
    <!-- Google Fonts (non-blocking) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/img/brand/logo.svg">
    <link rel="apple-touch-icon" href="/assets/img/brand/logo.svg">

    <!-- Alternate Languages (SEO) -->
    <?php
    $canonical_path = strtok($_SERVER['REQUEST_URI'], '?');
    $safe_host = htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8');
    $safe_path = htmlspecialchars($canonical_path, ENT_QUOTES, 'UTF-8');
    ?>
    <link rel="alternate" hreflang="fr" href="https://<?php echo $safe_host; ?><?php echo $safe_path; ?>?lang=fr" />
    <link rel="alternate" hreflang="en" href="https://<?php echo $safe_host; ?><?php echo $safe_path; ?>?lang=en" />
    <link rel="alternate" hreflang="x-default" href="https://<?php echo $safe_host; ?><?php echo $safe_path; ?>" />

    <!-- Open Graph / Facebook -->
    <?php
$meta_title = isset($page_title) ? $page_title : t('meta_title');
$meta_description = isset($page_description) ? $page_description : t('meta_description');
$meta_image = isset($page_image) ? $page_image : "https://" . $_SERVER['HTTP_HOST'] . "/assets/img/brand/logo.png";

// Ensure image URL is absolute
if (strpos($meta_image, 'http') === false) {
    $meta_image = "https://" . $_SERVER['HTTP_HOST'] . $meta_image;
}

// Build canonical URL (strip query params) — $canonical_path and $safe_host already set above
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

    <!-- Cloudflare Turnstile (loaded globally for Swup SPA compatibility) -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=onloadTurnstileCallback" async defer></script>

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
    // Load ALL page-specific CSS globally so SPA (Swup) navigation always has styles ready.
    // No conflicts because selectors target page-specific class names.
    ?>
    <link href="<?php echo css_url('/assets/css/homepage.css'); ?>" rel="stylesheet">
    <link href="<?php echo css_url('/assets/css/formation.css'); ?>" rel="stylesheet">
    <link href="<?php echo css_url('/assets/css/blog.css'); ?>" rel="stylesheet">

    <!-- Schema.org Organization -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "SlapIA",
      "url": "https://<?php echo $safe_host; ?>",
      "logo": "https://<?php echo $safe_host; ?>/assets/img/brand/logo.svg",
      "contactPoint": {
        "@type": "ContactPoint",
        "email": "contact@slapia.com",
        "contactType": "customer service"
      }
    }
    </script>
    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- PWA Manifest & Service Worker Registration -->
    <link rel="manifest" href="/manifest.json">
    <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
          .then(reg => console.log('[PWA] SW Registered'))
          .catch(err => console.log('[PWA] SW Failed', err));
      });
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
    <a href="/" class="mobile-menu-link <?php echo $slug == 'index' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i> <?php echo t('home'); ?>
    </a>
    <a href="/formation" class="mobile-menu-link <?php echo $slug == 'formation' ? 'active' : ''; ?>">
        <i class="fas fa-graduation-cap"></i> <?php echo t('formations'); ?>
    </a>
    <a href="/entreprises" class="mobile-menu-link <?php echo $slug == 'entreprises' ? 'active' : ''; ?>">
        <i class="fas fa-building"></i> <?php echo t('companies'); ?>
    </a>
    <a href="/expertise" class="mobile-menu-link <?php echo $slug == 'expertise' ? 'active' : ''; ?>">
        <i class="fas fa-star"></i> <?php echo t('expertise'); ?>
    </a>
    <a href="/blog" class="mobile-menu-link <?php echo $slug == 'blog' ? 'active' : ''; ?>">
        <i class="fas fa-newspaper"></i> Blog
    </a>
    <a href="#" onclick="switchLanguage('<?php echo $lang === 'en' ? 'fr' : 'en'; ?>'); return false;" class="mobile-menu-link">
        <i class="fas fa-flag"></i> <?php echo $lang === 'en' ? 'FR' : 'EN'; ?>
    </a>
    <div class="mobile-contact-btn pt-3">
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <a href="/dashboard" class="btn-primary-glow w-100 justify-content-center mb-2 p-2" style="border-radius: 999px; display:flex; align-items:center; text-decoration:none; gap: 10px;">
                <?php if (!empty($_SESSION['user_photo'])): ?>
                    <img src="<?php echo $_SESSION['user_photo']; ?>" alt="Profile" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.2);">
                <?php else: ?>
                    <i class="fas fa-user-circle"></i>
                <?php endif; ?>
                Mon Espace
            </a>
        <?php else: ?>
            <a href="/login" class="btn-outline-glass w-100 justify-content-center mb-2" style="display:flex; align-items:center; text-decoration:none;">
                <i class="fas fa-sign-in-alt me-2"></i> Connexion
            </a>
        <?php endif; ?>
        <a href="/contact" class="btn-apple w-100 justify-content-center">
            <i class="fas fa-envelope me-2"></i> <?php echo t('contact'); ?>
        </a>
    </div>
</nav>

<!-- Floating Dock Navbar -->
<nav class="dock-nav">
    <!-- Brand -->
    <a href="/" class="dock-brand">
        <img src="/assets/img/brand/logo.svg" alt="SlapIA Logo" style="height: 24px; width: auto;" loading="lazy">
        <span class="ms-2 d-none d-lg-inline">SlapIA</span>
    </a>
    
    <!-- Navigation Links -->
    <div class="dock-links-container d-none d-md-flex">
        <div class="nav-liquid-pill"></div>
        <a href="/" class="dock-link <?php echo $slug == 'index' ? 'active' : ''; ?>"><?php echo t('home'); ?></a>
        <a href="/formation" class="dock-link <?php echo $slug == 'formation' ? 'active' : ''; ?>"><?php echo t('formations'); ?></a>
        <a href="/entreprises" class="dock-link <?php echo $slug == 'entreprises' ? 'active' : ''; ?>"><?php echo t('companies'); ?></a>
        <a href="/expertise" class="dock-link <?php echo $slug == 'expertise' ? 'active' : ''; ?>"><?php echo t('expertise'); ?></a>
        <a href="/blog" class="dock-link <?php echo $slug == 'blog' ? 'active' : ''; ?>">Blog</a>
    </div>
    
    <!-- Dock Actions (Wait, only Lang and Contact here) -->
    <div class="d-flex align-items-center gap-2">
        <a href="#" onclick="switchLanguage('<?php echo $lang === 'en' ? 'fr' : 'en'; ?>'); return false;" class="btn btn-sm btn-apple px-3 py-2 fw-bold d-none d-sm-inline-flex" style="font-size: 0.8rem; border-radius: 999px;">
            <span><?php echo $lang === 'en' ? 'FR' : 'EN'; ?></span>
        </a>
        <a href="/contact" class="btn btn-sm btn-apple px-3 py-2 fw-bold d-none d-md-inline-flex" style="font-size: 0.8rem; border-radius: 999px;">
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

<!-- Right Side Floating Account (Outside Dock) -->
<div class="header-right-actions d-none d-md-flex align-items-center">
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
        <!-- Global Notification Bell -->
        <div class="dropdown me-3">
            <button class="btn btn-link p-0 position-relative border-0 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="globalNotifBtn" style="transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
                <i class="fas fa-bell text-white opacity-75 fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-dark" id="globalNotifBadge" style="font-size: 0.55rem; padding: 0.35em 0.5em; display: none; box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);">
                    0
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark bento-card border-white border-opacity-10 p-2 shadow-lg mt-3" 
                id="globalNotifList"
                style="min-width: 300px; background: rgba(15, 15, 15, 0.98); backdrop-filter: blur(30px); border-radius: 20px;">
                <li class="dropdown-header text-uppercase text-secondary small fw-bold mb-2 p-2">Notifications</li>
                <div id="globalNotifContainer" style="max-height: 400px; overflow-y: auto;">
                    <li class="p-4 text-center text-secondary small opacity-50">Aucune notification</li>
                </div>
                <li><hr class="dropdown-divider opacity-10"></li>
                <li><button onclick="markAllRead()" class="dropdown-item text-center small text-primary fw-medium py-2 rounded-3">Marquer tout comme lu</button></li>
            </ul>
        </div>

        <a href="/dashboard" class="account-pill hover-glow shadow-sm" title="<?php echo t('dash_title'); ?>">
            <?php if (!empty($_SESSION['user_photo'])): ?>
                <img src="<?php echo $_SESSION['user_photo']; ?>" alt="Profile" class="account-img shadow-lg">
            <?php else: ?>
                <i class="fas fa-user-circle text-white opacity-75 fs-5"></i>
            <?php endif; ?>
        </a>
    <?php else: ?>
        <a href="/login" class="btn btn-sm btn-primary-glow px-4 py-2 fw-bold d-none d-md-inline-flex" style="font-size: 0.8rem; border-radius: 999px;">
            <i class="fas fa-sign-in-alt me-1"></i> <?php echo t('login_title'); ?>
        </a>
    <?php endif; ?>
</div>

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

// Notification Management
let lastReadNotif = localStorage.getItem('slapia_notif_read') || 0;

async function fetchNotifications() {
    const list = document.getElementById('globalNotifContainer');
    const badge = document.getElementById('globalNotifBadge');
    if (!list) return;

    try {
        const res = await fetch('/api/check-notifications.php');
        const data = await res.json();
        
        if (data.success && data.notifications.length > 0) {
            let html = '';
            let unreadCount = 0;
            
            data.notifications.forEach(n => {
                const isNew = n.ts > lastReadNotif;
                if (isNew) unreadCount++;
                
                html += `
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-3 py-3 rounded-4 ${isNew ? 'bg-white bg-opacity-5' : ''}" href="${n.link || '#'}">
                            <div class="rounded-circle p-2 ${n.icon_bg || 'bg-primary'} bg-opacity-10">
                                <i class="${n.icon || 'fas fa-info-circle'} ${n.icon_color || 'text-primary'}"></i>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="text-white small fw-bold text-truncate">${n.title}</div>
                                <div class="text-secondary smaller text-truncate opacity-75">${n.desc}</div>
                            </div>
                            ${isNew ? '<div class="rounded-circle bg-danger" style="width: 6px; height: 6px;"></div>' : ''}
                        </a>
                    </li>`;
            });
            
            list.innerHTML = html;
            if (unreadCount > 0) {
                badge.textContent = unreadCount;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (e) {
        console.error('Notif error:', e);
    }
}

function markAllRead() {
    lastReadNotif = Date.now() / 1000;
    localStorage.setItem('slapia_notif_read', lastReadNotif);
    fetchNotifications();
}

// Initial fetch
<?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
document.addEventListener('DOMContentLoaded', fetchNotifications);
// Refresh occasionally
setInterval(fetchNotifications, 60000); 
<?php endif; ?>

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



