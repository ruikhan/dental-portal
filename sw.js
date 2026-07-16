// ============================================================
// DentalPortal Service Worker — Offline & PWA Support
// ============================================================

// Bumped v1 -> v2: the icon set and odontogram assets are new, and this
// forces any previously-registered worker (from before registration was
// actually wired into app.js) to fully replace its cache rather than
// serving stale/missing entries.
const CACHE_NAME = 'dental-portal-v2';
const STATIC_ASSETS = [
    '/assets/style.css',
    '/assets/app.js',
    '/assets/odontogram.css',
    '/assets/odontogram.js',
    '/assets/icons/icon-192.png',
    '/assets/icons/icon-512.png',
    '/assets/icons/apple-touch-icon.png',
    '/assets/icons/favicon-32.png',
    '/favicon.ico',
    '/offline.php',
    'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Serif+Display&display=swap',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css'
];

// Install — cache static assets
self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS).catch(err => {
                console.warn('Cache addAll partial failure:', err);
            });
        })
    );
});

// Activate — clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

// Fetch strategy:
// - Static assets (CSS, JS, fonts, icons): Cache First
// - PHP pages: Network First, fallback to offline page
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    // Cache-first for static assets
    if (
        url.pathname.startsWith('/assets/') ||
        url.pathname === '/favicon.ico' ||
        url.hostname.includes('googleapis.com') ||
        url.hostname.includes('jsdelivr.net') ||
        url.hostname.includes('gstatic.com')
    ) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                if (cached) return cached;
                return fetch(event.request).then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Network-first for PHP pages
    if (url.pathname.endsWith('.php') || url.pathname === '/') {
        event.respondWith(
            fetch(event.request)
                .then(response => response)
                .catch(() => {
                    return caches.match('/offline.php').then(cached => cached || new Response(
                        `<!DOCTYPE html><html><head><title>Offline</title>
                        <style>body{font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;background:#0f2d4a;color:white;text-align:center;gap:16px;}
                        h1{font-size:2rem;}p{opacity:0.7;}a{color:#0fb3b3;text-decoration:none;}</style>
                        </head><body>
                        <h1>🦷 You're Offline</h1>
                        <p>DentalPortal requires an internet connection.<br>Please check your connection and try again.</p>
                        <a href="/">Try Again</a>
                        </body></html>`,
                        { headers: { 'Content-Type': 'text/html' } }
                    ));
                })
        );
        return;
    }
});
