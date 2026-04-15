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
    <div class="info-card">
        <div class="info-card-label">Dirección</div>
        <div class="info-card-value" style="font-size:13px">{{ $cliente->direccion ?? '—' }}</div>
    </div>
    <div class="info-card">
        <div class="info-card-label">CURP</div>
        <div class="info-card-value" style="font-size:12px;font-family:monospace">{{ $cliente->curp ?? '—' }}</div>
    </div>
    <div class="info-card">
        <div class="info-card-label">Correo</div>
        <div class="info-card-value" style="font-size:12px">{{ $cliente->email ?? '—' }}</div>
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
@endphp
<div class="loan-block">
    <div class="loan-block-header" onclick="toggleLoan({{ $loan->id }})">
        <div style="display:flex;flex-direction:column;gap:3px;flex:1">
            <div class="loan-title">
                Préstamo #{{ $loan->id }} — ${{ number_format($loan->monto,0,'.',',') }}
                <span class="badge {{ $badgeClass }}" style="margin-left:8px;font-size:11px">{{ $loan->estatus }}</span>
            </div>
            <div class="loan-meta">
                <span>Cuota ${{ number_format($loan->cuota,2,'.',',') }} · {{ $loan->frecuencia }}</span>
                <span>Saldo ${{ number_format($loan->saldo_actual,2,'.',',') }}</span>
                <span>{{ $pagosCount }}/{{ $loan->num_pagos }} pagos · ${{ number_format($totalPagadoMonto,0,'.',',') }} cobrado</span>
                <span>Promotor: {{ $loan->promotor?->nombre ?? '—' }}</span>
            </div>
        </div>
        <svg class="loan-chevron {{ $idx === 0 ? 'open' : '' }}" id="chev-{{ $loan->id }}" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6l4 4 4-4"/></svg>
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
@endpush

@endsection
