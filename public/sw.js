/*
 * Service worker.
 * - Caches only static, non-personal assets (build files, icons, fonts) and an offline page.
 * - NEVER caches HTML pages or API responses: dashboards and bookings contain health data.
 */
const VERSION = 'v1';
const STATIC_CACHE = `static-${VERSION}`;
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll([OFFLINE_URL, '/icons/icon-192.png', '/icons/favicon.svg']))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== STATIC_CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Page navigations: always network. Offline fallback page if the network is down.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    // Fingerprinted build assets and icons: cache-first.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((res) => {
                if (res.ok) {
                    const copy = res.clone();
                    caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
                }
                return res;
            }))
        );
    }
});
