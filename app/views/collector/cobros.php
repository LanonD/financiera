<?php
// Variables: $cobrador, $prestamos
$maxCobro = $cobrador['capacidad_maxima'] ?? 200000;
?>
<div class="content-header">
    <div><h2>Mis cobros</h2><p>Marca los pagos del día y envíalos al sistema</p></div>
    <button class="btn-primary" id="btnEnviar" onclick="submitCobros()" disabled style="opacity:.5">
        <svg width="13" height="13" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 7l3 3 7-7"/></svg>
        Enviar cobros
    </button>
</div>

<!-- Stats -->
<div class="cobrador-bar" style="margin-bottom:16px">
    <div class="cobrador-stat"><div class="cobrador-stat-label">Rango</div><div class="cobrador-stat-value"><?= $cobrador['rango'] ?? '—' ?></div></div>
    <div class="cobrador-stat"><div class="cobrador-stat-label">Máximo</div><div class="cobrador-stat-value accent">$<?= number_format($maxCobro,0,'.',',') ?></div></div>
    <div class="cobrador-stat">
        <div class="cobrador-stat-label">Cobrado hoy</div>
        <div class="cobrador-stat-value" id="montoCobrado">$0</div>
        <div class="range-wrap"><div class="range-track"><div class="range-fill" id="cobroFill" style="width:0%"></div></div></div>
    </div>
    <div class="cobrador-stat"><div class="cobrador-stat-label">Completos</div><div class="cobrador-stat-value" id="nCompletos" style="color:#16a34a">0</div></div>
    <div class="cobrador-stat"><div class="cobrador-stat-label">Parciales</div><div class="cobrador-stat-value" id="nParciales" style="color:#ca8a04">0</div></div>
</div>

<!-- Filtros -->
<div class="filter-panel">
    <div class="filter-group"><label>Buscar</label><input class="filter-input" id="globalSearch" placeholder="Nombre…" oninput="filterRows()"></div>
    <div class="filter-divider"></div>
    <div class="filter-group">
        <label>Estatus</label>
        <div class="status-group">
            <span class="status-pill pill-activo"   data-status="Activo"   onclick="togglePill(this)"><span class="dot"></span>Activo</span>
            <span class="status-pill pill-atrasado" data-status="Atrasado" onclick="togglePill(this)"><span class="dot"></span>Atrasado</span>
            <span class="status-pill pill-pendiente"data-status="Pendiente"onclick="togglePill(this)"><span class="dot"></span>Pendiente</span>
        </div>
    </div>
    <div class="filter-actions"><button class="btn-secondary" onclick="resetFilters()">Limpiar</button></div>
</div>

