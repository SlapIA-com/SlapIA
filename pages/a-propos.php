<?php
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/stats.php';
$page_title = t('about.meta_title');
$page_description = t('about.meta_description');
$page_image = 'assets/img/team/Thomas-Lapierre.jpg';

$jsonld = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Person',
    'name' => t('about.founder_name'),
    'jobTitle' => t('about.founder_role'),
    'image' => 'https://www.slapia.com/assets/img/team/Thomas-Lapierre.jpg',
    'worksFor' => ['@id' => 'https://www.slapia.com/#organization'],
    'hasCredential' => [
        [
            '@type' => 'EducationalOccupationalCredential',
            'name' => t('about.cert1_title'),
            'credentialCategory' => 'certificate',
            'recognizedBy' => ['@type' => 'Organization', 'name' => 'Hoshin Partners'],
        ],
        [
            '@type' => 'EducationalOccupationalCredential',
            'name' => t('about.cert2_title'),
            'credentialCategory' => 'certificate',
            'recognizedBy' => ['@type' => 'Organization', 'name' => 'Hoshin Partners'],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

include __DIR__ . '/../includes/header.php';

$stats = getSlapiaStats();
$decimal_sep = $lang === 'en' ? '.' : ',';
$stat1_html = statNumHtml($stats['is_live'] ? (float)$stats['entreprises'] : null, 0, '+', t('about.stat1_num'), $decimal_sep);
$stat2_html = statNumHtml($stats['is_live'] ? (float)$stats['particuliers'] : null, 0, '+', t('about.stat2_num'), $decimal_sep);
$stat3_html = statNumHtml((float)count($T['levels']), 0, '', t('about.stat3_num'), $decimal_sep);
$stat4_html = statNumHtml(($stats['is_live'] && $stats['satisfaction'] !== null) ? (float)$stats['satisfaction'] : null, 1, '/5', t('about.stat4_num'), $decimal_sep);
?>

<section class="page-hero">
  <div class="page-hero-canvas" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span></div>
  <div class="container">
    <span class="eyebrow"><?php echo t('about.eyebrow'); ?></span>
    <h1 class="page-hero__title"><?php echo t('about.title_pre'); ?><mark><?php echo t('about.title_mark'); ?></mark><?php echo t('about.title_post'); ?></h1>
    <p class="page-hero__lede"><?php echo t('about.lede'); ?></p>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="about-stats">
      <div class="about-stat reveal">
        <?php echo $stat1_html; ?>
        <div class="stat__label"><?php echo t('about.stat1_label'); ?></div>
      </div>
      <div class="about-stat reveal">
        <?php echo $stat2_html; ?>
        <div class="stat__label"><?php echo t('about.stat2_label'); ?></div>
      </div>
      <div class="about-stat reveal">
        <?php echo $stat3_html; ?>
        <div class="stat__label"><?php echo t('about.stat3_label'); ?></div>
      </div>
      <div class="about-stat reveal">
        <?php echo $stat4_html; ?>
        <div class="stat__label"><?php echo t('about.stat4_label'); ?></div>
      </div>
    </div>
  </div>
</section>

<section class="section section--paper">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?php echo t('about.philosophy_eyebrow'); ?></span>
        <h2 class="section-head__title"><?php echo t('about.philosophy_title'); ?></h2>
      </div>
    </div>
    <div class="grid-3">
      <div class="value-card reveal">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('about.value1_title'); ?></h3>
        <p><?php echo t('about.value1_text'); ?></p>
      </div>
      <div class="value-card reveal">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('about.value2_title'); ?></h3>
        <p><?php echo t('about.value2_text'); ?></p>
      </div>
      <div class="value-card reveal">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('about.value3_title'); ?></h3>
        <p><?php echo t('about.value3_text'); ?></p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?php echo t('about.team_eyebrow'); ?></span>
        <h2 class="section-head__title"><?php echo t('about.team_title'); ?></h2>
      </div>
    </div>
    <div class="founder reveal">
      <div class="founder__avatar">
        <img src="assets/img/team/Thomas-Lapierre.jpg" alt="<?php echo htmlspecialchars(t('about.founder_name')); ?>">
      </div>
      <div>
        <h3><?php echo t('about.founder_name'); ?></h3>
        <span class="founder__role"><?php echo t('about.founder_role'); ?></span>
        <p class="bio"><?php echo t('about.founder_bio'); ?></p>
      </div>
    </div>

    <div class="cert-grid">
      <a href="assets/img/certifications/Formation_iA_Niveau_1_Entreprise.jpg" target="_blank" rel="noopener" class="cert-card reveal">
        <img src="assets/img/certifications/Formation_iA_Niveau_1_Entreprise.jpg" alt="<?php echo htmlspecialchars(t('about.cert1_title')); ?>">
        <div class="cert-card__body">
          <div class="cert-card__title"><?php echo t('about.cert1_title'); ?></div>
          <div class="cert-card__meta"><?php echo t('about.cert1_meta'); ?></div>
        </div>
      </a>
      <a href="assets/img/certifications/Formation_iA_Niveau_2_Entreprise.jpg" target="_blank" rel="noopener" class="cert-card reveal">
        <img src="assets/img/certifications/Formation_iA_Niveau_2_Entreprise.jpg" alt="<?php echo htmlspecialchars(t('about.cert2_title')); ?>">
        <div class="cert-card__body">
          <div class="cert-card__title"><?php echo t('about.cert2_title'); ?></div>
          <div class="cert-card__meta"><?php echo t('about.cert2_meta'); ?></div>
        </div>
      </a>
    </div>
  </div>
</section>

<section class="section section--paper">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?php echo t('about.timeline_eyebrow'); ?></span>
        <h2 class="section-head__title"><?php echo t('about.timeline_title'); ?></h2>
      </div>
    </div>
    <div class="timeline">
      <div class="timeline-item reveal">
        <time><?php echo t('about.tl1_year'); ?></time>
        <div><h4><?php echo t('about.tl1_title'); ?></h4><p><?php echo t('about.tl1_text'); ?></p></div>
      </div>
      <div class="timeline-item reveal">
        <time><?php echo t('about.tl2_year'); ?></time>
        <div><h4><?php echo t('about.tl2_title'); ?></h4><p><?php echo t('about.tl2_text'); ?></p></div>
      </div>
      <div class="timeline-item reveal">
        <time><?php echo t('about.tl3_year'); ?></time>
        <div><h4><?php echo t('about.tl3_title'); ?></h4><p><?php echo t('about.tl3_text'); ?></p></div>
      </div>
      <div class="timeline-item reveal">
        <time><?php echo t('about.tl4_year'); ?></time>
        <div><h4><?php echo t('about.tl4_title'); ?></h4><p><?php echo t('about.tl4_text'); ?></p></div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-band reveal">
      <h2><?php echo t('about.cta_title'); ?></h2>
      <div class="cta-band__actions">
        <a href="contact.php" class="btn btn--signal"><?php echo t('about.cta_btn'); ?> <span class="btn__arrow">→</span></a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
