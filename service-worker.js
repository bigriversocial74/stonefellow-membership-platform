const DESERTRIO_CACHE = 'desertrio-shell-v3';
const CORE_ASSETS = [
  './',
  './index.php',
  './offline.php',
  './episodes.php',
  './cast.php',
  './series.php',
  './merch.php',
  './assets/css/stonefellow.css',
  './assets/css/pwa-upload.css',
  './assets/css/desertrio.css',
  './assets/css/desertrio-pages.css',
  './assets/css/desertrio-responsive.css',
  './assets/js/stonefellow.js',
  './assets/js/pwa-upload.js',
  './assets/images/desertrio/desertrio-welcome.svg',
  './assets/images/desertrio/desertrio-newsletter.svg'
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(DESERTRIO_CACHE).then((cache) => cache.addAll(CORE_ASSETS)).catch(() => null));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== DESERTRIO_CACHE).map((key) => caches.delete(key)))));
  self.clients.claim();
});

function isNavigationRequest(request) {
  return request.mode === 'navigate' || (request.method === 'GET' && request.headers.get('accept') && request.headers.get('accept').includes('text/html'));
}

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (isNavigationRequest(request)) {
    event.respondWith(fetch(request).then((response) => {
      const copy = response.clone();
      caches.open(DESERTRIO_CACHE).then((cache) => cache.put(request, copy)).catch(() => null);
      return response;
    }).catch(() => caches.match(request).then((cached) => cached || caches.match('./offline.php'))));
    return;
  }

  event.respondWith(caches.match(request).then((cached) => {
    const network = fetch(request).then((response) => {
      if (response && response.status === 200) {
        const copy = response.clone();
        caches.open(DESERTRIO_CACHE).then((cache) => cache.put(request, copy)).catch(() => null);
      }
      return response;
    }).catch(() => cached);
    return cached || network;
  }));
});
