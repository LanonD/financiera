@extends('layouts.app')

@section('title', 'Nuevo préstamo')

@push('styles')
<style>
.np-grid{display:grid;grid-template-columns:380px 1fr;gap:20px;align-items:start}
.np-grid>*{min-width:0}
.np-panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:visible;position:sticky;top:80px}
.np-panel-header{border-radius:var(--radius) var(--radius) 0 0;overflow:hidden}
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
@media(max-width:900px){
    .np-grid{grid-template-columns:1fr!important;}
    #previewZone{min-width:0;max-width:100%;overflow:hidden}
    .np-panel{position:static!important;}
}
@media(max-width:600px){
    /* Preview visible pero compacto */
    #previewZone{display:block!important;min-width:0;max-width:100%;overflow:hidden}
    /* Ocultar columnas Capital y Costo — solo # Fecha Cuota */
    .schedule-table th:nth-child(4),
    .schedule-table td:nth-child(4),
    .schedule-table th:nth-child(5),
    .schedule-table td:nth-child(5),
    .schedule-table th:nth-child(6),
    .schedule-table td:nth-child(6){display:none!important}
    /* Tabla ocupa todo el ancho disponible sin scroll */
    .schedule-table{font-size:13px!important}
    .schedule-table th,.schedule-table td{padding:8px 10px!important}
    /* KPI 2x2 compacto */
    .kpi-grid-2{grid-template-columns:1fr 1fr!important}
    .kpi-val{font-size:16px!important}
    /* Cards del plan sin padding excesivo */
    .preview-card{margin-bottom:10px!important}
    .pay-row{padding:10px 14px!important}
    .pay-amount{font-size:13px!important}
}
@media(max-width:900px){
    /* Remove overflow so date picker icon is never clipped */
    .np-panel{overflow:visible!important;}
}
@media(max-width:768px){
    /* Page header back button row stacks */
    .np-page-header{flex-wrap:wrap!important;gap:8px!important;}
    /* Form submit buttons: full width */
    .np-form > div:last-child{flex-direction:column!important;}
    .np-form > div:last-child .btn,
    .np-form > div:last-child button{flex:none!important;width:100%!important;justify-content:center!important;}
    /* Inputs: ensure no fixed widths */
    .np-input,.np-select,.cs-input{width:100%!important;box-sizing:border-box!important;}
    /* Date inputs: shrink font so calendar icon has room */
    .np-input[type="date"]{font-size:13px!important;font-family:var(--font)!important;padding-right:8px!important;}
    /* Two-col grid under 600px stays 1 col */
    .np-2col{grid-template-columns:1fr!important;}
}
@media(max-width:640px){
    .kpi-grid-2{grid-template-columns:1fr!important;}
    .np-form-section{padding:16px!important;}
    .np-form{padding:14px!important;}
    .np-panel{border-radius:10px!important;}
}
@media(max-width:600px){
    .np-2col{grid-template-columns:1fr!important;}
}
</style>
@endpush

@section('content')

<div class="np-page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="{{ route('prestamos.index') }}" class="btn btn-sm" style="background:#f3f4f6;color:var(--text)">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M8 2L4 6l4 4"/></svg>
            Volver
        </a>
        <div>
            <h2 style="font-size:20px;font-weight:700;margin-bottom:2px">Nuevo préstamo</h2>
            <p style="color:var(--text2);font-size:13px">Ingresa el monto entregado y el porcentaje de rentabilidad acordado</p>
        </div>
    </div>
</div>

@php
$activoMap = $clientesConPrestamo;
$docsMap   = $clientesConDocs ?? [];
@endphp

