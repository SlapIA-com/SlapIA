<?php
require_once 'includes/i18n.php';
$page_title = t('services.meta_title');
$page_description = t('services.meta_description');
include 'includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-canvas" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span></div>
  <div class="container">
    <span class="eyebrow"><?php echo t('services.eyebrow'); ?></span>
    <h1 class="page-hero__title"><?php echo t('services.title_pre'); ?> <mark><?php echo t('services.title_mark'); ?></mark></h1>
    <p class="page-hero__lede"><?php echo t('services.lede'); ?></p>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="grid-2">
      <div class="value-card reveal">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('services.audience1_title'); ?></h3>
        <p><?php echo t('services.audience1_text'); ?></p>
      </div>
      <div class="value-card reveal">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('services.audience2_title'); ?></h3>
        <p><?php echo t('services.audience2_text'); ?></p>
      </div>
    </div>
  </div>
</section>

<section class="section section--paper">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?php echo t('services.services_eyebrow'); ?></span>
        <h2 class="section-head__title"><?php echo t('services.services_title'); ?></h2>
      </div>
      <p class="section-head__note"><?php echo t('services.services_note'); ?></p>
    </div>

    <div class="grid-3">
      <div class="course-card reveal" id="montage">
        <div class="course-card__meta">
          <span class="tag tag--signal"><?php echo t('services.card1_tag'); ?></span>
          <span class="tag tag--ghost"><?php echo t('services.card1_tag2'); ?></span>
        </div>
        <h3><?php echo t('services.card1_title'); ?></h3>
        <p class="desc"><?php echo t('services.card1_desc'); ?></p>
        <div class="course-card__foot">
          <span class="course-card__price"><?php echo t('services.card1_price'); ?></span>
          <a href="contact.php" class="course-card__link"><?php echo t('services.card1_cta'); ?> <span class="btn__arrow">→</span></a>
        </div>
      </div>

      <div class="course-card reveal" id="devis">
        <div class="course-card__meta">
          <span class="tag tag--signal"><?php echo t('services.card2_tag'); ?></span>
          <span class="tag tag--ghost"><?php echo t('services.card2_tag2'); ?></span>
        </div>
        <h3><?php echo t('services.card2_title'); ?></h3>
        <p class="desc"><?php echo t('services.card2_desc'); ?></p>
        <div class="course-card__foot">
          <span class="course-card__price"><?php echo t('services.card2_price'); ?></span>
          <a href="contact.php" class="course-card__link"><?php echo t('services.card2_cta'); ?> <span class="btn__arrow">→</span></a>
        </div>
      </div>

      <div class="course-card reveal" id="diagnostic">
        <div class="course-card__meta">
          <span class="tag tag--signal"><?php echo t('services.card3_tag'); ?></span>
          <span class="tag tag--ghost"><?php echo t('services.card3_tag2'); ?></span>
        </div>
        <h3><?php echo t('services.card3_title'); ?></h3>
        <p class="desc"><?php echo t('services.card3_desc'); ?></p>
        <div class="course-card__foot">
          <span class="course-card__price"><?php echo t('services.card3_price'); ?></span>
          <a href="contact.php" class="course-card__link"><?php echo t('services.card3_cta'); ?> <span class="btn__arrow">→</span></a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?php echo t('services.method_eyebrow'); ?></span>
        <h2 class="section-head__title"><?php echo t('services.method_title'); ?></h2>
      </div>
      <p class="section-head__note"><?php echo t('services.method_note'); ?></p>
    </div>
    <div class="method-list">
      <div class="method-item reveal">
        <span class="method-item__num">01</span>
        <div><h3><?php echo t('services.method1_title'); ?></h3><p><?php echo t('services.method1_text'); ?></p></div>
      </div>
      <div class="method-item reveal">
        <span class="method-item__num">02</span>
        <div><h3><?php echo t('services.method2_title'); ?></h3><p><?php echo t('services.method2_text'); ?></p></div>
      </div>
      <div class="method-item reveal">
        <span class="method-item__num">03</span>
        <div><h3><?php echo t('services.method3_title'); ?></h3><p><?php echo t('services.method3_text'); ?></p></div>
      </div>
      <div class="method-item reveal">
        <span class="method-item__num">04</span>
        <div><h3><?php echo t('services.method4_title'); ?></h3><p><?php echo t('services.method4_text'); ?></p></div>
      </div>
    </div>
  </div>
</section>

<section class="section section--paper">
  <div class="container">
    <div class="cta-band reveal">
      <h2><?php echo t('services.cta_title'); ?></h2>
      <div class="cta-band__actions">
        <a href="contact.php" class="btn btn--signal"><?php echo t('services.cta_primary'); ?> <span class="btn__arrow">→</span></a>
        <a href="tarifs.php#pc" class="btn btn--on-dark"><?php echo t('services.cta_secondary'); ?></a>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
