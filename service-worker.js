const CACHE_NAME = 'slapia-v4';

// Only pre-cache local assets — CDN resources (FA, Bootstrap, etc.) are
// handled by the browser's HTTP cache via their own Cache-Control headers.
// Pre-caching CDN CSS can break webfont relative URL resolution.
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

// Fetch: cache-first for static assets, network-first for API/PHP pages
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Never intercept API calls, PHP endpoints, or non-GET requests
  if (event.request.method !== 'GET') return;
  if (url.pathname.startsWith('/api/')) return;
  if (url.pathname.endsWith('.php')) return;

  // Cache-first for static assets (CSS, JS, images, fonts)
  const isStatic = url.pathname.match(/\.(css|js|svg|png|jpg|jpeg|webp|woff2?|ico)$/);
  if (isStatic || url.hostname !== location.hostname) {
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