<form method="POST" action="{{ route('prestamos.store') }}" id="formNuevo" enctype="multipart/form-data" novalidate onsubmit="return manejarSubmit(event)">
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
                        @php
                            $bloqueado = array_key_exists($c->id, $activoMap);
                            $cruzado   = !$bloqueado && array_key_exists($c->id, $clientesConPrestamoCruzado ?? []);
                            $cDocs     = $docsMap[$c->id] ?? ['ine'=>false,'comprobante'=>false];
                        @endphp
                        <div class="cs-item {{ ($bloqueado || $cruzado) ? 'cs-item-bloqueado' : '' }}"
                             data-id="{{ $c->id }}"
                             data-nombre="{{ $c->nombre }}"
                             data-celular="{{ $c->celular ?? '' }}"
                             data-bloqueado="{{ $bloqueado ? '1' : '0' }}"
                             data-promotor="{{ $bloqueado ? $activoMap[$c->id] : '' }}"
                             data-cruzado="{{ $cruzado ? '1' : '0' }}"
                             data-admin-cruzado="{{ $cruzado ? ($clientesConPrestamoCruzado[$c->id] ?? '') : '' }}"
                             data-tiene-ine="{{ $cDocs['ine'] ? '1' : '0' }}"
                             data-tiene-comprobante="{{ $cDocs['comprobante'] ? '1' : '0' }}"
                             onclick="csSelect(this)">
                            <div style="display:flex;align-items:center;justify-content:space-between">
                                <div class="cs-item-name">{{ $c->nombre }}</div>
                                @if($bloqueado)
                                <span style="font-size:10px;background:#fee2e2;color:#991b1b;border-radius:4px;padding:1px 6px;font-weight:700">Activo</span>
                                @elseif($cruzado)
                                <span style="font-size:10px;background:#fff3cd;color:#856404;border-radius:4px;padding:1px 6px;font-weight:700">Deuda activa</span>
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

            {{-- % Rentabilidad → calcula monto_retornar --}}
            <div>
                <label class="np-label">% Rentabilidad</label>
                <input type="number" id="inRentabilidad"
                       class="np-input" placeholder="30" step="0.1" min="0.1"
                       oninput="calcPreview()" required>
                <div class="np-hint">Porcentaje de ganancia sobre el monto entregado</div>
            </div>
            <input type="hidden" name="monto_retornar" id="inRetornar">

            {{-- Total a retornar calculado --}}
            <div id="retornarBox" style="display:none;background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.2);border-radius:6px;padding:10px 14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
                    <span style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#1d4ed8">Total a retornar</span>
                    <span style="font-size:11px;color:#1d4ed8" id="retornarGan">—</span>
                </div>
                <div id="retornarVal" style="font-size:22px;font-weight:700;font-family:monospace;color:#2563eb"></div>
            </div>

            {{-- Num pagos + frecuencia --}}
            <div class="np-2col" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
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
                       min="{{ date('Y-m-d') }}"
                       style="font-family:var(--font);max-width:100%" oninput="onFechaInicioChange()" required>
                <div class="np-hint">Hoy o fecha futura — día en que se entrega el dinero al cliente</div>
            </div>

            {{-- Fecha primer cobro --}}
            <div>
                <label class="np-label">Fecha del primer cobro</label>
                <input type="date" name="fecha_primer_cobro" id="inFechaPrimerCobro"
                       class="np-input" value="{{ date('Y-m-d', strtotime('+30 days')) }}"
                       min="{{ date('Y-m-d') }}"
                       style="font-family:var(--font);max-width:100%" oninput="calcPreview()" required>
                <div class="np-hint" id="hintPrimerCobro">Se calcula automáticamente según la frecuencia — puedes ajustarlo</div>
            </div>

            {{-- Desembolso toggle ──────────────────────────────────────── --}}
            <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:2px">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:0" id="togDesembolsoRow">
                    <label style="position:relative;width:38px;height:21px;flex-shrink:0;cursor:pointer">
                        <input type="checkbox" id="togDesembolso" name="desembolsar" value="1"
                               onchange="toggleDesembolso()" style="opacity:0;width:0;height:0">
                        <span id="togSlider" style="position:absolute;cursor:pointer;inset:0;background:#d1d5db;border-radius:21px;transition:.2s">
                            <span id="togKnob" style="position:absolute;width:15px;height:15px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s;display:block"></span>
                        </span>
                    </label>
                    <div>
                        <div style="font-size:12px;font-weight:600;color:var(--text)">Desembolsar ahora</div>
                        <div style="font-size:11px;color:var(--text3)" id="togDesembolsoHint">Activa para entregar el dinero al crear el préstamo</div>
                    </div>
                </div>

                {{-- Disbursement fields (hidden by default) --}}
                <div id="desembolsoFields" style="display:none;margin-top:14px;flex-direction:column;gap:12px">
                    <div>
                        <label class="np-label">Forma de entrega</label>
                        <select name="forma_entrega" class="np-select">
                            <option value="">— Seleccionar —</option>
                            @foreach(['Efectivo','Transferencia','Cheque','Depósito','Otro'] as $fe)
                            <option value="{{ $fe }}">{{ $fe }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="np-label">Fecha de entrega</label>
                        <input type="date" name="fecha_entrega" id="inFechaEntrega"
                               class="np-input" value="{{ date('Y-m-d') }}"
                               style="font-family:var(--font)">
                    </div>
                    <div>
                        <label class="np-label">Nota de entrega</label>
                        <textarea name="nota_entrega" rows="2"
                                  style="width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-family:var(--font);font-size:13px;outline:none;resize:vertical;box-sizing:border-box"
                                  placeholder="Observaciones…"></textarea>
                    </div>

                    {{-- Documentos --}}
                    <div style="border-top:1px solid var(--border);padding-top:12px;display:flex;flex-direction:column;gap:12px">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)">Documentos del desembolso</div>

                        @php
                        $docs = [
                            ['ine',         'doc_ine',           'INE / Identificación',      true,  '.jpg,.jpeg,.png,.pdf', 'image/*,application/pdf'],
                            ['pagare',      'doc_pagare',        'Pagaré',                    true,  '.jpg,.jpeg,.png,.pdf', 'image/*,application/pdf'],
                            ['comprobante', 'doc_comprobante',   'Comprobante de domicilio',  true,  '.jpg,.jpeg,.png,.pdf', 'image/*,application/pdf'],
                            ['foto',        'doc_foto_domicilio','Foto de domicilio',         false, '.jpg,.jpeg,.png',      'image/*'],
                        ];
                        @endphp

                        @foreach($docs as [$key, $name, $label, $required, $accept, $camAccept])
                        <div id="np_{{ $key }}_wrap">
                            <label class="np-label" id="np_{{ $key }}_label">
                                {{ $label }}
                                @if($required)<span id="np_{{ $key }}_req" style="color:#ef4444">*</span>@else<span style="font-weight:400;text-transform:none;color:var(--text3)"> (opcional)</span>@endif
                            </label>
                            <div style="display:flex;gap:6px">
                                {{-- Subir archivo --}}
                                <label style="flex:1;display:flex;align-items:center;gap:7px;padding:8px 12px;background:#f9fafb;border:1.5px dashed var(--border);border-radius:7px;cursor:pointer;font-size:12px;color:var(--text2);transition:border-color .15s;min-width:0" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    <span style="white-space:nowrap">Subir archivo</span>
                                    <input type="file" name="{{ $name }}" id="np_{{ $key }}_file" accept="{{ $accept }}" style="display:none"
                                           onchange="npSelDoc('{{ $key }}', this)">
                                </label>
                                {{-- Tomar foto directa (sin guardar en galería) --}}
                                <label style="display:flex;align-items:center;gap:7px;padding:8px 12px;background:#f9fafb;border:1.5px dashed var(--border);border-radius:7px;cursor:pointer;font-size:12px;color:var(--text2);transition:border-color .15s;white-space:nowrap" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                    Tomar foto
                                    <input type="file" id="np_{{ $key }}_cam" accept="{{ $camAccept }}" capture="environment" style="display:none"
                                           onchange="npSelDoc('{{ $key }}', this)">
                                </label>
                            </div>
                            <div id="np_{{ $key }}_txt" style="display:none;margin-top:5px;font-size:11px;color:#166534;padding:5px 8px;background:rgba(22,163,74,.06);border-radius:5px"></div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Buttons --}}
            <div style="display:flex;gap:10px;padding-top:4px">
                <a href="{{ route('prestamos.index') }}" class="btn" style="background:#f3f4f6;color:var(--text);flex:1;text-align:center;justify-content:center">Cancelar</a>
                <button type="submit" class="btn btn-primary" id="btnCrear" style="flex:2;justify-content:center" disabled>
                    <span id="btnCrearLabel">Crear préstamo</span>
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
    const cruzado   = el.dataset.cruzado   === '1';
    const warn = document.getElementById('activeLoanWarning');
    const msg  = document.getElementById('activeLoanMsg');
    if (bloqueado) {
        warn.style.background = '#fef2f2';
        warn.style.borderColor = '#fca5a5';
        warn.querySelector('div').style.color = '#991b1b';
        warn.querySelector('div').textContent = '⚠ Cliente con préstamo activo';
        msg.style.color = '#7f1d1d';
        msg.textContent = `Este cliente ya tiene un préstamo activo con el promotor "${el.dataset.promotor}". No se puede crear otro mientras haya uno en curso.`;
        warn.style.display = '';
    } else if (cruzado) {
        warn.style.background = '#fffbeb';
        warn.style.borderColor = '#fbbf24';
        warn.querySelector('div').style.color = '#92400e';
        warn.querySelector('div').textContent = '⚠ Deuda activa con otro administrador';
        msg.style.color = '#78350f';
        msg.textContent = `Este cliente ya tiene un préstamo activo con el administrador "${el.dataset.adminCruzado}". La deuda debe ser pagada antes de otorgar un nuevo préstamo.`;
        warn.style.display = '';
    } else {
        warn.style.display = 'none';
    }
    window._clienteBloqueado = bloqueado || cruzado;

    // Marcar INE y comprobante como opcionales si el cliente ya los tiene registrados
    const tieneIne         = el.dataset.tieneIne === '1';
    const tieneComprobante = el.dataset.tieneComprobante === '1';
    _actualizarDocReq('ine',         tieneIne,         'INE / Identificación');
    _actualizarDocReq('comprobante',  tieneComprobante,  'Comprobante de domicilio');

    checkCanSubmit();
}

