document.addEventListener('DOMContentLoaded', function () {
  var header = document.querySelector('.site-header');
  var railFill = document.querySelector('.rail__fill');
  var railPct = document.querySelector('.rail__pct');
  var mobileFill = document.querySelector('.progress-mobile__fill');

  function onScroll() {
    var scrollTop = window.scrollY || document.documentElement.scrollTop;
    var docHeight = document.documentElement.scrollHeight - window.innerHeight;
    var pct = docHeight > 0 ? Math.min(100, Math.max(0, (scrollTop / docHeight) * 100)) : 0;

    if (header) header.classList.toggle('is-scrolled', scrollTop > 8);
    if (railFill) railFill.style.height = pct + '%';
    if (railPct) railPct.textContent = Math.round(pct) + '%';
    if (mobileFill) mobileFill.style.width = pct + '%';
  }
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);

  var toggle = document.querySelector('.nav-toggle');
  var closeBtn = document.querySelector('.mobile-menu__close');
  var menu = document.querySelector('.mobile-menu');
  if (toggle && menu) {
    var openMenu = function () {
      menu.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    };
    var closeMenu = function () {
      menu.classList.remove('is-open');
      document.body.style.overflow = '';
    };
    toggle.addEventListener('click', openMenu);
    if (closeBtn) closeBtn.addEventListener('click', closeMenu);
    menu.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', closeMenu);
    });
  }

  var userMenu = document.querySelector('.user-menu');
  var userMenuTrigger = document.querySelector('.user-menu__trigger');
  if (userMenu && userMenuTrigger) {
    userMenuTrigger.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = userMenu.classList.toggle('is-open');
      userMenuTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (!userMenu.contains(e.target)) {
        userMenu.classList.remove('is-open');
        userMenuTrigger.setAttribute('aria-expanded', 'false');
      }
    });
  }

  var staggerGroups = document.querySelectorAll('.grid-2, .grid-3, .grid-4, .method-list, .pricing-grid, .timeline, .about-stats');
  staggerGroups.forEach(function (group) {
    var children = group.querySelectorAll(':scope > .reveal');
    children.forEach(function (child, i) {
      child.style.transitionDelay = (Math.min(i, 5) * 0.08) + 's';
    });
  });

  var revealEls = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && revealEls.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
    revealEls.forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('is-visible'); });
  }

  var themeToggles = document.querySelectorAll('.theme-toggle');
  if (themeToggles.length) {
    var setTheme = function (theme) {
      document.documentElement.setAttribute('data-theme', theme);
      try { localStorage.setItem('slapia-theme', theme); } catch (e) {}
    };
    themeToggles.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var current = document.documentElement.getAttribute('data-theme') || 'light';
        setTheme(current === 'dark' ? 'light' : 'dark');
      });
    });
  }

  initReviewsCarousel();
  initHeroReveal();
  initMarkReveal();
  initCountStats();

  var filterBtns = document.querySelectorAll('.filter-btn');
  var syllabusItems = document.querySelectorAll('[data-level]');
  if (filterBtns.length && syllabusItems.length) {
    filterBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        filterBtns.forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        var filter = btn.getAttribute('data-filter');
        syllabusItems.forEach(function (item) {
          var show = filter === 'all' || item.getAttribute('data-level') === filter;
          item.style.display = show ? '' : 'none';
        });
      });
    });
  }
});

