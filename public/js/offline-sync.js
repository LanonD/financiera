/**
 * offline-sync.js
 * Manages the offline queue for pending prestamos.
 * Uses localStorage to persist data and syncs with the server when online.
 */
(function () {
    'use strict';

    const STORAGE_KEY = 'financiera_prestamos_offline';

    // ── Queue helpers ──────────────────────────────────────────────────────────
    function getPending() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }
        catch { return []; }
    }

    function savePending(list) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
    }

    function guardarOffline(data) {
        const pending = getPending();
        const entry = {
            localId:       Date.now() + '-' + Math.random().toString(36).slice(2, 8),
            savedAt:       new Date().toISOString(),
            clienteNombre: data._clienteNombre || '(sin nombre)',
            synced:        false,
            data:          Object.fromEntries(
                               Object.entries(data).filter(([k]) => k !== '_clienteNombre')
                           ),
        };
        pending.push(entry);
        savePending(pending);
        updateUI();
        registerBackgroundSync();
        return entry;
    }

    function removeFromPending(localId) {
        savePending(getPending().filter(p => p.localId !== localId));
        updateUI();
    }

    // ── CSRF token (always fresh from current page) ────────────────────────────
    function getCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    // ── Sync a single entry with the server ────────────────────────────────────
    async function syncOne(entry) {
        const body = new URLSearchParams();
        body.append('_token', getCsrf());
        Object.entries(entry.data).forEach(([k, v]) => body.append(k, v));

        const resp = await fetch('/prestamos', {
            method:  'POST',
            headers: {
                'Content-Type':     'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
            redirect: 'follow',
        });

        // Success = server redirected to /prestamos/{id}
        if (resp.ok && resp.redirected && /\/prestamos\/\d+/.test(resp.url)) {
            return { ok: true };
        }
        // 419 = session/CSRF expired — user needs to refresh
        if (resp.status === 419) {
            return { ok: false, reason: 'csrf' };
        }
        return { ok: false, reason: 'server', status: resp.status };
    }

    // ── Sync all pending entries ───────────────────────────────────────────────
    async function sincronizar() {
        if (!navigator.onLine) return;
        const pending = getPending();
        if (!pending.length) return;

        let synced = 0, failed = 0, csrfExpired = false;

        for (const entry of [...pending]) {
            try {
                const result = await syncOne(entry);
                if (result.ok) {
                    removeFromPending(entry.localId);
                    synced++;
                } else {
                    if (result.reason === 'csrf') csrfExpired = true;
                    failed++;
                }
            } catch {
                failed++;
            }
        }

        if (synced > 0) {
            showToast(`✓ ${synced} préstamo(s) sincronizado(s) correctamente.`, 'green');
        }
        if (csrfExpired) {
            showToast('Sesión expirada. Recarga la página para sincronizar.', 'red');
        } else if (failed > 0 && synced === 0) {
            showToast(`No se pudieron sincronizar ${failed} préstamo(s). Se intentará de nuevo.`, 'red');
        }

        updateUI();
        return { synced, failed };
    }

    // ── Background Sync registration ───────────────────────────────────────────
    function registerBackgroundSync() {
        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            navigator.serviceWorker.ready
                .then(sw => sw.sync.register('sync-prestamos'))
                .catch(() => {});
        }
    }

    // ── UI helpers ─────────────────────────────────────────────────────────────
    function updateUI() {
        updateBadge();
        updatePendingPanel();
        updateOfflineBanner();
    }

    function updateBadge() {
        const count = getPending().length;
        document.querySelectorAll('.offline-badge').forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'inline-flex' : 'none';
        });
    }

    function updateOfflineBanner() {
        const banner = document.getElementById('offline-banner');
        if (!banner) return;
        banner.style.display = navigator.onLine ? 'none' : '';
    }

    function updatePendingPanel() {
        const panel = document.getElementById('offline-pending-panel');
        if (!panel) return;
        const pending = getPending();
        if (!pending.length) { panel.style.display = 'none'; return; }

        panel.style.display = '';
        const list = document.getElementById('offline-pending-list');
        if (!list) return;
        list.innerHTML = pending.map(p => {
            const date = new Date(p.savedAt);
            const fmt  = date.toLocaleDateString('es-MX') + ' ' + date.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
            const monto = parseFloat(p.data.monto_entregado || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
            return `<div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid var(--border);gap:12px">
                <div style="min-width:0">
                    <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escHtml(p.clienteNombre)}</div>
                    <div style="font-size:11px;color:var(--text2)">${monto} · ${p.data.frecuencia} · ${p.data.num_pagos} pagos</div>
                    <div style="font-size:10px;color:var(--text3);margin-top:2px">Guardado: ${fmt}</div>
                </div>
                <span style="font-size:10px;padding:2px 8px;border-radius:999px;background:#fef9c3;color:#854d0e;font-weight:600;white-space:nowrap">Pendiente</span>
            </div>`;
        }).join('');
    }

    function escHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function showToast(msg, color) {
        const colors = { green: ['#16a34a', '#dcfce7'], red: ['#dc2626', '#fee2e2'] };
        const [fg, bg] = colors[color] || colors.green;

        let el = document.getElementById('offline-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'offline-toast';
            el.style.cssText = [
                'position:fixed', 'bottom:24px', 'right:24px',
                'max-width:340px', 'padding:12px 18px',
                'border-radius:8px', 'font-size:13px', 'font-weight:500',
                'z-index:9999', 'box-shadow:0 4px 20px rgba(0,0,0,.15)',
                'transition:opacity .3s,transform .3s',
                'pointer-events:none',
            ].join(';');
            document.body.appendChild(el);
        }
        el.textContent = msg;
        el.style.background = bg;
        el.style.color = fg;
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
        clearTimeout(el._t);
        el._t = setTimeout(() => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(8px)';
        }, 5000);
    }

    // ── Initialization ─────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        updateUI();
        if (navigator.onLine && getPending().length > 0) {
            setTimeout(sincronizar, 1200); // slight delay so page settles
        }
    });

    window.addEventListener('online', () => {
        updateUI();
        if (getPending().length > 0) {
            showToast('Conexión restaurada. Sincronizando préstamos…', 'green');
            setTimeout(sincronizar, 1000);
        } else {
            showToast('Conexión restaurada.', 'green');
        }
    });

    window.addEventListener('offline', () => updateUI());

    // Listen for messages from the Service Worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', e => {
            if (e.data?.type === 'SYNC_PRESTAMOS') sincronizar();
        });
    }

    // Public API
    window.OfflineSync = { guardarOffline, sincronizar, getPending };
})();
