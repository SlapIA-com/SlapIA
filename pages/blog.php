<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';
include_once '../api/notion-blog.php';

$page_title = t('blog_title') . " - SlapIA";
$page_description = t('blog_desc');
$page_image = '/assets/img/brand/logo.png';
include '../includes/header.php';

// Fetch articles max. 100 for display
$articles = getBlogArticles(100);
?>

<section class="py-5 mt-5">
    <div class="container">
        
        <!-- Hero Blog Section -->
        <div class="row align-items-center mb-5 pb-5 border-bottom border-light border-opacity-10">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill border border-secondary border-opacity-25 mb-4 fade-in-up" 
                     style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
                    <i class="fas fa-rss text-warning"></i>
                    <span class="text-secondary small fw-medium"><?php echo t('news_and_insights'); ?></span>
                </div>
                <h1 class="display-title mb-3 fade-in-up delay-100" style="font-size: 3.5rem;">
                    <?php echo t('blog_title'); ?>
                </h1>
                <p class="text-secondary fs-5 mb-4 fade-in-up delay-200" style="max-width: 600px;">
                    <?php echo t('blog_desc'); ?>
                </p>
                <div class="fade-in-up delay-300">
                    <button type="button" class="btn btn-outline-warning rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#rssSubscribeModal">
                        <i class="fas fa-rss me-2"></i> <?php echo t('rss_subscribe'); ?>
                    </button>
                    <a href="/rss.xml" class="d-inline-flex align-items-center ms-3 text-secondary text-decoration-none hover-white small fade-in" target="_blank" title="Lien direct vers le flux">
                        <i class="fas fa-link me-1"></i> Flux direct
                    </a>
                </div>
            </div>
            
            <div class="col-lg-4 text-center mt-5 mt-lg-0 fade-in-up delay-300">
                 <!-- Decorative RSS Element -->
                 <div class="position-relative d-inline-block">
                    <!-- Glow behind -->
                    <div class="position-absolute top-50 start-50 translate-middle"
                         style="width: 200px; height: 200px; background: rgba(255,193,7,0.3); filter: blur(60px); opacity: 0.6;">
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center position-relative" 
                         style="width: 140px; height: 140px; background: linear-gradient(135deg, rgba(255,193,7,0.1), rgba(255,87,34,0.05)); border: 1px solid rgba(255,193,7,0.3);">
                         <i class="fas fa-satellite-dish fa-3x text-warning opacity-75"></i>
                    </div>
                 </div>
            </div>
        </div>

        <!-- Articles Grid -->
        <div class="row g-4 mb-5">
            <?php if (empty($articles)): ?>
                <div class="col-12 text-center py-5 fade-in-up">
                    <div class="bento-card p-5 mx-auto" style="max-width: 500px;">
                         <i class="fas fa-folder-open fa-3x text-secondary mb-3 opacity-50"></i>
                         <h4 class="text-white"><?php echo t('no_articles_yet'); ?></h4>
                         <p class="text-secondary mb-0"><?php echo t('coming_soon_articles'); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($articles as $index => $article): ?>
                    <!-- Article Card -->
                    <div class="col-lg-4 col-md-6" id="<?php echo $article['slug']; ?>">
                        <div class="bento-card bento-card-glow h-100 d-flex flex-column p-0 overflow-hidden scroll-reveal blog-read-btn <?php echo ($index % 3 == 0) ? '' : ($index % 3 == 1 ? 'delay-100' : 'delay-200'); ?>"
                            style="cursor: pointer;"
                            data-article-title="<?php echo htmlspecialchars($article['titre'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-article-image="<?php echo htmlspecialchars($article['image'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-article-date="<?php echo date('d M Y', strtotime($article['date'])); ?>"
                            data-article-readtime="<?php echo max(1, round(str_word_count(strip_tags($article['contenu'])) / 200)); ?>"
                            data-article-content="<?php echo htmlspecialchars($article['contenu'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-article-slug="<?php echo htmlspecialchars($article['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                            
                            <!-- Internal glow orbs -->
                            <div class="card-glow-orb orb-purple" style="opacity: 0.2"></div>

                            <?php if (!empty($article['image'])): ?>
                                <div class="blog-card-img-wrapper" style="height: 220px; overflow: hidden; position: relative;">
                                    <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="<?php echo htmlspecialchars($article['titre']); ?>" class="w-100 h-100" style="object-fit: cover; transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);">
                                    <div class="position-absolute bottom-0 start-0 w-100 p-3" style="background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0) 100%);">
                                        <span class="badge bg-primary bg-opacity-75 border border-primary border-opacity-50 rounded-pill"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y', strtotime($article['date'])); ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="p-3 border-bottom border-light border-opacity-10" style="background: rgba(255,255,255,0.02);">
                                    <span class="badge bg-secondary bg-opacity-50 text-white rounded-pill"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y', strtotime($article['date'])); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="p-4 d-flex flex-column flex-grow-1 position-relative" style="z-index: 2;">
                                <h4 class="text-white mb-3 fw-bold" style="font-size: 1.3rem; line-height: 1.4;"><?php echo htmlspecialchars($article['titre']); ?></h4>
                                <p class="text-secondary small mb-4 flex-grow-1 text-opacity-75">
                                    <?php 
                                        $desc = !empty($article['extrait']) ? $article['extrait'] : strip_tags($article['contenu']);
                                        echo mb_strimwidth($desc, 0, 160, "...");
                                    ?>
                                </p>
                                
                                <span class="btn btn-sm btn-outline-glass align-self-start">
                                    <?php echo t('read_article'); ?> <i class="fas fa-arrow-right ms-2 fs-6"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Hidden data for share text -->
<span id="blog-share-text" style="display:none;"><?php echo htmlspecialchars(t('link_copied_to_clipboard'), ENT_QUOTES, 'UTF-8'); ?></span>


<?php include '../includes/footer.php'; ?>
