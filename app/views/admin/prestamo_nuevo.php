<?php
// Variables: $clientes
$frecuencias = ['Mensual' => 30, 'Quincenal' => 14, 'Semanal' => 7, 'Diario' => 1];
?>

<style>
.np-grid{display:grid;grid-template-columns:380px 1fr;gap:20px;align-items:start}
.np-panel{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;position:sticky;top:80px}
.np-panel-header{padding:14px 20px;border-bottom:1px solid var(--border)}
.np-panel-title{font-size:14px;font-weight:600}
.np-panel-sub{font-size:11px;color:var(--text-muted);margin-top:2px}
.np-form{padding:20px;display:flex;flex-direction:column;gap:14px}
.np-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px}
.np-input{width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:14px;color:var(--text-primary);outline:none;box-sizing:border-box}
.np-input:focus{border-color:var(--accent)}
.np-select{width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none;cursor:pointer;color:var(--text-primary)}
.np-hint{font-size:11px;color:var(--text-muted);margin-top:4px}
/* Client searcher */
.cs-wrap{position:relative}
.cs-input{width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none;box-sizing:border-box}
.cs-input:focus{border-color:var(--accent)}
.cs-list{position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);max-height:220px;overflow-y:auto;z-index:50;box-shadow:0 8px 24px rgba(0,0,0,.12);display:none}
.cs-list.open{display:block}
.cs-item{padding:9px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border)}
.cs-item:last-child{border-bottom:none}
.cs-item:hover,.cs-item.selected{background:var(--accent-light);color:var(--accent)}
.cs-item-name{font-weight:500}
.cs-item-sub{font-size:11px;color:var(--text-muted);margin-top:1px}
.cs-selected{margin-top:8px;padding:8px 12px;background:var(--accent-light);border:1px solid var(--accent);border-radius:var(--radius-sm);display:none;align-items:center;justify-content:space-between;gap:8px}
.cs-selected.show{display:flex}
.cs-selected-name{font-size:13px;font-weight:600;color:var(--accent)}
.cs-clear{border:none;background:none;cursor:pointer;color:var(--text-muted);font-size:16px;line-height:1;padding:0}
.cs-empty{padding:14px 12px;text-align:center;font-size:12px;color:var(--text-muted)}
/* Preview card */
.preview-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:16px}
.preview-header{padding:14px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:space-between}
.kpi-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:0}
.kpi-cell{padding:16px 18px;border-right:1px solid var(--border);border-bottom:1px solid var(--border)}
.kpi-cell:nth-child(even){border-right:none}
.kpi-cell:nth-last-child(-n+2){border-bottom:none}
.kpi-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:5px}
.kpi-val{font-size:20px;font-weight:700;font-family:var(--font-mono)}
.pay-row{display:flex;align-items:center;justify-content:space-between;padding:10px 18px;border-bottom:1px solid var(--border)}
.pay-row:last-child{border-bottom:none}
.pay-label{font-size:13px;color:var(--text-secondary)}
.pay-amount{font-size:15px;font-weight:700;font-family:var(--font-mono)}
.ganancia-bar{height:6px;background:var(--bg-input);border-radius:3px;overflow:hidden;margin-top:6px}
.ganancia-fill{height:100%;background:linear-gradient(90deg,#3b82f6,#16a34a);border-radius:3px;transition:width .3s}
.empty-preview{padding:48px 20px;text-align:center;color:var(--text-muted)}
.schedule-table th,.schedule-table td{padding:9px 14px;font-size:12px}
.schedule-table thead{background:var(--bg-hover)}
</style>

<div class="content-header">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="<?= APP_URL ?>/prestamos" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--text-muted);text-decoration:none;padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--bg-card)">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M8 2L4 6l4 4"/></svg>
            Volver
        </a>
        <div>
            <h2>Nuevo préstamo</h2>
            <p>Acuerda el monto entregado y el total a retornar con el cliente</p>
        </div>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:var(--radius-sm);padding:10px 16px;margin-bottom:16px;font-size:13px;color:#991b1b;font-weight:500">
    Datos inválidos. Verifica que el monto a retornar sea mayor al entregado y que el número de pagos sea al menos 1.
</div>
<?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/prestamos/crear2" id="formNuevo"
      onsubmit="return validarFormulario()">