function _actualizarDocReq(key, yaTiene, label) {
    const reqSpan = document.getElementById('np_' + key + '_req');
    const fileInp = document.getElementById('np_' + key + '_file');
    const camInp  = document.getElementById('np_' + key + '_cam');
    const wrap    = document.getElementById('np_' + key + '_wrap');
    if (!wrap) return;

    if (yaTiene) {
        // Mostrar chip "Ya registrado" y hacer opcional
        if (reqSpan) reqSpan.outerHTML = '<span id="np_' + key + '_req" style="font-size:10px;padding:1px 7px;border-radius:999px;background:#dcfce7;color:#166534;font-weight:600;text-transform:none;letter-spacing:0;margin-left:4px">✓ Ya registrado</span>';
        if (fileInp) fileInp.removeAttribute('required');
        if (camInp)  camInp.removeAttribute('required');
        // Mostrar aviso debajo
        let note = document.getElementById('np_' + key + '_ya_reg');
        if (!note) {
            note = document.createElement('div');
            note.id = 'np_' + key + '_ya_reg';
            note.style.cssText = 'font-size:11px;color:#16a34a;margin-top:4px';
            note.textContent   = 'El cliente ya tiene ' + label + ' en un préstamo anterior. Puedes subir uno nuevo si cambió.';
            wrap.appendChild(note);
        }
        note.style.display = '';
    } else {
        if (reqSpan) reqSpan.outerHTML = '<span id="np_' + key + '_req" style="color:#ef4444">*</span>';
        if (fileInp) fileInp.setAttribute('required', '');
        // El input de cámara NO lleva required: no tiene name, no se envía al server
        const note = document.getElementById('np_' + key + '_ya_reg');
        if (note) note.style.display = 'none';
    }
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
    const entregado       = parseFloat(document.getElementById('inEntregado').value)    || 0;
    const rentPctInput    = parseFloat(document.getElementById('inRentabilidad').value) || 0;
    const numPagos        = parseInt(document.getElementById('inNumPagos').value)       || 0;
    const frecuencia      = document.getElementById('inFrecuencia').value;
    const fechaInicio     = document.getElementById('inFechaInicio').value;
    const fechaPrimerCobro= document.getElementById('inFechaPrimerCobro').value;
    const dias            = DIAS[frecuencia] || 30;

    // Compute retornar from entregado + % rentabilidad
    const retornar = entregado > 0 && rentPctInput > 0
        ? Math.round(entregado * (1 + rentPctInput / 100) * 100) / 100
        : 0;

    // Update hidden field for form submission
    document.getElementById('inRetornar').value = retornar || '';

    if (entregado > 0 && retornar > 0) {
        const gan = retornar - entregado;
        document.getElementById('retornarBox').style.display = '';
        document.getElementById('retornarVal').textContent = fmtMXN(retornar);
        document.getElementById('retornarGan').textContent = 'Ganancia: ' + fmtMXN(gan);
    } else {
        document.getElementById('retornarBox').style.display = 'none';
    }

    const ok = entregado > 0 && retornar >= entregado && numPagos > 0 && fechaInicio && fechaPrimerCobro;
    document.getElementById('emptyState').style.display = ok ? 'none' : '';
    ['kpiCard','pagosCard','tablaCard'].forEach(id => {
        document.getElementById(id).style.display = ok ? '' : 'none';
    });
    if (!ok) { checkCanSubmit(); return; }

    const cuotaBase  = numPagos > 1 ? Math.ceil(retornar / numPagos / 5) * 5 : retornar;
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

    // Build schedule — interest-first: all interest collected before any principal
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
    checkCanSubmit();
}

