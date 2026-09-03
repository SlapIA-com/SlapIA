<?php
http_response_code(404);
require_once 'includes/i18n.php';
$page_title = t('error404.meta_title');
$page_description = t('error404.meta_description');
include 'includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-canvas page-hero-canvas--broken" aria-hidden="true">
    <svg viewBox="0 0 300 160" class="broken-link-svg">
      <line x1="40" y1="80" x2="140" y2="50" stroke="var(--signal)" stroke-width="2" opacity="0.5"/>
      <line x1="140" y1="50" x2="150" y2="90" stroke="var(--forest)" stroke-width="2" stroke-dasharray="4 6" opacity="0.5"/>
      <line x1="180" y1="100" x2="250" y2="70" stroke="var(--signal-pink)" stroke-width="2" opacity="0" class="broken-link-svg__spark"/>
      <circle cx="40" cy="80" r="6" fill="var(--signal)"/>
      <circle cx="140" cy="50" r="6" fill="var(--forest)"/>
      <circle cx="180" cy="100" r="6" fill="var(--signal-pink)" class="broken-link-svg__node"/>
      <circle cx="250" cy="70" r="6" fill="var(--signal-pink)" opacity="0.35"/>
    </svg>
  </div>
  <div class="container">
    <span class="eyebrow"><?php echo t('error404.eyebrow'); ?></span>
    <h1 class="page-hero__title"><?php echo t('error404.title_pre'); ?><mark><?php echo t('error404.title_mark'); ?></mark><?php echo t('error404.title_post'); ?></h1>
    <p class="page-hero__lede"><?php echo t('error404.lede'); ?></p>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="grid-4">
      <a href="index.php" class="value-card reveal" style="text-decoration:none">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('error404.link_home'); ?></h3>
      </a>
      <a href="formations.php" class="value-card reveal" style="text-decoration:none">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('error404.link_courses'); ?></h3>
      </a>
      <a href="services-pc.php" class="value-card reveal" style="text-decoration:none">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('error404.link_services'); ?></h3>
      </a>
      <a href="contact.php" class="value-card reveal" style="text-decoration:none">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('error404.link_contact'); ?></h3>
      </a>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