function initReviewsCarousel() {
  var track = document.querySelector('.reviews-track');
  var container = document.querySelector('.reviews-inner');
  var items = document.querySelectorAll('.review-item');
  var prevBtn = document.getElementById('prev-review');
  var nextBtn = document.getElementById('next-review');

  if (!track || !container || items.length === 0) return;

  var clonesStart = [];
  var clonesEnd = [];
  items.forEach(function (item) {
    var cloneS = item.cloneNode(true);
    cloneS.classList.add('clone');
    clonesStart.push(cloneS);

    var cloneE = item.cloneNode(true);
    cloneE.classList.add('clone');
    clonesEnd.push(cloneE);
  });
  clonesEnd.forEach(function (clone) { track.appendChild(clone); });
  clonesStart.reverse().forEach(function (clone) { track.insertBefore(clone, track.firstChild); });

  var totalOriginalItems = items.length;
  var currentIndex = totalOriginalItems;
  var isTransitioning = false;
  var autoPlayInterval = null;

  function stepWidth() {
    var first = track.querySelector('.review-item');
    var gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || '0') || 0;
    return first ? first.getBoundingClientRect().width + gap : 344;
  }

  function updatePosition(index, animate) {
    track.style.transition = animate ? 'transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1)' : 'none';
    track.style.transform = 'translateX(' + (-(index * stepWidth())) + 'px)';
  }

  updatePosition(currentIndex, false);

  function move(direction) {
    if (isTransitioning) return;
    isTransitioning = true;
    currentIndex += direction === 'next' ? 1 : -1;
    updatePosition(currentIndex, true);
  }

  track.addEventListener('transitionend', function () {
    isTransitioning = false;
    if (currentIndex >= totalOriginalItems * 2) {
      currentIndex = totalOriginalItems;
      updatePosition(currentIndex, false);
    } else if (currentIndex < totalOriginalItems) {
      currentIndex = totalOriginalItems * 2 - 1;
      updatePosition(currentIndex, false);
    }
  });

  function startAutoPlay() {
    clearInterval(autoPlayInterval);
    autoPlayInterval = setInterval(function () { move('next'); }, 3000);
  }
  function stopAutoPlay() { clearInterval(autoPlayInterval); }

  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      stopAutoPlay();
      move('next');
      startAutoPlay();
    });
  }
  if (prevBtn) {
    prevBtn.addEventListener('click', function () {
      stopAutoPlay();
      move('prev');
      startAutoPlay();
    });
  }

  startAutoPlay();
  container.addEventListener('mouseenter', stopAutoPlay);
  container.addEventListener('mouseleave', startAutoPlay);
  window.addEventListener('resize', function () { updatePosition(currentIndex, false); });
}

function initHeroReveal() {
  var els = document.querySelectorAll('.hero-reveal');
  if (!els.length) return;
  requestAnimationFrame(function () {
    requestAnimationFrame(function () {
      els.forEach(function (el) { el.classList.add('is-in'); });
    });
  });
}

function initMarkReveal() {
  var marks = document.querySelectorAll('mark, .mark');
  if (!marks.length || !('IntersectionObserver' in window)) {
    marks.forEach(function (m) { m.classList.add('is-marked'); });
    return;
  }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-marked');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });
  marks.forEach(function (m) { io.observe(m); });
}

function initCountStats() {
  var els = document.querySelectorAll('.js-count');
  if (!els.length) return;

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function animate(el) {
    var target = parseFloat(el.getAttribute('data-count-value'));
    var decimals = parseInt(el.getAttribute('data-count-decimals'), 10) || 0;
    var suffix = el.getAttribute('data-count-suffix') || '';
    var sep = el.getAttribute('data-count-sep') || '.';
    var final = el.getAttribute('data-count-final');

    if (reduceMotion || isNaN(target)) {
      el.textContent = final;
      return;
    }

    var duration = 1100;
    var start = null;

    function frame(ts) {
      if (!start) start = ts;
      var progress = Math.min((ts - start) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      var current = target * eased;
      el.textContent = current.toFixed(decimals).replace('.', sep) + suffix;
      if (progress < 1) {
        requestAnimationFrame(frame);
      } else {
        el.textContent = final;
      }
    }
    requestAnimationFrame(frame);
  }

  if (!('IntersectionObserver' in window)) {
    els.forEach(function (el) { el.textContent = el.getAttribute('data-count-final'); });
    return;
  }

  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        animate(entry.target);
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.4 });
  els.forEach(function (el) { io.observe(el); });
}
