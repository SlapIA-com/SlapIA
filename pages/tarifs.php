<?php
require_once __DIR__ . '/../includes/i18n.php';
$page_title = t('pricing.meta_title');
$page_description = t('pricing.meta_description');
include __DIR__ . '/../includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-canvas" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span></div>
  <div class="container">
    <span class="eyebrow"><?php echo t('pricing.eyebrow'); ?></span>
    <h1 class="page-hero__title"><?php echo t('pricing.title_pre'); ?><mark><?php echo t('pricing.title_mark'); ?></mark><?php echo t('pricing.title_post'); ?></h1>
    <p class="page-hero__lede"><?php echo t('pricing.lede'); ?></p>
  </div>
</section>

<section class="section" style="padding-top:0" id="formations">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?php echo t('pricing.formations_eyebrow'); ?></span>
        <h2 class="section-head__title"><?php echo t('pricing.formations_title'); ?></h2>
      </div>
      <p class="section-head__note"><?php echo t('courses_page.vip_lede'); ?></p>
    </div>

    <div class="grid-4" style="margin-bottom:40px">
      <div class="value-card reveal">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('courses_page.vip_f1_title'); ?></h3>
        <p><?php echo t('courses_page.vip_f1_text'); ?></p>
      </div>
      <div class="value-card reveal">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('courses_page.vip_f2_title'); ?></h3>
        <p><?php echo t('courses_page.vip_f2_text'); ?></p>
      </div>
      <div class="value-card reveal">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('courses_page.vip_f3_title'); ?></h3>
        <p><?php echo t('courses_page.vip_f3_text'); ?></p>
      </div>
      <div class="value-card reveal">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('courses_page.vip_f4_title'); ?></h3>
        <p><?php echo t('courses_page.vip_f4_text'); ?></p>
      </div>
    </div>

    <div class="pricing-grid pricing-grid--pair" id="mentorat">
      <div class="price-card price-card--featured reveal">
        <div class="price-card__name"><?php echo t('pricing.tier3_name'); ?></div>
        <div class="price-card__desc"><?php echo t('pricing.tier3_desc'); ?></div>
        <div class="price-card__price"><?php echo t('pricing.tier3_price'); ?></div>
        <div class="price-card__unit"><?php echo t('pricing.tier3_unit'); ?></div>
        <hr class="divider">
        <ul class="price-card__features">
          <li><?php echo t('pricing.tier3_f1'); ?></li>
          <li><?php echo t('pricing.tier3_f2'); ?></li>
          <li><?php echo t('pricing.tier3_f3'); ?></li>
          <li><?php echo t('pricing.tier3_f4'); ?></li>
        </ul>
        <a href="contact.php" class="btn btn--signal btn--block"><?php echo t('pricing.tier3_cta'); ?></a>
      </div>

      <div class="price-card price-card--featured reveal">
        <div class="price-card__name"><?php echo t('courses_page.vip_price_name'); ?></div>
        <div class="price-card__desc"><?php echo t('courses_page.vip_subtitle'); ?></div>
        <div class="price-card__price"><?php echo t('courses_page.vip_price'); ?></div>
        <div class="price-card__unit"><?php echo t('courses_page.vip_unit'); ?></div>
        <hr class="divider">
        <a href="contact.php" class="btn btn--signal btn--block"><?php echo t('courses_page.vip_cta'); ?></a>
      </div>
    </div>
  </div>
</section>

