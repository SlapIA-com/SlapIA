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

<!-- RSS Subscribe Modal -->
<div class="modal fade" id="rssSubscribeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: rgba(18, 18, 18, 0.95); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(20px); border-radius: 20px;">
      <div class="modal-header border-bottom border-light border-opacity-10 position-relative p-4">
        <h5 class="modal-title text-warning fw-bold"><i class="fas fa-rss me-2"></i> <?php echo t('rss_subscribe'); ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; right: 20px; top: 20px;"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <p class="text-white-50 mb-4">Laissez votre e-mail pour être prévenu de nos prochains articles et actualités exclusives.</p>
        <form id="rss-subscribe-form">
          <input type="email" id="rss-email-input" class="form-control mb-3 text-white fw-medium" placeholder="votre.email@exemple.com" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff !important;">
          <style>
              #rss-email-input::placeholder { color: rgba(255,255,255,0.4); }
              #rss-email-input:-webkit-autofill,
              #rss-email-input:-webkit-autofill:hover, 
              #rss-email-input:-webkit-autofill:focus {
                  -webkit-text-fill-color: white;
                  -webkit-box-shadow: 0 0 0px 1000px rgba(18,18,18,0.95) inset;
                  transition: background-color 5000s ease-in-out 0s;
              }
          </style>
          
          <!-- Cloudflare Turnstile -->
          <div class="d-flex justify-content-center mb-3">
              <div id="cf-turnstile-rss" data-sitekey="<?php echo config('TURNSTILE_SITE_KEY'); ?>"></div>
          </div>

          <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold" id="rss-submit-btn">
            M'inscrire
          </button>
        </form>
        <div id="rss-subscribe-msg" class="mt-3 small" style="display:none;"></div>
      </div>
    </div>
  </div>
