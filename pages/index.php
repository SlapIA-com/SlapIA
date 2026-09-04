<?php
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/stats.php';
require_once __DIR__ . '/../includes/reviews.php'; // getClientReviews() — MySQL, plus Notion
$page_title = t('home.meta_title');
$page_description = t('home.meta_description');

$stats = getSlapiaStats();
$decimal_sep = $lang === 'en' ? '.' : ',';
$stat1_html = statNumHtml($stats['is_live'] ? (float)$stats['entreprises'] : null, 0, '+', t('home.stat1_num'), $decimal_sep);
$stat2_html = statNumHtml($stats['is_live'] ? (float)$stats['particuliers'] : null, 0, '+', t('home.stat2_num'), $decimal_sep);
$stat3_html = statNumHtml(($stats['is_live'] && $stats['satisfaction'] !== null) ? (float)$stats['satisfaction'] : null, 1, '/5', t('home.stat3_num'), $decimal_sep);

$reviews = getClientReviews(12);

function renderStarsHtml(?float $note): string
{
    $filled = $note !== null ? (int)round($note) : 0;
    $filled = max(0, min(5, $filled));
    return str_repeat('★', $filled) . str_repeat('☆', 5 - $filled);
}

$course_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Course',
    '@id' => 'https://www.slapia.com/formations.php#course',
    'name' => 'Formation IA Slapia — Parcours en 3 niveaux',
    'description' => t('courses_page.meta_description'),
    'provider' => ['@id' => 'https://www.slapia.com/#organization'],
];
if ($stats['is_live'] && $stats['satisfaction'] !== null && $stats['satisfaction_count'] > 0) {
    $course_schema['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => (string)$stats['satisfaction'],
        'bestRating' => '5',
        'reviewCount' => $stats['satisfaction_count'],
    ];
}
if (!empty($reviews)) {
    $reviewItems = [];
    foreach ($reviews as $r) {
        if ($r['note'] === null) continue;
        $reviewItems[] = [
            '@type' => 'Review',
            'author' => ['@type' => 'Person', 'name' => trim($r['prenom'] . ' ' . $r['nom'])],
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (string)round($r['note'], 1), 'bestRating' => '5'],
            'reviewBody' => $r['avis'],
        ];
    }
    if (!empty($reviewItems)) $course_schema['review'] = $reviewItems;
}
$jsonld = json_encode($course_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

include __DIR__ . '/../includes/header.php';
?>

<section class="hero">
  <div class="hero-canvas" aria-hidden="true"></div>
  <div class="container hero__grid">
    <div>
      <span class="eyebrow hero-reveal" style="transition-delay:0.05s"><?php echo t('home.hero_eyebrow'); ?></span>
      <h1 class="hero__title hero-reveal" style="transition-delay:0.15s"><?php echo t('home.hero_title_line1'); ?><br><mark><?php echo t('home.hero_title_mark'); ?></mark></h1>
      <p class="hero__lede hero-reveal" style="transition-delay:0.28s"><?php echo t('home.hero_lede'); ?></p>
      <div class="hero__ctas hero-reveal" style="transition-delay:0.4s">
        <a href="formations.php" class="btn btn--primary"><?php echo t('home.cta_primary'); ?> <span class="btn__arrow">→</span></a>
        <a href="contact.php" class="btn btn--ghost"><?php echo t('home.cta_secondary'); ?></a>
      </div>
      <div class="hero__stats hero-reveal" style="transition-delay:0.5s">
        <div class="stat">
          <?php echo $stat1_html; ?>
          <div class="stat__label"><?php echo t('home.stat1_label'); ?></div>
        </div>
        <div class="stat">
          <?php echo $stat2_html; ?>
          <div class="stat__label"><?php echo t('home.stat2_label'); ?></div>
        </div>
        <div class="stat">
          <?php echo $stat3_html; ?>
          <div class="stat__label"><?php echo t('home.stat3_label'); ?></div>
        </div>
      </div>
    </div>

    <div class="hero__panel hero-reveal hero-reveal--panel" style="transition-delay:0.35s">
      <span class="hero__panel-label"><?php echo t('home.panel_label'); ?></span>
      <div class="hero__panel-list">
        <div class="hero__panel-item">
          <span class="hero__panel-tag"><?php echo t('home.panel1_tag'); ?></span>
          <div><strong><?php echo t('home.panel1_title'); ?></strong><span><?php echo t('home.panel1_meta'); ?></span></div>
        </div>
        <div class="hero__panel-item">
          <span class="hero__panel-tag"><?php echo t('home.panel2_tag'); ?></span>
          <div><strong><?php echo t('home.panel2_title'); ?></strong><span><?php echo t('home.panel2_meta'); ?></span></div>
        </div>
        <div class="hero__panel-item">
          <span class="hero__panel-tag"><?php echo t('home.panel3_tag'); ?></span>
          <div><strong><?php echo t('home.panel3_title'); ?></strong><span><?php echo t('home.panel3_meta'); ?></span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section--dark">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?php echo t('home.problem_eyebrow'); ?></span>
        <h2 class="section-head__title"><?php echo t('home.problem_title'); ?></h2>
      </div>
      <p class="section-head__note"><?php echo t('home.problem_note'); ?></p>
    </div>
    <div class="grid-3">
      <div class="value-card" style="background:transparent;border-color:rgba(255,255,255,0.15);color:var(--on-dark)">
        <div class="value-card__icon" style="background:rgba(255,255,255,0.08)">⚠</div>
        <h3 style="color:var(--on-dark)"><?php echo t('home.problem_card1_title'); ?></h3>
        <p style="color:rgba(245,242,250,0.65)"><?php echo t('home.problem_card1_text'); ?></p>
      </div>
      <div class="value-card" style="background:transparent;border-color:rgba(255,255,255,0.15);color:var(--on-dark)">
        <div class="value-card__icon" style="background:rgba(255,255,255,0.08)">⏱</div>
        <h3 style="color:var(--on-dark)"><?php echo t('home.problem_card2_title'); ?></h3>
        <p style="color:rgba(245,242,250,0.65)"><?php echo t('home.problem_card2_text'); ?></p>
      </div>
      <div class="value-card" style="background:transparent;border-color:rgba(255,255,255,0.15);color:var(--on-dark)">
        <div class="value-card__icon" style="background:rgba(255,255,255,0.08)">◎</div>
        <h3 style="color:var(--on-dark)"><?php echo t('home.problem_card3_title'); ?></h3>
        <p style="color:rgba(245,242,250,0.65)"><?php echo t('home.problem_card3_text'); ?></p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?php echo t('home.catalogue_eyebrow'); ?></span>
        <h2 class="section-head__title"><?php echo t('home.catalogue_title'); ?></h2>
      </div>
      <p class="section-head__note"><?php echo t('home.catalogue_note'); ?></p>
    </div>

    <div class="grid-3">
      <?php foreach ($T['levels'] as $level): ?>
      <a href="formations.php#<?php echo htmlspecialchars($level['anchor']); ?>" class="course-card reveal" style="text-decoration:none">
        <div class="course-card__meta">
          <span class="tag tag--signal">Niveau <?php echo htmlspecialchars($level['num']); ?></span>
        </div>
        <h3><?php echo htmlspecialchars($level['title']); ?></h3>
        <p class="desc"><?php echo htmlspecialchars($level['teaser']); ?></p>
        <div class="course-card__meta">
          <?php foreach ($level['tools'] as $tool): ?>
          <span class="tag tag--ghost"><?php echo htmlspecialchars($tool); ?></span>
          <?php endforeach; ?>
        </div>
        <div class="course-card__foot course-card__foot--end">
          <span class="course-card__link"><?php echo t('home.catalogue_link'); ?> <span class="btn__arrow">→</span></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--paper">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?php echo t('home.method_eyebrow'); ?></span>
        <h2 class="section-head__title"><?php echo t('home.method_title'); ?></h2>
      </div>
      <p class="section-head__note"><?php echo t('home.method_note'); ?></p>
    </div>
    <div class="method-list">
      <div class="method-item reveal">
        <span class="method-item__num">01</span>
        <div><h3><?php echo t('home.method1_title'); ?></h3><p><?php echo t('home.method1_text'); ?></p></div>
      </div>
      <div class="method-item reveal">
        <span class="method-item__num">02</span>
        <div><h3><?php echo t('home.method2_title'); ?></h3><p><?php echo t('home.method2_text'); ?></p></div>
      </div>
      <div class="method-item reveal">
        <span class="method-item__num">03</span>
        <div><h3><?php echo t('home.method3_title'); ?></h3><p><?php echo t('home.method3_text'); ?></p></div>
      </div>
      <div class="method-item reveal">
        <span class="method-item__num">04</span>
        <div><h3><?php echo t('home.method4_title'); ?></h3><p><?php echo t('home.method4_text'); ?></p></div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?php echo t('home.testimonials_eyebrow'); ?></span>
        <h2 class="section-head__title"><?php echo t('home.testimonials_title'); ?></h2>
      </div>
      <?php if (!empty($reviews)): ?>
      <div class="reviews-nav">
        <button id="prev-review" class="nav-btn" aria-label="Précédent" type="button">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <button id="next-review" class="nav-btn" aria-label="Suivant" type="button">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </button>
      </div>
      <?php else: ?>
      <p class="section-head__note"><?php echo t('home.testimonials_note'); ?></p>
      <?php endif; ?>
    </div>

    <?php if (!empty($reviews)): ?>
    <div class="reviews-marquee">
      <div class="reviews-inner">
        <div class="reviews-track">
          <?php foreach ($reviews as $r):
            $name = trim($r['prenom'] . ' ' . $r['nom']);
            $initials = strtoupper((($r['prenom'][0] ?? '')) . (($r['nom'][0] ?? '')));
            $avatarSrc = 'api/avatar.php?id=' . urlencode((string)$r['client_id']);
          ?>
          <div class="review-item">
            <div class="review-header">
              <div class="review-avatar">
                <?php if ($avatarSrc): ?>
                  <img src="<?php echo htmlspecialchars($avatarSrc); ?>" alt="" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                  <span style="display:none"><?php echo htmlspecialchars($initials); ?></span>
                <?php else: ?>
                  <?php echo htmlspecialchars($initials); ?>
                <?php endif; ?>
              </div>
              <div class="review-info">
                <?php if ($r['linkedin']): ?>
                  <a href="<?php echo htmlspecialchars($r['linkedin']); ?>" target="_blank" rel="noopener noreferrer" class="review-name"><?php echo htmlspecialchars($name); ?></a>
                <?php else: ?>
                  <span class="review-name"><?php echo htmlspecialchars($name); ?></span>
                <?php endif; ?>
                <?php if ($r['profession']): ?>
                <div class="review-profession"><?php echo htmlspecialchars($r['profession']); ?><?php if ($r['status'] === 'Entreprise' && $r['entreprise']): ?> <span class="company-name">· <?php echo htmlspecialchars($r['entreprise']); ?></span><?php endif; ?></div>
                <?php endif; ?>
              </div>
            </div>
            <div class="review-content-scroll">
              <p class="review-text"><?php echo htmlspecialchars($r['avis']); ?></p>
            </div>
            <div class="review-stars"><?php echo renderStarsHtml($r['note']); ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="grid-3">
      <div class="quote-card reveal">
        <p>« <?php echo t('home.quote1_text'); ?> »</p>
        <div class="quote-card__who">
          <span class="quote-card__avatar">MC</span>
          <div><strong><?php echo t('home.quote1_name'); ?></strong><span><?php echo t('home.quote1_role'); ?></span></div>
        </div>
      </div>
      <div class="quote-card reveal">
        <p>« <?php echo t('home.quote2_text'); ?> »</p>
        <div class="quote-card__who">
          <span class="quote-card__avatar">JL</span>
          <div><strong><?php echo t('home.quote2_name'); ?></strong><span><?php echo t('home.quote2_role'); ?></span></div>
        </div>
      </div>
      <div class="quote-card reveal">
        <p>« <?php echo t('home.quote3_text'); ?> »</p>
        <div class="quote-card__who">
          <span class="quote-card__avatar">SB</span>
          <div><strong><?php echo t('home.quote3_name'); ?></strong><span><?php echo t('home.quote3_role'); ?></span></div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section--paper">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?php echo t('home.pc_teaser_eyebrow'); ?></span>
        <h2 class="section-head__title"><?php echo t('home.pc_teaser_title'); ?></h2>
      </div>
      <p class="section-head__note"><?php echo t('home.pc_teaser_note'); ?></p>
    </div>
    <div class="grid-3">
      <div class="value-card reveal">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('home.pc_card1_title'); ?></h3>
        <p><?php echo t('home.pc_card1_text'); ?></p>
      </div>
      <div class="value-card reveal">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('home.pc_card2_title'); ?></h3>
        <p><?php echo t('home.pc_card2_text'); ?></p>
      </div>
      <div class="value-card reveal">
        <div class="value-card__icon">◆</div>
        <h3><?php echo t('home.pc_card3_title'); ?></h3>
        <p><?php echo t('home.pc_card3_text'); ?></p>
      </div>
    </div>
    <div style="margin-top:32px">
      <a href="services-pc.php" class="btn btn--ghost"><?php echo t('home.pc_teaser_link'); ?> <span class="btn__arrow">→</span></a>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-band reveal">
      <h2><?php echo t('home.final_cta_title'); ?></h2>
      <div class="cta-band__actions">
        <a href="contact.php" class="btn btn--signal"><?php echo t('home.final_cta_primary'); ?> <span class="btn__arrow">→</span></a>
        <a href="tarifs.php" class="btn btn--on-dark"><?php echo t('home.final_cta_secondary'); ?></a>
      </div>
    </div>
  </div>
</section>

<script src="<?php echo assetUrl('assets/js/hero-canvas.js'); ?>" defer></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