<section class="section section--paper" id="pc">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?php echo t('pricing.pc_eyebrow'); ?></span>
        <h2 class="section-head__title"><?php echo t('pricing.pc_title'); ?></h2>
      </div>
      <p class="section-head__note"><?php echo t('pricing.pc_note'); ?></p>
    </div>
    <div class="pricing-grid">

      <div class="price-card reveal">
        <div class="price-card__name"><?php echo t('pricing.pctier1_name'); ?></div>
        <div class="price-card__desc"><?php echo t('pricing.pctier1_desc'); ?></div>
        <div class="price-card__price"><?php echo t('pricing.pctier1_price'); ?></div>
        <div class="price-card__unit"><?php echo t('pricing.pctier1_unit'); ?></div>
        <hr class="divider">
        <ul class="price-card__features">
          <li><?php echo t('pricing.pctier1_f1'); ?></li>
          <li><?php echo t('pricing.pctier1_f2'); ?></li>
          <li><?php echo t('pricing.pctier1_f3'); ?></li>
          <li><?php echo t('pricing.pctier1_f4'); ?></li>
        </ul>
        <a href="contact.php" class="btn btn--ghost btn--block"><?php echo t('pricing.pctier1_cta'); ?></a>
      </div>

      <div class="price-card price-card--featured reveal">
        <span class="price-card__badge"><?php echo t('pricing.pctier2_badge'); ?></span>
        <div class="price-card__name"><?php echo t('pricing.pctier2_name'); ?></div>
        <div class="price-card__desc"><?php echo t('pricing.pctier2_desc'); ?></div>
        <div class="price-card__price"><?php echo t('pricing.pctier2_price'); ?></div>
        <div class="price-card__unit"><?php echo t('pricing.pctier2_unit'); ?></div>
        <hr class="divider">
        <ul class="price-card__features">
          <li><?php echo t('pricing.pctier2_f1'); ?></li>
          <li><?php echo t('pricing.pctier2_f2'); ?></li>
          <li><?php echo t('pricing.pctier2_f3'); ?></li>
          <li><?php echo t('pricing.pctier2_f4'); ?></li>
        </ul>
        <a href="contact.php" class="btn btn--signal btn--block"><?php echo t('pricing.pctier2_cta'); ?></a>
      </div>

      <div class="price-card reveal">
        <div class="price-card__name"><?php echo t('pricing.pctier3_name'); ?></div>
        <div class="price-card__desc"><?php echo t('pricing.pctier3_desc'); ?></div>
        <div class="price-card__price"><?php echo t('pricing.pctier3_price'); ?></div>
        <div class="price-card__unit"><?php echo t('pricing.pctier3_unit'); ?></div>
        <hr class="divider">
        <ul class="price-card__features">
          <li><?php echo t('pricing.pctier3_f1'); ?></li>
          <li><?php echo t('pricing.pctier3_f2'); ?></li>
          <li><?php echo t('pricing.pctier3_f3'); ?></li>
          <li><?php echo t('pricing.pctier3_f4'); ?></li>
        </ul>
        <a href="contact.php" class="btn btn--ghost btn--block"><?php echo t('pricing.pctier3_cta'); ?></a>
      </div>

    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?php echo t('pricing.faq_eyebrow'); ?></span>
        <h2 class="section-head__title"><?php echo t('pricing.faq_title'); ?></h2>
      </div>
    </div>

    <div>
      <details class="faq-item" open>
        <summary><?php echo t('pricing.faq1_q'); ?></summary>
        <div class="faq-item__body"><?php echo t('pricing.faq1_a'); ?></div>
      </details>
      <details class="faq-item">
        <summary><?php echo t('pricing.faq2_q'); ?></summary>
        <div class="faq-item__body"><?php echo t('pricing.faq2_a'); ?></div>
      </details>
      <details class="faq-item">
        <summary><?php echo t('pricing.faq3_q'); ?></summary>
        <div class="faq-item__body"><?php echo t('pricing.faq3_a'); ?></div>
      </details>
      <details class="faq-item">
        <summary><?php echo t('pricing.faq4_q'); ?></summary>
        <div class="faq-item__body"><?php echo t('pricing.faq4_a'); ?></div>
      </details>
      <details class="faq-item">
        <summary><?php echo t('pricing.faq5_q'); ?></summary>
        <div class="faq-item__body"><?php echo t('pricing.faq5_a'); ?></div>
      </details>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-band reveal">
      <h2><?php echo t('pricing.cta_title'); ?></h2>
      <div class="cta-band__actions">
        <a href="contact.php" class="btn btn--signal"><?php echo t('pricing.cta_btn'); ?> <span class="btn__arrow">→</span></a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
