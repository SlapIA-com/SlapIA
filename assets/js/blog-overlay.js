/**
 * Blog Article Overlay System
 * Global script to handle article interactivity across Swup transitions.
 */
(function() {
    'use strict';

    var overlayEl = null;
    var shareText = 'Lien copié !';

    /**
     * Create or retrieve the overlay container on document.body
     */
    function getOrCreateOverlay() {
        if (overlayEl && document.body.contains(overlayEl)) return overlayEl;
        
        overlayEl = document.createElement('div');
        overlayEl.className = 'article-overlay';
        overlayEl.id = 'blog-article-overlay';
        document.body.appendChild(overlayEl);
        return overlayEl;
    }

    /**
     * Close the article overlay
     */
    window.closeBlogOverlay = function() {
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
    };

    /**
     * Safely escape HTML
     */
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    /**
     * Safely escape Attribute
     */
    function escapeAttr(str) {
        if (!str) return '';
        return str.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    /**
     * Open an article based on card data
     */
    window.openBlogArticle = function(btn) {
        if (!btn) return;
        
        var title = btn.getAttribute('data-article-title') || '';
        var image = btn.getAttribute('data-article-image') || '';
        var date = btn.getAttribute('data-article-date') || '';
        var readtime = btn.getAttribute('data-article-readtime') || '1';
        var content = btn.getAttribute('data-article-content') || '';
        var slug = btn.getAttribute('data-article-slug') || '';
        var id = btn.getAttribute('data-article-id') || '';

        // Update URL hash dynamically
        if (slug && ('#' + slug) !== window.location.hash) {
            history.replaceState(null, null, '#' + slug);
        }

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

        // initial content (summary)
        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = content;
        var cleanContent = tempDiv.textContent || tempDiv.innerText || '';
        var contentHtml = escapeHtml(cleanContent).replace(/\n/g, '<br>');

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
                        '<div class="article-content" style="padding-bottom:2rem;">' + 
                            '<div class="article-content-loader" style="display:none; text-align:center; padding: 2rem;"><i class="fas fa-spinner fa-spin text-primary"></i></div>' +
                            '<div class="article-content-body">' + contentHtml + '</div>' + 
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="article-overlay-footer">' +
                    '<a href="#' + escapeAttr(slug) + '" class="btn btn-outline-glass rounded-pill" style="font-size:0.85rem;" id="blog-share-btn">' +
                        '<i class="fas fa-link" style="margin-right:8px;"></i>Partager l\'article' +
                    '</a>' +
                '</div>' +
            '</div>';

        // Attempt to load full content if ID is available
        if (id) {
            var contentBody = overlay.querySelector('.article-content-body');
            var loader = overlay.querySelector('.article-content-loader');
            
            // Show loader if we're going to fetch
            loader.style.display = 'block';
            contentBody.style.opacity = '0.5';

            fetch('/api/notion-blog.php?id=' + id)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    loader.style.display = 'none';
                    contentBody.style.opacity = '1';
                    if (data && data.content) {
                        // Replace double newlines with <p> equivalent or just nl2br
                        var fullHtml = escapeHtml(data.content).replace(/\n\n/g, '</div><div style="margin-bottom:1.2rem;">').replace(/\n/g, '<br>');
                        contentBody.innerHTML = '<div style="margin-bottom:1.2rem;">' + fullHtml + '</div>';
                    }
                })
                .catch(function(err) {
                    console.error('Error fetching full content:', err);
                    loader.style.display = 'none';
                    contentBody.style.opacity = '1';
                });
        }

        // Attach event listeners
        overlay.querySelector('.article-overlay-backdrop').addEventListener('click', window.closeBlogOverlay);
        overlay.querySelector('.article-overlay-close').addEventListener('click', window.closeBlogOverlay);

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
                    alert(shareText);
                }
            });
        }

        // Lock body scroll and show
        document.body.style.overflow = 'hidden';
        // Force reflow
        overlay.offsetHeight;
        overlay.classList.add('active');
    };

    /**
     * Initializer called by Swup or on page load
     */
    window.initBlogOverlay = function() {
        var shareEl = document.getElementById('blog-share-text');
        if (shareEl) shareText = shareEl.textContent.trim();

        // Auto-open article if a valid hash is present
        if (window.location.hash) {
            var inputSlug = window.location.hash.substring(1);
            if (/^[a-zA-Z0-9_-]+$/.test(inputSlug)) {
                var articleWrapper = document.getElementById(inputSlug);
                if (articleWrapper) {
                    var articleBtn = articleWrapper.classList.contains('blog-read-btn') ? articleWrapper : articleWrapper.querySelector('.blog-read-btn');
                    if (articleBtn) {
                        setTimeout(function() {
                            window.openBlogArticle(articleBtn);
                        }, 200);
                    }
                }
            }
        }
    };

    /**
     * Cleanup called before navigation
     */
    window.cleanupBlogOverlay = function() {
        window.closeBlogOverlay();
        if (overlayEl && document.body.contains(overlayEl)) {
            document.body.removeChild(overlayEl);
            overlayEl = null;
        }
    };

    // Attach delegated listener exactly once
    if (!window._blogDelegatedListenerAdded) {
        document.body.addEventListener('click', function(e) {
            var btn = e.target.closest('.blog-read-btn');
            if (btn) {
                e.preventDefault();
                window.openBlogArticle(btn);
            }
        });
        
        // Escape key to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') window.closeBlogOverlay();
        });

        window._blogDelegatedListenerAdded = true;
    }

    // Auto-init on first load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initBlogOverlay);
    } else {
        // Use a slight timeout to ensure DOM (even if parsed) is fully ready
        setTimeout(window.initBlogOverlay, 100);
    }

})();