<div class="np-grid">

    <!-- ── Panel izquierdo: formulario ───────────────────────────────────── -->
    <div class="np-panel">
        <div class="np-panel-header">
            <div class="np-panel-title">Datos del préstamo</div>
            <div class="np-panel-sub">Pago fijo acordado — sin tasa de interés variable</div>
        </div>
        <div class="np-form">

            <!-- Cliente -->
            <div>
                <label class="np-label">Cliente</label>
                <div class="cs-wrap" id="csWrap">
                    <input type="text" class="cs-input" id="csSearch"
                           placeholder="Buscar por nombre o celular…"
                           autocomplete="off"
                           oninput="csFilter()"
                           onfocus="csOpen()"
                           onblur="setTimeout(csClose, 180)">
                    <div class="cs-list" id="csList">
                        <?php if (empty($clientes)): ?>
                        <div class="cs-empty">No hay clientes registrados. <a href="<?= APP_URL ?>/clientes" style="color:var(--accent)">Crear cliente</a></div>
                        <?php else: ?>
                        <?php foreach ($clientes as $c): ?>
                        <div class="cs-item"
                             data-id="<?= $c['id'] ?>"
                             data-nombre="<?= htmlspecialchars($c['nombre']) ?>"
                             data-celular="<?= htmlspecialchars($c['celular'] ?? '') ?>"
                             onclick="csSelect(this)">
                            <div class="cs-item-name"><?= htmlspecialchars($c['nombre']) ?></div>
                            <div class="cs-item-sub"><?= htmlspecialchars($c['celular'] ?? '—') ?> · <?= htmlspecialchars($c['promotor_nombre'] ?? '—') ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="cs-selected" id="csSelected">
                        <div>
                            <div class="cs-selected-name" id="csSelectedName"></div>
                            <div style="font-size:11px;color:var(--text-muted)" id="csSelectedSub"></div>
                        </div>
                        <button type="button" class="cs-clear" onclick="csClear()" title="Cambiar cliente">×</button>
                    </div>
                    <input type="hidden" name="cliente_id" id="csClienteId" required>
                </div>
                <div class="np-hint">Solo clientes activos asignados a tu cartera</div>
            </div>

            <!-- Monto entregado -->
            <div>
                <label class="np-label">Dinero a entregar ($)</label>
                <input type="number" name="monto_entregado" id="inEntregado"
                       class="np-input" placeholder="50,000" step="0.01" min="1"
                       oninput="calcPreview()" required>
                <div class="np-hint">Monto real que recibirá el cliente</div>
            </div>

            <!-- Monto a retornar -->
            <div>
                <label class="np-label">Total a retornar ($)</label>
                <input type="number" name="monto_retornar" id="inRetornar"
                       class="np-input" placeholder="65,000" step="0.01" min="1"
                       oninput="calcPreview()" required>
                <div class="np-hint">Suma total de todos los pagos del cliente</div>
            </div>

            <!-- Ganancia inline -->
            <div id="gananciaBox" style="display:none;background:rgba(22,163,74,.07);border:1px solid rgba(22,163,74,.2);border-radius:var(--radius-sm);padding:10px 14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <span style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#166534">Ganancia del acuerdo</span>
                    <span style="font-size:11px;color:#166534" id="ganPct">—</span>
                </div>
                <div id="ganVal" style="font-size:22px;font-weight:700;font-family:var(--font-mono);color:#16a34a"></div>
                <div class="ganancia-bar"><div class="ganancia-fill" id="ganFill" style="width:0%"></div></div>
            </div>

            <!-- Num pagos + frecuencia -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div>
                    <label class="np-label">Número de pagos</label>
                    <input type="number" name="num_pagos" id="inNumPagos"
                           class="np-input" placeholder="10" step="1" min="1"
                           oninput="calcPreview()" required>
                </div>
                <div>
                    <label class="np-label">Frecuencia</label>
                    <select name="frecuencia" id="inFrecuencia" class="np-select" onchange="calcPreview()">
                        <?php foreach (array_keys($frecuencias) as $f): ?>
                        <option><?= $f ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Fecha inicio -->
            <div>
                <label class="np-label">Fecha de inicio</label>
                <input type="date" name="fecha_inicio" id="inFechaInicio"
                       class="np-input" value="<?= date('Y-m-d') ?>"
                       style="font-family:var(--font)" oninput="calcPreview()" required>
            </div>

            <!-- Botones -->
            <div style="display:flex;gap:10px;padding-top:4px">
                <a href="<?= APP_URL ?>/prestamos" class="btn-secondary" style="text-decoration:none;flex:1;text-align:center;padding:10px">Cancelar</a>
                <button type="submit" class="btn-primary" id="btnCrear" style="flex:2;justify-content:center;padding:10px" disabled>
                    <svg width="13" height="13" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M2 7h10"/></svg>
                    Crear préstamo
                </button>
            </div>

        </div>
    </div>

    <!-- ── Panel derecho: preview ─────────────────────────────────────────── -->
    <div id="previewZone">

        <!-- Estado vacío -->
        <div class="preview-card" id="emptyState">
            <div class="empty-preview">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.35"><rect x="6" y="6" width="28" height="28" rx="4"/><path d="M14 20h12M20 14v12"/></svg>
                <div style="font-size:14px;font-weight:500;color:var(--text-secondary);margin-bottom:6px">Ingresa los datos del préstamo</div>
                <div style="font-size:12px">El plan de pagos aparecerá aquí en tiempo real</div>
            </div>
        </div>

        <!-- KPIs -->
        <div class="preview-card" id="kpiCard" style="display:none">
            <div class="preview-header">
                <span>Resumen del acuerdo</span>
                <span id="pvFrecLabel" style="font-size:12px;color:var(--text-muted)"></span>
            </div>
            <div class="kpi-grid">
                <div class="kpi-cell"><div class="kpi-label">Dinero entregado</div><div class="kpi-val" id="pvEntregado" style="color:#3b82f6">—</div></div>
                <div class="kpi-cell"><div class="kpi-label">Total a cobrar</div><div class="kpi-val" id="pvRetornar">—</div></div>
                <div class="kpi-cell"><div class="kpi-label">Ganancia</div><div class="kpi-val" id="pvGanancia" style="color:#16a34a">—</div></div>
                <div class="kpi-cell"><div class="kpi-label">Rentabilidad</div><div class="kpi-val" id="pvRent" style="color:#f59e0b">—</div></div>
            </div>
        </div>

        <!-- Estructura de pagos -->
        <div class="preview-card" id="pagosCard" style="display:none">
            <div class="preview-header">Estructura de pagos</div>
            <div class="pay-row" style="background:rgba(245,158,11,.05)">
                <div>
                    <div class="pay-label">Pago 1 <span style="font-size:11px;color:#ca8a04">(ajuste de redondeo)</span></div>
                    <div style="font-size:11px;color:var(--text-muted)" id="pvFecha1"></div>
                </div>
                <div class="pay-amount" id="pvPago1" style="color:#ca8a04">—</div>
            </div>
            <div class="pay-row" id="pvRestRow">
                <div>
                    <div class="pay-label" id="pvRestLabel">Pagos 2–N (iguales)</div>
                    <div style="font-size:11px;color:var(--text-muted)" id="pvFrecInfo"></div>
                </div>
                <div class="pay-amount" id="pvCuota" style="color:#16a34a">—</div>
            </div>
            <div class="pay-row" style="background:var(--bg-hover)">
                <div class="pay-label" style="font-weight:700">Total</div>
                <div class="pay-amount" id="pvTotal">—</div>
            </div>
        </div>

        <!-- Tabla de pagos preview -->
        <div class="preview-card" id="tablaCard" style="display:none">
            <div class="preview-header">
                <span>Plan de pagos</span>
                <span id="pvTablaCount" style="font-size:12px;color:var(--text-muted)"></span>
            </div>
            <div style="overflow-x:auto">
                <table class="schedule-table" style="width:100%">
                    <thead>
                        <tr>
                            <th style="text-align:center">#</th>
                            <th>Fecha</th>
                            <th class="td-amount">Cuota</th>
                            <th class="td-amount">Capital</th>
                            <th class="td-amount">Costo crédito</th>
                            <th class="td-amount">Saldo</th>
                        </tr>
                    </thead>
                    <tbody id="pvTablaBody"></tbody>
                </table>
            </div>
        </div>

    </div>
