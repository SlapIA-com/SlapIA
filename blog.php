<?php
require_once 'includes/i18n.php';
require_once 'includes/notion-blog.php';

$page_title = t('blog.meta_title');
$page_description = t('blog.meta_description');

$articles = listBlogArticles();

include 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo assetUrl('assets/css/blog.css'); ?>">

<section class="page-hero">
  <div class="page-hero-canvas" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span></div>
  <div class="container">
    <span class="eyebrow"><?php echo t('blog.eyebrow'); ?></span>
    <h1 class="page-hero__title"><?php echo t('blog.title_pre'); ?><mark><?php echo t('blog.title_mark'); ?></mark></h1>
    <p class="page-hero__lede"><?php echo t('blog.lede'); ?></p>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <?php if (empty($articles)): ?>
      <p class="blog-empty"><?php echo t('blog.empty'); ?></p>
    <?php else: ?>
      <div class="blog-grid">
        <?php foreach ($articles as $article): ?>
          <a href="/blog/<?php echo htmlspecialchars($article['slug']); ?>" class="blog-card reveal">
            <?php if ($article['image']): ?>
              <div class="blog-card__image">
                <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="" loading="lazy">
              </div>
            <?php endif; ?>
            <div class="blog-card__body">
              <span class="blog-card__date"><?php echo date('d M Y', strtotime($article['date'])); ?></span>
              <h2 class="blog-card__title"><?php echo htmlspecialchars($article['title']); ?></h2>
              <p class="blog-card__excerpt"><?php echo htmlspecialchars(mb_strimwidth($article['excerpt'], 0, 160, '...')); ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
