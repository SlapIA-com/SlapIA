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
                                
                                <button type="button" class="btn btn-sm btn-outline-glass align-self-start blog-read-btn"
                                    data-article-title="<?php echo htmlspecialchars($article['titre'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-article-image="<?php echo htmlspecialchars($article['image'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-article-date="<?php echo date('d M Y', strtotime($article['date'])); ?>"
                                    data-article-readtime="<?php echo max(1, round(str_word_count(strip_tags($article['contenu'])) / 200)); ?>"
                                    data-article-content="<?php echo htmlspecialchars($article['contenu'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-article-slug="<?php echo htmlspecialchars($article['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo t('read_article'); ?> <i class="fas fa-arrow-right ms-2 fs-6"></i>
                                </button>
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

<style>
/* ===== Blog Card Hover ===== */
.blog-card-img-wrapper:hover img {
    transform: scale(1.08) !important;
}

/* ===== Article Overlay (injected on body, OUTSIDE #swup) ===== */
.article-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 99990;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}
.article-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* Backdrop */
.article-overlay-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    cursor: pointer;
}

/* Panel (the card itself) */
.article-overlay-panel {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.95);
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    background: rgba(18, 18, 18, 0.97);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 24px;
    backdrop-filter: blur(40px);
    -webkit-backdrop-filter: blur(40px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.article-overlay.active .article-overlay-panel {
    transform: translate(-50%, -50%) scale(1);
}

/* Close button */
.article-overlay-close {
    position: absolute;
    right: 16px;
    top: 16px;
    z-index: 10;
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
.article-overlay-close:hover {
    background: rgba(255,60,60,0.7);
    border-color: rgba(255,60,60,0.5);
    transform: scale(1.1);
}

/* Scrollable body */
.article-overlay-body {
    overflow-y: auto;
    flex: 1 1 auto;
    -webkit-overflow-scrolling: touch;
}

/* Hero image */
.article-overlay-hero {
    position: relative;
    height: 300px;
    width: 100%;
    flex-shrink: 0;
}
.article-overlay-hero img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.article-overlay-hero-gradient {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.1) 40%, rgba(18,18,18,1) 100%);
    z-index: 1;
}
.article-overlay-hero-title {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 1.5rem 2rem;
    z-index: 2;
}

/* Footer */
.article-overlay-footer {
    flex-shrink: 0;
    padding: 12px 2rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.25);
    border-radius: 0 0 24px 24px;
}

/* Article content styles */
.article-overlay .article-content {
    line-height: 1.8;
    font-size: 1.08rem;
    color: rgba(255,255,255,0.85);
}
.article-overlay .article-content a {
    color: var(--accent-blue, #2997ff);
    text-decoration: underline;
    text-underline-offset: 4px;
}
.article-overlay .article-content a:hover {
    color: var(--accent-purple, #bf5af2);
}

/* Scrollbar styling */
.article-overlay-body::-webkit-scrollbar {
    width: 6px;
}
.article-overlay-body::-webkit-scrollbar-track {
    background: transparent;
}
.article-overlay-body::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.15);
    border-radius: 3px;
}
.article-overlay-body::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.3);
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .article-overlay-panel {
        width: 95%;
        max-height: 95vh;
        border-radius: 16px;
    }
    .article-overlay-hero {
        height: 200px;
    }
    .article-overlay-close {
        right: 12px;
        top: 12px;
        width: 36px;
        height: 36px;
    }
    .article-overlay-hero-title {
        padding: 1rem 1.25rem;
    }
    .article-overlay-footer {
        padding: 10px 1.25rem;
        border-radius: 0 0 16px 16px;
    }
}
</style>

<script>
/**
 * Blog Article Overlay — renders OUTSIDE #swup on document.body
 * to bypass the CSS transform stacking context that breaks position:fixed.
 */
