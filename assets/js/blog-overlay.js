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
     * Lightweight Markdown → HTML for blog fallback content
     */
    function markdownToHtml(md) {
        if (!md) return '';
        var lines = md.split('\n');
        var html = '', inOl = false, inUl = false;

        function closeList() {
            if (inOl) { html += '</ol>'; inOl = false; }
            if (inUl) { html += '</ul>'; inUl = false; }
        }

        function fmt(t) {
            t = t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            t = t.replace(/`([^`]+)`/g,'<code style="background:rgba(255,255,255,0.08);padding:2px 6px;border-radius:4px;font-family:monospace;font-size:.9em;">$1</code>');
            t = t.replace(/\*\*\*(.*?)\*\*\*/g,'<strong><em>$1</em></strong>');
            t = t.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>');
            t = t.replace(/(?<!\*)\*(?!\s|\*)(.*?)(?<!\s)\*(?!\*)/g,'<em>$1</em>');
            t = t.replace(/#([\wÀ-ÿ]+)/g,'<span style="display:inline-block;background:rgba(108,0,255,.15);border:1px solid rgba(108,0,255,.3);color:rgba(180,130,255,.9);border-radius:999px;padding:2px 10px;font-size:.8em;margin:2px;">&#x23;$1</span>');
            return t;
        }

        for (var i = 0; i < lines.length; i++) {
            var line = lines[i], tr = line.trim();
            if (!tr) { closeList(); continue; }
            if (/^### /.test(tr)) { closeList(); html += '<h3 style="font-size:1.15rem;font-weight:700;color:#fff;margin:1.8rem 0 .6rem;">'+fmt(tr.slice(4))+'</h3>'; continue; }
            if (/^## /.test(tr))  { closeList(); html += '<h2 style="font-size:1.35rem;font-weight:700;color:#fff;margin:2rem 0 .75rem;">'+fmt(tr.slice(3))+'</h2>'; continue; }
            if (/^# /.test(tr))   { closeList(); html += '<h1 style="font-size:1.6rem;font-weight:800;color:#fff;margin:2rem 0 1rem;">'+fmt(tr.slice(2))+'</h1>'; continue; }
            if (/^(-{3,}|\*{3,})$/.test(tr)) { closeList(); html += '<hr style="border:0;border-top:1px solid rgba(255,255,255,.1);margin:2rem 0;">'; continue; }
            if (/^> /.test(tr)) { closeList(); html += '<blockquote style="border-left:3px solid rgba(108,0,255,.6);margin:1rem 0;padding:.5rem 0 .5rem 1.25rem;color:rgba(255,255,255,.75);font-style:italic;">'+fmt(tr.slice(2))+'</blockquote>'; continue; }
            var olM = tr.match(/^(\d+)\.\s+(.*)/);
            if (olM) { if (inUl){html+='</ul>';inUl=false;} if (!inOl){html+='<ol style="margin:.75rem 0 .75rem 1.5rem;padding:0;color:rgba(255,255,255,.85);line-height:1.8;">';inOl=true;} html += '<li style="margin-bottom:.5rem;">'+fmt(olM[2])+'</li>'; continue; }
            var ulM = tr.match(/^[-*•]\s+(.*)/);
            if (ulM) { if (inOl){html+='</ol>';inOl=false;} if (!inUl){html+='<ul style="margin:.75rem 0 .75rem 1.5rem;padding:0;list-style:none;color:rgba(255,255,255,.85);line-height:1.8;">';inUl=true;} html += '<li style="margin-bottom:.4rem;display:flex;gap:8px;"><span style="color:var(--accent-blue,#2997ff);flex-shrink:0;">•</span><span>'+fmt(ulM[1])+'</span></li>'; continue; }
            closeList();
            var para = [tr];
            while (i+1 < lines.length) {
                var nx = lines[i+1].trim();
                if (!nx || /^(#{1,3} |[-*•]\s|\d+\.\s|> |---|---)/.test(nx)) break;
                para.push(nx); i++;
            }
            html += '<p style="margin:0 0 1.1rem;line-height:1.8;color:rgba(255,255,255,.85);">'+fmt(para.join(' '))+'</p>';
        }
        closeList();
        return html;
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
        var excerpt = btn.getAttribute('data-article-excerpt') || '';
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

        // Setup introductory text (Excerpt if available, otherwise first part of content/post)
        var introHtml = '';
        if (excerpt) {
            introHtml = '<p class="article-excerpt" style="font-size:1.1rem; color: rgba(255,255,255,0.8); font-style: italic; margin-bottom: 1.5rem;">' + escapeHtml(excerpt) + '</p>';
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
                                    '<div style="color:#fff;font-size:0.85rem;font-weight:700;">Thomas LAPIERRE</div>' +
                                    '<div style="color:rgba(255,255,255,0.5);font-size:0.75rem;"><i class="far fa-calendar-alt" style="margin-right:4px;"></i>' + escapeHtml(date) + '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div style="margin-left:auto;">' +
                                '<span class="article-readtime-badge" style="background:rgba(255,255,255,0.08);color:var(--accent-blue,#2997ff);padding:6px 14px;border-radius:20px;font-size:0.8rem;transition:opacity 0.3s ease;"><i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Chargement...</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="article-content" style="padding-bottom:2rem;">' + 
                            introHtml +
                            '<div class="article-content-loader" style="text-align:center; padding: 2rem;"><i class="fas fa-spinner fa-spin text-primary"></i></div>' +
                            '<div class="article-content-body" style="opacity:0;"></div>' + 
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="article-overlay-footer">' +
                    '<a href="#' + escapeAttr(slug) + '" class="btn btn-outline-glass rounded-pill" style="font-size:0.85rem;" id="blog-share-btn">' +
                        '<i class="fas fa-link" style="margin-right:8px;"></i>Partager l\'article' +
                    '</a>' +
                '</div>' +
                '<div class="article-progress-btn" id="article-progress-btn" title="Retour en haut" style="position:fixed;bottom:2rem;right:2rem;z-index:99999;cursor:pointer;opacity:0;visibility:hidden;transition:opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;transform:scale(0.8);">' +
                    '<svg width="52" height="52" viewBox="0 0 52 52">' +
                        '<circle cx="26" cy="26" r="23" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="3"></circle>' +
                        '<circle class="progress-ring" cx="26" cy="26" r="23" fill="none" stroke="var(--accent-blue,#2997ff)" stroke-width="3" stroke-linecap="round" stroke-dasharray="144.51" stroke-dashoffset="144.51" transform="rotate(-90 26 26)" style="transition:stroke-dashoffset 0.15s ease;"></circle>' +
                    '</svg>' +
                    '<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:0.85rem;"><i class="fas fa-arrow-up"></i></div>' +
                '</div>' +
            '</div>';

        // Helper to load content
        function displayContent(htmlContent) {
            var loader = overlay.querySelector('.article-content-loader');
            var contentBody = overlay.querySelector('.article-content-body');
            var readTimeBadge = overlay.querySelector('.article-readtime-badge');

            if (loader) {
                loader.style.display = 'none';
            }
            if (contentBody) {
                contentBody.innerHTML = htmlContent;
                contentBody.style.opacity = '1';
                contentBody.style.transition = 'opacity 0.5s ease';

                // Recalculate read time based on the full text
                var text = contentBody.innerText || contentBody.textContent || '';
                var introText = excerpt || '';
                var fullText = (introText + ' ' + text).trim();
                var words = fullText.split(/\s+/).filter(Boolean).length;
                var newTime = Math.max(1, Math.round(words / 200));
                
                if (readTimeBadge) {
                    readTimeBadge.style.opacity = '0';
                    setTimeout(function() {
                        readTimeBadge.innerHTML = '<i class="fas fa-clock" style="margin-right:4px;"></i>' + newTime + ' min read';
                        readTimeBadge.style.opacity = '1';
                    }, 300);
                }
            }
        }

        // Setup reading progress tracker
        var scrollBody = overlay.querySelector('.article-overlay-body');
        var progressBtn = overlay.querySelector('#article-progress-btn');
        var progressRing = overlay.querySelector('.progress-ring');
        var circumference = 2 * Math.PI * 23; // r=23

        if (scrollBody && progressBtn && progressRing) {
            scrollBody.addEventListener('scroll', function() {
                var scrollTop = scrollBody.scrollTop;
                var scrollHeight = scrollBody.scrollHeight - scrollBody.clientHeight;
                var progress = scrollHeight > 0 ? Math.min(scrollTop / scrollHeight, 1) : 0;
                
                // Update ring
                var offset = circumference - (progress * circumference);
                progressRing.style.strokeDashoffset = offset;
                
                // Show/hide button
                if (scrollTop > 300) {
                    progressBtn.style.opacity = '1';
                    progressBtn.style.visibility = 'visible';
                    progressBtn.style.transform = 'scale(1)';
                } else {
                    progressBtn.style.opacity = '0';
                    progressBtn.style.visibility = 'hidden';
                    progressBtn.style.transform = 'scale(0.8)';
                }
            });

            progressBtn.addEventListener('click', function() {
                scrollBody.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        // Attempt to load full content if ID is available
        if (id) {
            fetch('/api/notion-blog.php?id=' + id)
                .then(function(res) { 
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json(); 
                })
                .then(function(data) {
                    if (data && typeof data.content === 'string' && data.content.trim().length > 0) {
                        displayContent(data.content);
                    } else {
                        // Fallback: If no blocks found, use the 'content' attribute (post summary)
                        if (content && content.trim().length > 0) {
                            displayContent('<div class="alert alert-info py-2 small mb-3 border-0 bg-white bg-opacity-5 text-secondary">Note : Affichage de la version courte car l\'article long n\'est pas encore disponible.</div>' + markdownToHtml(content));
                        } else {
                            displayContent('<div class="py-5 text-center text-secondary opacity-50"><i class="fas fa-ghost fa-3x mb-3"></i><p>Cet article semble vide pour le moment.</p></div>');
                        }
                    }
                })
                .catch(function(err) {
                    console.error('Error fetching full content:', err);
                    if (content && content.trim().length > 0) {
                        displayContent(markdownToHtml(content));
                    } else {
                        displayContent('<p class="text-danger">Erreur de chargement du contenu détaillé. Veuillez réessayer plus tard.</p>');
                    }
                });
        } else {
            // No ID, just use the post/summary immediately
            displayContent(markdownToHtml(content));
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
