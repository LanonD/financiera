/**
 * offline-sync.js  v2
 * Cola unificada offline para PrestaCRM.
 * Soporta: prestamos, clientes, cobros.
 * Mantiene compatibilidad con v1 (window.OfflineSync.guardarOffline).
 */
(function () {
    'use strict';

    const QUEUE_KEY  = 'financiera_queue_v2';
    const LEGACY_KEY = 'financiera_prestamos_offline';

    // ── Migrar cola legacy (v1 solo tenía préstamos) ───────────────────────────
    function migrateLegacy() {
        try {
            const raw = localStorage.getItem(LEGACY_KEY);
            if (!raw) return;
            const items = JSON.parse(raw) || [];
            if (!items.length) { localStorage.removeItem(LEGACY_KEY); return; }
            const queue = getQueue();
            const existing = new Set(queue.map(e => e.localId));
            items.forEach(item => {
                if (!existing.has(item.localId)) {
                    queue.push({
                        localId:       item.localId,
                        type:          'prestamo',
                        savedAt:       item.savedAt,
                        status:        'pending',
                        failReason:    null,
                        displayLabel:  item.clienteNombre || '(sin nombre)',
                        endpoint:      '/prestamos',
                        contentType:   'form',
                        data:          item.data || {},
                    });
                }
            });
            saveQueue(queue);
            localStorage.removeItem(LEGACY_KEY);
        } catch (_) {}
    }

    // ── Queue CRUD ─────────────────────────────────────────────────────────────
    function getQueue() {
        try { return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]'); }
        catch { return []; }
    }

    function saveQueue(q) {
        localStorage.setItem(QUEUE_KEY, JSON.stringify(q));
    }

    function getPending() {
        return getQueue().filter(e => e.status === 'pending');
    }

    function getFailed() {
        return getQueue().filter(e => e.status === 'failed');
    }

    function enqueue({ type, displayLabel, endpoint, contentType, data }) {
        const entry = {
            localId:      Date.now() + '-' + Math.random().toString(36).slice(2, 8),
            type,
            savedAt:      new Date().toISOString(),
            status:       'pending',
            failReason:   null,
            displayLabel: displayLabel || '(sin nombre)',
            endpoint,
            contentType:  contentType || 'form',
            data,
        };
        const q = getQueue();
        q.push(entry);
        saveQueue(q);
        updateUI();
        registerBackgroundSync();
        return entry;
    }

    function markEntry(localId, status, reason) {
        const q = getQueue();
        const idx = q.findIndex(e => e.localId === localId);
        if (idx >= 0) {
            q[idx].status    = status;
            q[idx].failReason = reason || null;
            if (status === 'synced') q[idx].syncedAt = new Date().toISOString();
        }
        saveQueue(q);
    }

    function dismissFailed() {
        saveQueue(getQueue().filter(e => e.status !== 'failed'));
        updateUI();
    }

    // ── Public: guardar operación ──────────────────────────────────────────────

    // v1 compat: guardarOffline(data) → préstamo
    function guardarOffline(data) {
        return enqueue({
            type:         'prestamo',
            displayLabel: data._clienteNombre || '(sin nombre)',
            endpoint:     '/prestamos',
            contentType:  'form',
            data:         Object.fromEntries(
                              Object.entries(data).filter(([k]) => k !== '_clienteNombre')
                          ),
        });
    }

    // v2: guardar cliente
    function guardarClienteOffline(data, displayLabel) {
        return enqueue({
            type:        'cliente',
            displayLabel,
            endpoint:    '/clientes',
            contentType: 'form',
            data,
        });
    }

    // v2: guardar cobros (objeto { prestamoId: {tipo,monto,nota} })
    function guardarCobrosOffline(cobrosObj, displayLabel) {
        return enqueue({
            type:        'cobro',
            displayLabel: displayLabel || Object.keys(cobrosObj).length + ' cobro(s)',
            endpoint:    '/cobros/registrar',
            contentType: 'json',
            data:        cobrosObj,
        });
    }

    // ── Sync ───────────────────────────────────────────────────────────────────
    function getCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async function syncOne(entry) {
        const token = getCsrf();
        if (!token) return { ok: false, reason: 'Sin token CSRF — recarga la página' };

        try {
            let resp;

            if (entry.contentType === 'json') {
                // Cobros: JSON POST
                resp = await fetch(entry.endpoint, {
                    method:  'POST',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(entry.data),
                });
                if (resp.status === 419) return { ok: false, reason: 'Sesión expirada' };
                const json = await resp.json().catch(() => null);
                if (json?.ok) return { ok: true };
                return { ok: false, reason: json?.error || `Error ${resp.status}` };

            } else {
                // Préstamo / Cliente: form-encoded POST
                const body = new URLSearchParams({ _token: token });
                Object.entries(entry.data || {}).forEach(([k, v]) => {
                    if (v !== null && v !== undefined) body.append(k, v);
                });
                resp = await fetch(entry.endpoint, {
                    method:   'POST',
                    headers:  {
                        'Content-Type':     'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body:     body.toString(),
                    redirect: 'follow',
                });
                if (resp.status === 419) return { ok: false, reason: 'Sesión expirada' };

                // Laravel redirects to detail page on success, back to form on error
                if (resp.redirected) {
                    const u = resp.url;
                    // Success patterns per type
                    if (entry.type === 'prestamo' && /\/prestamos\/\d+/.test(u)) return { ok: true };
                    if (entry.type === 'cliente'  && /\/clientes(\/\d+)?$/.test(u)) return { ok: true };
                    // Redirected back to creation form = validation error
                    const html = await resp.text().catch(() => '');
                    const err  = extractError(html);
                    return { ok: false, reason: err || 'Error de validación — revisa los datos' };
                }
                if (resp.ok) return { ok: true };
                return { ok: false, reason: `Error ${resp.status}` };
            }
        } catch (e) {
            return { ok: false, reason: 'Sin red — se reintentará al reconectarse' };
        }
    }

    function extractError(html) {
        // Extract first validation error from Laravel HTML response
        const m = html.match(/class="frm-error"[^>]*>\s*([^<]+)/);
        if (m) return m[1].trim();
        const m2 = html.match(/"message"\s*:\s*"([^"]+)"/);
        if (m2) return m2[1];
        return null;
    }

    async function sincronizar() {
        if (!navigator.onLine) return;
        const pending = getPending();
        if (!pending.length) return;

        let synced = 0, failed = 0, sessionExpired = false;

        for (const entry of pending) {
            const result = await syncOne(entry);
            if (result.ok) {
                markEntry(entry.localId, 'synced', null);
                synced++;
            } else {
                if (result.reason === 'Sesión expirada') sessionExpired = true;
                markEntry(entry.localId, 'failed', result.reason);
                failed++;
            }
        }

        updateUI();

        if (sessionExpired) {
            showToast('Sesión expirada — recarga la página para sincronizar.', 'red');
        } else if (synced > 0 && failed === 0) {
            showToast(`✓ ${synced} operación(es) sincronizada(s).`, 'green');
            setTimeout(() => location.reload(), 1400);
        } else if (synced > 0 && failed > 0) {
            showToast(`✓ ${synced} sincronizada(s) · ${failed} con error — revisa el panel.`, 'yellow');
            setTimeout(() => location.reload(), 1400);
        } else if (failed > 0) {
            showToast(`${failed} operación(es) fallaron — revisa el panel offline.`, 'red');
        }
    }

    function registerBackgroundSync() {
        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            navigator.serviceWorker.ready
                .then(sw => sw.sync.register('sync-all'))
                .catch(() => {});
        }
    }

    // ── UI ─────────────────────────────────────────────────────────────────────
    function updateUI() {
        updateBadge();
        updateBanner();
        updatePendingPanel();
    }

    function updateBadge() {
        const count = getPending().length;
        document.querySelectorAll('.offline-badge').forEach(el => {
            el.textContent  = count || '';
            el.style.display = count > 0 ? 'inline-flex' : 'none';
        });
    }

    function updateBanner() {
        const banner = document.getElementById('offline-banner');
        if (!banner) return;
        const hasQueue = getPending().length > 0 || getFailed().length > 0;
        banner.style.display = (!navigator.onLine || hasQueue) ? 'flex' : 'none';
    }

    function typeLabel(type) {
        return { prestamo: 'Préstamo', cliente: 'Cliente', cobro: 'Cobro' }[type] || type;
    }

    function updatePendingPanel() {
        const panel = document.getElementById('offline-pending-panel');
        if (!panel) return;
        const pending = getPending();
        const failed  = getFailed();
        const all     = [...pending, ...failed].sort((a, b) =>
            new Date(a.savedAt) - new Date(b.savedAt)
        );
        if (!all.length) { panel.style.display = 'none'; return; }

        panel.style.display = '';
        const list = document.getElementById('offline-pending-list');
        if (!list) return;

        list.innerHTML = all.map(e => {
            const d    = new Date(e.savedAt);
            const hora = d.toLocaleDateString('es-MX', { day:'2-digit', month:'2-digit' })
                       + ' ' + d.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });
            const isFailed = e.status === 'failed';
            const color = isFailed ? '#dc2626' : '#92400e';
            const bg    = isFailed ? '#fee2e2'  : 'transparent';
            return `<div style="padding:9px 16px;border-bottom:1px solid #fcd34d;background:${bg}">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                    <div style="min-width:0">
                        <span style="font-size:12px;font-weight:700;color:${color}">
                            ${isFailed ? '✗ ' : '⏳ '}${escHtml(typeLabel(e.type))}
                        </span>
                        <span style="font-size:12px;color:#92400e"> — ${escHtml(e.displayLabel)}</span>
                        ${isFailed && e.failReason
                            ? `<div style="font-size:11px;color:#dc2626;margin-top:2px">${escHtml(e.failReason)}</div>`
                            : ''}
                    </div>
                    <span style="font-size:10px;color:#78350f;white-space:nowrap;flex-shrink:0">${hora}</span>
                </div>
            </div>`;
        }).join('');

        // Add dismiss-failed button if there are failures
        if (failed.length > 0) {
            list.innerHTML += `<div style="padding:10px 16px;border-top:1px solid #fcd34d;text-align:right">
                <button onclick="window.OfflineSync.dismissFailed()"
                    style="font-size:11px;padding:4px 10px;border:1px solid #fca5a5;background:#fff;color:#dc2626;border-radius:6px;cursor:pointer;font-family:var(--font,sans-serif)">
                    Limpiar errores
                </button>
            </div>`;
        }
    }

    function escHtml(s) {
        return String(s).replace(/[&<>"']/g, c =>
            ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])
        );
    }

    function showToast(msg, color) {
        const colors = {
            green:  ['#16a34a', '#dcfce7', '#86efac'],
            red:    ['#dc2626', '#fee2e2', '#fca5a5'],
            yellow: ['#92400e', '#fef9c3', '#fcd34d'],
        };
        const [fg, bg, border] = colors[color] || colors.green;
        let el = document.getElementById('offline-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'offline-toast';
            el.style.cssText = 'position:fixed;bottom:24px;right:20px;max-width:320px;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,.15);transition:opacity .3s,transform .3s;pointer-events:none;font-family:var(--font,system-ui)';
            document.body.appendChild(el);
        }
        el.style.background = bg;
        el.style.color      = fg;
        el.style.border     = `1px solid ${border}`;
        el.style.opacity    = '1';
        el.style.transform  = 'translateY(0)';
        el.textContent = msg;
        clearTimeout(el._t);
        el._t = setTimeout(() => {
            el.style.opacity   = '0';
            el.style.transform = 'translateY(8px)';
        }, 5000);
    }

    // ── Init ───────────────────────────────────────────────────────────────────
    function init() {
        migrateLegacy();
        updateUI();

        if (navigator.onLine && getPending().length > 0) {
            setTimeout(sincronizar, 1200);
        }
    }

    window.addEventListener('online', () => {
        updateUI();
        if (getPending().length > 0) {
            showToast('Conexión restaurada — sincronizando…', 'green');
            setTimeout(sincronizar, 1000);
        } else {
            showToast('Conexión restaurada.', 'green');
        }
    });

    window.addEventListener('offline', updateUI);

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', e => {
            if (e.data?.type === 'SYNC_ALL' || e.data?.type === 'SYNC_PRESTAMOS') {
                sincronizar();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ── Public API ─────────────────────────────────────────────────────────────
    window.OfflineSync = {
        // v1 compat
        guardarOffline,
        sincronizar,
        getPending,
        // v2
        guardarClienteOffline,
        guardarCobrosOffline,
        dismissFailed,
        getFailed,
        showToast,
    };
})();
