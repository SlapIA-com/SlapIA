<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';
include_once '../api/notion-blog.php';

$page_title = t('blog_title') . " - SlapIA";
$page_description = t('blog_desc');
$page_image = '/assets/img/logo.png';
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
                    <a href="/rss.xml" class="btn btn-outline-warning rounded-pill px-4" target="_blank">
                        <i class="fas fa-rss me-2"></i> <?php echo t('rss_subscribe'); ?>
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
                        <div class="bento-card bento-card-glow h-100 d-flex flex-column p-0 overflow-hidden scroll-reveal <?php echo ($index % 3 == 0) ? '' : ($index % 3 == 1 ? 'delay-100' : 'delay-200'); ?>">
                            
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
                                
                                <button type="button" class="btn btn-sm btn-outline-glass align-self-start" data-bs-toggle="modal" data-bs-target="#articleModal_<?php echo $article['id']; ?>">
                                    <?php echo t('read_article'); ?> <i class="fas fa-arrow-right ms-2 fs-6"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal For Full Article Reading -->
                    <div class="modal fade blog-article-modal" id="articleModal_<?php echo $article['id']; ?>" tabindex="-1" aria-labelledby="modalLabel_<?php echo $article['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content border-0 blog-modal-content">
                                <!-- Close button — high z-index to stay above hero image -->
                                <button type="button" class="blog-modal-close" data-bs-dismiss="modal" aria-label="Close">
                                    <i class="fas fa-times"></i>
                                </button>
                                
                                <div class="modal-body p-0 blog-modal-body">
                                    <?php if (!empty($article['image'])): ?>
                                        <div class="blog-modal-hero">
                                            <div class="blog-modal-hero-gradient"></div>
                                            <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="" class="w-100 h-100" style="object-fit: cover;">
                                            
                                            <!-- Title embedded inside the image gradient -->
                                            <div class="position-absolute bottom-0 start-0 w-100 p-4 p-md-5" style="z-index: 2;">
                                                <h2 class="text-white fw-bold mb-2" id="modalLabel_<?php echo $article['id']; ?>"><?php echo htmlspecialchars($article['titre']); ?></h2>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Header for article without image -->
                                        <div class="p-4 p-md-5 border-bottom border-light border-opacity-10" style="padding-top: 80px !important; background: rgba(255,255,255,0.02);">
                                            <h2 class="text-white fw-bold mb-2" id="modalLabel_<?php echo $article['id']; ?>"><?php echo htmlspecialchars($article['titre']); ?></h2>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Author/Meta row -->
                                    <div class="px-4 px-md-5 pt-4">
                                        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom border-light border-opacity-10">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle p-1 bg-gradient-to-br from-primary to-purple d-inline-block me-2">
                                                    <img src="/assets/img/Thomas-Lapierre.jpg" alt="Author" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="text-white small fw-bold">SlapIA Team</span>
                                                    <span class="text-secondary" style="font-size: 0.75rem;"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y', strtotime($article['date'])); ?></span>
                                                </div>
                                            </div>
                                            <div class="ms-auto">
                                                <span class="badge bg-secondary bg-opacity-25 text-primary rounded-pill px-3 py-2"><i class="fas fa-clock me-1"></i> <?php echo max(1, round(str_word_count(strip_tags($article['contenu'])) / 200)); ?> min read</span>
                                            </div>
                                        </div>

                                        <!-- The content -->
                                        <div class="article-content text-light pb-5" style="line-height: 1.8; font-size: 1.08rem;">
                                            <?php echo nl2br(htmlspecialchars($article['contenu'])); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer border-top border-light border-opacity-10 bg-dark bg-opacity-25 px-4 px-md-5 py-3">
                                    <a href="#<?php echo $article['slug']; ?>" class="btn btn-outline-glass rounded-pill" onclick="navigator.clipboard.writeText(window.location.origin + '/blog#' + '<?php echo $article['slug']; ?>'); alert('<?php echo t('link_copied_to_clipboard'); ?>'); return false;">
                                        <i class="fas fa-link me-2"></i> Partager l'article
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
/* ===== Blog Card Hover ===== */
.blog-card-img-wrapper:hover img {
    transform: scale(1.08) !important;
}

