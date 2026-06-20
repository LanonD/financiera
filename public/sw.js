// Bump de versión: limpia automáticamente la caché vieja en todos los dispositivos
const CACHE = 'financiera-v3';

// Sin precache de rutas fijas: el SW vive en distinta ruta según el entorno
// (subcarpeta en local, raíz en producción). Las páginas se cachean al visitarse.
const PRECACHE = [];

// Install: preparar caché y activar de inmediato
self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE)
            .then(c => c.addAll(PRECACHE).catch(() => {}))
            .then(() => self.skipWaiting())
    );
});

// Activate: borrar cachés de versiones anteriores
self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys.filter(k => k !== CACHE).map(k => caches.delete(k))
            ))
            .then(() => self.clients.claim())
    );
});

// Fetch strategy
self.addEventListener('fetch', e => {
    const req = e.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    if (req.mode === 'navigate') {
        // HTML: network-first, con respaldo a caché y página offline
        e.respondWith(
            fetch(req)
                .then(resp => {
                    if (resp.ok) {
                        const copy = resp.clone();           // clonar ANTES de devolver
                        caches.open(CACHE).then(c => c.put(req, copy));
                    }
                    return resp;
                })
                .catch(() =>
                    caches.match(req).then(cached =>
                        cached || new Response(offlinePage(), {
                            status: 503,
                            headers: { 'Content-Type': 'text/html; charset=utf-8' }
                        })
                    )
                )
        );
        return;
    }

    // Assets estáticos: cache-first
    e.respondWith(
        caches.match(req).then(cached =>
            cached || fetch(req).then(resp => {
                if (resp.ok) {
                    const copy = resp.clone();               // clonar ANTES de devolver
                    caches.open(CACHE).then(c => c.put(req, copy));
                }
                return resp;
            })
        )
    );
});

// Background sync: avisar a las pestañas abiertas para que el JS de la página sincronice
self.addEventListener('sync', e => {
    if (e.tag === 'sync-prestamos' || e.tag === 'sync-all') {
        e.waitUntil(
            self.clients
                .matchAll({ includeUncontrolled: true, type: 'window' })
                .then(clients =>
                    Promise.all(clients.map(c => c.postMessage({ type: 'SYNC_ALL' })))
                )
        );
    }
});

function offlinePage() {
    return `<!doctype html><html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sin conexión — PrestaCRM</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#f0f2f5;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.box{background:#fff;border-radius:16px;padding:40px 32px;text-align:center;max-width:360px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.08)}
.icon{font-size:48px;margin-bottom:16px}
h2{font-size:20px;font-weight:700;color:#111827;margin-bottom:8px}
p{font-size:14px;color:#6b7280;line-height:1.6;margin-bottom:24px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#3b82f6;color:#fff;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;cursor:pointer;border:none;font-family:inherit}
.btn:hover{background:#2563eb}
.tip{margin-top:16px;font-size:12px;color:#9ca3af}
</style>
</head>
<body>
<div class="box">
    <div class="icon">📡</div>
    <h2>Sin conexión</h2>
    <p>No hay internet. Si ya visitaste esta página antes, intenta recargar — puede estar en caché.</p>
    <button class="btn" onclick="location.reload()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        Reintentar
    </button>
    <p class="tip">Los cobros y clientes registrados sin conexión se subirán automáticamente al reconectarte.</p>
</div>
</body></html>`;
}