function previewDoc(input, previewId) {
    const container = document.getElementById(previewId);
    const file = input.files[0];
    if (!file) { container.style.display = 'none'; container.innerHTML = ''; return; }
    const isPdf = file.name.toLowerCase().endsWith('.pdf');
    const sizeMb = (file.size / 1024 / 1024).toFixed(1);
    if (isPdf) {
        container.innerHTML = `<div style="display:flex;align-items:center;gap:8px;padding:7px 10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;font-size:12px;color:#15803d">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="1" width="12" height="14" rx="1.5"/><path d="M5 5h6M5 8h6M5 11h4"/></svg>
            ${file.name} <span style="color:var(--text3)">(${sizeMb} MB)</span></div>`;
    } else {
        const reader = new FileReader();
        reader.onload = e => {
            container.innerHTML = `<img src="${e.target.result}" style="max-width:100%;max-height:120px;border-radius:6px;border:1px solid var(--border);object-fit:cover">
                <div style="font-size:11px;color:var(--text3);margin-top:3px">${file.name} · ${sizeMb} MB</div>`;
        };
        reader.readAsDataURL(file);
    }
    container.style.display = '';
}

// Maneja selección desde botón "Subir archivo" O "Tomar foto"
// Copia el archivo al input principal (name="doc_xxx") para que el form lo envíe
const npActiveDoc = {};
function npSelDoc(key, srcInput) {
    if (!srcInput.files || !srcInput.files[0]) return;
    const file = srcInput.files[0];
    npActiveDoc[key] = srcInput; // guardar cuál input tiene el archivo

    // Transferir al input principal (el que tiene name=) para el submit
    const mainInput = document.getElementById('np_' + key + '_file');
    if (mainInput && srcInput !== mainInput) {
        try {
            const dt = new DataTransfer();
            dt.items.add(file);
            mainInput.files = dt.files;
        } catch(e) { /* DataTransfer no soportado en este entorno */ }
    }

    // Quitar required de ambos inputs ya que el documento fue seleccionado
    if (mainInput) mainInput.removeAttribute('required');
    const camInput = document.getElementById('np_' + key + '_cam');
    if (camInput)  camInput.removeAttribute('required');

    // Mostrar nombre del archivo
    const txt = document.getElementById('np_' + key + '_txt');
    if (txt) {
        const sizeMb = (file.size / 1024 / 1024).toFixed(1);
        txt.textContent = '✓ ' + (file.name.length > 40 ? '…' + file.name.slice(-37) : file.name) + ' · ' + sizeMb + ' MB';
        txt.style.display = '';
    }
}

