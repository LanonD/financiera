@extends('layouts.app')

@section('title', $cliente->nombre)

@push('styles')
<style>
.cl-header{display:grid;grid-template-columns:auto 1fr auto;gap:20px;align-items:start;margin-bottom:24px}
.cl-avatar{width:64px;height:64px;border-radius:50%;background:var(--accent);color:#fff;font-size:22px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.cl-name{font-size:22px;font-weight:700;margin:0 0 4px}
.cl-sub{font-size:13px;color:var(--text2)}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px}
.info-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px}
.info-card-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:4px}
.info-card-value{font-size:14px;font-weight:600}
.score-ring{position:relative;width:80px;height:80px;flex-shrink:0}
.score-ring svg{transform:rotate(-90deg)}
.score-inner{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
.score-num{font-size:18px;font-weight:800;line-height:1}
.score-txt{font-size:9px;font-weight:600;text-transform:uppercase;color:var(--text3);margin-top:1px}
.score-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;display:flex;align-items:center;gap:20px;margin-bottom:24px}
.score-stats{display:flex;gap:24px;flex-wrap:wrap}
.score-stat{text-align:center}
.score-stat-val{font-size:18px;font-weight:700}
.score-stat-lbl{font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text3)}
.loan-block{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:16px;overflow:hidden}
.loan-block-header{padding:14px 18px;display:flex;align-items:center;gap:12px;cursor:pointer;user-select:none;border-bottom:1px solid var(--border)}
.loan-block-header:hover{background:rgba(0,0,0,.02)}
.loan-title{font-size:13px;font-weight:600;flex:1}
.loan-meta{font-size:12px;color:var(--text2);display:flex;gap:16px;flex-wrap:wrap}
.loan-chevron{transition:transform .2s;color:var(--text2)}
.loan-chevron.open{transform:rotate(180deg)}
.loan-body{display:none}
.loan-body.open{display:block}
.pago-row-adelante{background:#f0fdf4}
.pago-row-tiempo{background:#fff}
.pago-row-leve{background:#fffbeb}
.pago-row-tarde{background:#fff7ed}
.pago-row-muytarde{background:#fef2f2}
.pago-row-pendiente{background:#fafafa;opacity:.85}
.pill-pago{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:600}
.diff-badge{font-size:11px;font-weight:700;font-family:monospace}

/* ── Responsive ─────────────────────────────── */
@media(max-width:768px){
    .cl-header{grid-template-columns:auto 1fr!important;}
    .cl-header>a.btn{grid-column:1/-1;justify-content:center;width:100%;}
    .cl-name{font-size:18px;}
    .score-card{flex-direction:column;align-items:flex-start;gap:14px;}
    .loan-meta{font-size:11px;gap:8px;}
    .info-grid{grid-template-columns:repeat(2,1fr)!important;}
}
@media(max-width:480px){
    .cl-header{grid-template-columns:1fr!important;}
    .cl-avatar{width:44px!important;height:44px!important;font-size:16px!important;}
    .info-grid{grid-template-columns:1fr!important;}
    .score-stats{gap:14px;}
}
</style>
@endpush

@section('content')

@php
$puesto = auth()->user()->puesto;
$backUrl = $puesto === 'collector' ? route('cobros.index') : route('clientes.index');
$backLabel = $puesto === 'collector' ? 'Volver a cobros' : 'Volver a clientes';

// Score calculation
$totalPagados = 0; $puntos = 0; $enTiempo = 0; $tardios = 0; $muytardios = 0;
foreach ($prestamos as $loan) {
    foreach ($loan->pagos as $p) {
        if (!in_array($p->estatus, ['Pagado', 'Parcial'])) continue;
        $totalPagados++;
        $diff = $p->dias_diff ?? 0;
        if ($diff <= 0)      { $enTiempo++;   $puntos += 100; }
        elseif ($diff <= 3)  { $puntos += 80; $tardios++; }
        elseif ($diff <= 7)  { $puntos += 60; $tardios++; }
        elseif ($diff <= 15) { $puntos += 40; $muytardios++; }
        else                 { $puntos += 10; $muytardios++; }
    }
}
$score = $totalPagados > 0 ? round($puntos / $totalPagados) : null;
if     ($score === null) { $scoreLabel = 'Sin historial'; $scoreColor = '#94a3b8'; }
elseif ($score >= 90)    { $scoreLabel = 'Excelente';     $scoreColor = '#16a34a'; }
elseif ($score >= 70)    { $scoreLabel = 'Bueno';         $scoreColor = '#2563eb'; }
elseif ($score >= 50)    { $scoreLabel = 'Regular';       $scoreColor = '#ca8a04'; }
else                     { $scoreLabel = 'Riesgo';        $scoreColor = '#dc2626'; }
$onTimePct = $totalPagados > 0 ? round($enTiempo / $totalPagados * 100) : 0;

// Totales históricos
$totalCapitalEnviado = $prestamos->sum('monto_entregado');
$totalAcordado       = $prestamos->sum('monto');
$totalCobrado        = $prestamos->sum(fn($l) => $l->pagos->whereIn('estatus',['Pagado','Parcial'])->sum('monto_cobrado'));
$gananciaBruta       = $totalCobrado - $totalCapitalEnviado;
$numPrestamos        = $prestamos->count();
$numFinalizados      = $prestamos->where('estatus','Finalizado')->count();
@endphp

<a class="btn btn-sm" style="background:#f3f4f6;color:var(--text);margin-bottom:16px;display:inline-flex" href="{{ $backUrl }}">
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11L5 7l4-4"/></svg>
    {{ $backLabel }}
</a>

<div class="cl-header">
    <div class="cl-avatar">{{ strtoupper(substr($cliente->nombre,0,2)) }}</div>
    <div>
        <div class="cl-name">{{ $cliente->nombre }}</div>
        <div class="cl-sub">{{ $cliente->ocupacion ?? '—' }} · {{ $cliente->celular ?? '—' }}</div>
    </div>
    @if($puesto === 'admin')
    <a href="{{ route('clientes.edit', $cliente->id) }}" class="btn" style="background:#f3f4f6;color:var(--text);font-size:12px">Editar</a>
    @endif
</div>

<div class="info-grid">
    <div class="info-card" style="grid-column:1/-1">
        <div class="info-card-label">
            <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:11px;height:11px;vertical-align:middle;margin-right:3px"><path d="M7 1C4.79 1 3 2.79 3 5c0 3.25 4 8 4 8s4-4.75 4-8c0-2.21-1.79-4-4-4z"/><circle cx="7" cy="5" r="1.5"/></svg>
            Dirección
        </div>
        <div class="info-card-value" style="font-size:13px">{{ $cliente->direccion ?? '—' }}</div>
    </div>
    <div class="info-card">
        <div class="info-card-label">
            <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:11px;height:11px;vertical-align:middle;margin-right:3px"><rect x="2" y="3" width="10" height="8" rx="1"/><path d="M5 3V2M9 3V2"/></svg>
            CURP
        </div>
        <div class="info-card-value" style="font-size:12px;font-family:monospace">{{ $cliente->curp ?? '—' }}</div>
    </div>
    <div class="info-card">
        <div class="info-card-label">
            <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:11px;height:11px;vertical-align:middle;margin-right:3px"><path d="M2 4l5 4 5-4"/><rect x="1" y="3" width="12" height="8" rx="1"/></svg>
            Correo
        </div>
        <div class="info-card-value" style="font-size:12px">
            @if($cliente->email)
                <a href="mailto:{{ $cliente->email }}" style="color:var(--accent)">{{ $cliente->email }}</a>
            @else
                —
            @endif
        </div>
    </div>
    <div class="info-card">
        <div class="info-card-label">
            <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:11px;height:11px;vertical-align:middle;margin-right:3px"><path d="M2 2l2 2-1 3 3-1 2 2 1-4-4-4z"/></svg>
            Celular
        </div>
        <div class="info-card-value" style="font-size:13px">
            @if($cliente->celular)
                <a href="https://wa.me/52{{ preg_replace('/\D/','',$cliente->celular) }}" target="_blank"
                   style="color:#16a34a;display:inline-flex;align-items:center;gap:4px">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px"><path d="M10 0C4.477 0 0 4.477 0 10c0 1.763.46 3.417 1.264 4.857L0 20l5.285-1.384A9.958 9.958 0 0010 20c5.523 0 10-4.477 10-10S15.523 0 10 0zm0 18.182a8.173 8.173 0 01-4.163-1.136l-.298-.178-3.136.822.837-3.056-.195-.314A8.182 8.182 0 1110 18.182zm4.504-6.13c-.247-.124-1.463-.722-1.69-.804-.227-.083-.392-.124-.557.124-.165.247-.638.804-.782.969-.144.165-.288.185-.535.062-.247-.124-1.043-.384-1.987-1.225-.734-.655-1.23-1.464-1.374-1.71-.144-.247-.015-.381.108-.504.111-.11.247-.288.37-.432.124-.144.165-.247.247-.412.083-.165.041-.309-.02-.432-.062-.124-.557-1.343-.763-1.838-.2-.483-.405-.418-.557-.426l-.474-.008c-.165 0-.432.062-.659.309-.227.247-.866.846-.866 2.063s.887 2.392 1.011 2.557c.124.165 1.746 2.665 4.231 3.737.591.255 1.052.407 1.411.521.593.188 1.132.162 1.559.098.475-.071 1.463-.598 1.669-1.176.206-.577.206-1.072.144-1.176-.062-.103-.227-.165-.474-.289z"/></svg>
                    {{ $cliente->celular }}
                </a>
            @else
                —
            @endif
        </div>
    </div>
    <div class="info-card">
        <div class="info-card-label">Promotor</div>
        <div class="info-card-value">{{ $cliente->promotor?->nombre ?? '—' }}</div>
    </div>
    <div class="info-card">
        <div class="info-card-label">Préstamos</div>
        <div class="info-card-value">{{ $prestamos->count() }}</div>
    </div>
</div>

{{-- Mapa de ubicación --}}
@if($cliente->latitud && $cliente->longitud)
<div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:24px">
    <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div style="font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px">
            <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:var(--accent)"><path d="M7 1C4.79 1 3 2.79 3 5c0 3.25 4 8 4 8s4-4.75 4-8c0-2.21-1.79-4-4-4z"/><circle cx="7" cy="5" r="1.5"/></svg>
            Ubicación del cliente
        </div>
        <a href="https://www.google.com/maps?q={{ $cliente->latitud }},{{ $cliente->longitud }}"
           target="_blank" class="btn btn-sm" style="background:#eff6ff;color:#2563eb;font-size:11px">
            Abrir en Maps
        </a>
    </div>
    <div id="mapDetalle" style="width:100%;height:260px"></div>
</div>
@endif

{{-- Score card --}}
<div class="score-card">
    @php $pct = $score ?? 0; $circ = 2 * M_PI * 30; $dash = $circ * $pct / 100; @endphp
    <div class="score-ring">
        <svg width="80" height="80" viewBox="0 0 80 80">
            <circle cx="40" cy="40" r="30" fill="none" stroke="var(--border)" stroke-width="7"/>
            <circle cx="40" cy="40" r="30" fill="none" stroke="{{ $scoreColor }}" stroke-width="7"
                stroke-dasharray="{{ round($dash,2) }} {{ round($circ,2) }}" stroke-linecap="round"/>
        </svg>
        <div class="score-inner">
            <div class="score-num" style="color:{{ $scoreColor }}">{{ $score ?? '—' }}</div>
            <div class="score-txt">score</div>
        </div>
    </div>
    <div>
        <div style="font-size:16px;font-weight:700;color:{{ $scoreColor }};margin-bottom:6px">{{ $scoreLabel }}</div>
        <div class="score-stats">
            <div class="score-stat"><div class="score-stat-val">{{ $totalPagados }}</div><div class="score-stat-lbl">Pagos realizados</div></div>
            <div class="score-stat"><div class="score-stat-val" style="color:#16a34a">{{ $enTiempo }}</div><div class="score-stat-lbl">A tiempo</div></div>
            <div class="score-stat"><div class="score-stat-val" style="color:#ca8a04">{{ $tardios }}</div><div class="score-stat-lbl">Con leve retraso</div></div>
            <div class="score-stat"><div class="score-stat-val" style="color:#dc2626">{{ $muytardios }}</div><div class="score-stat-lbl">Muy tardíos</div></div>
            @if($totalPagados > 0)
            <div class="score-stat"><div class="score-stat-val">{{ $onTimePct }}%</div><div class="score-stat-lbl">Puntualidad</div></div>
            @endif
        </div>
    </div>
</div>

{{-- Totales históricos --}}
@if($numPrestamos > 0)
<div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:18px 22px;margin-bottom:24px">
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:14px;display:flex;align-items:center;gap:6px">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><rect x="1" y="3" width="14" height="10" rx="1.5"/><path d="M1 6h14M5 10h2"/></svg>
        Historial financiero total
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px">

        <div style="display:flex;flex-direction:column;gap:3px">
            <span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)">Capital enviado</span>
            <span style="font-size:22px;font-weight:800;letter-spacing:-.02em;color:var(--text)">${{ number_format($totalCapitalEnviado,0,'.',',') }}</span>
            <span style="font-size:11px;color:var(--text3)">en {{ $numPrestamos }} préstamo{{ $numPrestamos !== 1 ? 's' : '' }}</span>
        </div>

        <div style="display:flex;flex-direction:column;gap:3px">
            <span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)">Total cobrado</span>
            <span style="font-size:22px;font-weight:800;letter-spacing:-.02em;color:#2563eb">${{ number_format($totalCobrado,0,'.',',') }}</span>
            <span style="font-size:11px;color:var(--text3)">de ${{ number_format($totalAcordado,0,'.',',') }} acordado</span>
        </div>

        <div style="display:flex;flex-direction:column;gap:3px">
            <span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)">Ganancia generada</span>
            <span style="font-size:22px;font-weight:800;letter-spacing:-.02em;color:{{ $gananciaBruta >= 0 ? '#16a34a' : '#dc2626' }}">${{ number_format(abs($gananciaBruta),0,'.',',') }}</span>
            <span style="font-size:11px;color:var(--text3)">{{ $gananciaBruta >= 0 ? 'sobre el capital enviado' : 'pendiente de recuperar' }}</span>
        </div>

        @if($numFinalizados > 0)
        <div style="display:flex;flex-direction:column;gap:3px">
            <span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)">Finalizados</span>
            <span style="font-size:22px;font-weight:800;letter-spacing:-.02em;color:#16a34a">{{ $numFinalizados }}</span>
            <span style="font-size:11px;color:var(--text3)">de {{ $numPrestamos }} completados</span>
        </div>
        @endif

    </div>
</div>
@endif

@if($prestamos->isEmpty())
<div class="card" style="text-align:center;padding:40px;color:var(--text3)">Este cliente no tiene préstamos registrados</div>
@endif

@foreach($prestamos as $idx => $loan)
@php
    $totalPagadoMonto = $loan->pagos->whereIn('estatus', ['Pagado','Parcial'])->sum('monto_cobrado');
    $pagosCount = $loan->pagos->whereIn('estatus', ['Pagado','Parcial'])->count();
    $pendCount  = $loan->pagos->whereIn('estatus', ['Pendiente','Atrasado'])->count();
    $badgeClass = match($loan->estatus) {
        'Activo'     => 'badge-green',
        'Atrasado'   => 'badge-red',
        'Finalizado' => 'badge-gray',
        default      => 'badge-yellow',
    };

    // Payment progress percentage (can exceed 100% if overpayment due to mora)
    $pctPagado   = $loan->monto > 0 ? round($totalPagadoMonto / $loan->monto * 100, 1) : 0;
    $sobrepago   = $pctPagado > 100;
    $pctBar      = min($pctPagado, 100); // cap bar at 100% visually
    $pctColor    = $sobrepago   ? '#16a34a'
                 : ($pctPagado >= 80 ? '#2563eb'
                 : ($pctPagado >= 40 ? '#ca8a04' : '#9ca3af'));
@endphp
<div class="loan-block">
    <div class="loan-block-header" onclick="toggleLoan({{ $loan->id }})">
        <div style="display:flex;flex-direction:column;gap:4px;flex:1">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span class="loan-title">Préstamo #{{ $loan->id }} — ${{ number_format($loan->monto,0,'.',',') }}</span>
                <span class="badge {{ $badgeClass }}" style="font-size:11px">{{ $loan->estatus }}</span>
                @if($sobrepago)
                <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;background:#dcfce7;color:#15803d;border:1px solid #86efac">
                    ✓ {{ $pctPagado }}% pagado
                </span>
                @elseif($pctPagado > 0)
                <span style="font-size:11px;font-weight:600;color:{{ $pctColor }}">{{ $pctPagado }}%</span>
                @endif
            </div>

            {{-- Progress bar --}}
            @if($pctPagado > 0)
            <div style="height:4px;background:#f1f5f9;border-radius:2px;overflow:hidden;max-width:320px">
                <div style="height:100%;width:{{ $pctBar }}%;background:{{ $pctColor }};border-radius:2px;transition:width .3s"></div>
            </div>
            @endif

            <div class="loan-meta">
                <span>Cuota ${{ number_format($loan->cuota,2,'.',',') }} · {{ $loan->frecuencia }}</span>
                <span>Saldo ${{ number_format($loan->saldo_actual,2,'.',',') }}</span>
                <span>{{ $pagosCount }}/{{ $loan->num_pagos }} pagos · ${{ number_format($totalPagadoMonto,0,'.',',') }} cobrado</span>
                <span>Promotor: {{ $loan->promotor?->nombre ?? '—' }}</span>
                <span>Solicitud: {{ $loan->created_at?->format('d/m/Y') ?? '—' }}</span>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
            {{-- Link to full loan detail --}}
            <a href="{{ route('prestamos.show', $loan->id) }}"
               onclick="event.stopPropagation()"
               class="btn btn-sm"
               style="background:#eff6ff;color:#2563eb;font-size:11px;white-space:nowrap">
                Ver detalle
                <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:10px;height:10px"><path d="M2 6h8M6 2l4 4-4 4"/></svg>
            </a>
            <svg class="loan-chevron {{ $idx === 0 ? 'open' : '' }}" id="chev-{{ $loan->id }}" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6l4 4 4-4"/></svg>
        </div>
    </div>
    <div class="loan-body {{ $idx === 0 ? 'open' : '' }}" id="body-{{ $loan->id }}">
        @if($loan->pagos->isEmpty())
        <p style="padding:20px;color:var(--text3);font-size:13px">Sin historial de pagos registrado.</p>
        @else
        <div style="overflow-x:auto">
        <table style="width:100%">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Fecha acordada</th>
                    <th>Fecha de pago</th>
                    <th>Puntualidad</th>
                    <th style="text-align:right">Cuota</th>
                    <th style="text-align:right">Cobrado</th>
                    <th style="text-align:right">Capital</th>
                    <th style="text-align:right">Interés</th>
                    <th style="text-align:right">Saldo tras pago</th>
                    <th>Estatus</th>
                    <th>Nota cobro</th>
                </tr>
            </thead>
            <tbody>
            @foreach($loan->pagos as $p)
            @php
                $paid    = in_array($p->estatus, ['Pagado','Parcial']);
                $diff    = $paid ? ($p->dias_diff ?? 0) : null;
                $rowCls  = 'pago-row-pendiente';
                $diffTxt = '—';
                $diffClr = 'var(--text3)';
                if ($paid) {
                    if      ($diff < 0)  { $rowCls='pago-row-adelante'; $diffTxt=abs($diff).' día'.(abs($diff)>1?'s':'').' antes'; $diffClr='#166534'; }
                    elseif  ($diff === 0){ $rowCls='pago-row-tiempo';   $diffTxt='En fecha';                                        $diffClr='#16a34a'; }
                    elseif  ($diff <= 3) { $rowCls='pago-row-leve';     $diffTxt=$diff.' día'.($diff>1?'s':'').' tarde';            $diffClr='#ca8a04'; }
                    elseif  ($diff <= 7) { $rowCls='pago-row-tarde';    $diffTxt=$diff.' días tarde';                               $diffClr='#d97706'; }
                    else                 { $rowCls='pago-row-muytarde'; $diffTxt=$diff.' días tarde';                               $diffClr='#dc2626'; }
                }
                $statusStyles = match($p->estatus) {
                    'Pagado'   => ['#dcfce7','#166534'],
                    'Parcial'  => ['#fef9c3','#854d0e'],
                    'Atrasado' => ['#fee2e2','#991b1b'],
                    default    => ['#f3f4f6','var(--text2)'],
                };
            @endphp
            <tr class="{{ $rowCls }}">
                <td style="text-align:center;font-weight:600;font-size:12px">{{ $p->numero_pago }}</td>
                <td style="font-family:monospace;font-size:12px">{{ \Carbon\Carbon::parse($p->fecha_programada)->format('d/m/Y') }}</td>
                <td style="font-family:monospace;font-size:12px">{{ $p->fecha_pago ? \Carbon\Carbon::parse($p->fecha_pago)->format('d/m/Y') : '—' }}</td>
                <td><span class="diff-badge" style="color:{{ $diffClr }}">{{ $diffTxt }}</span></td>
                <td style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($p->monto_cuota,2,'.',',') }}</td>
                <td style="text-align:right;font-family:monospace;font-size:12px">{{ $p->monto_cobrado ? '$'.number_format($p->monto_cobrado,2,'.',',') : '—' }}</td>
                <td style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($p->capital,2,'.',',') }}</td>
                <td style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($p->interes,2,'.',',') }}</td>
                <td style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($p->saldo_restante,2,'.',',') }}</td>
                <td><span class="pill-pago" style="background:{{ $statusStyles[0] }};color:{{ $statusStyles[1] }}">{{ $p->estatus }}</span></td>
                <td style="font-size:12px;color:var(--text2);max-width:180px">{{ $p->nota_cobro ?? '—' }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif

        {{-- Documentos del desembolso --}}
        @if($loan->doc_ine || $loan->doc_pagare || $loan->doc_comprobante || $loan->doc_foto_domicilio)
        <div style="padding:14px 18px;border-top:1px solid var(--border);background:#fafafa">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:8px;display:flex;align-items:center;gap:6px">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Documentos del desembolso
                @if($loan->fecha_entrega)
                <span style="font-weight:400;color:var(--text3)">— entregado {{ \Carbon\Carbon::parse($loan->fecha_entrega)->format('d/m/Y') }}</span>
                @endif
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                @foreach([
                    ['INE', $loan->doc_ine, '#0369a1', '#e0f2fe', '#bae6fd'],
                    ['Pagaré firmado', $loan->doc_pagare, '#065f46', '#d1fae5', '#6ee7b7'],
                    ['Comprobante domicilio', $loan->doc_comprobante, '#7c3aed', '#ede9fe', '#c4b5fd'],
                    ['Foto vivienda', $loan->doc_foto_domicilio, '#9a3412', '#ffedd5', '#fdba74'],
                ] as [$docLabel, $docPath, $color, $bg, $border])
                @if($docPath)
                <a href="{{ asset('public/'.$docPath) }}" target="_blank"
                   style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:{{ $bg }};border:1px solid {{ $border }};border-radius:6px;font-size:12px;color:{{ $color }};text-decoration:none;font-weight:500">
                    @php $ext = strtolower(pathinfo($docPath, PATHINFO_EXTENSION)); @endphp
                    @if(in_array($ext, ['jpg','jpeg','png','webp']))
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    @else
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    @endif
                    {{ $docLabel }}
                    <span style="font-size:10px;opacity:.7;font-weight:400;text-transform:uppercase">{{ $ext }}</span>
                </a>
                @endif
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endforeach

@push('scripts')
<script>
function toggleLoan(id) {
    document.getElementById('body-'  + id).classList.toggle('open');
    document.getElementById('chev-'  + id).classList.toggle('open');
}
</script>

@if($cliente->latitud && $cliente->longitud)
<script>
function initMapDetalle() {
    try {
        const lat = {{ $cliente->latitud }};
        const lng = {{ $cliente->longitud }};
        const pos = { lat, lng };
        const map = new google.maps.Map(document.getElementById('mapDetalle'), {
            center: pos, zoom: 16,
            mapTypeControl: false, streetViewControl: false,
        });
        new google.maps.Marker({
            position: pos, map,
            title: '{{ addslashes($cliente->nombre) }}',
        });
    } catch(e) {
        document.getElementById('mapDetalle').innerHTML =
            '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:12px;color:#9ca3af">Mapa no disponible</div>';
    }
}
</script>
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfb3MRYco1aN4yaJyXmK8jperHTMJl07E&callback=initMapDetalle"
    onerror="document.getElementById('mapDetalle').innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;font-size:12px;color:#9ca3af\'>Mapa no disponible</div>'">
</script>
@endif
@endpush

@endsection
