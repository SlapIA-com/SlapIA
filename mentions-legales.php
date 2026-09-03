<?php
require_once 'includes/i18n.php';
$page_title = t('legal_mentions.meta_title');
$page_description = t('legal_mentions.meta_description');
include 'includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow"><?php echo t('legal_common.eyebrow'); ?></span>
    <h1 class="page-hero__title"><?php echo t('legal_mentions.title'); ?></h1>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container legal">
    <p class="legal-meta"><?php echo t('legal_common.updated'); ?></p>

    <nav class="legal-nav" aria-label="Legal pages">
      <a href="mentions-legales.php" class="tag"><?php echo t('legal_common.nav_mentions'); ?></a>
      <a href="confidentialite.php" class="tag tag--ghost"><?php echo t('legal_common.nav_privacy'); ?></a>
      <a href="cgv.php" class="tag tag--ghost"><?php echo t('legal_common.nav_cgv'); ?></a>
    </nav>

    <h2><?php echo t('legal_mentions.h1'); ?></h2>
    <table class="legal-table">
      <tr><th><?php echo t('legal_mentions.row_denomination'); ?></th><td>SlapIA</td></tr>
      <tr><th><?php echo t('legal_mentions.row_forme'); ?></th><td><?php echo t('legal_mentions.val_forme'); ?></td></tr>
      <tr><th><?php echo t('legal_mentions.row_siren'); ?></th><td>100 946 722</td></tr>
      <tr><th><?php echo t('legal_mentions.row_siret'); ?></th><td>100 946 722 00012</td></tr>
      <tr><th><?php echo t('legal_mentions.row_ape'); ?></th><td><?php echo t('legal_mentions.val_ape'); ?></td></tr>
      <tr><th><?php echo t('legal_mentions.row_tva'); ?></th><td><?php echo t('legal_mentions.val_tva'); ?></td></tr>
      <tr><th><?php echo t('legal_mentions.row_directeur'); ?></th><td>Thomas Lapierre</td></tr>
      <tr><th><?php echo t('legal_mentions.row_contact'); ?></th><td><a href="mailto:contact@slapia.com">contact@slapia.com</a></td></tr>
    </table>
    <p><?php echo t('legal_mentions.dev_note'); ?></p>

    <h2><?php echo t('legal_mentions.h2'); ?></h2>
    <table class="legal-table">
      <tr><th><?php echo t('legal_mentions.row_hebergeur'); ?></th><td>GNL Solution</td></tr>
      <tr><th><?php echo t('legal_mentions.row_adresse'); ?></th><td>20 rue Gustave Courbet, 25000 Besançon</td></tr>
      <tr><th><?php echo t('legal_mentions.row_contact'); ?></th><td><a href="mailto:contact@gnl-solution.fr">contact@gnl-solution.fr</a></td></tr>
      <tr><th><?php echo t('legal_mentions.row_telephone'); ?></th><td>03 65 67 01 69</td></tr>
    </table>
    <p><?php echo t('legal_mentions.infra_note'); ?></p>

    <h2><?php echo t('legal_mentions.h3'); ?></h2>
    <p><?php echo t('legal_mentions.h3_text'); ?></p>

    <h2><?php echo t('legal_mentions.h4'); ?></h2>
    <p><?php echo t('legal_mentions.h4_text'); ?></p>

    <h2><?php echo t('legal_mentions.h5'); ?></h2>
    <p><?php echo t('legal_mentions.h5_text_pre'); ?> <a href="confidentialite.php"><?php echo t('legal_mentions.h5_link1'); ?></a><?php echo t('legal_mentions.h5_text_mid'); ?> <a href="cgv.php"><?php echo t('legal_mentions.h5_link2'); ?></a>.</p>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
