const CACHE = 'financiera-v1';

// Install: activate immediately
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', e => e.waitUntil(self.clients.claim()));

// Fetch: Network-First for HTML navigation, Cache-First for static assets
self.addEventListener('fetch', e => {
    const req = e.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);

    // Skip cross-origin requests (Google Fonts, etc.)
    if (url.origin !== self.location.origin) return;

    if (req.mode === 'navigate') {
        // HTML pages: try network, fall back to cache
        e.respondWith(
            fetch(req)
                .then(resp => {
                    if (resp.ok) {
                        const clone = resp.clone();
                        caches.open(CACHE).then(c => c.put(req, clone));
                    }
                    return resp;
                })
                .catch(() =>
                    caches.match(req).then(cached =>
                        cached || caches.match('/prestamos/crear')
                            .then(fb => fb || new Response(
                                `<!doctype html><html lang="es"><head><meta charset="UTF-8">
                                <meta name="viewport" content="width=device-width,initial-scale=1">
                                <title>Sin conexión</title>
                                <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f0f2f5}
                                .box{text-align:center;padding:40px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.1)}
                                h2{color:#111;margin-bottom:8px}p{color:#6b7280;font-size:14px}
                                a{display:inline-block;margin-top:16px;padding:8px 20px;background:#3b82f6;color:#fff;border-radius:6px;text-decoration:none;font-size:13px}</style>
                                </head><body><div class="box">
                                <h2>Sin conexión</h2>
                                <p>Visita la página cuando tengas internet para cargarla en caché.</p>
                                <a href="javascript:location.reload()">Reintentar</a>
                                </div></body></html>`,
                                { status: 503, headers: { 'Content-Type': 'text/html' } }
                            ))
                    )
                )
        );
        return;
    }

    // Static assets (JS, CSS, images): Cache-First
    e.respondWith(
        caches.match(req).then(cached =>
            cached || fetch(req).then(resp => {
                if (resp.ok) {
                    caches.open(CACHE).then(c => c.put(req, resp.clone()));
                }
                return resp;
            })
        )
    );
});

// Background Sync: relay message to all open tabs so page JS can do the actual sync
// (page JS has access to CSRF token and DOM)
self.addEventListener('sync', e => {
    if (e.tag === 'sync-prestamos') {
        e.waitUntil(
            self.clients
                .matchAll({ includeUncontrolled: true, type: 'window' })
                .then(clients =>
                    Promise.all(clients.map(c => c.postMessage({ type: 'SYNC_PRESTAMOS' })))
                )
        );
    }
});
