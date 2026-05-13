const CACHE_NAME = 'maketu-v1';
const STATIC_ASSETS = ['/', '/manifest.json'];

// Never intercept Vite dev server HMR / module requests
const isDevServer = (url) =>
    url.hostname === 'localhost' ||
    url.hostname === '[::1]' ||
    url.hostname === '127.0.0.1' ||
    url.port === '5173' ||
    url.port === '5174';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS).catch(() => {}))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip dev server entirely — let Vite handle everything in development
    if (isDevServer(url)) return;

    // Only handle GET
    if (event.request.method !== 'GET') return;

    // Skip non-http/https (chrome-extension, etc.)
    if (!url.protocol.startsWith('http')) return;

    // Cache-first for static assets (images, fonts, icons)
    if (url.pathname.match(/\.(png|jpg|jpeg|svg|webp|ico|woff2?)$/) ||
        url.pathname.startsWith('/images/')) {
        event.respondWith(
            caches.match(event.request).then(
                (cached) => cached || fetch(event.request).then((res) => {
                    if (res.ok) {
                        const clone = res.clone();
                        caches.open(CACHE_NAME).then((c) => c.put(event.request, clone));
                    }
                    return res;
                })
            )
        );
        return;
    }

    // Network-first for pages/API — fallback to cache if offline
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});