function toggleDesembolso() {
    const on     = document.getElementById('togDesembolso').checked;
    const fields = document.getElementById('desembolsoFields');
    const slider = document.getElementById('togSlider');
    const knob   = document.getElementById('togKnob');
    const hint   = document.getElementById('togDesembolsoHint');
    const lbl    = document.getElementById('btnCrearLabel');

    fields.style.display  = on ? 'flex' : 'none';
    slider.style.background = on ? 'var(--accent)' : '#d1d5db';
    knob.style.transform    = on ? 'translateX(17px)' : 'translateX(0)';
    hint.textContent  = on
        ? 'El préstamo quedará en estatus Activo al guardar'
        : 'Activa para entregar el dinero al crear el préstamo';
    lbl.textContent   = on ? 'Crear y desembolsar' : 'Crear préstamo';

    // IMPORTANTE: evitar required en inputs ocultos
    ['ine', 'pagare', 'comprobante'].forEach(key => {
        const file = document.getElementById(`np_${key}_file`);
        const cam  = document.getElementById(`np_${key}_cam`);

        if (file) file.required = on;
        if (cam) cam.required = false;
    });
}

function checkCanSubmit() {
    const entregado       = parseFloat(document.getElementById('inEntregado').value)    || 0;
    const rentPctInput    = parseFloat(document.getElementById('inRentabilidad').value) || 0;
    const retornar        = parseFloat(document.getElementById('inRetornar').value)     || 0;
    const numPagos        = parseInt(document.getElementById('inNumPagos').value)       || 0;
    const clienteOk       = !!document.getElementById('csClienteId').value;
    const fechaPrimerCobro= document.getElementById('inFechaPrimerCobro').value;
    const bloqueado       = !!window._clienteBloqueado;
    const ok = clienteOk && !bloqueado && entregado > 0 && rentPctInput > 0 && retornar >= entregado && numPagos > 0 && !!fechaPrimerCobro;
    const btn = document.getElementById('btnCrear');
    btn.disabled = !ok;
    btn.style.opacity = ok ? '1' : '.5';
}