</div>

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
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
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
    position: relative;
    width: 100%;
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
    transform: scale(0.95);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 10;
}
.article-overlay.active .article-overlay-panel {
    transform: scale(1);
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
    min-height: 0;
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
.article-overlay-content-wrap {
    padding: 1.5rem 2rem;
}
.article-overlay .article-content {
    line-height: 1.8;
    font-size: 1.08rem;
    color: rgba(255,255,255,0.85);
    word-wrap: break-word;
    overflow-wrap: break-word;
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
    .article-overlay {
        padding: 0;
    }
    .article-overlay-panel {
        width: 100%;
        height: 100%;
        max-height: 100%;
        border-radius: 0;
        border: none;
        transform: scale(0.98);
    }
    .article-overlay.active .article-overlay-panel {
        transform: scale(1);
    }
    .article-overlay-hero {
        height: 200px;
    }
    .article-overlay-close {
        right: 12px;
        top: 12px;
        width: 36px;
        height: 36px;
        background: rgba(0,0,0,0.8);
    }
    .article-overlay-hero-title {
        padding: 1rem 1.25rem;
    }
    .article-overlay-content-wrap {
        padding: 1.25rem;
    }
    .article-overlay-footer {
        padding: 12px 1.25rem;
        border-radius: 0;
        padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px));
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
        
        // Remove hash from URL without reloading
        if (window.location.hash) {
            history.replaceState('', document.title, window.location.pathname + window.location.search);
        }

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

        // Update URL hash dynamically when opening the article
        if (slug && ('#' + slug) !== window.location.hash) {
            history.replaceState(null, null, '#' + slug);
        }

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
                    '<div class="article-overlay-content-wrap">' +
                        '<div style="display:flex;align-items:center;gap:12px;margin-bottom:1rem;padding-bottom:0.75rem;border-bottom:1px solid rgba(255,255,255,0.1);flex-wrap:wrap;">' +
                            '<div style="display:flex;align-items:center;">' +
                                '<img src="/assets/img/team/Thomas-Lapierre.jpg" alt="Author" style="width:36px;height:36px;border-radius:50%;object-fit:cover;margin-right:10px;">' +
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
                var shareUrl = window.location.origin + '/blog#' + slug;
                if (navigator.share) {
                    navigator.share({
                        title: title,
                        text: 'Retrouvez cet article de SlapIA : ' + title,
                        url: shareUrl
                    }).catch(function(err) {
                        console.error('Erreur de partage :', err);
                    });
                } else {
                    navigator.clipboard.writeText(shareUrl);
                    alert(window._blogShareText || shareText || 'Lien copié !');
                }
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

    // Initialize - just get share text and handle deep linking
    window.initBlogOverlay = function() {
        var shareEl = document.getElementById('blog-share-text');
        if (shareEl) shareText = shareEl.textContent.trim();
        window._blogShareText = shareText;

        // Turnstile robust initialization
        window.initRssTurnstile = function() {
            var container = document.getElementById('cf-turnstile-rss');
            if (!container || typeof turnstile === 'undefined') return;
            var sitekey = container.getAttribute('data-sitekey');
            if (container.querySelector('iframe')) return; // Already rendered
            try { turnstile.remove(container); } catch(e) {}
            try { turnstile.render(container, { sitekey: sitekey, theme: 'dark' }); } catch (e) {}
        };

        // Safely move the RSS modal to document.body to avoid z-index/transform trapping issues
        var rssModal = document.getElementById('rssSubscribeModal');
        if (rssModal && rssModal.parentNode !== document.body) {
            document.body.appendChild(rssModal);
        }

        // RSS Form handling
        var form = document.getElementById('rss-subscribe-form');
        if (form && !form.dataset.initialized) {
            form.dataset.initialized = 'true';

            // Initialiser Turnstile quand la modal s'ouvre, pour garantir que le DOM est prêt
            var modalEl = document.getElementById('rssSubscribeModal');
            if (modalEl) {
                modalEl.addEventListener('show.bs.modal', function () {
                    window.initRssTurnstile();
                    setTimeout(window.initRssTurnstile, 500);
                });
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var email = document.getElementById('rss-email-input').value;
                var btn = document.getElementById('rss-submit-btn');
                var msg = document.getElementById('rss-subscribe-msg');
                
                var formData = new FormData(form);
                var cfResponse = formData.get('cf-turnstile-response');

                if (!cfResponse) {
                    msg.style.display = 'block';
                    msg.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Veuillez valider le Captcha Cloudflare.</span>';
                    return;
                }
                
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> En cours...';
                btn.disabled = true;
                msg.style.display = 'none';

                fetch('/api/subscribe-rss.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        email: email,
                        'cf-turnstile-response': cfResponse
                    })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    btn.disabled = false;
                    btn.innerHTML = 'M\'inscrire';
                    msg.style.display = 'block';
                    if(data.success) {
                        msg.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Inscription réussie ! Merci.</span>';
                        form.reset();
                        setTimeout(function() {
                            var modalEl = document.getElementById('rssSubscribeModal');
                            if (window.bootstrap && bootstrap.Modal) {
                                var modal = bootstrap.Modal.getInstance(modalEl);
                                if (modal) modal.hide();
                            }
                        }, 2000);
                    } else {
                        msg.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> ' + (data.error || 'Erreur inconnue') + '</span>';
                    }
                })
                .catch(function(err) {
                    btn.disabled = false;
                    btn.innerHTML = 'M\'inscrire';
                    msg.style.display = 'block';
                    msg.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Erreur de connexion au serveur.</span>';
                });
            });
        }

        // Auto-open article if a valid hash is present in the URL
        if (window.location.hash) {
            var inputSlug = window.location.hash.substring(1);
            if (/^[a-zA-Z0-9_-]+$/.test(inputSlug)) {
                var articleWrapper = document.getElementById(inputSlug);
                if (articleWrapper) {
                    var articleBtn = articleWrapper.classList.contains('blog-read-btn') ? articleWrapper : articleWrapper.querySelector('.blog-read-btn');
                    if (articleBtn) {
                        // Slight delay to ensure DOM and styles are fully ready
                        setTimeout(function() {
                            if (window._blogOpenArticle) {
                                window._blogOpenArticle(articleBtn);
                            }
                        }, 200);
                    }
                }
            }
        }
    };

    // Expose openArticle to the global delegated listener
    window._blogOpenArticle = openArticle;

    // Attach exactly one delegated listener to the document body
    // This survives Swup transitions seamlessly!
    if (!window._blogDelegatedListenerAdded) {
        document.body.addEventListener('click', function(e) {
            var btn = e.target.closest('.blog-read-btn');
            if (btn) {
                e.preventDefault();
                if (window._blogOpenArticle) {
                    window._blogOpenArticle(btn);
                }
            }
        });
        window._blogDelegatedListenerAdded = true;
    }

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

        // Force cleanup the RSS modal and its backdrop if moved to body
        var rssModal = document.getElementById('rssSubscribeModal');
        if (rssModal) {
            var backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(function(b) { b.remove(); });
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
            if (rssModal.parentNode) rssModal.parentNode.removeChild(rssModal);
        }
    };
})();
</script>

<?php include '../includes/footer.php'; ?>