<!-- Tabla desktop -->
<div class="table-card">
    <div class="table-header">
        <div class="table-title">Clientes asignados</div>
        <div class="table-count" id="tableCount"><?= count($prestamos) ?> clientes</div>
    </div>
    <table>
        <thead><tr><th style="width:90px">Cobro</th><th>ID</th><th>Cliente</th><th>Celular</th><th>Cuota</th><th>Saldo</th><th>Próximo pago</th><th>Estatus</th></tr></thead>
        <tbody id="tableBody">
        <?php foreach ($prestamos as $row):
            $dias   = (int)($row['dias_atraso'] ?? 0);
            $fechaTxt   = $dias > 0 ? "Hoy — {$dias} día".($dias>1?'s':'')." atraso" : ($dias === 0 ? 'Hoy' : ($row['proximo_pago'] ?? '—'));
            $fechaClass = $dias >= 0 ? 'fecha-hoy' : 'fecha-ok';
            $badge = match($row['estatus']) { 'Activo' => 'badge-activo', 'Atrasado' => 'badge-atrasado', default => 'badge-pendiente' };
            $nombre = $row['cliente_nombre'] ?? '—';
        ?>
        <tr data-status="<?= $row['estatus'] ?>" data-id="<?= $row['id'] ?>" data-pago="<?= $row['cuota'] ?>" data-nombre="<?= htmlspecialchars($nombre) ?>">
            <td>
                <div style="display:flex;align-items:center;justify-content:center;gap:6px">
                    <button class="check-btn" onclick="toggleCheck(this)" title="Pago completo $<?= number_format($row['cuota'],0,'.',',') ?>">
                        <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg>
                    </button>
                    <button class="parcial-btn" onclick="openModal(this)" title="Registrar monto parcial">
                        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M3 6h8"/></svg>
                    </button>
                </div>
                <div id="tag-<?= $row['id'] ?>" style="text-align:center"></div>
            </td>
            <td class="td-id">#<?= $row['id'] ?></td>
            <td class="td-name"><span class="initials"><?= strtoupper(substr($nombre,0,2)) ?></span><?= htmlspecialchars($nombre) ?></td>
            <td class="td-numeric"><?= htmlspecialchars($row['celular'] ?? '—') ?></td>
            <td class="td-amount">$<?= number_format($row['cuota'],2,'.',',') ?></td>
            <td class="td-amount">$<?= number_format($row['saldo_actual'],2,'.',',') ?></td>
            <td class="<?= $fechaClass ?> td-numeric"><?= $fechaTxt ?></td>
            <td><span class="badge <?= $badge ?>"><span class="dot"></span><?= $row['estatus'] ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($prestamos)): ?>
        <tr><td colspan="8">
            <div style="text-align:center;padding:48px 20px">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 12px;display:block"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                <div style="font-size:14px;font-weight:600;color:var(--text-secondary);margin-bottom:6px">Sin cobros asignados</div>
                <div style="font-size:13px;color:var(--text-muted);max-width:320px;margin:0 auto">No tienes cuentas asignadas todavía. Espera a que el administrador o promotor te asigne préstamos a cobrar.</div>
            </div>
        </td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <div class="table-footer"><span id="footerInfo" class="pagination-info">Cobrado hoy: $0</span></div>
</div>

<!-- Modal pago parcial -->
<div class="modal-overlay" id="modalParcial" onclick="if(event.target===this)cerrarModal()">
    <div class="modal">
        <div class="modal-header">
            <h3>Registrar pago — <span id="mNombre" style="color:var(--accent)"></span></h3>
            <button class="modal-close" onclick="cerrarModal()">×</button>
        </div>
        <div class="modal-body">
            <div style="background:var(--bg-hover);border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:16px;display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <div><div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted)">Préstamo ID</div><div style="font-size:13px;font-weight:600;font-family:var(--font-mono)" id="mId">—</div></div>
                <div><div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted)">Cuota esperada</div><div style="font-size:13px;font-weight:600;font-family:var(--font-mono)" id="mCuota">—</div></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px">
                <div id="optCompleto" onclick="selectOpt('completo')" style="padding:10px 14px;border:1.5px solid var(--border-input);border-radius:var(--radius-sm);cursor:pointer;text-align:center;transition:all .15s">
                    <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text-muted)">Pago completo</div>
                    <div style="font-size:16px;font-weight:600;font-family:var(--font-mono)" id="optCompletoVal">—</div>
                </div>
                <div id="optParcial" onclick="selectOpt('parcial')" style="padding:10px 14px;border:1.5px solid var(--border-input);border-radius:var(--radius-sm);cursor:pointer;text-align:center;transition:all .15s">
                    <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text-muted)">Otro monto</div>
                    <div style="font-size:16px;font-weight:600;font-family:var(--font-mono)">$…</div>
                </div>
            </div>
            <div style="margin-bottom:14px">
                <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Monto cobrado</label>
                <div style="position:relative"><span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-family:var(--font-mono)">$</span>
                <input type="number" id="mMonto" placeholder="0.00" min="1" step="0.01" oninput="onMontoChange()"
                    style="width:100%;padding:9px 12px 9px 22px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:14px;outline:none"></div>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Nota (opcional)</label>
                <textarea id="mNota" rows="3" placeholder="Observaciones del cobro…"
                    style="width:100%;padding:8px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;resize:none;outline:none"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
            <button class="btn-primary" onclick="confirmarPago()">Confirmar pago</button>
        </div>
    </div>
</div>