function manejarSubmit(e) {
    if (!document.getElementById('csClienteId').value) {
        alert('Selecciona un cliente para continuar.');
        return false;
    }

    // Validar documentos requeridos si desembolso está activo
    const desembolsando = document.getElementById('togDesembolso')?.checked;
    if (desembolsando) {
        const requeridos = [
            { key: 'ine',         label: 'INE / Identificación' },
            { key: 'pagare',      label: 'Pagaré' },
            { key: 'comprobante', label: 'Comprobante de domicilio' },
        ];
        for (const doc of requeridos) {
            const inp  = document.getElementById('np_' + doc.key + '_file');
            const cam  = document.getElementById('np_' + doc.key + '_cam');
            const req  = document.getElementById('np_' + doc.key + '_req');
            // Pasar si el campo ya fue marcado como "Ya registrado" (req tiene color verde)
            if (req && req.style && req.style.background === 'rgb(220, 252, 231)') continue;
            const tieneArchivo = (inp && inp.files && inp.files.length > 0)
                              || (cam && cam.files && cam.files.length > 0);
            if (!tieneArchivo) {
                alert('Falta el documento: ' + doc.label);
                // Resaltar el campo faltante
                const wrap = document.getElementById('np_' + doc.key + '_wrap');
                if (wrap) { wrap.style.outline = '2px solid #ef4444'; wrap.style.borderRadius = '6px'; }
                return false;
            }
        }
    }

    // ── Offline: save locally ─────────────────────────────────────────────────
    if (!navigator.onLine) {
        e.preventDefault();

        const datos = {
            cliente_id:         document.getElementById('csClienteId').value,
            monto_entregado:    document.getElementById('inEntregado').value,
            monto_retornar:     document.getElementById('inRetornar').value,
            num_pagos:          document.getElementById('inNumPagos').value,
            frecuencia:         document.getElementById('inFrecuencia').value,
            fecha_inicio:       document.getElementById('inFechaInicio').value,
            fecha_primer_cobro: document.getElementById('inFechaPrimerCobro').value,
            _clienteNombre:     clienteSeleccionado?.nombre || '(sin nombre)',
        };

        if (!window.OfflineSync) { alert('Módulo offline no cargado. Intenta recargar la página.'); return false; }

        const entry = window.OfflineSync.guardarOffline(datos);
        mostrarExitoOffline(entry);
        return false;
    }

    // ── Online: normal submit ─────────────────────────────────────────────────
    document.getElementById('btnCrear').textContent = 'Creando…';
    document.getElementById('btnCrear').disabled = true;
    return true;
}

function mostrarExitoOffline(entry) {
    const monto = parseFloat(entry.data.monto_entregado || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
    const hora  = new Date(entry.savedAt).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });

    document.getElementById('formNuevo').outerHTML = `
    <div style="max-width:480px;margin:40px auto;text-align:center;padding:36px 32px;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:0 4px 24px rgba(0,0,0,.06)">
        <div style="width:56px;height:56px;border-radius:50%;background:#fef9c3;border:2px solid #fcd34d;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px">☁</div>
        <h3 style="font-size:17px;font-weight:700;margin-bottom:6px">Guardado sin conexión</h3>
        <p style="font-size:13px;color:var(--text2);margin-bottom:18px">El préstamo se enviará automáticamente al servidor cuando recuperes internet.</p>
        <div style="background:#f9fafb;border:1px solid var(--border);border-radius:8px;padding:14px 16px;text-align:left;margin-bottom:20px;font-size:13px">
            <div style="font-weight:700;margin-bottom:4px">${entry.clienteNombre}</div>
            <div style="color:var(--text2)">Entrega: ${monto} · ${entry.data.frecuencia} · ${entry.data.num_pagos} pagos</div>
            <div style="font-size:11px;color:var(--text3);margin-top:4px">Guardado a las ${hora}</div>
        </div>
        <div style="display:flex;gap:10px;justify-content:center">
            <a href="{{ route('prestamos.create') }}" class="btn" style="background:#f3f4f6;color:var(--text)">+ Otro préstamo</a>
            <a href="{{ route('prestamos.index') }}" class="btn btn-primary">Ver préstamos</a>
        </div>
    </div>`;
}

document.addEventListener('DOMContentLoaded', () => { autoFechaPrimerCobro(); calcPreview(); });
</script>
@endpush

@endsection
