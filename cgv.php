<?php
require_once 'includes/i18n.php';
$page_title = t('legal_cgv.meta_title');
$page_description = t('legal_cgv.meta_description');
include 'includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow"><?php echo t('legal_common.eyebrow'); ?></span>
    <h1 class="page-hero__title"><?php echo t('legal_cgv.title'); ?></h1>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container legal">
    <p class="legal-meta"><?php echo t('legal_common.updated'); ?></p>

    <nav class="legal-nav" aria-label="Legal pages">
      <a href="mentions-legales.php" class="tag tag--ghost"><?php echo t('legal_common.nav_mentions'); ?></a>
      <a href="confidentialite.php" class="tag tag--ghost"><?php echo t('legal_common.nav_privacy'); ?></a>
      <a href="cgv.php" class="tag"><?php echo t('legal_common.nav_cgv'); ?></a>
    </nav>

    <h2><?php echo t('legal_cgv.h1'); ?></h2>
    <p><?php echo t('legal_cgv.h1_text'); ?></p>

    <h2><?php echo t('legal_cgv.h2'); ?></h2>
    <p><?php echo t('legal_cgv.h2_text'); ?></p>

    <h2><?php echo t('legal_cgv.h3'); ?></h2>
    <p><?php echo t('legal_cgv.h3_text'); ?></p>

    <h2><?php echo t('legal_cgv.h4'); ?></h2>
    <p><?php echo t('legal_cgv.h4_text'); ?></p>

    <h2><?php echo t('legal_cgv.h5'); ?></h2>
    <p><?php echo t('legal_cgv.h5_text'); ?></p>

    <h2><?php echo t('legal_cgv.h6'); ?></h2>
    <p><?php echo t('legal_cgv.h6_text'); ?></p>

    <h2><?php echo t('legal_cgv.h7'); ?></h2>
    <p><?php echo t('legal_cgv.h7_text_pre'); ?> <a href="mailto:contact@slapia.com">contact@slapia.com</a>. <?php echo t('legal_cgv.h7_text_post'); ?></p>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
