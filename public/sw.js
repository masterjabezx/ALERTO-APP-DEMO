/**
 * ALERTO APP — Progressive Web App Service Worker
 * Hinoba-an LDRRMO Emergency Platform
 * Cache Strategy: Cache-First for static assets; Network-First for navigation;
 * Explicitly NEVER caches credentials, authentication tokens, private messages, or confidential records.
 */

const CACHE_NAME = 'alerto-pwa-v1';
const STATIC_ASSETS = [
    '/offline',
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-maskable.png',
    '/icons/icon.svg',
    '/favicon.ico'
];

// Sensitive or real-time paths that MUST NEVER be stored in service worker cache
const NEVER_CACHE_PATTERNS = [
    /\/login/,
    /\/logout/,
    /\/register/,
    /\/password/,
    /\/api\//,
    /\/pwa\/sync/,
    /\/pwa\/ping/,
    /\/messages/,
    /\/live-map\/data/,
    /\/sanctum/,
    /\/broadcasting/,
    /\/reverb/,
    /\/storage\/evidence/,
    /\/admin\/audit/
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('[ALERTO SW] Pre-caching partial failure:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME && name.startsWith('alerto-pwa-')) {
                        console.log('[ALERTO SW] Deleting obsolete cache:', name);
                        return caches.delete(name);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Only intercept GET requests
    if (req.method !== 'GET') {
        return;
    }

    const url = new URL(req.url);

    // Skip websocket / reverb / chrome extension requests
    if (url.protocol !== 'http:' && url.protocol !== 'https:') {
        return;
    }

    // Check if URL matches NEVER_CACHE rules
    const isSensitive = NEVER_CACHE_PATTERNS.some((pattern) => pattern.test(url.pathname));
    if (isSensitive) {
        return; // Always fetch from network directly
    }

    // Case 1: Static assets (Vite build, icons, fonts) -> Cache-First with Network fallback
    const isStaticAsset = url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/') ||
        url.hostname.includes('fonts.googleapis.com') ||
        url.hostname.includes('fonts.gstatic.com') ||
        url.pathname === '/favicon.ico' ||
        url.pathname === '/manifest.json';

    if (isStaticAsset) {
        event.respondWith(
            caches.match(req).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(req).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(req, responseClone);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    return caches.match('/offline');
                });
            })
        );
        return;
    }

    // Case 2: HTML Page Navigations -> Network-First, fallback to Cache, fallback to /offline
    if (req.mode === 'navigate' || (req.headers.get('accept') && req.headers.get('accept').includes('text/html'))) {
        event.respondWith(
            fetch(req).then((networkResponse) => {
                // If network response is good, optionally cache public pages
                return networkResponse;
            }).catch(() => {
                return caches.match(req).then((cached) => {
                    if (cached) return cached;
                    return caches.match('/offline');
                });
            })
        );
        return;
    }
});

// Support dynamic skip waiting via message
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

