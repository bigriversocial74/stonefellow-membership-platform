const LIKENESSING_CACHE = 'likenessing-shell-v2-20260716';
const CORE_ASSETS = [
  './',
  './index.php',
  './offline.php',
  './episodes.php',
  './cast.php',
  './series.php',
  './extras.php',
  './news.php',
  './assets/css/stonefellow.css',
  './assets/css/likenessing.css?v=20260716',
  './assets/js/stonefellow.js',
  './assets/js/likenessing-theme.js?v=20260716',
  './likenessing-asset.php?name=logo&v=20260716',
  './likenessing-asset.php?name=favicon&v=20260716',
  './likenessing-asset.php?name=hero&v=20260716',
  './likenessing-asset.php?name=premise&v=20260716'
];
self.addEventListener('install', (event) => { event.waitUntil(caches.open(LIKENESSING_CACHE).then((cache) => cache.addAll(CORE_ASSETS)).catch(() => null)); self.skipWaiting(); });
self.addEventListener('activate', (event) => { event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== LIKENESSING_CACHE).map((key) => caches.delete(key))))); self.clients.claim(); });
function isNavigationRequest(request) { return request.mode === 'navigate' || (request.method === 'GET' && request.headers.get('accept') && request.headers.get('accept').includes('text/html')); }
self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;
  if (isNavigationRequest(request)) {
    event.respondWith(fetch(request).then((response) => { const copy = response.clone(); caches.open(LIKENESSING_CACHE).then((cache) => cache.put(request, copy)).catch(() => null); return response; }).catch(() => caches.match(request).then((cached) => cached || caches.match('./offline.php'))));
    return;
  }
  event.respondWith(caches.match(request).then((cached) => {
    const network = fetch(request).then((response) => { if (response && response.status === 200) { const copy = response.clone(); caches.open(LIKENESSING_CACHE).then((cache) => cache.put(request, copy)).catch(() => null); } return response; }).catch(() => cached);
    return cached || network;
  }));
});
