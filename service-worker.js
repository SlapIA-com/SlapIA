const CACHE_NAME = 'slapia-v5';

// Only pre-cache local assets — CDN resources (FA, Bootstrap, Google Fonts, etc.)
// must NOT be intercepted by the SW because:
// 1. Opaque cross-origin responses lose their URL, breaking @font-face relative paths
// 2. The browser's HTTP cache already handles CDN resources via Cache-Control headers
const STATIC_ASSETS = [
  '/',
  '/assets/img/brand/logo.svg',
  '/assets/css/style.css',
  '/assets/css/header.css',
  '/assets/css/footer.css',
  '/assets/css/animations.css',
  '/assets/css/homepage.css',
  '/assets/css/formation.css',
  '/assets/css/blog.css',
  '/assets/css/reviews.css',
  '/assets/css/lightbox.css',
  '/assets/css/theme-matrix.css',
];

// Install: cache all static assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('[SW] Caching static assets');
      // Cache individually so one failure doesn't block the rest
      return Promise.allSettled(STATIC_ASSETS.map(url => cache.add(url)));
    }).then(() => self.skipWaiting())
  );
});

// Activate: delete old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.filter(key => key !== CACHE_NAME).map(key => {
          console.log('[SW] Deleting old cache:', key);
          return caches.delete(key);
        })
      )
    ).then(() => self.clients.claim())
  );
});

// Fetch: only intercept same-origin requests — never touch cross-origin CDN/font requests
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Never intercept non-GET, API calls, PHP endpoints, or cross-origin requests
  if (event.request.method !== 'GET') return;
  if (url.hostname !== location.hostname) return;   // let browser handle CDN/fonts natively
  if (url.pathname.startsWith('/api/')) return;
  if (url.pathname.endsWith('.php')) return;

  // Cache-first for local static assets (CSS, JS, images, fonts)
  const isStatic = url.pathname.match(/\.(css|js|svg|png|jpg|jpeg|webp|woff2?|ico)$/);
  if (isStatic) {
    event.respondWith(
      caches.match(event.request).then(cached => cached || fetch(event.request))
    );
    return;
  }

  // Network-first for HTML pages (always fresh content)
  event.respondWith(
    fetch(event.request)
      .catch(() => caches.match(event.request))
  );
});