/* ===== Blog Article Modal ===== */
.blog-modal-content {
    background: rgba(18, 18, 18, 0.97) !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
    border-radius: 24px !important;
    backdrop-filter: blur(40px);
    -webkit-backdrop-filter: blur(40px);
    overflow: hidden !important;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

/* Close button */
.blog-modal-close {
    position: absolute;
    right: 16px;
    top: 16px;
    z-index: 100;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(10px);
    color: #fff;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.5);
}
.blog-modal-close:hover {
    background: rgba(255,60,60,0.7);
    border-color: rgba(255,60,60,0.5);
    transform: scale(1.1);
}

/* Scrollable body */
.blog-modal-body {
    overflow-y: auto !important;
    flex: 1 1 auto;
    -webkit-overflow-scrolling: touch;
}

/* Hero image */
.blog-modal-hero {
    position: relative;
    height: 300px;
    width: 100%;
    flex-shrink: 0;
}
.blog-modal-hero-gradient {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.1) 40%, rgba(18,18,18,1) 100%);
    z-index: 1;
}

/* Fix the modal-footer to stay at bottom */
.blog-article-modal .modal-footer {
    flex-shrink: 0;
    border-radius: 0 0 24px 24px;
}

/* Article content styles */
.article-content a {
    color: var(--accent-blue);
    text-decoration: underline;
    text-underline-offset: 4px;
}
.article-content a:hover {
    color: var(--accent-purple);
    text-decoration-color: var(--accent-purple);
}
.article-content p {
    margin-bottom: 1.5rem;
}

/* Scrollbar styling for modal */
.blog-modal-body::-webkit-scrollbar {
    width: 6px;
}
.blog-modal-body::-webkit-scrollbar-track {
    background: transparent;
}
.blog-modal-body::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.15);
    border-radius: 3px;
}
.blog-modal-body::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.3);
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .blog-modal-content {
        max-height: 95vh;
        border-radius: 16px !important;
    }
    .blog-modal-hero {
        height: 200px;
    }
    .blog-modal-close {
        right: 12px;
        top: 12px;
        width: 36px;
        height: 36px;
    }
}
</style>

<script>
// Blog modals initialization — exposed globally for Swup re-init
window.initBlogModals = function() {
    if (typeof bootstrap === 'undefined') return;

    // Initialize all blog modals
    document.querySelectorAll('.blog-article-modal').forEach(function(modalEl) {
        // Destroy any stale instance first
        var existing = bootstrap.Modal.getInstance(modalEl);
        if (existing) {
            try { existing.dispose(); } catch(e) {}
        }
        // Create fresh instance
        new bootstrap.Modal(modalEl);
    });

    // Close button click handler (robust fallback)
    document.querySelectorAll('.blog-modal-close').forEach(function(btn) {
        // Remove old listeners by cloning  
        var newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);

        newBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var modal = this.closest('.modal');
            if (modal) {
                var bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                } else {
                    // Emergency fallback: remove modal manually
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                    var backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }
            }
        });
    });

    // Open button handler — use event delegation for robustness
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(function(trigger) {
        var newTrigger = trigger.cloneNode(true);
        trigger.parentNode.replaceChild(newTrigger, trigger);

        newTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('data-bs-target');
            var modalEl = document.querySelector(targetId);
            if (modalEl) {
                var bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                bsModal.show();
            }
        });
    });

    // Cleanup body scroll lock when modal is hidden
    document.querySelectorAll('.blog-article-modal').forEach(function(modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function() {
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            var backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) backdrop.remove();
        });
    });
};

// Initialize on first load (wait for Bootstrap to be available from footer)
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    // If page is already loaded (Swup navigation), init immediately
    setTimeout(window.initBlogModals, 100);
} else {
    document.addEventListener('DOMContentLoaded', function() {
        // Wait a tick for Bootstrap script from footer to load
        setTimeout(window.initBlogModals, 200);
    });
}
</script>

<?php include '../includes/footer.php'; ?>
