@extends('layouts.app')

@section('title', 'Nuevo cliente + préstamo')

@push('styles')
<style>
/* ── Wizard nav ────────────────────────────────────────────────────── */
.wiz-bar{display:flex;align-items:center;gap:0;margin-bottom:28px;max-width:860px}
.wiz-step{display:flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px;font-weight:600;color:var(--text3);border-bottom:2px solid var(--border);flex:1;cursor:default;transition:color .2s,border-color .2s}
.wiz-step .wiz-num{width:24px;height:24px;border-radius:50%;background:#e5e7eb;color:var(--text3);font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .2s,color .2s}
.wiz-step.active{color:var(--accent);border-bottom-color:var(--accent)}
.wiz-step.active .wiz-num{background:var(--accent);color:#fff}
.wiz-step.done{color:#16a34a;border-bottom-color:#16a34a}
.wiz-step.done .wiz-num{background:#16a34a;color:#fff}
.wiz-arrow{color:var(--border);font-size:16px;flex-shrink:0}

/* ── Sections ──────────────────────────────────────────────────────── */
.wiz-section{display:none}
.wiz-section.active{display:block}

/* ── Form fields ───────────────────────────────────────────────────── */
.frm-grid-2col{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.frm-field label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px}
.frm-field input,.frm-field select,.frm-field textarea{width:100%;padding:9px 11px;background:#f9fafb;border:1.5px solid var(--border);border-radius:6px;font-family:var(--font);font-size:13px;outline:none;transition:border-color .15s;box-sizing:border-box}
.frm-field input:focus,.frm-field select:focus,.frm-field textarea:focus{border-color:var(--accent);background:#fff}
.frm-field input.is-error,.frm-field select.is-error{border-color:#ef4444}
.frm-error{font-size:11px;color:#dc2626;margin-top:4px}
.frm-hint{font-size:11px;color:var(--text3);margin-top:4px}

/* ── Loan form (step 2) — 2-col grid ──────────────────────────────── */
.np-grid{display:grid;grid-template-columns:380px 1fr;gap:20px;align-items:start}
.np-grid>*{min-width:0}
.np-panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:visible}
.np-panel-header{padding:14px 20px;border-bottom:1px solid var(--border)}
.np-panel-title{font-size:14px;font-weight:600}
.np-panel-sub{font-size:11px;color:var(--text3);margin-top:2px}
.np-form{padding:20px;display:flex;flex-direction:column;gap:14px}
.np-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:block;margin-bottom:5px}
.np-input{width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-family:monospace;font-size:14px;color:var(--text);outline:none;box-sizing:border-box}
.np-input:focus{border-color:var(--accent)}
.np-select{width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-family:var(--font);font-size:13px;outline:none;cursor:pointer;color:var(--text)}
.np-hint{font-size:11px;color:var(--text3);margin-top:4px}
.np-2col{display:grid;grid-template-columns:1fr 1fr;gap:12px}

/* ── Preview cards (step 2 right panel) ───────────────────────────── */
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

/* ── Toggle switch ─────────────────────────────────────────────────── */
.tog-row{display:flex;align-items:center;gap:12px;padding:14px 20px;background:#f8fafc;border:1px solid var(--border);border-radius:8px;margin-bottom:20px}
.tog-switch{position:relative;width:40px;height:22px;flex-shrink:0}
.tog-switch input{opacity:0;width:0;height:0}
.tog-slider{position:absolute;cursor:pointer;inset:0;background:#d1d5db;border-radius:22px;transition:.2s}
.tog-slider:before{content:'';position:absolute;width:16px;height:16px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s}
.tog-switch input:checked+.tog-slider{background:var(--accent)}
.tog-switch input:checked+.tog-slider:before{transform:translateX(18px)}

/* ── Footer nav ────────────────────────────────────────────────────── */
.wiz-footer{display:flex;gap:10px;justify-content:flex-end;padding:14px 20px;border-top:1px solid var(--border);background:#f9fafb;border-radius:0 0 var(--radius) var(--radius);flex-wrap:wrap}

/* ── Summary pill (step 3) ─────────────────────────────────────────── */
.sum-pill{display:inline-flex;align-items:center;gap:6px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:20px;padding:5px 14px;font-size:12px;color:#1d4ed8;font-weight:600}

/* ── Responsive ────────────────────────────────────────────────────── */
@media(max-width:900px){
    .np-grid{grid-template-columns:1fr}
    .np-panel{position:static}
    #previewZone{min-width:0;max-width:100%;overflow:hidden}
}
@media(max-width:640px){
    .frm-grid-2col{grid-template-columns:1fr}
    .frm-grid-2col [style*="grid-column"]{grid-column:unset!important}
    .np-2col{grid-template-columns:1fr}
    .wiz-step .wiz-label{display:none}
    .wiz-step{justify-content:center;padding:10px 10px}
    .schedule-table th:nth-child(4),
    .schedule-table td:nth-child(4),
    .schedule-table th:nth-child(5),
    .schedule-table td:nth-child(5),
    .schedule-table th:nth-child(6),
    .schedule-table td:nth-child(6){display:none!important}
}
</style>
@endpush

@section('content')

<a href="{{ route('clientes.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);margin-bottom:16px;text-decoration:none">
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11L5 7l4-4"/></svg>
    Volver a clientes
</a>

<div style="margin-bottom:22px">
    <h2 style="font-size:20px;font-weight:700;margin-bottom:4px">Nuevo cliente + préstamo</h2>
    <p style="color:var(--text2);font-size:13px">Registra al cliente, crea el préstamo y desembolsa en un solo paso</p>
</div>

{{-- Validation errors (server-side) --}}
@if($errors->any())
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px 16px;margin-bottom:16px;max-width:860px">
    <div style="font-size:13px;font-weight:600;color:#991b1b;margin-bottom:6px">Corrige los siguientes errores:</div>
    <ul style="margin:0;padding-left:18px;font-size:12px;color:#b91c1c">
        @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Step indicator ──────────────────────────────────────────────────────── --}}
<div class="wiz-bar" style="max-width:860px">
    <div class="wiz-step active" id="navStep1">
        <div class="wiz-num">1</div>
        <span class="wiz-label">Cliente</span>
    </div>
    <div class="wiz-step" id="navStep2">
        <div class="wiz-num">2</div>
        <span class="wiz-label">Préstamo</span>
    </div>
    <div class="wiz-step" id="navStep3">
        <div class="wiz-num">3</div>
        <span class="wiz-label">Desembolso</span>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<form id="wizForm" method="POST" action="{{ route('clientes.store_with_prestamo') }}" enctype="multipart/form-data">
@csrf
<input type="hidden" name="with_prestamo" id="hidWithPrestamo" value="0">
<input type="hidden" name="desembolsar"   id="hidDesembolsar"  value="0">
<input type="hidden" name="monto_retornar" id="inRetornar">

{{-- ── STEP 1: Cliente ─────────────────────────────────────────────────── --}}
<div class="wiz-section active" id="section1">
<div class="card" style="max-width:860px;padding:0;overflow:hidden">

    <div style="padding:20px 20px 4px">
        <div class="frm-grid-2col">

            {{-- Nombre --}}
            <div style="grid-column:1/-1" class="frm-field">
                <label>Nombre completo *</label>
                <input type="text" name="nombre" id="fNombre" value="{{ old('nombre') }}" required
                       class="{{ $errors->has('nombre') ? 'is-error' : '' }}">
                @error('nombre')<p class="frm-error">{{ $message }}</p>@enderror
            </div>

            {{-- Celular --}}
            <div class="frm-field">
                <label>Celular</label>
                <input type="tel" name="celular" value="{{ old('celular') }}"
                       class="{{ $errors->has('celular') ? 'is-error' : '' }}">
                @error('celular')<p class="frm-error">{{ $message }}</p>@enderror
            </div>

            {{-- Email --}}
            <div class="frm-field">
                <label>Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="{{ $errors->has('email') ? 'is-error' : '' }}">
                @error('email')<p class="frm-error">{{ $message }}</p>@enderror
            </div>

            {{-- CURP --}}
            <div class="frm-field">
                <label>CURP</label>
                <input type="text" name="curp" value="{{ old('curp') }}" maxlength="18"
                       style="text-transform:uppercase"
                       class="{{ $errors->has('curp') ? 'is-error' : '' }}">
                @error('curp')<p class="frm-error">{{ $message }}</p>@enderror
            </div>

            {{-- Ocupación --}}
            <div class="frm-field">
                <label>Ocupación</label>
                <select name="ocupacion">
                    <option value="">— Seleccionar —</option>
                    @foreach(['Empleado','Negocio propio','Independiente','Otro'] as $op)
                    <option value="{{ $op }}" {{ old('ocupacion') === $op ? 'selected' : '' }}>{{ $op }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Dirección --}}
            <div style="grid-column:1/-1" class="frm-field">
                <label>Dirección</label>
                <input type="text" name="direccion" id="direccion" value="{{ old('direccion') }}"
                       autocomplete="off"
                       class="{{ $errors->has('direccion') ? 'is-error' : '' }}">
                @error('direccion')<p class="frm-error">{{ $message }}</p>@enderror
            </div>

            {{-- Mapa --}}
            <div style="grid-column:1/-1">
                <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:block;margin-bottom:5px">
                    Ubicación en mapa <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text3)">(opcional)</span>
                </label>
                <div id="map" style="width:100%;height:220px;border-radius:8px;border:1px solid var(--border);overflow:hidden;background:#f1f5f9;display:flex;align-items:center;justify-content:center">
                    <span id="map-placeholder" style="font-size:12px;color:var(--text3)">Cargando mapa…</span>
                </div>
                <input type="hidden" name="latitud"  id="latitud"  value="{{ old('latitud') }}">
                <input type="hidden" name="longitud" id="longitud" value="{{ old('longitud') }}">
                <p style="font-size:12px;color:var(--text2);margin-top:6px">Da clic en el mapa o mueve el marcador para seleccionar la ubicación.</p>
            </div>

            {{-- Promotor (solo admin) --}}
            @if(auth()->user()->puesto === 'admin')
            <div style="grid-column:1/-1" class="frm-field">
                <label>Promotor</label>
                <select name="promotor_id" class="{{ $errors->has('promotor_id') ? 'is-error' : '' }}">
                    <option value="">— Sin asignar —</option>
                    @foreach($promotores as $p)
                    <option value="{{ $p->id }}" {{ old('promotor_id') == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                    @endforeach
                </select>
                @error('promotor_id')<p class="frm-error">{{ $message }}</p>@enderror
            </div>
            @endif

        </div>
    </div>

    <div class="wiz-footer">
        <a href="{{ route('clientes.index') }}" class="btn" style="background:#f3f4f6;color:var(--text)">Cancelar</a>
        <button type="button" class="btn btn-primary" onclick="goTo(2)">
            Siguiente — Préstamo
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M4 2l4 4-4 4"/></svg>
        </button>
    </div>
</div>
</div>

{{-- ── STEP 2: Préstamo ──────────────────────────────────────────────────── --}}
<div class="wiz-section" id="section2">

    {{-- Toggle: ¿agregar préstamo? --}}
    <div class="tog-row" style="max-width:860px;margin-bottom:20px">
        <label class="tog-switch">
            <input type="checkbox" id="togPrestamo" checked onchange="togglePrestamo()">
            <span class="tog-slider"></span>
        </label>
        <div>
            <div style="font-size:13px;font-weight:600">Agregar préstamo</div>
            <div style="font-size:11px;color:var(--text3)">Activa para crear un préstamo junto con el cliente</div>
        </div>
    </div>

    {{-- Loan form (shown when toggle ON) --}}
    <div id="prestamoFields">
    <div class="np-grid" style="max-width:860px">

        {{-- Left: form panel --}}
        <div class="np-panel">
            <div class="np-panel-header">
                <div class="np-panel-title">Datos del préstamo</div>
                <div class="np-panel-sub">Pago fijo — sin tasa de interés variable</div>
            </div>
            <div class="np-form">

                {{-- Monto entregado --}}
                <div>
                    <label class="np-label">Dinero a entregar ($)</label>
                    <input type="number" name="monto_entregado" id="inEntregado"
                           class="np-input" placeholder="50,000" step="0.01" min="1"
                           value="{{ old('monto_entregado') }}"
                           oninput="calcPreview()">
                    <div class="np-hint">Monto real que recibirá el cliente</div>
                </div>

                {{-- % Rentabilidad --}}
                <div>
                    <label class="np-label">% Rentabilidad</label>
                    <input type="number" id="inRentabilidad"
                           class="np-input" placeholder="30" step="0.1" min="0.1"
                           oninput="calcPreview()">
                    <div class="np-hint">Porcentaje de ganancia sobre el monto entregado</div>
                </div>

                {{-- Total a retornar box --}}
                <div id="retornarBox" style="display:none;background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.2);border-radius:6px;padding:10px 14px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
                        <span style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#1d4ed8">Total a retornar</span>
                        <span style="font-size:11px;color:#1d4ed8" id="retornarGan">—</span>
                    </div>
                    <div id="retornarVal" style="font-size:22px;font-weight:700;font-family:monospace;color:#2563eb"></div>
                </div>

                {{-- Num pagos + frecuencia --}}
                <div class="np-2col">
                    <div>
                        <label class="np-label">Número de pagos</label>
                        <input type="number" name="num_pagos" id="inNumPagos"
                               class="np-input" placeholder="10" step="1" min="1"
                               value="{{ old('num_pagos') }}"
                               oninput="calcPreview()">
                    </div>
                    <div>
                        <label class="np-label">Frecuencia</label>
                        <select name="frecuencia" id="inFrecuencia" class="np-select" onchange="autoFechaPrimerCobro();calcPreview()">
                            @foreach(['Mensual','Quincenal','Semanal','Diario'] as $f)
                            <option {{ old('frecuencia') === $f ? 'selected' : '' }}>{{ $f }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Fecha inicio --}}
                <div>
                    <label class="np-label">Fecha de inicio del préstamo</label>
                    <input type="date" name="fecha_inicio" id="inFechaInicio"
                           class="np-input" value="{{ old('fecha_inicio', date('Y-m-d')) }}"
                           style="font-family:var(--font)" oninput="onFechaInicioChange()">
                </div>

                {{-- Fecha primer cobro --}}
                <div>
                    <label class="np-label">Fecha del primer cobro</label>
                    <input type="date" name="fecha_primer_cobro" id="inFechaPrimerCobro"
                           class="np-input" value="{{ old('fecha_primer_cobro', date('Y-m-d', strtotime('+30 days'))) }}"
                           style="font-family:var(--font)" oninput="calcPreview()">
                    <div class="np-hint" id="hintPrimerCobro">Se calcula automáticamente según la frecuencia</div>
                </div>

            </div>
        </div>

        {{-- Right: live preview --}}
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
                        <div class="pay-label">Último pago (ajuste)</div>
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
    </div>{{-- /#prestamoFields --}}

    {{-- Message when loan is disabled --}}
    <div id="sinPrestamoMsg" style="display:none;max-width:860px">
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:20px 22px;display:flex;align-items:center;gap:14px">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round"><circle cx="14" cy="14" r="10"/><path d="M9 14l3 3 6-6"/></svg>
            <div>
                <div style="font-size:14px;font-weight:600;color:#15803d">Solo se registrará el cliente</div>
                <div style="font-size:12px;color:#166534;margin-top:2px">Podrás agregar un préstamo después desde el detalle del cliente.</div>
            </div>
        </div>
    </div>

    <div class="card" style="max-width:860px;padding:0;overflow:hidden;margin-top:20px;border:none;box-shadow:none">
    <div class="wiz-footer" style="border:1px solid var(--border);border-radius:var(--radius)">
        <button type="button" class="btn" style="background:#f3f4f6;color:var(--text)" onclick="goTo(1)">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M8 2L4 6l4 4"/></svg>
            Anterior
        </button>
        {{-- Submit without loan --}}
        <button type="button" id="btnSoloCliente" class="btn" style="display:none;background:#f3f4f6;color:var(--text)" onclick="submitSoloCliente()">
            Registrar solo cliente
        </button>
        {{-- Continue to disbursement --}}
        <button type="button" id="btnSiguiente2" class="btn btn-primary" onclick="goTo(3)">
            Siguiente — Desembolso
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M4 2l4 4-4 4"/></svg>
        </button>
    </div>
    </div>

</div>

{{-- ── STEP 3: Desembolso ────────────────────────────────────────────────── --}}
<div class="wiz-section" id="section3">
<div style="max-width:860px">

    {{-- Summary pills --}}
    <div id="sumResumen" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
        <span class="sum-pill" id="sumCliente">—</span>
        <span class="sum-pill" style="background:#f0fdf4;border-color:#bbf7d0;color:#15803d" id="sumPrestamo">—</span>
    </div>

    {{-- Toggle: desembolsar ahora --}}
    <div class="tog-row">
        <label class="tog-switch">
            <input type="checkbox" id="togDesembolso" checked onchange="toggleDesembolso()">
            <span class="tog-slider"></span>
        </label>
        <div>
            <div style="font-size:13px;font-weight:600">Desembolsar ahora</div>
            <div style="font-size:11px;color:var(--text3)">El préstamo quedará en estatus <strong>Activo</strong>. Si desactivas, queda como <strong>Pendiente</strong>.</div>
        </div>
    </div>

    {{-- Disbursement fields --}}
    <div id="desembolsoFields" class="card" style="padding:0;overflow:hidden">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Datos del desembolso</div>
        <div style="padding:20px">
            <div class="frm-grid-2col">

                <div class="frm-field">
                    <label>Forma de entrega</label>
                    <select name="forma_entrega">
                        <option value="">— Seleccionar —</option>
                        @foreach(['Efectivo','Transferencia','Cheque','Depósito','Otro'] as $fe)
                        <option value="{{ $fe }}" {{ old('forma_entrega') === $fe ? 'selected' : '' }}>{{ $fe }}</option>
                        @endforeach
                    </select>
                    <div class="frm-hint">Cómo se entrega el dinero al cliente</div>
                </div>

                <div class="frm-field">
                    <label>Fecha de entrega</label>
                    <input type="date" name="fecha_entrega" id="inFechaEntrega"
                           value="{{ old('fecha_entrega', date('Y-m-d')) }}"
                           style="font-family:var(--font)">
                    <div class="frm-hint">Fecha en que se entrega el dinero</div>
                </div>

                <div style="grid-column:1/-1" class="frm-field">
                    <label>Nota de entrega</label>
                    <textarea name="nota_entrega" rows="2" style="resize:vertical" placeholder="Observaciones sobre el desembolso…">{{ old('nota_entrega') }}</textarea>
                </div>

            </div>

            {{-- Documentos del desembolso --}}
            <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--border)">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:12px">Documentos del desembolso</div>

                @php $docStyle = 'width:100%;padding:7px 10px;background:#f9fafb;border:1.5px solid var(--border);border-radius:6px;font-family:var(--font);font-size:12px;color:var(--text);cursor:pointer;box-sizing:border-box'; @endphp

                <div class="frm-grid-2col">
                    <div class="frm-field">
                        <label>INE / Identificación <span style="color:#ef4444">*</span></label>
                        <input type="file" name="doc_ine" accept=".jpg,.jpeg,.png,.pdf"
                               style="{{ $docStyle }}" onchange="previewDocWiz(this,'wiz-prev-ine')">
                        <div id="wiz-prev-ine" style="display:none;margin-top:6px"></div>
                        <div class="frm-hint">JPG, PNG o PDF — máx. 10 MB</div>
                    </div>

                    <div class="frm-field">
                        <label>Pagaré <span style="color:#ef4444">*</span></label>
                        <input type="file" name="doc_pagare" accept=".jpg,.jpeg,.png,.pdf"
                               style="{{ $docStyle }}" onchange="previewDocWiz(this,'wiz-prev-pagare')">
                        <div id="wiz-prev-pagare" style="display:none;margin-top:6px"></div>
                        <div class="frm-hint">JPG, PNG o PDF — máx. 10 MB</div>
                    </div>

                    <div class="frm-field">
                        <label>Comprobante de domicilio <span style="color:#ef4444">*</span></label>
                        <input type="file" name="doc_comprobante" accept=".jpg,.jpeg,.png,.pdf"
                               style="{{ $docStyle }}" onchange="previewDocWiz(this,'wiz-prev-comprobante')">
                        <div id="wiz-prev-comprobante" style="display:none;margin-top:6px"></div>
                        <div class="frm-hint">JPG, PNG o PDF — máx. 10 MB</div>
                    </div>

                    <div class="frm-field">
                        <label>Foto de domicilio <span style="color:var(--text3);font-weight:400">(opcional)</span></label>
                        <input type="file" name="doc_foto_domicilio" accept=".jpg,.jpeg,.png"
                               style="{{ $docStyle }}" onchange="previewDocWiz(this,'wiz-prev-foto')">
                        <div id="wiz-prev-foto" style="display:none;margin-top:6px"></div>
                        <div class="frm-hint">Solo imágenes — máx. 10 MB</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- "Pending" message when disbursement is OFF --}}
    <div id="pendingMsg" style="display:none" class="card" style="padding:18px 22px">
        <div style="padding:18px 22px;display:flex;align-items:center;gap:12px">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            <div style="font-size:13px;color:#92400e">El préstamo quedará en estatus <strong>Pendiente</strong> hasta que lo desembolses desde el detalle del préstamo.</div>
        </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden;margin-top:20px;border:none;box-shadow:none">
    <div class="wiz-footer" style="border:1px solid var(--border);border-radius:var(--radius)">
        <button type="button" class="btn" style="background:#f3f4f6;color:var(--text)" onclick="goTo(2)">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M8 2L4 6l4 4"/></svg>
            Anterior
        </button>
        <button type="submit" id="btnFinal" class="btn btn-primary" onclick="prepareSubmit()">
            <span id="btnFinalLabel">Registrar cliente y préstamo</span>
        </button>
    </div>
    </div>

</div>
</div>
{{-- ── /STEPS ────────────────────────────────────────────────────────────── --}}

</form>

@endsection

@push('scripts')
<script>
// ── State ──────────────────────────────────────────────────────────────────
let currentStep = {{ $errors->any() ? 2 : 1 }};
const DIAS = { Mensual: 30, Quincenal: 14, Semanal: 7, Diario: 1 };

// ── Step navigation ────────────────────────────────────────────────────────
function goTo(step) {
    // Validate before leaving step 1
    if (currentStep === 1 && step > 1) {
        const nombre = document.getElementById('fNombre');
        if (!nombre.value.trim()) {
            nombre.focus();
            nombre.style.borderColor = '#ef4444';
            nombre.addEventListener('input', () => nombre.style.borderColor = '', { once: true });
            return;
        }
    }
    // Validate before leaving step 2 (only if loan is toggled on)
    if (currentStep === 2 && step === 3) {
        const togP = document.getElementById('togPrestamo').checked;
        if (togP) {
            const e = parseFloat(document.getElementById('inEntregado').value) || 0;
            const r = parseFloat(document.getElementById('inRentabilidad').value) || 0;
            const n = parseInt(document.getElementById('inNumPagos').value) || 0;
            if (!e || !r || !n) {
                alert('Completa los datos del préstamo: monto, rentabilidad y número de pagos son requeridos.');
                return;
            }
        }
    }

    // Update sections
    document.querySelectorAll('.wiz-section').forEach(s => s.classList.remove('active'));
    document.getElementById('section' + step).classList.add('active');

    // Update nav pills
    [1, 2, 3].forEach(i => {
        const el = document.getElementById('navStep' + i);
        el.classList.remove('active', 'done');
        if (i < step) el.classList.add('done');
        if (i === step) el.classList.add('active');
    });

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });

    currentStep = step;

    // Update step 3 summary pills
    if (step === 3) updateSummary();
}

// ── Loan toggle ────────────────────────────────────────────────────────────
function togglePrestamo() {
    const on = document.getElementById('togPrestamo').checked;
    document.getElementById('prestamoFields').style.display = on ? '' : 'none';
    document.getElementById('sinPrestamoMsg').style.display = on ? 'none' : '';
    document.getElementById('btnSiguiente2').style.display  = on ? '' : 'none';
    document.getElementById('btnSoloCliente').style.display = on ? 'none' : '';
}

// ── Document preview ──────────────────────────────────────────────────────
function previewDocWiz(input, previewId) {
    const container = document.getElementById(previewId);
    const file = input.files[0];
    if (!file) { container.style.display = 'none'; container.innerHTML = ''; return; }
    const isPdf  = file.name.toLowerCase().endsWith('.pdf');
    const sizeMb = (file.size / 1024 / 1024).toFixed(1);
    if (isPdf) {
        container.innerHTML = `<div style="display:flex;align-items:center;gap:8px;padding:7px 10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;font-size:12px;color:#15803d">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="1" width="12" height="14" rx="1.5"/><path d="M5 5h6M5 8h6M5 11h4"/></svg>
            ${file.name} <span style="color:var(--text3)">(${sizeMb} MB)</span></div>`;
    } else {
        const reader = new FileReader();
        reader.onload = e => {
            container.innerHTML = `<img src="${e.target.result}" style="max-width:100%;max-height:100px;border-radius:6px;border:1px solid var(--border);object-fit:cover">
                <div style="font-size:11px;color:var(--text3);margin-top:3px">${file.name} · ${sizeMb} MB</div>`;
        };
        reader.readAsDataURL(file);
    }
    container.style.display = '';
}

// ── Disbursement toggle ───────────────────────────────────────────────────
function toggleDesembolso() {
    const on = document.getElementById('togDesembolso').checked;
    document.getElementById('desembolsoFields').style.display = on ? '' : 'none';
    document.getElementById('pendingMsg').style.display       = on ? 'none' : '';
    const lbl = document.getElementById('btnFinalLabel');
    lbl.textContent = on ? 'Registrar cliente y préstamo' : 'Registrar (sin desembolsar)';
}

// ── Submit helpers ─────────────────────────────────────────────────────────
function submitSoloCliente() {
    document.getElementById('hidWithPrestamo').value = '0';
    document.getElementById('hidDesembolsar').value  = '0';
    document.getElementById('wizForm').submit();
}

function prepareSubmit() {
    const togP = document.getElementById('togPrestamo').checked;
    const togD = document.getElementById('togDesembolso').checked;
    document.getElementById('hidWithPrestamo').value = togP ? '1' : '0';
    document.getElementById('hidDesembolsar').value  = (togP && togD) ? '1' : '0';
}

// ── Summary pills ──────────────────────────────────────────────────────────
function updateSummary() {
    const nombre = document.getElementById('fNombre').value || '(sin nombre)';
    document.getElementById('sumCliente').textContent = '👤 ' + nombre;

    const entregado  = parseFloat(document.getElementById('inEntregado').value) || 0;
    const retornar   = parseFloat(document.getElementById('inRetornar').value)  || 0;
    const numPagos   = parseInt(document.getElementById('inNumPagos').value)    || 0;
    const frecuencia = document.getElementById('inFrecuencia').value;

    if (entregado > 0 && retornar > 0) {
        document.getElementById('sumPrestamo').textContent =
            '💰 ' + fmtMXN(entregado) + ' → ' + fmtMXN(retornar) + ' · ' + numPagos + ' pagos ' + frecuencia;
    } else {
        document.getElementById('sumPrestamo').style.display = 'none';
    }
}

// ── Date helpers ───────────────────────────────────────────────────────────
function onFechaInicioChange() { autoFechaPrimerCobro(); calcPreview(); }
function autoFechaPrimerCobro() {
    const fi  = document.getElementById('inFechaInicio').value;
    const frec = document.getElementById('inFrecuencia').value;
    if (!fi) return;
    const dias = DIAS[frec] || 30;
    document.getElementById('inFechaPrimerCobro').value = addDays(fi, dias);
    document.getElementById('hintPrimerCobro').textContent =
        `Calculado como inicio + ${dias} días (${frec.toLowerCase()}) — puedes ajustarlo`;
}

function addDays(dateStr, days) {
    const d = new Date(dateStr + 'T12:00:00');
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
}
function fmtDate(s) { const [y,m,d] = s.split('-'); return `${d}/${m}/${y}`; }
function fmtMXN(n)  { return '$' + n.toLocaleString('es-MX', { minimumFractionDigits:2, maximumFractionDigits:2 }); }

// ── Live preview ───────────────────────────────────────────────────────────
function calcPreview() {
    const entregado        = parseFloat(document.getElementById('inEntregado').value)    || 0;
    const rentPct          = parseFloat(document.getElementById('inRentabilidad').value) || 0;
    const numPagos         = parseInt(document.getElementById('inNumPagos').value)       || 0;
    const frecuencia       = document.getElementById('inFrecuencia').value;
    const fechaInicio      = document.getElementById('inFechaInicio').value;
    const fechaPrimerCobro = document.getElementById('inFechaPrimerCobro').value;
    const dias             = DIAS[frecuencia] || 30;

    const retornar = entregado > 0 && rentPct > 0
        ? Math.round(entregado * (1 + rentPct / 100) * 100) / 100 : 0;

    document.getElementById('inRetornar').value = retornar || '';

    if (entregado > 0 && retornar > 0) {
        const gan = retornar - entregado;
        document.getElementById('retornarBox').style.display = '';
        document.getElementById('retornarVal').textContent   = fmtMXN(retornar);
        document.getElementById('retornarGan').textContent   = 'Ganancia: ' + fmtMXN(gan);
    } else {
        document.getElementById('retornarBox').style.display = 'none';
    }

    const ok = entregado > 0 && retornar >= entregado && numPagos > 0 && fechaInicio && fechaPrimerCobro;
    document.getElementById('emptyState').style.display = ok ? 'none' : '';
    ['kpiCard','pagosCard','tablaCard'].forEach(id =>
        document.getElementById(id).style.display = ok ? '' : 'none');
    if (!ok) return;

    const cuotaBase  = numPagos > 1 ? Math.ceil(retornar / numPagos / 10) * 10 : retornar;
    const ultimoPago = Math.max(0, Math.round((retornar - cuotaBase * (numPagos - 1)) * 100) / 100);
    const ganancia   = retornar - entregado;
    const rentPctFmt = (ganancia / entregado * 100).toFixed(1);

    document.getElementById('pvEntregado').textContent = fmtMXN(entregado);
    document.getElementById('pvRetornar').textContent  = fmtMXN(retornar);
    document.getElementById('pvGanancia').textContent  = fmtMXN(ganancia);
    document.getElementById('pvRent').textContent      = rentPctFmt + '%';
    document.getElementById('pvFrecLabel').textContent = `${numPagos} pagos · ${frecuencia}`;
    document.getElementById('pvPago1').textContent     = fmtMXN(cuotaBase);
    document.getElementById('pvFecha1').textContent    = `Pagos 1–${numPagos > 1 ? numPagos - 1 : 1} (iguales)`;
    document.getElementById('pvCuota').textContent     = fmtMXN(ultimoPago);
    document.getElementById('pvTotal').textContent     = fmtMXN(retornar);
    document.getElementById('pvRestRow').style.display = numPagos > 1 ? '' : 'none';
    document.getElementById('pvFrecInfo').textContent  = `Primer cobro: ${fmtDate(fechaPrimerCobro)} · cada ${dias} días`;
    document.getElementById('pvTablaCount').textContent= `${numPagos} pagos · ${frecuencia}`;

    let interesPendiente = Math.round((retornar - entregado) * 100) / 100;
    let saldo = entregado; let rows = '';
    for (let i = 1; i <= numPagos; i++) {
        const fecha   = addDays(fechaPrimerCobro, dias * (i - 1));
        const cuota   = i === numPagos ? ultimoPago : cuotaBase;
        const interes = Math.min(cuota, Math.round(interesPendiente * 100) / 100);
        const capital = Math.round((cuota - interes) * 100) / 100;
        interesPendiente = Math.max(0, Math.round((interesPendiente - interes) * 100) / 100);
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
}

// ── Init ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    autoFechaPrimerCobro();
    calcPreview();
    // If there are validation errors, open to step 2
    @if($errors->any())
    goTo(2);
    @endif
});

// ── Google Maps ────────────────────────────────────────────────────────────
let mapInitialized = false;
function initMap() {
    try {
        const latInput  = document.getElementById('latitud');
        const lngInput  = document.getElementById('longitud');
        const dirInput  = document.getElementById('direccion');
        const defaultLoc = { lat: 17.220609, lng: -100.630392 };
        const initLoc = (latInput.value && lngInput.value)
            ? { lat: parseFloat(latInput.value), lng: parseFloat(lngInput.value) }
            : defaultLoc;

        document.getElementById('map-placeholder')?.remove();

        const map = new google.maps.Map(document.getElementById('map'), { center: initLoc, zoom: 15 });
        const geocoder = new google.maps.Geocoder();
        const marker   = new google.maps.Marker({ position: initLoc, map, draggable: true });

        if (!latInput.value) { latInput.value = initLoc.lat; lngInput.value = initLoc.lng; }

        const autocomplete = new google.maps.places.Autocomplete(dirInput, {
            componentRestrictions: { country: 'mx' },
            fields: ['formatted_address', 'geometry', 'name'],
        });
        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();
            if (!place.geometry?.location) return;
            const loc = place.geometry.location;
            map.setCenter(loc); map.setZoom(17);
            marker.setPosition(loc);
            latInput.value = loc.lat(); lngInput.value = loc.lng();
            dirInput.value = place.formatted_address || place.name;
        });

        function setLoc(latLng) {
            marker.setPosition(latLng);
            latInput.value = latLng.lat(); lngInput.value = latLng.lng();
            geocoder.geocode({ location: latLng }, (results, status) => {
                if (status === 'OK' && results[0]) dirInput.value = results[0].formatted_address;
            });
        }
        map.addListener('click', e => setLoc(e.latLng));
        marker.addListener('dragend', e => setLoc(e.latLng));
        mapInitialized = true;
    } catch(err) {
        console.warn('Maps error:', err);
        document.getElementById('map').innerHTML =
            '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:12px;color:#9ca3af">Mapa no disponible</div>';
    }
}
window.addEventListener('load', () => {
    if (!mapInitialized) {
        const ph = document.getElementById('map-placeholder');
        if (ph) ph.textContent = 'Mapa no disponible — el formulario funciona sin él.';
    }
});
</script>

<script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfb3MRYco1aN4yaJyXmK8jperHTMJl07E&libraries=places&callback=initMap"
    onerror="document.getElementById('map').innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;font-size:12px;color:#9ca3af\'>Mapa no disponible</div>'">
</script>

@endpush
