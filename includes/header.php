<?php
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/auth.php';

$current = basename($_SERVER['PHP_SELF']);
$nav_items = [
  'index.php'        => t('nav.home'),
  'formations.php'   => t('nav.courses'),
  'services-pc.php'  => t('nav.services'),
  'tarifs.php'       => t('nav.pricing'),
  'blog.php'         => t('nav.blog'),
  'a-propos.php'     => t('nav.about'),
  'contact.php'      => t('nav.contact'),
];

$site_url = 'https://www.slapia.com/';
$path = isset($page_path) ? $page_path : (($current === 'index.php') ? '' : $current);
$canonical = isset($page_canonical) ? $page_canonical : ($site_url . $path . '?lang=' . $lang);
$title = isset($page_title) ? htmlspecialchars($page_title) . ' — Slapia' : t('home.meta_title') . ' — Slapia';
$description = isset($page_description) ? htmlspecialchars($page_description) : t('home.meta_description');
$og_image = isset($page_image) ? $page_image : 'assets/img/brand/logo.png';
if (!preg_match('#^https?://#i', $og_image)) {
    $og_image = $site_url . $og_image;
}

$lang_names = ['fr' => 'FR', 'en' => 'EN', 'de' => 'DE'];
?>
<!doctype html>
<html lang="<?php echo t('meta.html_lang'); ?>">
<head>
<meta charset="UTF-8">
<script>
(function(){
  try {
    var stored = localStorage.getItem('slapia-theme');
    var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', theme);
  } catch (e) {}
})();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $title; ?></title>
<meta name="description" content="<?php echo $description; ?>">
<link rel="canonical" href="<?php echo $canonical; ?>">
<?php foreach ($supported_langs as $l): ?>
<link rel="alternate" hreflang="<?php echo $l; ?>" href="<?php echo $site_url . $path . '?lang=' . $l; ?>">
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="<?php echo $site_url . $path; ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="<?php echo t('meta.og_locale'); ?>">
<meta property="og:site_name" content="Slapia">
<meta property="og:title" content="<?php echo $title; ?>">
<meta property="og:description" content="<?php echo $description; ?>">
<meta property="og:url" content="<?php echo $canonical; ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
<meta name="twitter:card" content="<?php echo isset($page_image) ? 'summary_large_image' : 'summary'; ?>">
<link rel="stylesheet" href="<?php echo assetUrl('/assets/css/style.css'); ?>">
<link rel="icon" type="image/png" href="/assets/img/brand/logo.png">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "@id": "<?php echo $site_url; ?>#organization",
  "name": "Slapia",
  "legalName": "SlapIA",
  "url": "<?php echo $site_url; ?>",
  "logo": "<?php echo $site_url; ?>assets/img/brand/logo.png",
  "description": "<?php echo t('home.meta_description'); ?>"
}
</script>
<?php if (isset($jsonld) && $jsonld !== ''): ?>
<script type="application/ld+json"><?php echo $jsonld; ?></script>
<?php endif; ?>
</head>
<body class="has-rail">

<div class="rail" aria-hidden="true">
  <img src="/assets/img/brand/logo.svg" alt="" class="rail__mark">
  <span class="rail__label">FORMATIONS IA — SERVICES PC</span>
  <span class="rail__track"><span class="rail__fill"></span></span>
  <span class="rail__pct">0%</span>
</div>
<div class="progress-mobile" aria-hidden="true"><span class="progress-mobile__fill"></span></div>