(function() {
    'use strict';

    var overlayEl = null;
    var shareText = '';

    function getOrCreateOverlay() {
        if (overlayEl && document.body.contains(overlayEl)) return overlayEl;
        // Create if missing
        overlayEl = document.createElement('div');
        overlayEl.className = 'article-overlay';
        overlayEl.id = 'blog-article-overlay';
        document.body.appendChild(overlayEl);
        return overlayEl;
    }

    function closeOverlay() {
        if (!overlayEl) return;
        overlayEl.classList.remove('active');
        document.body.style.overflow = '';
        // After transition, clear content
        setTimeout(function() {
            if (overlayEl) overlayEl.innerHTML = '';
        }, 300);
    }

    function openArticle(btn) {
        var title = btn.getAttribute('data-article-title') || '';
        var image = btn.getAttribute('data-article-image') || '';
        var date = btn.getAttribute('data-article-date') || '';
        var readtime = btn.getAttribute('data-article-readtime') || '1';
        var content = btn.getAttribute('data-article-content') || '';
        var slug = btn.getAttribute('data-article-slug') || '';

        // Convert newlines to <br> for display
        var contentHtml = content.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#039;/g, "'");
        // Re-escape for safety then nl2br
        contentHtml = escapeHtml(content).replace(/\n/g, '<br>');

        var overlay = getOrCreateOverlay();

        // Build hero section
        var heroHtml = '';
        if (image) {
            heroHtml = '<div class="article-overlay-hero">' +
                '<div class="article-overlay-hero-gradient"></div>' +
                '<img src="' + escapeAttr(image) + '" alt="">' +
                '<div class="article-overlay-hero-title">' +
                '<h2 style="color:#fff;font-weight:700;margin:0;">' + escapeHtml(title) + '</h2>' +
                '</div></div>';
        } else {
            heroHtml = '<div style="padding: 60px 2rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02);">' +
                '<h2 style="color:#fff;font-weight:700;margin:0;">' + escapeHtml(title) + '</h2>' +
                '</div>';
        }

        overlay.innerHTML =
            '<div class="article-overlay-backdrop"></div>' +
            '<div class="article-overlay-panel">' +
                '<button type="button" class="article-overlay-close" aria-label="Close"><i class="fas fa-times"></i></button>' +
                '<div class="article-overlay-body">' +
                    heroHtml +
                    '<div style="padding: 1.5rem 2rem;">' +
                        '<div style="display:flex;align-items:center;gap:12px;margin-bottom:1rem;padding-bottom:0.75rem;border-bottom:1px solid rgba(255,255,255,0.1);">' +
                            '<div style="display:flex;align-items:center;">' +
                                '<img src="/assets/img/Thomas-Lapierre.jpg" alt="Author" style="width:36px;height:36px;border-radius:50%;object-fit:cover;margin-right:10px;">' +
                                '<div>' +
                                    '<div style="color:#fff;font-size:0.85rem;font-weight:700;">SlapIA Team</div>' +
                                    '<div style="color:rgba(255,255,255,0.5);font-size:0.75rem;"><i class="far fa-calendar-alt" style="margin-right:4px;"></i>' + escapeHtml(date) + '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div style="margin-left:auto;">' +
                                '<span style="background:rgba(255,255,255,0.08);color:var(--accent-blue,#2997ff);padding:6px 14px;border-radius:20px;font-size:0.8rem;"><i class="fas fa-clock" style="margin-right:4px;"></i>' + escapeHtml(readtime) + ' min read</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="article-content" style="padding-bottom:2rem;">' + contentHtml + '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="article-overlay-footer">' +
                    '<a href="#' + escapeAttr(slug) + '" class="btn btn-outline-glass rounded-pill" style="font-size:0.85rem;" id="blog-share-btn">' +
                        '<i class="fas fa-link" style="margin-right:8px;"></i>Partager l\'article' +
                    '</a>' +
                '</div>' +
            '</div>';

        // Attach event listeners
        overlay.querySelector('.article-overlay-backdrop').addEventListener('click', closeOverlay);
        overlay.querySelector('.article-overlay-close').addEventListener('click', closeOverlay);

        var shareBtn = overlay.querySelector('#blog-share-btn');
        if (shareBtn) {
            shareBtn.addEventListener('click', function(e) {
                e.preventDefault();
                navigator.clipboard.writeText(window.location.origin + '/blog#' + slug);
                alert(shareText || 'Lien copié !');
            });
        }

        // Lock body scroll and show
        document.body.style.overflow = 'hidden';
        // Force reflow before adding class for animation
        overlay.offsetHeight;
        overlay.classList.add('active');
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escapeAttr(str) {
        return str.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // Initialize — attach click handlers to all read buttons
    window.initBlogOverlay = function() {
        // Get share text
        var shareEl = document.getElementById('blog-share-text');
        if (shareEl) shareText = shareEl.textContent.trim();

        document.querySelectorAll('.blog-read-btn').forEach(function(btn) {
            // Clone to remove old listeners
            var newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                openArticle(this);
            });
        });
    };

    // Escape key to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeOverlay();
    });

    // Init on load
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(window.initBlogOverlay, 100);
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(window.initBlogOverlay, 200);
        });
    }

    // Cleanup when Swup navigates away
    window.cleanupBlogOverlay = function() {
        closeOverlay();
        if (overlayEl && document.body.contains(overlayEl)) {
            document.body.removeChild(overlayEl);
            overlayEl = null;
        }
    };
})();
</script>

<?php include '../includes/footer.php'; ?>