<style>
.check-btn{width:28px;height:28px;border-radius:50%;border:2px solid var(--border-input);background:transparent;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all .15s;padding:0}
.check-btn:hover{border-color:#16a34a;background:#dcfce7}
.check-btn.checked{border-color:#16a34a;background:#16a34a}
.check-btn svg{width:13px;height:13px;opacity:0;color:white;fill:none;stroke:currentColor;stroke-width:2.5;transition:opacity .1s}
.check-btn.checked svg{opacity:1}
.parcial-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--border-input);background:transparent;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all .15s;padding:0;color:var(--text-muted)}
.parcial-btn:hover,.parcial-btn.active{border-color:#ca8a04;background:#fef9c3;color:#854d0e}
.parcial-btn svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round}
.tag-badge{display:inline-flex;align-items:center;padding:2px 7px;border-radius:10px;font-size:10px;font-weight:600;font-family:var(--font-mono)}
tr.cobro-completo td{opacity:.5}tr.cobro-completo td:first-child{opacity:1}
tr.cobro-parcial{background:#fffbeb}
.opt-selected{border-color:var(--accent)!important;background:var(--accent-light)!important}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center;backdrop-filter:blur(2px)}
.modal-overlay.open{display:flex}
.modal{background:var(--bg-card);border-radius:var(--radius-lg);width:420px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.15);overflow:hidden}
.modal-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.modal-header h3{font-size:14px;font-weight:600}
.modal-close{width:26px;height:26px;border:none;background:var(--bg-hover);border-radius:6px;cursor:pointer;font-size:16px;color:var(--text-muted)}
.modal-body{padding:20px}
.modal-footer{padding:14px 20px;border-top:1px solid var(--border);background:var(--bg-hover);display:flex;gap:8px;justify-content:flex-end}
</style>
<script>
const MAX = <?= $maxCobro ?>;
const cobros = {};
let modalRow = null;

function toggleCheck(btn) {
    const row = btn.closest('tr');
    const id  = row.dataset.id, pago = parseFloat(row.dataset.pago);
    const was = btn.classList.contains('checked');
    if (cobros[id]) { row.querySelector('.parcial-btn').classList.remove('active'); delete cobros[id]; }
    btn.classList.toggle('checked', !was);
    row.classList.toggle('cobro-completo', !was);
    row.classList.remove('cobro-parcial');
    if (!was) { cobros[id] = {tipo:'completo', monto:pago, nota:''}; setTag(id, pago, 'completo'); }
    else { delete cobros[id]; setTag(id, 0, null); }
    updateStats();
}

function openModal(btn) {
    modalRow = btn.closest('tr');
    const id = modalRow.dataset.id, pago = parseFloat(modalRow.dataset.pago);
    document.getElementById('mNombre').textContent   = modalRow.dataset.nombre;
    document.getElementById('mId').textContent       = '#' + id;
    document.getElementById('mCuota').textContent    = '$' + pago.toLocaleString();
    document.getElementById('optCompletoVal').textContent = '$' + pago.toLocaleString();
    const ex = cobros[id];
    document.getElementById('mMonto').value = ex ? ex.monto : '';
    document.getElementById('mNota').value  = ex ? (ex.nota||'') : '';
    selectOpt(ex ? ex.tipo : null);
    document.getElementById('modalParcial').classList.add('open');
    setTimeout(() => document.getElementById('mMonto').focus(), 200);
}

function cerrarModal() { document.getElementById('modalParcial').classList.remove('open'); modalRow = null; }
document.addEventListener('keydown', e => { if(e.key==='Escape') cerrarModal(); });

function selectOpt(tipo) {
    document.getElementById('optCompleto').classList.toggle('opt-selected', tipo==='completo');
    document.getElementById('optParcial').classList.toggle('opt-selected',  tipo==='parcial');
    if (tipo==='completo' && modalRow) document.getElementById('mMonto').value = modalRow.dataset.pago;
    else if (tipo==='parcial') { document.getElementById('mMonto').value=''; document.getElementById('mMonto').focus(); }
}

function onMontoChange() {
    const pago = modalRow ? parseFloat(modalRow.dataset.pago) : 0;
    const m    = parseFloat(document.getElementById('mMonto').value)||0;
    document.getElementById('optCompleto').classList.toggle('opt-selected', m===pago);
    document.getElementById('optParcial').classList.toggle('opt-selected',  m>0&&m!==pago);
}

