const STONEFELLOW_CACHE = 'stonefellow-shell-v1';
const CORE_ASSETS = [
  './',
  './index.php',
  './offline.php',
  './episodes.php',
  './music.php',
  './player.php',
  './assets/css/stonefellow.css',
  './assets/css/pwa-upload.css',
  './assets/js/stonefellow.js',
  './assets/js/pwa-upload.js',
  './assets/images/brand/home-brand-approved.png',
  './assets/images/brand/footer-brand-approved.png',
  './assets/images/brand/logo-mark.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STONEFELLOW_CACHE).then((cache) => cache.addAll(CORE_ASSETS)).catch(() => null));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== STONEFELLOW_CACHE).map((key) => caches.delete(key)))));
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
      caches.open(STONEFELLOW_CACHE).then((cache) => cache.put(request, copy)).catch(() => null);
      return response;
    }).catch(() => caches.match(request).then((cached) => cached || caches.match('./offline.php'))));
    return;
  }

  event.respondWith(caches.match(request).then((cached) => {
    const network = fetch(request).then((response) => {
      if (response && response.status === 200) {
        const copy = response.clone();
        caches.open(STONEFELLOW_CACHE).then((cache) => cache.put(request, copy)).catch(() => null);
      }
      return response;
    }).catch(() => cached);
    return cached || network;
  }));
});
