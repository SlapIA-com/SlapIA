<?php
require_once __DIR__ . '/../includes/i18n.php';
$page_title = t('legal_privacy.meta_title');
$page_description = t('legal_privacy.meta_description');
include __DIR__ . '/../includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow"><?php echo t('legal_common.eyebrow'); ?></span>
    <h1 class="page-hero__title"><?php echo t('legal_privacy.title'); ?></h1>
    <p class="page-hero__lede"><?php echo t('legal_privacy.lede'); ?></p>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container legal">
    <p class="legal-meta"><?php echo t('legal_common.updated'); ?></p>

    <nav class="legal-nav" aria-label="Legal pages">
      <a href="mentions-legales.php" class="tag tag--ghost"><?php echo t('legal_common.nav_mentions'); ?></a>
      <a href="confidentialite.php" class="tag"><?php echo t('legal_common.nav_privacy'); ?></a>
      <a href="cgv.php" class="tag tag--ghost"><?php echo t('legal_common.nav_cgv'); ?></a>
    </nav>

    <h2><?php echo t('legal_privacy.h1'); ?></h2>
    <p><?php echo t('legal_privacy.h1_text'); ?> <a href="mailto:contact@slapia.com">contact@slapia.com</a>.</p>

    <h2><?php echo t('legal_privacy.h2'); ?></h2>
    <p><?php echo t('legal_privacy.h2_text'); ?></p>
    <ul>
      <li><?php echo t('legal_privacy.h2_li1'); ?></li>
      <li><?php echo t('legal_privacy.h2_li2'); ?></li>
      <li><?php echo t('legal_privacy.h2_li3'); ?></li>
      <li><?php echo t('legal_privacy.h2_li4'); ?></li>
    </ul>
    <p><?php echo t('legal_privacy.h2_text2'); ?></p>

    <h2><?php echo t('legal_privacy.h3'); ?></h2>
    <p><?php echo t('legal_privacy.h3_text'); ?></p>
    <ul>
      <li><?php echo t('legal_privacy.h3_li1'); ?></li>
      <li><?php echo t('legal_privacy.h3_li2'); ?></li>
      <li><?php echo t('legal_privacy.h3_li3'); ?></li>
      <li><?php echo t('legal_privacy.h3_li4'); ?></li>
    </ul>
    <p><?php echo t('legal_privacy.h3_text2'); ?></p>

    <h2><?php echo t('legal_privacy.h4'); ?></h2>
    <p><?php echo t('legal_privacy.h4_text'); ?></p>

    <h2><?php echo t('legal_privacy.h5'); ?></h2>
    <p><?php echo t('legal_privacy.h5_text'); ?></p>

    <h2><?php echo t('legal_privacy.h6'); ?></h2>
    <p><?php echo t('legal_privacy.h6_text'); ?></p>

    <h2><?php echo t('legal_privacy.h7'); ?></h2>
    <p><?php echo t('legal_privacy.h7_text'); ?></p>

    <h2><?php echo t('legal_privacy.h8'); ?></h2>
    <p><?php echo t('legal_privacy.h8_text_pre'); ?> <a href="mailto:contact@slapia.com">contact@slapia.com</a><?php echo t('legal_privacy.h8_text_mid'); ?><a href="https://www.cnil.fr" target="_blank" rel="noopener">www.cnil.fr</a>).</p>

    <h2><?php echo t('legal_privacy.h9'); ?></h2>
    <p><?php echo t('legal_privacy.h9_text'); ?></p>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