function confirmarPago() {
    if (!modalRow) return;
    const id   = modalRow.dataset.id, pago = parseFloat(modalRow.dataset.pago);
    const monto = parseFloat(document.getElementById('mMonto').value);
    const nota  = document.getElementById('mNota').value.trim();
    if (!monto || monto <= 0) { document.getElementById('mMonto').style.borderColor='#dc2626'; return; }
    document.getElementById('mMonto').style.borderColor='';
    const tipo = monto >= pago ? 'completo' : 'parcial';
    modalRow.querySelector('.check-btn').classList.toggle('checked', tipo==='completo');
    modalRow.querySelector('.parcial-btn').classList.toggle('active', tipo==='parcial');
    modalRow.classList.toggle('cobro-completo', tipo==='completo');
    modalRow.classList.toggle('cobro-parcial',  tipo==='parcial');
    cobros[id] = { tipo, monto, nota };
    setTag(id, monto, tipo);
    updateStats();
    cerrarModal();
}

function setTag(id, monto, tipo) {
    const el = document.getElementById('tag-'+id);
    if (!el) return;
    if (!tipo||monto<=0) { el.innerHTML=''; return; }
    const bg = tipo==='completo'?'#dcfce7':'#fef9c3', tx = tipo==='completo'?'#166534':'#854d0e';
    el.innerHTML = `<span class="tag-badge" style="background:${bg};color:${tx}">$${parseFloat(monto).toLocaleString()}</span>`;
}

function updateStats() {
    let total=0, comp=0, parc=0;
    Object.values(cobros).forEach(c => { total+=c.monto; c.tipo==='completo'?comp++:parc++; });
    document.getElementById('montoCobrado').textContent = '$'+total.toLocaleString();
    document.getElementById('cobroFill').style.width    = Math.min(100,total/MAX*100).toFixed(1)+'%';
    document.getElementById('nCompletos').textContent   = comp;
    document.getElementById('nParciales').textContent   = parc;
    document.getElementById('footerInfo').textContent   = `Cobrado hoy: $${total.toLocaleString()} · ${comp} completos · ${parc} parciales`;
    const hay = Object.keys(cobros).length > 0;
    document.getElementById('btnEnviar').disabled    = !hay;
    document.getElementById('btnEnviar').style.opacity = hay ? '1' : '.5';
}

function submitCobros() {
    if (!Object.keys(cobros).length) return;
    document.getElementById('btnEnviar').textContent = 'Enviando…';
    document.getElementById('btnEnviar').disabled = true;
    fetch('<?= APP_URL ?>/cobros/registrar', {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(cobros)
    }).then(r=>r.json()).then(d => {
        if (d.ok) { alert('✅ '+d.registrados+' cobro(s) registrado(s) correctamente'); location.reload(); }
        else { alert('Error: '+(d.error||'intenta de nuevo')); document.getElementById('btnEnviar').disabled=false; document.getElementById('btnEnviar').textContent='Enviar cobros'; }
    }).catch(() => { alert('Error de conexión'); document.getElementById('btnEnviar').disabled=false; });
}

let activeFilters = new Set(['Activo','Pendiente','Atrasado']);
function togglePill(el){const s=el.dataset.status;activeFilters.has(s)?(activeFilters.delete(s),el.classList.add('inactive')):(activeFilters.add(s),el.classList.remove('inactive'));filterRows();}
function filterRows(){const q=document.getElementById('globalSearch').value.toLowerCase();let v=0;document.querySelectorAll('#tableBody tr[data-id]').forEach(r=>{const show=activeFilters.has(r.dataset.status)&&(!q||r.textContent.toLowerCase().includes(q));r.style.display=show?'':'none';if(show)v++;});document.getElementById('tableCount').textContent=v+' clientes';}
function resetFilters(){document.getElementById('globalSearch').value='';activeFilters=new Set(['Activo','Pendiente','Atrasado']);document.querySelectorAll('.status-pill').forEach(p=>p.classList.remove('inactive'));filterRows();}
window.addEventListener('load',filterRows);
</script>
