<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';
include_once '../api/notion-blog.php';

// Sanitize slug from URL
$slug = preg_replace('/[^a-zA-Z0-9-]/', '', $_GET['slug'] ?? '');

if (empty($slug)) {
    header('Location: /blog');
    exit;
}

// Fetch all articles and find by slug
$articles = getBlogArticles(100);
$article = null;
foreach ($articles as $a) {
    if ($a['slug'] === $slug) {
        $article = $a;
        break;
    }
}

if (!$article) {
    http_response_code(404);
    header('Location: /blog');
    exit;
}

// Fetch full content via Notion blocks
$fullContent = getNotionBlocks($article['id']);
if (empty($fullContent) && !empty($article['contenu'])) {
    $fullContent = '<p>' . nl2br(htmlspecialchars($article['contenu'])) . '</p>';
}

$readTime = max(1, round(str_word_count(strip_tags($fullContent ?: $article['contenu'])) / 200));

$page_title = $article['titre'] . " - SlapIA";
$page_description = !empty($article['extrait'])
    ? mb_strimwidth($article['extrait'], 0, 160, '...')
    : mb_strimwidth(strip_tags($article['contenu']), 0, 160, '...');
$page_image = $article['image'] ?: '/assets/img/brand/logo.png';

include '../includes/header.php';
?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "<?php echo htmlspecialchars($article['titre'], ENT_QUOTES); ?>",
  "datePublished": "<?php echo $article['date']; ?>",
  "author": {
    "@type": "Person",
    "name": "Thomas Lapierre",
    "url": "https://www.slapia.com/expertise"
  },
  "publisher": {
    "@type": "Organization",
    "name": "SlapIA",
    "logo": { "@type": "ImageObject", "url": "https://www.slapia.com/assets/img/brand/logo.svg" }
  }
  <?php if ($article['image']): ?>
  ,"image": "<?php echo htmlspecialchars($article['image'], ENT_QUOTES); ?>"
  <?php endif; ?>
}
</script>

<section class="py-5 mt-5">
    <div class="container">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-5">
            <ol class="breadcrumb" style="background:none; padding:0;">
                <li class="breadcrumb-item">
                    <a href="/" class="text-secondary text-decoration-none small"><i class="fas fa-home me-1"></i>Accueil</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="/blog" class="text-secondary text-decoration-none small">Blog</a>
                </li>
                <li class="breadcrumb-item active text-white small" aria-current="page">
                    <?php echo htmlspecialchars(mb_strimwidth($article['titre'], 0, 50, '...')); ?>
                </li>
            </ol>
        </nav>

        <div class="row justify-content-center">
            <div class="col-lg-8">

                <!-- Article Hero Image -->
                <?php if ($article['image']): ?>
                <div class="mb-5 rounded-3 overflow-hidden fade-in-up" style="max-height: 450px;">
                    <img src="<?php echo htmlspecialchars($article['image']); ?>"
                         alt="<?php echo htmlspecialchars($article['titre']); ?>"
                         class="w-100" style="object-fit: cover; height: 450px;">
                </div>
                <?php endif; ?>

                <!-- Title & Meta -->
                <div class="mb-5 fade-in-up delay-100">
                    <h1 class="fw-bold text-white mb-4" style="font-size: clamp(1.8rem, 4vw, 2.8rem); line-height: 1.25;">
                        <?php echo htmlspecialchars($article['titre']); ?>
                    </h1>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <img src="/assets/img/team/Thomas-Lapierre.jpg" alt="Thomas Lapierre"
                             style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid rgba(99,102,241,0.4);">
                        <div>
                            <div class="text-white small fw-bold">Thomas LAPIERRE</div>
                            <div class="text-secondary" style="font-size:0.8rem;">
                                <i class="far fa-calendar-alt me-1"></i><?php echo date('d M Y', strtotime($article['date'])); ?>
                                &nbsp;·&nbsp;
                                <i class="fas fa-clock me-1"></i><?php echo $readTime; ?> min read
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="bento-card p-4 p-md-5 mb-5 fade-in-up delay-200">
                    <?php if (!empty($article['extrait'])): ?>
                    <p class="article-excerpt mb-4 pb-4" style="font-size:1.1rem; color:rgba(255,255,255,0.8); font-style:italic; border-bottom:1px solid rgba(255,255,255,0.08);">
                        <?php echo htmlspecialchars($article['extrait']); ?>
                    </p>
                    <?php endif; ?>
                    <div class="article-content">
                        <?php echo $fullContent ?: '<p class="text-secondary">Contenu non disponible.</p>'; ?>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-5 fade-in-up delay-300">
                    <a href="/blog" class="btn btn-outline-glass rounded-pill px-4">
                        <i class="fas fa-arrow-left me-2"></i><?php echo t('blog_title'); ?>
                    </a>
                    <button type="button" class="btn btn-outline-glass rounded-pill px-4" onclick="
                        navigator.clipboard.writeText(window.location.href).then(function() {
                            this.innerHTML = '<i class=\'fas fa-check me-2\'></i>Lien copié !';
                            setTimeout(function() { document.querySelector('#share-btn').innerHTML = '<i class=\'fas fa-link me-2\'></i>Partager'; }, 2000);
                        }.bind(this));
                    " id="share-btn">
                        <i class="fas fa-link me-2"></i>Partager
                    </button>
                </div>

            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