</div>
</form>

<script>
// ── Buscador de clientes ─────────────────────────────────────────────────────
let clienteSeleccionado = null;

function csOpen() {
    document.getElementById('csList').classList.add('open');
    csFilter();
}
function csClose() {
    document.getElementById('csList').classList.remove('open');
}
function csFilter() {
    const q = document.getElementById('csSearch').value.toLowerCase();
    let visible = 0;
    document.querySelectorAll('#csList .cs-item').forEach(el => {
        const match = !q
            || el.dataset.nombre.toLowerCase().includes(q)
            || (el.dataset.celular || '').includes(q);
        el.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    const empty = document.querySelector('#csList .cs-empty');
    if (empty) return;
    // show "no results" if needed
    let noRes = document.getElementById('csNoRes');
    if (!noRes) {
        noRes = document.createElement('div');
        noRes.id = 'csNoRes';
        noRes.className = 'cs-empty';
        noRes.textContent = 'Sin resultados';
        document.getElementById('csList').appendChild(noRes);
    }
    noRes.style.display = visible === 0 ? '' : 'none';
}
function csSelect(el) {
    clienteSeleccionado = { id: el.dataset.id, nombre: el.dataset.nombre, celular: el.dataset.celular };
    document.getElementById('csClienteId').value = el.dataset.id;
    document.getElementById('csSearch').style.display = 'none';
    const sel = document.getElementById('csSelected');
    sel.classList.add('show');
    document.getElementById('csSelectedName').textContent = el.dataset.nombre;
    document.getElementById('csSelectedSub').textContent  = el.dataset.celular || '';
    document.getElementById('csList').classList.remove('open');
    checkCanSubmit();
}
function csClear() {
    clienteSeleccionado = null;
    document.getElementById('csClienteId').value = '';
    document.getElementById('csSearch').style.display = '';
    document.getElementById('csSearch').value = '';
    document.getElementById('csSelected').classList.remove('show');
    csFilter();
    checkCanSubmit();
}

// ── Preview en tiempo real ───────────────────────────────────────────────────
const DIAS = { Mensual: 30, Quincenal: 14, Semanal: 7, Diario: 1 };

function fmtMXN(n) {
    return '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function roundDownMexican(amount) {
    if (amount <= 0)      return 0;
    if (amount < 100)     return Math.floor(amount / 10)   * 10;   // $10s
    if (amount < 1000)    return Math.floor(amount / 50)   * 50;   // $50s
    if (amount < 5000)    return Math.floor(amount / 100)  * 100;  // $100s
    if (amount < 20000)   return Math.floor(amount / 500)  * 500;  // $500s
    return Math.floor(amount / 1000) * 1000;                       // $1000s
}

function addDays(dateStr, days) {
    const d = new Date(dateStr + 'T12:00:00');
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
}

function fmtDate(dateStr) {
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}

function calcPreview() {
    const entregado  = parseFloat(document.getElementById('inEntregado').value)  || 0;
    const retornar   = parseFloat(document.getElementById('inRetornar').value)   || 0;
    const numPagos   = parseInt(document.getElementById('inNumPagos').value)     || 0;
    const frecuencia = document.getElementById('inFrecuencia').value;
    const fechaInicio= document.getElementById('inFechaInicio').value;
    const dias       = DIAS[frecuencia] || 30;

    // Ganancia inline
    if (entregado > 0 && retornar > entregado) {
        const gan = retornar - entregado;
        const pct = (gan / entregado * 100).toFixed(1);
        document.getElementById('gananciaBox').style.display = '';
        document.getElementById('ganVal').textContent = fmtMXN(gan);
        document.getElementById('ganPct').textContent = pct + '% rentabilidad';
        document.getElementById('ganFill').style.width = Math.min(100, pct) + '%';
    } else {
        document.getElementById('gananciaBox').style.display = 'none';
    }

    const ok = entregado > 0 && retornar >= entregado && numPagos > 0 && fechaInicio;
    document.getElementById('emptyState').style.display = ok ? 'none' : '';
    ['kpiCard','pagosCard','tablaCard'].forEach(id => {
        document.getElementById(id).style.display = ok ? '' : 'none';
    });

    if (!ok) { checkCanSubmit(); return; }

    // Cuota = ceil a la decena más cercana (termina en 0); último pago absorbe el ajuste
    const cuotaBase  = numPagos > 1 ? Math.ceil(retornar / numPagos / 10) * 10 : retornar;
    const ultimoPago = Math.max(0, Math.round((retornar - cuotaBase * (numPagos - 1)) * 100) / 100);
    const ganancia   = retornar - entregado;
    const rentPct    = (ganancia / entregado * 100).toFixed(1);

    // KPIs
    document.getElementById('pvEntregado').textContent = fmtMXN(entregado);
    document.getElementById('pvRetornar').textContent  = fmtMXN(retornar);
    document.getElementById('pvGanancia').textContent  = fmtMXN(ganancia);
    document.getElementById('pvRent').textContent      = rentPct + '%';
    document.getElementById('pvFrecLabel').textContent = `${numPagos} pagos · ${frecuencia}`;

    // Estructura
    const fechaUlt = addDays(fechaInicio, dias * numPagos);
    document.getElementById('pvPago1').textContent    = fmtMXN(cuotaBase);
    document.getElementById('pvFecha1').textContent   = `Pagos 1–${numPagos > 1 ? numPagos - 1 : 1} (iguales)`;
    document.getElementById('pvCuota').textContent    = fmtMXN(ultimoPago);
    document.getElementById('pvTotal').textContent    = fmtMXN(retornar);
    if (numPagos > 1) {
        document.getElementById('pvRestLabel').textContent = `Pago ${numPagos} (ajuste final) · ${fmtDate(fechaUlt)}`;
        document.getElementById('pvRestRow').style.display = '';
    } else {
        document.getElementById('pvRestRow').style.display = 'none';
    }
    document.getElementById('pvFrecInfo').textContent = `Cada ${dias} días · ${frecuencia.toLowerCase()}`;
    document.getElementById('pvTablaCount').textContent = `${numPagos} pagos · ${frecuencia}`;

    // Tabla de pagos
    const ratio = retornar > 0 ? entregado / retornar : 1;
    let saldo    = entregado;
    let rows     = '';
    for (let i = 1; i <= numPagos; i++) {
        const fecha     = addDays(fechaInicio, dias * i);
        const cuota     = i === numPagos ? ultimoPago : cuotaBase;
        const capital   = i === numPagos ? saldo : Math.round(cuota * ratio * 100) / 100;
        const interes   = Math.round((cuota - capital) * 100) / 100;
        saldo           = Math.max(0, Math.round((saldo - capital) * 100) / 100);
        const esUlt     = i === numPagos && numPagos > 1;
        rows += `<tr ${esUlt ? 'style="background:rgba(245,158,11,.05)"' : ''}>
            <td style="text-align:center;font-size:12px;font-weight:600">${i}${esUlt ? ' <span style="font-size:10px;color:#ca8a04">(ajuste)</span>' : ''}</td>
            <td style="font-size:12px">${fmtDate(fecha)}</td>
            <td class="td-amount" style="font-weight:700;color:${esUlt ? '#ca8a04' : '#16a34a'}">${fmtMXN(cuota)}</td>
            <td class="td-amount">${fmtMXN(capital)}</td>
            <td class="td-amount" style="color:#f59e0b">${fmtMXN(interes)}</td>
            <td class="td-amount">${fmtMXN(saldo)}</td>
        </tr>`;
    }
    document.getElementById('pvTablaBody').innerHTML = rows;

    checkCanSubmit();
}

function checkCanSubmit() {
    const entregado = parseFloat(document.getElementById('inEntregado').value)  || 0;
    const retornar  = parseFloat(document.getElementById('inRetornar').value)   || 0;
    const numPagos  = parseInt(document.getElementById('inNumPagos').value)     || 0;
    const clienteOk = !!document.getElementById('csClienteId').value;
    const ok = clienteOk && entregado > 0 && retornar >= entregado && numPagos > 0;
    const btn = document.getElementById('btnCrear');
    btn.disabled     = !ok;
    btn.style.opacity = ok ? '1' : '.5';
}

function validarFormulario() {
    const clienteId = document.getElementById('csClienteId').value;
    if (!clienteId) {
        alert('Selecciona un cliente para continuar.');
        document.getElementById('csSearch').focus();
        return false;
    }
    document.getElementById('btnCrear').textContent = 'Creando…';
    document.getElementById('btnCrear').disabled = true;
    return true;
}

// Inicializar
document.addEventListener('DOMContentLoaded', () => { calcPreview(); });
</script>
