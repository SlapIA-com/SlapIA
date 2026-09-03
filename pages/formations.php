<?php
require_once __DIR__ . '/../includes/i18n.php';
$page_title = t('courses_page.meta_title');
$page_description = t('courses_page.meta_description');

$level_courses = [];
foreach ($T['levels'] as $level) {
    $level_courses[] = [
        '@type' => 'Course',
        'name' => $level['title'],
        'description' => $level['teaser'],
        'provider' => ['@id' => 'https://www.slapia.com/#organization'],
    ];
}
$jsonld = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Course',
    '@id' => 'https://www.slapia.com/formations.php#course',
    'name' => 'Formation IA Slapia — Parcours en 3 niveaux',
    'description' => t('courses_page.meta_description'),
    'provider' => ['@id' => 'https://www.slapia.com/#organization'],
    'hasPart' => $level_courses,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

include __DIR__ . '/../includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-canvas" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span></div>
  <div class="container">
    <span class="eyebrow"><?php echo t('courses_page.eyebrow'); ?></span>
    <h1 class="page-hero__title"><?php echo t('courses_page.title_pre'); ?><mark><?php echo t('courses_page.title_mark'); ?></mark><?php echo t('courses_page.title_post'); ?></h1>
    <p class="page-hero__lede"><?php echo t('courses_page.lede'); ?></p>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="grid-3">
      <?php foreach ($T['levels'] as $level): ?>
      <a href="#<?php echo htmlspecialchars($level['anchor']); ?>" class="course-card reveal" style="text-decoration:none">
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
          <span class="course-card__link"><?php echo t('courses_page.levels_cta'); ?> <span class="btn__arrow">→</span></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--paper">
  <div class="container">
    <?php foreach ($T['levels'] as $level): ?>
    <div class="level-block reveal" id="<?php echo htmlspecialchars($level['anchor']); ?>">
      <div class="level-block__head">
        <span class="level-block__num"><?php echo htmlspecialchars($level['num']); ?></span>
        <div>
          <h3><?php echo htmlspecialchars($level['detail_title']); ?></h3>
          <p><?php echo htmlspecialchars($level['detail_subtitle']); ?></p>
        </div>
      </div>
      <div class="curriculum-table-wrap">
        <table class="curriculum-table">
          <thead>
            <tr>
              <th><?php echo t('courses_page.module_label'); ?></th>
              <th><?php echo t('courses_page.theme_label'); ?></th>
              <th><?php echo t('courses_page.action_label'); ?></th>
              <th><?php echo t('courses_page.tools_label'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($level['modules'] as $module): ?>
            <tr>
              <td><?php echo htmlspecialchars($module['code']); ?></td>
              <td><?php echo htmlspecialchars($module['theme']); ?></td>
              <td><?php echo htmlspecialchars($module['desc']); ?></td>
              <td>
                <?php if (empty($module['tools'])): ?>
                  <span class="curriculum-table__none"><?php echo t('courses_page.no_tool'); ?></span>
                <?php else: ?>
                  <div class="curriculum-table__tools">
                    <?php foreach ($module['tools'] as $tool): ?>
                    <span class="tag"><?php echo htmlspecialchars($tool); ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section section--paper">
  <div class="container">
    <p style="text-align:center; color:var(--ink-fade); margin-bottom:28px">
      <?php echo t('courses_page.vip_title'); ?>
      <a href="tarifs.php#mentorat" style="color:var(--forest); text-decoration:underline; text-underline-offset:2px"><?php echo t('courses_page.vip_cta'); ?> →</a>
    </p>
    <div class="cta-band reveal">
      <h2><?php echo t('courses_page.bottom_cta_title'); ?></h2>
      <div class="cta-band__actions">
        <a href="contact.php" class="btn btn--signal"><?php echo t('courses_page.bottom_cta_btn'); ?> <span class="btn__arrow">→</span></a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
