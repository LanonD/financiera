@extends('layouts.app')

@section('title', 'Nuevo préstamo')

@push('styles')
<style>
.np-grid{display:grid;grid-template-columns:380px 1fr;gap:20px;align-items:start}
.np-panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;position:sticky;top:80px}
.np-panel-header{padding:14px 20px;border-bottom:1px solid var(--border)}
.np-panel-title{font-size:14px;font-weight:600}
.np-panel-sub{font-size:11px;color:var(--text3);margin-top:2px}
.np-form{padding:20px;display:flex;flex-direction:column;gap:14px}
.np-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:block;margin-bottom:5px}
.np-input{width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-family:monospace;font-size:14px;color:var(--text);outline:none;box-sizing:border-box}
.np-input:focus{border-color:var(--accent)}
.np-select{width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-family:var(--font);font-size:13px;outline:none;cursor:pointer;color:var(--text)}
.np-hint{font-size:11px;color:var(--text3);margin-top:4px}
.cs-wrap{position:relative}
.cs-input{width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-family:var(--font);font-size:13px;outline:none;box-sizing:border-box}
.cs-input:focus{border-color:var(--accent)}
.cs-list{position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--card);border:1px solid var(--border);border-radius:6px;max-height:220px;overflow-y:auto;z-index:50;box-shadow:0 8px 24px rgba(0,0,0,.12);display:none}
.cs-list.open{display:block}
.cs-item{padding:9px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border)}
.cs-item:last-child{border-bottom:none}
.cs-item:hover{background:#f0f7ff;color:var(--accent)}
.cs-item-name{font-weight:500}
.cs-item-sub{font-size:11px;color:var(--text3);margin-top:1px}
.cs-selected{margin-top:8px;padding:8px 12px;background:#eff6ff;border:1px solid var(--accent);border-radius:6px;display:none;align-items:center;justify-content:space-between;gap:8px}
.cs-selected.show{display:flex}
.cs-selected-name{font-size:13px;font-weight:600;color:var(--accent)}
.cs-clear{border:none;background:none;cursor:pointer;color:var(--text3);font-size:16px;line-height:1;padding:0}
.cs-empty{padding:14px 12px;text-align:center;font-size:12px;color:var(--text3)}
.preview-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:16px}
.preview-header{padding:14px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:space-between}
.kpi-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:0}
.kpi-cell{padding:16px 18px;border-right:1px solid var(--border);border-bottom:1px solid var(--border)}
.kpi-cell:nth-child(even){border-right:none}
.kpi-cell:nth-last-child(-n+2){border-bottom:none}
.kpi-lbl{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:5px}
.kpi-val{font-size:20px;font-weight:700;font-family:monospace}
.pay-row{display:flex;align-items:center;justify-content:space-between;padding:10px 18px;border-bottom:1px solid var(--border)}
.pay-row:last-child{border-bottom:none}
.pay-label{font-size:13px;color:var(--text2)}
.pay-amount{font-size:15px;font-weight:700;font-family:monospace}
.empty-preview{padding:48px 20px;text-align:center;color:var(--text3)}
.schedule-table th,.schedule-table td{padding:9px 14px;font-size:12px}
.schedule-table thead{background:#f9fafb}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="{{ route('prestamos.index') }}" class="btn btn-sm" style="background:#f3f4f6;color:var(--text)">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M8 2L4 6l4 4"/></svg>
            Volver
        </a>
        <div>
            <h2 style="font-size:20px;font-weight:700;margin-bottom:2px">Nuevo préstamo</h2>
            <p style="color:var(--text2);font-size:13px">Acuerda el monto entregado y el total a retornar con el cliente</p>
        </div>
    </div>
</div>

@php
$activoMap = $clientesConPrestamo->toArray();
@endphp

<form method="POST" action="{{ route('prestamos.store') }}" id="formNuevo" onsubmit="return validarFormulario()">
@csrf

@if($errors->has('cliente_id'))
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px">
    <svg viewBox="0 0 20 20" fill="#ef4444" style="width:18px;height:18px;flex-shrink:0;margin-top:1px"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
    <span style="font-size:13px;color:#991b1b;font-weight:500">{{ $errors->first('cliente_id') }}</span>
</div>
@endif
<div class="np-grid">

    {{-- Left panel: form --}}
    <div class="np-panel">
        <div class="np-panel-header">
            <div class="np-panel-title">Datos del préstamo</div>
            <div class="np-panel-sub">Pago fijo acordado — sin tasa de interés variable</div>
        </div>
        <div class="np-form">

            {{-- Client search --}}
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
                        @if($clientes->isEmpty())
                        <div class="cs-empty">No hay clientes registrados. <a href="{{ route('clientes.create') }}" style="color:var(--accent)">Crear cliente</a></div>
                        @else
                         @foreach($clientes as $c)
                        @php $bloqueado = array_key_exists($c->id, $activoMap); @endphp
                        <div class="cs-item {{ $bloqueado ? 'cs-item-bloqueado' : '' }}"
                             data-id="{{ $c->id }}"
                             data-nombre="{{ $c->nombre }}"
                             data-celular="{{ $c->celular ?? '' }}"
                             data-bloqueado="{{ $bloqueado ? '1' : '0' }}"
                             data-promotor="{{ $bloqueado ? $activoMap[$c->id] : '' }}"
                             onclick="csSelect(this)">
                            <div style="display:flex;align-items:center;justify-content:space-between">
                                <div class="cs-item-name">{{ $c->nombre }}</div>
                                @if($bloqueado)
                                <span style="font-size:10px;background:#fee2e2;color:#991b1b;border-radius:4px;padding:1px 6px;font-weight:700">Activo</span>
                                @endif
                            </div>
                            <div class="cs-item-sub">{{ $c->celular ?? '—' }} · {{ $c->promotor?->nombre ?? '—' }}</div>
                        </div>
                        @endforeach
                        @endif
                    </div>
                    <div class="cs-selected" id="csSelected">
                        <div>
                            <div class="cs-selected-name" id="csSelectedName"></div>
                            <div style="font-size:11px;color:var(--text3)" id="csSelectedSub"></div>
                        </div>
                        <button type="button" class="cs-clear" onclick="csClear()" title="Cambiar cliente">×</button>
                    </div>
                    <input type="hidden" name="cliente_id" id="csClienteId" required>
                </div>
                <!-- Active loan warning -->
                <div id="activeLoanWarning" style="display:none;margin-top:10px;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px">
                    <div style="font-size:12px;font-weight:700;color:#991b1b;margin-bottom:2px">⚠ Cliente con préstamo activo</div>
                    <div id="activeLoanMsg" style="font-size:12px;color:#7f1d1d"></div>
                </div>
                <div class="np-hint">Solo clientes activos asignados a tu cartera</div>
            </div>

            {{-- Monto entregado --}}
            <div>
                <label class="np-label">Dinero a entregar ($)</label>
                <input type="number" name="monto_entregado" id="inEntregado"
                       class="np-input" placeholder="50,000" step="0.01" min="1"
                       oninput="calcPreview()" required>
                <div class="np-hint">Monto real que recibirá el cliente</div>
            </div>

            {{-- Monto a retornar --}}
            <div>
                <label class="np-label">Total a retornar ($)</label>
                <input type="number" name="monto_retornar" id="inRetornar"
                       class="np-input" placeholder="65,000" step="0.01" min="1"
                       oninput="calcPreview()" required>
                <div class="np-hint">Suma total de todos los pagos del cliente</div>
            </div>

            {{-- Ganancia inline --}}
            <div id="gananciaBox" style="display:none;background:rgba(22,163,74,.07);border:1px solid rgba(22,163,74,.2);border-radius:6px;padding:10px 14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <span style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#166534">Ganancia del acuerdo</span>
                    <span style="font-size:11px;color:#166534" id="ganPct">—</span>
                </div>
                <div id="ganVal" style="font-size:22px;font-weight:700;font-family:monospace;color:#16a34a"></div>
            </div>

            {{-- Num pagos + frecuencia --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div>
                    <label class="np-label">Número de pagos</label>
                    <input type="number" name="num_pagos" id="inNumPagos"
                           class="np-input" placeholder="10" step="1" min="1"
                           oninput="calcPreview()" required>
                </div>
                <div>
                    <label class="np-label">Frecuencia</label>
                    <select name="frecuencia" id="inFrecuencia" class="np-select" onchange="autoFechaPrimerCobro();calcPreview()">
                        @foreach(['Mensual','Quincenal','Semanal','Diario'] as $f)
                        <option>{{ $f }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Fecha inicio --}}
            <div>
                <label class="np-label">Fecha de inicio del préstamo</label>
                <input type="date" name="fecha_inicio" id="inFechaInicio"
                       class="np-input" value="{{ date('Y-m-d') }}"
                       style="font-family:var(--font)" oninput="onFechaInicioChange()" required>
                <div class="np-hint">Día en que se entrega el dinero al cliente</div>
            </div>

            {{-- Fecha primer cobro --}}
            <div>
                <label class="np-label">Fecha del primer cobro</label>
                <input type="date" name="fecha_primer_cobro" id="inFechaPrimerCobro"
                       class="np-input" value="{{ date('Y-m-d', strtotime('+30 days')) }}"
                       style="font-family:var(--font)" oninput="calcPreview()" required>
                <div class="np-hint" id="hintPrimerCobro">Se calcula automáticamente según la frecuencia — puedes ajustarlo</div>
            </div>

            {{-- Buttons --}}
            <div style="display:flex;gap:10px;padding-top:4px">
                <a href="{{ route('prestamos.index') }}" class="btn" style="background:#f3f4f6;color:var(--text);flex:1;text-align:center;justify-content:center">Cancelar</a>
                <button type="submit" class="btn btn-primary" id="btnCrear" style="flex:2;justify-content:center" disabled>
                    <svg width="13" height="13" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M2 7h10"/></svg>
                    Crear préstamo
                </button>
            </div>

        </div>
    </div>

    {{-- Right panel: preview --}}
    <div id="previewZone">

        <div class="preview-card" id="emptyState">
            <div class="empty-preview">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.35"><rect x="6" y="6" width="28" height="28" rx="4"/><path d="M14 20h12M20 14v12"/></svg>
                <div style="font-size:14px;font-weight:500;color:var(--text2);margin-bottom:6px">Ingresa los datos del préstamo</div>
                <div style="font-size:12px">El plan de pagos aparecerá aquí en tiempo real</div>
            </div>
        </div>

        <div class="preview-card" id="kpiCard" style="display:none">
            <div class="preview-header">
                <span>Resumen del acuerdo</span>
                <span id="pvFrecLabel" style="font-size:12px;color:var(--text3)"></span>
            </div>
            <div class="kpi-grid-2">
                <div class="kpi-cell"><div class="kpi-lbl">Dinero entregado</div><div class="kpi-val" id="pvEntregado" style="color:#3b82f6">—</div></div>
                <div class="kpi-cell"><div class="kpi-lbl">Total a cobrar</div><div class="kpi-val" id="pvRetornar">—</div></div>
                <div class="kpi-cell"><div class="kpi-lbl">Ganancia</div><div class="kpi-val" id="pvGanancia" style="color:#16a34a">—</div></div>
                <div class="kpi-cell"><div class="kpi-lbl">Rentabilidad</div><div class="kpi-val" id="pvRent" style="color:#f59e0b">—</div></div>
            </div>
        </div>

        <div class="preview-card" id="pagosCard" style="display:none">
            <div class="preview-header">Estructura de pagos</div>
            <div class="pay-row" style="background:rgba(245,158,11,.05)">
                <div>
                    <div class="pay-label">Pagos regulares <span style="font-size:11px;color:#ca8a04">(iguales)</span></div>
                    <div style="font-size:11px;color:var(--text3)" id="pvFecha1"></div>
                </div>
                <div class="pay-amount" id="pvPago1" style="color:#ca8a04">—</div>
            </div>
            <div class="pay-row" id="pvRestRow">
                <div>
                    <div class="pay-label" id="pvRestLabel">Último pago (ajuste)</div>
                    <div style="font-size:11px;color:var(--text3)" id="pvFrecInfo"></div>
                </div>
                <div class="pay-amount" id="pvCuota" style="color:#16a34a">—</div>
            </div>
            <div class="pay-row" style="background:#f9fafb">
                <div class="pay-label" style="font-weight:700">Total</div>
                <div class="pay-amount" id="pvTotal">—</div>
            </div>
        </div>

        <div class="preview-card" id="tablaCard" style="display:none">
            <div class="preview-header">
                <span>Plan de pagos</span>
                <span id="pvTablaCount" style="font-size:12px;color:var(--text3)"></span>
            </div>
            <div style="overflow-x:auto">
                <table class="schedule-table" style="width:100%">
                    <thead>
                        <tr>
                            <th style="text-align:center">#</th>
                            <th>Fecha</th>
                            <th style="text-align:right">Cuota</th>
                            <th style="text-align:right">Capital</th>
                            <th style="text-align:right">Costo crédito</th>
                            <th style="text-align:right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody id="pvTablaBody"></tbody>
                </table>
            </div>
        </div>

    </div>
</div>
</form>

@push('scripts')
<script>
let clienteSeleccionado = null;
const DIAS = { Mensual: 30, Quincenal: 14, Semanal: 7, Diario: 1 };

/* Auto-calcula fecha_primer_cobro cuando cambia fecha_inicio o frecuencia */
function onFechaInicioChange() {
    autoFechaPrimerCobro();
    calcPreview();
}
function autoFechaPrimerCobro() {
    const fechaInicio = document.getElementById('inFechaInicio').value;
    const frecuencia  = document.getElementById('inFrecuencia').value;
    if (!fechaInicio) return;
    const dias   = DIAS[frecuencia] || 30;
    const nueva  = addDays(fechaInicio, dias);
    document.getElementById('inFechaPrimerCobro').value = nueva;
    document.getElementById('hintPrimerCobro').textContent =
        `Calculado como inicio + ${dias} días (${frecuencia.toLowerCase()}) — puedes ajustarlo`;
}

function csOpen()  { document.getElementById('csList').classList.add('open'); csFilter(); }
function csClose() { document.getElementById('csList').classList.remove('open'); }
function csFilter() {
    const q = document.getElementById('csSearch').value.toLowerCase();
    let visible = 0;
    document.querySelectorAll('#csList .cs-item').forEach(el => {
        const match = !q || el.dataset.nombre.toLowerCase().includes(q) || (el.dataset.celular || '').includes(q);
        el.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    let noRes = document.getElementById('csNoRes');
    if (!noRes) {
        noRes = document.createElement('div');
        noRes.id = 'csNoRes'; noRes.className = 'cs-empty'; noRes.textContent = 'Sin resultados';
        document.getElementById('csList').appendChild(noRes);
    }
    noRes.style.display = visible === 0 && !document.querySelector('#csList .cs-empty:not(#csNoRes)') ? '' : 'none';
}
function csSelect(el) {
    clienteSeleccionado = { id: el.dataset.id, nombre: el.dataset.nombre, celular: el.dataset.celular };
    document.getElementById('csClienteId').value = el.dataset.id;
    document.getElementById('csSearch').style.display = 'none';
    document.getElementById('csSelected').classList.add('show');
    document.getElementById('csSelectedName').textContent = el.dataset.nombre;
    document.getElementById('csSelectedSub').textContent  = el.dataset.celular || '';
    document.getElementById('csList').classList.remove('open');

    // Show/hide active loan warning
    const bloqueado = el.dataset.bloqueado === '1';
    const warn = document.getElementById('activeLoanWarning');
    const msg  = document.getElementById('activeLoanMsg');
    if (bloqueado) {
        msg.textContent = `Este cliente ya tiene un préstamo activo con el promotor "${el.dataset.promotor}". No se puede crear otro mientras haya uno en curso.`;
        warn.style.display = '';
    } else {
        warn.style.display = 'none';
    }
    window._clienteBloqueado = bloqueado;

    checkCanSubmit();
}
function csClear() {
    clienteSeleccionado = null;
    window._clienteBloqueado = false;
    document.getElementById('csClienteId').value = '';
    document.getElementById('csSearch').style.display = '';
    document.getElementById('csSearch').value = '';
    document.getElementById('csSelected').classList.remove('show');
    document.getElementById('activeLoanWarning').style.display = 'none';
    csFilter(); checkCanSubmit();
}

function fmtMXN(n) { return '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

function addDays(dateStr, days) {
    const d = new Date(dateStr + 'T12:00:00');
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
}
function fmtDate(dateStr) { const [y, m, d] = dateStr.split('-'); return `${d}/${m}/${y}`; }

function calcPreview() {
    const entregado       = parseFloat(document.getElementById('inEntregado').value)       || 0;
    const retornar        = parseFloat(document.getElementById('inRetornar').value)        || 0;
    const numPagos        = parseInt(document.getElementById('inNumPagos').value)          || 0;
    const frecuencia      = document.getElementById('inFrecuencia').value;
    const fechaInicio     = document.getElementById('inFechaInicio').value;
    const fechaPrimerCobro= document.getElementById('inFechaPrimerCobro').value;
    const dias            = DIAS[frecuencia] || 30;

    if (entregado > 0 && retornar > entregado) {
        const gan = retornar - entregado;
        const pct = (gan / entregado * 100).toFixed(1);
        document.getElementById('gananciaBox').style.display = '';
        document.getElementById('ganVal').textContent = fmtMXN(gan);
        document.getElementById('ganPct').textContent = pct + '% rentabilidad';
    } else {
        document.getElementById('gananciaBox').style.display = 'none';
    }

    const ok = entregado > 0 && retornar >= entregado && numPagos > 0 && fechaInicio && fechaPrimerCobro;
    document.getElementById('emptyState').style.display = ok ? 'none' : '';
    ['kpiCard','pagosCard','tablaCard'].forEach(id => {
        document.getElementById(id).style.display = ok ? '' : 'none';
    });
    if (!ok) { checkCanSubmit(); return; }

    const cuotaBase  = numPagos > 1 ? Math.ceil(retornar / numPagos / 10) * 10 : retornar;
    const ultimoPago = Math.max(0, Math.round((retornar - cuotaBase * (numPagos - 1)) * 100) / 100);
    const ganancia   = retornar - entregado;
    const rentPct    = (ganancia / entregado * 100).toFixed(1);

    document.getElementById('pvEntregado').textContent = fmtMXN(entregado);
    document.getElementById('pvRetornar').textContent  = fmtMXN(retornar);
    document.getElementById('pvGanancia').textContent  = fmtMXN(ganancia);
    document.getElementById('pvRent').textContent      = rentPct + '%';
    document.getElementById('pvFrecLabel').textContent = `${numPagos} pagos · ${frecuencia}`;

    document.getElementById('pvPago1').textContent    = fmtMXN(cuotaBase);
    document.getElementById('pvFecha1').textContent   = `Pagos 1–${numPagos > 1 ? numPagos - 1 : 1} (iguales)`;
    document.getElementById('pvCuota').textContent    = fmtMXN(ultimoPago);
    document.getElementById('pvTotal').textContent    = fmtMXN(retornar);
    document.getElementById('pvRestRow').style.display = numPagos > 1 ? '' : 'none';
    document.getElementById('pvFrecInfo').textContent = `Primer cobro: ${fmtDate(fechaPrimerCobro)} · cada ${dias} días`;
    document.getElementById('pvTablaCount').textContent = `${numPagos} pagos · ${frecuencia}`;

    // Build schedule: payment i → fechaPrimerCobro + dias*(i-1)
    const ratio = retornar > 0 ? entregado / retornar : 1;
    let saldo = entregado; let rows = '';
    for (let i = 1; i <= numPagos; i++) {
        const fecha   = addDays(fechaPrimerCobro, dias * (i - 1));
        const cuota   = i === numPagos ? ultimoPago : cuotaBase;
        const capital = i === numPagos ? saldo : Math.round(cuota * ratio * 100) / 100;
        const interes = Math.round((cuota - capital) * 100) / 100;
        saldo = Math.max(0, Math.round((saldo - capital) * 100) / 100);
        const esUlt = i === numPagos && numPagos > 1;
        rows += `<tr ${esUlt ? 'style="background:rgba(245,158,11,.05)"' : ''}>
            <td style="text-align:center;font-size:12px;font-weight:600">${i}${esUlt ? ' <span style="font-size:10px;color:#ca8a04">(ajuste)</span>' : ''}</td>
            <td style="font-size:12px">${fmtDate(fecha)}</td>
            <td style="text-align:right;font-weight:700;color:${esUlt ? '#ca8a04' : '#16a34a'}">${fmtMXN(cuota)}</td>
            <td style="text-align:right">${fmtMXN(capital)}</td>
            <td style="text-align:right;color:#f59e0b">${fmtMXN(interes)}</td>
            <td style="text-align:right">${fmtMXN(saldo)}</td>
        </tr>`;
    }
    document.getElementById('pvTablaBody').innerHTML = rows;
    checkCanSubmit();
}

function checkCanSubmit() {
    const entregado       = parseFloat(document.getElementById('inEntregado').value)       || 0;
    const retornar        = parseFloat(document.getElementById('inRetornar').value)        || 0;
    const numPagos        = parseInt(document.getElementById('inNumPagos').value)          || 0;
    const clienteOk       = !!document.getElementById('csClienteId').value;
    const fechaPrimerCobro= document.getElementById('inFechaPrimerCobro').value;
    const bloqueado       = !!window._clienteBloqueado;
    const ok = clienteOk && !bloqueado && entregado > 0 && retornar >= entregado && numPagos > 0 && !!fechaPrimerCobro;
    const btn = document.getElementById('btnCrear');
    btn.disabled = !ok;
    btn.style.opacity = ok ? '1' : '.5';
}

function validarFormulario() {
    if (!document.getElementById('csClienteId').value) {
        alert('Selecciona un cliente para continuar.');
        return false;
    }
    document.getElementById('btnCrear').textContent = 'Creando…';
    document.getElementById('btnCrear').disabled = true;
    return true;
}

document.addEventListener('DOMContentLoaded', () => { autoFechaPrimerCobro(); calcPreview(); });
</script>
@endpush

@endsection
