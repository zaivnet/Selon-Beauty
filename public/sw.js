const CACHE_NAME = 'selon-beauty-static-v1';
const PRECACHE_ASSETS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/icons/maskable-icon-512x512.png',
    '/icons/favicon-32x32.png',
    '/favicon.ico'
];

// Install Event: Pre-cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// Activate Event: Clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event: Network-Only for Private Authenticated Routes & POST; Cache-First for Static Assets
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // 1. Non-GET requests (POST, PUT, DELETE) -> Network-Only (Never cache)
    if (request.method !== 'GET') {
        return;
    }

    // 2. Private Authenticated Routes & Sensitive Media -> Network-Only (Never cache private HTML/data)
    const isPrivate = url.pathname.startsWith('/app') ||
                      url.pathname.startsWith('/admin') ||
                      url.pathname.startsWith('/attendance') ||
                      url.pathname.startsWith('/leave-requests') ||
                      url.pathname.startsWith('/overtime-requests') ||
                      url.pathname.startsWith('/reports') ||
                      url.pathname.startsWith('/notifications') ||
                      url.pathname.startsWith('/forgot-password') ||
                      url.pathname.startsWith('/reset-password') ||
                      url.pathname.startsWith('/password') ||
                      url.pathname.startsWith('/api');

    if (isPrivate) {
        event.respondWith(
            fetch(request).catch(() => {
                // If HTML navigation request and offline, serve offline fallback
                if (request.headers.get('accept')?.includes('text/html')) {
                    return caches.match('/offline.html');
                }
                return new Response('Anda sedang offline. Koneksi internet diperlukan.', {
                    status: 503,
                    headers: { 'Content-Type': 'text/plain; charset=utf-8' }
                });
            })
        );
        return;
    }

    // 3. Static Assets (CSS, JS, Fonts, Icons, Manifest, Offline Page) -> Cache-First
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }
            return fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200 &&
                    (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/') || url.pathname.endsWith('.woff2'))) {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseToCache);
                    });
                }
                return networkResponse;
            }).catch(() => {
                if (request.headers.get('accept')?.includes('text/html')) {
                    return caches.match('/offline.html');
                }
            });
        })
    );
});
