const CACHE_NAME = 'selon-beauty-static-v3';
const PRECACHE_ASSETS = [
    '/offline.html',
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

// Activate Event: Clean up old application caches safely
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName.startsWith('selon-beauty-') && cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event: Network-Only for Private Authenticated & Auth Nav Routes; Cache-First for Static Assets
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // 1. Non-GET requests (POST, PUT, PATCH, DELETE) -> Network-Only (Never cache mutations)
    if (request.method !== 'GET') {
        return;
    }

    // 2. Private Authenticated Routes, Auth Flows, Private Evidence & Dynamic Media -> Network-Only (Never cache private HTML or session data)
    const isPrivateOrAuthNav = url.pathname === '/' ||
                      url.pathname === '/login' ||
                      url.pathname === '/logout' ||
                      url.pathname.startsWith('/app') ||
                      url.pathname.startsWith('/admin') ||
                      url.pathname.startsWith('/employee') ||
                      url.pathname.startsWith('/attendance') ||
                      url.pathname.startsWith('/leave-requests') ||
                      url.pathname.startsWith('/overtime-requests') ||
                      url.pathname.startsWith('/overtime-sessions') ||
                      url.pathname.startsWith('/shift-swaps') ||
                      url.pathname.startsWith('/reports') ||
                      url.pathname.startsWith('/monthly-recaps') ||
                      url.pathname.startsWith('/notifications') ||
                      url.pathname.startsWith('/profile') ||
                      url.pathname.startsWith('/settings') ||
                      url.pathname.startsWith('/selfie') ||
                      url.pathname.startsWith('/attachments') ||
                      url.pathname.startsWith('/storage') ||
                      url.pathname.startsWith('/forgot-password') ||
                      url.pathname.startsWith('/reset-password') ||
                      url.pathname.startsWith('/password') ||
                      url.pathname.startsWith('/api');

    if (isPrivateOrAuthNav) {
        event.respondWith(
            fetch(request).catch(() => {
                // If HTML navigation request and offline, serve generic offline fallback page
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

    // 3. Static Assets (Build CSS/JS, Fonts, Icons, Favicon, Offline Page) -> Cache-First
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }
            return fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200 &&
                    (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/') || url.pathname.endsWith('.woff2') || url.pathname.endsWith('.woff'))) {
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
