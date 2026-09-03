<?php
require_once 'includes/i18n.php';
require_once 'includes/notion-blog.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
    header('Location: /404');
    exit;
}

$article = getBlogArticleBySlug($slug);
if ($article === null) {
    header('Location: /404');
    exit;
}

$page_title = $article['title'];
$page_description = mb_strimwidth($article['excerpt'], 0, 160, '...');
$page_canonical = 'https://www.slapia.com/blog/' . $article['slug'];
$page_path = 'blog/' . $article['slug'];
if ($article['image']) {
    $page_image = $article['image'];
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo assetUrl('/assets/css/blog.css'); ?>">

<section class="page-hero">
  <div class="container">
    <span class="eyebrow"><?php echo date('d M Y', strtotime($article['date'])); ?></span>
    <h1 class="page-hero__title"><?php echo htmlspecialchars($article['title']); ?></h1>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container blog-article-layout">
    <?php if ($article['image']): ?>
      <div class="blog-article__cover">
        <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="" loading="lazy">
      </div>
    <?php endif; ?>
    <div class="blog-article__content">
      <?php echo $article['content']; ?>
    </div>
    <a href="/blog.php" class="btn btn--ghost blog-article__back">← <?php echo t('blog.back_to_list'); ?></a>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