<header class="site-header">
  <div class="container">
    <a href="/index.php" class="logo"><img src="/assets/img/brand/logo.svg" alt="" class="logo__mark"> Slapia</a>

    <nav class="nav" aria-label="Navigation principale">
      <?php foreach ($nav_items as $href => $label): ?>
        <a class="nav__link<?php echo $current === $href ? ' is-active' : ''; ?>" href="/<?php echo $href; ?>"><?php echo $label; ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="header__actions">
      <div class="lang-switch">
        <?php foreach ($lang_names as $l => $label): ?>
          <a href="?lang=<?php echo $l; ?>" class="lang-switch__link<?php echo $lang === $l ? ' is-active' : ''; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
      </div>
      <button class="theme-toggle" type="button" aria-label="<?php echo t('common.toggle_theme'); ?>" title="<?php echo t('common.toggle_theme'); ?>">
        <svg class="theme-toggle__icon theme-toggle__icon--sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2.3M12 19.2v2.3M4.4 4.4l1.6 1.6M18 18l1.6 1.6M2.5 12h2.3M19.2 12h2.3M4.4 19.6l1.6-1.6M18 6l1.6-1.6"/></svg>
        <svg class="theme-toggle__icon theme-toggle__icon--moon" viewBox="0 0 24 24" fill="currentColor"><path d="M20 14.5A8.5 8.5 0 019.5 4a8.5 8.5 0 1010.5 10.5z"/></svg>
      </button>
      <?php $me = currentUser(); ?>
      <?php if ($me): ?>
        <?php $dashHref = $me['role'] === 'admin' ? '/admin' : '/dashboard'; ?>
        <?php $dashLabel = $me['role'] === 'admin' ? t('nav.admin') : t('nav.dashboard'); ?>
        <div class="user-menu">
          <button type="button" class="user-menu__trigger" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo t('nav.account_menu'); ?>">
            <img src="/api/notion-avatar.php?id=<?php echo urlencode($me['id']); ?>" alt="" class="user-menu__avatar">
          </button>
          <div class="user-menu__dropdown">
            <div class="user-menu__name"><?php echo htmlspecialchars($me['name']); ?></div>
            <a href="<?php echo $dashHref; ?>" class="user-menu__link"><?php echo $dashLabel; ?></a>
            <a href="/api/auth-logout.php" class="user-menu__link user-menu__link--danger"><?php echo t('nav.logout'); ?></a>
          </div>
        </div>
      <?php else: ?>
        <a href="/login" class="btn btn--ghost"><?php echo t('nav.login'); ?></a>
      <?php endif; ?>
      <a href="/contact.php" class="btn btn--primary"><?php echo t('common.book_call'); ?> <span class="btn__arrow">→</span></a>
      <button class="nav-toggle" aria-label="<?php echo t('common.open_menu'); ?>"><span></span></button>
    </div>
  </div>
</header>

<div class="mobile-menu">
  <div class="mobile-menu__top">
    <a href="/index.php" class="logo"><img src="/assets/img/brand/logo.svg" alt="" class="logo__mark"> Slapia</a>
    <div style="display:flex; gap:10px; align-items:center;">
      <button class="theme-toggle theme-toggle--on-dark" type="button" aria-label="<?php echo t('common.toggle_theme'); ?>" title="<?php echo t('common.toggle_theme'); ?>">
        <svg class="theme-toggle__icon theme-toggle__icon--sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2.3M12 19.2v2.3M4.4 4.4l1.6 1.6M18 18l1.6 1.6M2.5 12h2.3M19.2 12h2.3M4.4 19.6l1.6-1.6M18 6l1.6-1.6"/></svg>
        <svg class="theme-toggle__icon theme-toggle__icon--moon" viewBox="0 0 24 24" fill="currentColor"><path d="M20 14.5A8.5 8.5 0 019.5 4a8.5 8.5 0 1010.5 10.5z"/></svg>
      </button>
      <button class="mobile-menu__close" aria-label="<?php echo t('common.close_menu'); ?>">✕</button>
    </div>
  </div>
  <nav class="mobile-menu__links" aria-label="Navigation mobile">
    <?php foreach ($nav_items as $href => $label): ?>
      <a class="<?php echo $current === $href ? 'is-active' : ''; ?>" href="/<?php echo $href; ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="mobile-menu__lang">
    <?php foreach ($lang_names as $l => $label): ?>
      <a href="?lang=<?php echo $l; ?>" class="lang-switch__link lang-switch__link--on-dark<?php echo $lang === $l ? ' is-active' : ''; ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
  </div>
  <div class="mobile-menu__foot">
    <?php if ($me): ?>
      <a href="<?php echo $dashHref; ?>" class="btn btn--on-dark btn--block" style="margin-bottom:10px;"><?php echo $dashLabel; ?></a>
      <a href="/api/auth-logout.php" class="btn btn--on-dark btn--block" style="margin-bottom:10px;"><?php echo t('nav.logout'); ?></a>
    <?php else: ?>
      <a href="/login" class="btn btn--on-dark btn--block" style="margin-bottom:10px;"><?php echo t('nav.login'); ?></a>
    <?php endif; ?>
    <a href="/contact.php" class="btn btn--signal btn--block"><?php echo t('common.book_call'); ?></a>
  </div>
</div>
