@extends('layouts.app')

@section('title', 'Rendimientos por administrador')

@push('styles')
<style>
/* ── Layout ─────────────────────────────────────────────── */
.rnd-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}

/* ── KPI globales ────────────────────────────────────────── */
.rnd-kpi-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px}
.rnd-kpi{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;position:relative;overflow:hidden}
.rnd-kpi-accent{position:absolute;top:0;left:0;width:3px;height:100%;border-radius:3px 0 0 3px}
.rnd-kpi-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:6px}
.rnd-kpi-value{font-size:22px;font-weight:800;letter-spacing:-.03em;color:var(--text);line-height:1}
.rnd-kpi-sub{font-size:11px;color:var(--text2);margin-top:4px}

/* ── Admin cards ─────────────────────────────────────────── */
:root{--rnd-cols:200px 1fr 1fr 1fr 1fr 100px 80px 82px}
.rnd-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:12px;overflow:hidden;transition:box-shadow .15s}
.rnd-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.07)}
.rnd-list-header{display:grid;grid-template-columns:var(--rnd-cols);gap:0;padding:8px 20px;background:#f9fafb;border:1px solid var(--border);border-radius:var(--radius) var(--radius) 0 0;border-bottom:none}
.rnd-list-hcell{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)}
.rnd-card-header{display:grid;grid-template-columns:var(--rnd-cols);align-items:center;gap:0;padding:16px 20px;cursor:pointer;user-select:none}
.rnd-card-header:hover .rnd-admin-name{color:var(--accent)}
.rnd-col-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:3px}
.rnd-col-value{font-size:14px;font-weight:700;color:var(--text)}
.rnd-col-sub{font-size:11px;color:var(--text2);margin-top:1px}
.rnd-admin-avatar{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0}
.rnd-admin-name{font-size:13px;font-weight:700;color:var(--text);transition:color .1s}
.rnd-admin-sub{font-size:11px;color:var(--text3);margin-top:2px}

/* Progress bar */
.rnd-bar-wrap{height:5px;background:#f3f4f6;border-radius:99px;overflow:hidden;margin-top:5px}
.rnd-bar-fill{height:100%;border-radius:99px}

/* Pills */
.rnd-pill{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:99px;font-size:10px;font-weight:700;white-space:nowrap}
.rnd-pill-blue{background:#eff6ff;color:#1d4ed8}
.rnd-pill-red{background:#fef2f2;color:#dc2626}
.rnd-pill-green{background:#f0fdf4;color:#16a34a}
.rnd-pill-yellow{background:#fefce8;color:#ca8a04}
.rnd-pill-gray{background:#f3f4f6;color:#6b7280}

/* % badge */
.rnd-pct-badge{display:inline-flex;align-items:center;justify-content:center;padding:4px 10px;border-radius:8px;font-size:14px;font-weight:800;min-width:56px}
.rnd-pct-green{background:#f0fdf4;color:#16a34a}
.rnd-pct-yellow{background:#fefce8;color:#d97706}
.rnd-pct-red{background:#fef2f2;color:#dc2626}
.rnd-pct-blue{background:#eff6ff;color:#2563eb}
.rnd-pct-gray{background:#f3f4f6;color:#9ca3af}

/* Chevron */
.rnd-chevron{transition:transform .2s;color:var(--text3);flex-shrink:0}
.rnd-chevron.open{transform:rotate(180deg)}

/* ── Expanded detail panel ──────────────────────────────── */
.rnd-detail{display:none;border-top:1px solid var(--border);padding:20px;background:#f9fafb}
.rnd-detail.open{display:block}

/* Charts row */
.rnd-charts-row{display:grid;grid-template-columns:260px 1fr;gap:14px;margin-bottom:16px}
.rnd-chart-box{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:14px 16px}
.rnd-chart-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:10px}

/* Stat boxes */
.rnd-detail-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px}
.rnd-stat-box{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:13px 15px}
.rnd-stat-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:4px}
.rnd-stat-value{font-size:18px;font-weight:800;letter-spacing:-.02em;color:var(--text)}
.rnd-stat-sub{font-size:11px;color:var(--text2);margin-top:2px}

/* ── Portfolio chart section ────────────────────────────── */
.pf-section{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:22px;overflow:hidden}
.pf-section-head{padding:16px 20px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.pf-title{font-size:15px;font-weight:800;letter-spacing:-.02em;color:var(--text)}
.pf-subtitle{font-size:11px;color:var(--text3);margin-top:2px}
.pf-filters{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:12px 20px 0}
.pf-filter-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);white-space:nowrap}
.pf-select,.pf-date{padding:7px 10px;background:#f9fafb;border:1.5px solid var(--border);border-radius:7px;font-size:12px;font-family:var(--font);color:var(--text);outline:none;cursor:pointer;transition:border-color .15s}
.pf-select:focus,.pf-date:focus{border-color:var(--accent)}
.pf-quick-btns{display:flex;gap:4px}
.pf-qbtn{padding:5px 10px;background:#f3f4f6;border:none;border-radius:6px;font-size:11px;font-weight:600;color:var(--text2);cursor:pointer;transition:background .12s,color .12s}
.pf-qbtn:hover,.pf-qbtn.active{background:var(--accent);color:#fff}
.pf-mode-btns{display:flex;border:1.5px solid var(--border);border-radius:8px;overflow:hidden}
.pf-mbtn{padding:6px 14px;background:transparent;border:none;font-size:11px;font-weight:700;color:var(--text2);cursor:pointer;transition:background .12s,color .12s;white-space:nowrap}
.pf-mbtn.active{background:var(--accent);color:#fff}
.pf-chart-wrap{padding:16px 20px 18px;position:relative;height:280px}
.pf-legend{display:flex;gap:16px;padding:0 20px 14px;flex-wrap:wrap}
.pf-legend-item{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text2);font-weight:500}
.pf-legend-dot{width:20px;height:3px;border-radius:2px;flex-shrink:0}

/* Responsive */
@media(max-width:1200px){
    .rnd-kpi-grid{grid-template-columns:repeat(3,1fr)}
    :root{--rnd-cols:180px 1fr 1fr 1fr}
    .rnd-list-header .rnd-col-hide,.rnd-card-header .rnd-col-hide{display:none}
    .rnd-detail-grid{grid-template-columns:repeat(2,1fr)}
    .rnd-charts-row{grid-template-columns:220px 1fr}
}
@media(max-width:768px){
    .rnd-kpi-grid{grid-template-columns:1fr 1fr}
    .rnd-list-header{display:none}
    :root{--rnd-cols:1fr auto}
    .rnd-card-header > *:not(:first-child):not(:last-child){display:none}
    .rnd-detail-grid{grid-template-columns:1fr 1fr}
    .rnd-charts-row{grid-template-columns:1fr}
}
@media(max-width:480px){
    .rnd-kpi-grid{grid-template-columns:1fr}
    .rnd-detail-grid{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')

{{-- ── Header ─────────────────────────────────────────────── --}}
<div class="rnd-header">
    <div>
        <h2 style="font-size:22px;font-weight:800;letter-spacing:-.03em;margin-bottom:3px">Rendimientos</h2>
        <p style="font-size:13px;color:var(--text2)">Métricas financieras consolidadas por administrador · Gráficas últimos 90 días</p>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
        <span style="font-size:11px;color:var(--text3);background:var(--card);border:1px solid var(--border);padding:5px 12px;border-radius:20px">
            {{ now()->format('d/m/Y') }}
        </span>
        <a href="{{ route('owner.dashboard') }}" class="btn" style="background:#f3f4f6;color:var(--text2);font-size:12px">
            Administradores
        </a>
    </div>
</div>

{{-- ── KPIs globales ───────────────────────────────────────── --}}
<div class="rnd-kpi-grid">
    <div class="rnd-kpi">
        <div class="rnd-kpi-accent" style="background:#3b82f6"></div>
        <div class="rnd-kpi-label">Capital desplegado</div>
        <div class="rnd-kpi-value" style="color:#2563eb">${{ number_format($globales['capital_desplegado'], 0, '.', ',') }}</div>
        <div class="rnd-kpi-sub">{{ $globales['total_prestamos'] }} préstamos</div>
    </div>
    <div class="rnd-kpi">
        <div class="rnd-kpi-accent" style="background:#16a34a"></div>
        <div class="rnd-kpi-label">Total cobrado</div>
        <div class="rnd-kpi-value" style="color:#16a34a">${{ number_format($globales['total_cobrado'], 0, '.', ',') }}</div>
        <div class="rnd-kpi-sub">{{ $globales['recuperado_pct'] }}% del acordado</div>
    </div>
    <div class="rnd-kpi">
        <div class="rnd-kpi-accent" style="background:#7c3aed"></div>
        <div class="rnd-kpi-label">Interés cobrado</div>
        <div class="rnd-kpi-value" style="color:#7c3aed">${{ number_format($globales['interes_cobrado'], 0, '.', ',') }}</div>
        <div class="rnd-kpi-sub">cobrado/capital: {{ $globales['rendimiento_pct'] }}%</div>
    </div>
    <div class="rnd-kpi">
        <div class="rnd-kpi-accent" style="background:#f59e0b"></div>
        <div class="rnd-kpi-label">Saldo en cartera</div>
        <div class="rnd-kpi-value" style="color:#d97706">${{ number_format($globales['saldo_pendiente'], 0, '.', ',') }}</div>
        <div class="rnd-kpi-sub">activos + atrasados</div>
    </div>
    <div class="rnd-kpi">
        <div class="rnd-kpi-accent" style="background:#ef4444"></div>
        <div class="rnd-kpi-label">Mora acumulada</div>
        <div class="rnd-kpi-value" style="color:#dc2626">${{ number_format($globales['mora_pendiente'], 0, '.', ',') }}</div>
        <div class="rnd-kpi-sub">moratorio pendiente</div>
    </div>
</div>

{{-- ── Barra de cobranza global ────────────────────────────── --}}
@php $gPct = $globales['recuperado_pct']; $gBarColor = $gPct>=75?'#16a34a':($gPct>=40?'#f59e0b':'#ef4444'); @endphp
<div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 20px;margin-bottom:22px;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:1;min-width:200px">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <span style="font-size:12px;font-weight:600;color:var(--text2)">Cobranza global</span>
            <span style="font-size:12px;font-weight:800;color:{{ $gBarColor }}">{{ $gPct }}%</span>
        </div>
        <div style="height:8px;background:#f3f4f6;border-radius:99px;overflow:hidden">
            <div style="height:100%;width:{{ $gPct }}%;background:{{ $gBarColor }};border-radius:99px"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:4px">
            <span style="font-size:11px;color:var(--text3)">Cobrado: ${{ number_format($globales['total_cobrado'],0,'.', ',') }}</span>
            <span style="font-size:11px;color:var(--text3)">Acordado: ${{ number_format($globales['total_acordado'],0,'.',',') }}</span>
        </div>
    </div>
    <div style="display:flex;gap:24px;flex-shrink:0;flex-wrap:wrap">
        <div style="text-align:center">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)">Interés esperado</div>
            <div style="font-size:16px;font-weight:800;color:var(--text)">${{ number_format($globales['total_acordado']-$globales['capital_desplegado'],0,'.',',') }}</div>
        </div>
        <div style="text-align:center">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)">Interés cobrado</div>
            <div style="font-size:16px;font-weight:800;color:#7c3aed">${{ number_format($globales['interes_cobrado'],0,'.',',') }}</div>
        </div>
        <div style="text-align:center">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)">Rendimiento real</div>
            <div style="font-size:16px;font-weight:800;color:{{ $globales['rendimiento_pct']>0?'#16a34a':($globales['rendimiento_pct']===0.0?'#9ca3af':'#dc2626') }}">{{ $globales['rendimiento_pct'] > 0 ? '+' : '' }}{{ $globales['rendimiento_pct'] }}%</div>
        </div>
    </div>
</div>

{{-- ── Rendimiento general de cartera ─────────────────────── --}}
<div class="pf-section">
    <div class="pf-section-head">
        <div>
            <div class="pf-title">Rendimiento general de cartera</div>
            <div class="pf-subtitle">Flujo diario de desembolsos y cobros — filtra por admin, rango y modo</div>
        </div>
        <div class="pf-mode-btns" id="pfModeBtns">
            <button class="pf-mbtn active" data-mode="comparar" onclick="setPfMode(this)">Desembolsos vs Cobros</button>
            <button class="pf-mbtn"        data-mode="sumatoria" onclick="setPfMode(this)">Sumatoria</button>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="pf-filters">
        <span class="pf-filter-label">Admin</span>
        <select class="pf-select" id="pf-admin" onchange="updatePortfolioChart()" style="min-width:160px">
            <option value="all">Todos los administradores</option>
            @foreach($stats as $s)
            <option value="{{ $s['admin']->id }}">{{ $s['admin']->alias ?: ($s['admin']->nombre ?: $s['admin']->usuario) }}</option>
            @endforeach
        </select>

        <span class="pf-filter-label" style="margin-left:4px">Desde</span>
        <input type="date" class="pf-date" id="pf-desde" onchange="updatePortfolioChart()">

        <span class="pf-filter-label">Hasta</span>
        <input type="date" class="pf-date" id="pf-hasta" onchange="updatePortfolioChart()">

        <div class="pf-quick-btns" id="pfQuickBtns">
            <button class="pf-qbtn" onclick="setPfRange(7,this)">7D</button>
            <button class="pf-qbtn" onclick="setPfRange(30,this)">30D</button>
            <button class="pf-qbtn" onclick="setPfRange(60,this)">60D</button>
            <button class="pf-qbtn active" onclick="setPfRange(90,this)">90D</button>
        </div>
    </div>

    {{-- Leyenda dinámica --}}
    <div class="pf-legend" id="pf-legend">
        <span class="pf-legend-item"><span class="pf-legend-dot" style="background:#ef4444"></span>Desembolsos</span>
        <span class="pf-legend-item"><span class="pf-legend-dot" style="background:#16a34a"></span>Cobros</span>
    </div>

    {{-- Canvas --}}
    <div class="pf-chart-wrap">
        <canvas id="portfolioChart"></canvas>
    </div>
</div>

{{-- ── Buscador de administradores ─────────────────────────── --}}
@if(!$stats->isEmpty())
<div style="position:relative;margin-bottom:14px">
    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
         style="position:absolute;left:13px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--text3);pointer-events:none">
        <circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/>
    </svg>
    <input type="text" id="rndSearch" autocomplete="off"
        placeholder="Buscar administrador por nombre o alias…"
        style="width:100%;padding:9px 14px 9px 38px;background:var(--card);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:var(--font);color:var(--text);outline:none;box-sizing:border-box;transition:border-color .15s"
        onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'"
        oninput="filtrarRendimientos(this.value)">
    <div id="rndNoResult" style="display:none;padding:24px;text-align:center;color:var(--text3);font-size:13px;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-top:8px">
        Sin resultados para tu búsqueda.
    </div>
</div>
@endif

{{-- ── Sin datos ────────────────────────────────────────────── --}}
@if($stats->isEmpty())
<div class="card" style="text-align:center;padding:60px 24px;color:var(--text3)">
    <p style="font-size:14px;font-weight:600;color:var(--text2);margin-bottom:4px">Sin administradores registrados</p>
</div>
@else

{{-- ── Column headers ──────────────────────────────────────── --}}
<div class="rnd-list-header">
    <div class="rnd-list-hcell">Administrador</div>
    <div class="rnd-list-hcell">Capital / Cobranza</div>
    <div class="rnd-list-hcell rnd-col-hide">Total cobrado</div>
    <div class="rnd-list-hcell rnd-col-hide">Saldo activo</div>
    <div class="rnd-list-hcell rnd-col-hide">Préstamos</div>
    <div class="rnd-list-hcell rnd-col-hide" style="text-align:center">Mora</div>
    <div class="rnd-list-hcell" style="text-align:center">Rentab.</div>
    <div class="rnd-list-hcell" style="text-align:center">Cobrado/Capital</div>
</div>

{{-- ── Admin cards ─────────────────────────────────────────── --}}
@foreach($stats as $i => $s)
@php
    $admin    = $s['admin'];
    $colors   = ['#3b82f6','#6366f1','#8b5cf6','#ec4899','#10b981','#f59e0b','#ef4444','#0ea5e9'];
    $color    = $colors[crc32($admin->usuario) % count($colors)];
    $pct      = $s['recuperado_pct'];
    $barColor = $pct>=75?'#16a34a':($pct>=40?'#f59e0b':'#ef4444');
    // Rentabilidad pactada: tasa de rendimiento acordada (interés/capital)
    $rentab    = $s['rentabilidad_pct'] ?? 0;
    $rentabColor = $rentab >= 40 ? 'rnd-pct-green' : ($rentab >= 20 ? 'rnd-pct-blue' : ($rentab > 0 ? 'rnd-pct-yellow' : 'rnd-pct-gray'));
    // Rendimiento real: total cobrado / capital desplegado
    $rndVal   = $s['rendimiento_pct'];
    // Positivo = recuperaste capital + ganancia; negativo = aún en déficit
    $rndColor = $rndVal > 0 ? 'rnd-pct-green' : ($rndVal === 0.0 ? 'rnd-pct-gray' : 'rnd-pct-red');

    // Pie chart data (only non-zero statuses)
    $pieLabels = []; $pieValues = []; $pieColors = [];
    $statusColors = ['Activo'=>'#3b82f6','Atrasado'=>'#ef4444','Finalizado'=>'#16a34a','Pendiente'=>'#f59e0b','Retirado'=>'#9ca3af'];
    foreach($s['por_estatus'] as $est => $cnt) {
        if ($cnt > 0) {
            $pieLabels[] = $est;
            $pieValues[] = $cnt;
            $pieColors[] = $statusColors[$est] ?? '#ccc';
        }
    }
@endphp

<div class="rnd-card" style="{{ $i===0?'border-radius:0 0 var(--radius) var(--radius);border-top:none':'' }}"
     data-search="{{ strtolower(($admin->alias ?? '') . ' ' . ($admin->nombre ?? '') . ' ' . $admin->usuario) }}">
    <div class="rnd-card-header" onclick="toggleDetalle({{ $admin->id }})">

        {{-- Admin --}}
        <div style="display:flex;align-items:center;gap:10px;min-width:0">
            <div class="rnd-admin-avatar" style="background:{{ $color }}">{{ strtoupper(substr($admin->usuario,0,1)) }}</div>
            <div style="min-width:0">
                <div class="rnd-admin-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    {{ $admin->alias ?: ($admin->nombre ?: $admin->usuario) }}
                </div>
                <div class="rnd-admin-sub">{{ $s['total'] }} préstamos</div>
            </div>
        </div>

        {{-- Capital + progress --}}
        <div style="padding-right:16px">
            <div class="rnd-col-label">Capital / Cobranza</div>
            <div style="font-size:13px;font-weight:700;color:var(--text)">${{ number_format($s['capital_desplegado'],0,'.',',') }}</div>
            <div class="rnd-bar-wrap" style="width:90%"><div class="rnd-bar-fill" style="width:{{ $pct }}%;background:{{ $barColor }}"></div></div>
            <div style="font-size:10px;color:var(--text3);margin-top:2px">{{ $pct }}% cobrado</div>
        </div>

        {{-- Total cobrado --}}
        <div class="rnd-col-hide" style="padding-right:16px">
            <div class="rnd-col-label">Total cobrado</div>
            <div class="rnd-col-value" style="color:#16a34a">${{ number_format($s['total_cobrado'],0,'.',',') }}</div>
            <div class="rnd-col-sub">de ${{ number_format($s['total_acordado'],0,'.',',') }}</div>
        </div>

        {{-- Saldo --}}
        <div class="rnd-col-hide" style="padding-right:16px">
            <div class="rnd-col-label">Saldo activo</div>
            <div class="rnd-col-value" style="color:#d97706">${{ number_format($s['saldo_pendiente'],0,'.',',') }}</div>
            <div class="rnd-col-sub">en cartera</div>
        </div>

        {{-- Pills --}}
        <div class="rnd-col-hide" style="display:flex;flex-wrap:wrap;gap:4px;padding-right:16px;align-content:center">
            @if($s['por_estatus']['Activo']>0)<span class="rnd-pill rnd-pill-blue">{{ $s['por_estatus']['Activo'] }} Activo</span>@endif
            @if($s['por_estatus']['Atrasado']>0)<span class="rnd-pill rnd-pill-red">{{ $s['por_estatus']['Atrasado'] }} Atrasado</span>@endif
            @if($s['por_estatus']['Finalizado']>0)<span class="rnd-pill rnd-pill-green">{{ $s['por_estatus']['Finalizado'] }} Finalizado</span>@endif
            @if($s['por_estatus']['Pendiente']>0)<span class="rnd-pill rnd-pill-yellow">{{ $s['por_estatus']['Pendiente'] }} Pendiente</span>@endif
            @if($s['total']===0)<span class="rnd-pill rnd-pill-gray">Sin préstamos</span>@endif
        </div>

        {{-- Mora --}}
        <div class="rnd-col-hide" style="text-align:center;padding-right:12px">
            <div class="rnd-col-label">Mora</div>
            <div style="font-size:13px;font-weight:700;color:{{ $s['mora_pendiente']>0?'#dc2626':'#16a34a' }}">${{ number_format($s['mora_pendiente'],0,'.',',') }}</div>
        </div>

        {{-- Rentabilidad pactada --}}
        <div style="text-align:center">
            <span class="rnd-pct-badge {{ $rentabColor }}">{{ $rentab }}%</span>
            <div style="font-size:10px;color:var(--text3);margin-top:3px">tasa pactada</div>
        </div>

        {{-- Rendimiento real (cobrado/capital) + chevron --}}
        <div style="display:flex;flex-direction:column;align-items:center;gap:6px">
            <span class="rnd-pct-badge {{ $rndColor }}">{{ $rndVal > 0 ? '+' : '' }}{{ $rndVal }}%</span>
            <svg class="rnd-chevron" id="chev-{{ $admin->id }}" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:14px;height:14px">
                <path d="M4 6l4 4 4-4"/>
            </svg>
        </div>
    </div>

    {{-- ── Panel expandible ──────────────────────────────────── --}}
    <div class="rnd-detail" id="detail-{{ $admin->id }}">

        {{-- Gráficas --}}
        <div class="rnd-charts-row">
            {{-- Donut --}}
            <div class="rnd-chart-box" style="display:flex;flex-direction:column">
                <div class="rnd-chart-title">Distribución por estatus</div>
                @if($s['total'] > 0)
                <div style="flex:1;position:relative;min-height:200px">
                    <canvas id="pie-{{ $admin->id }}"></canvas>
                </div>
                @else
                <div style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--text3);font-size:12px;min-height:160px">Sin préstamos</div>
                @endif
            </div>

            {{-- Línea diaria --}}
            <div class="rnd-chart-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                    <div class="rnd-chart-title" style="margin-bottom:0">Flujo diario — últimos 90 días</div>
                    <div style="display:flex;gap:12px;font-size:11px">
                        <span style="display:flex;align-items:center;gap:4px"><span style="width:20px;height:2px;background:#ef4444;display:inline-block;border-radius:2px"></span>Desembolsos</span>
                        <span style="display:flex;align-items:center;gap:4px"><span style="width:20px;height:2px;background:#16a34a;display:inline-block;border-radius:2px"></span>Cobros</span>
                    </div>
                </div>
                <div style="position:relative;height:210px">
                    <canvas id="line-{{ $admin->id }}"></canvas>
                </div>
            </div>
        </div>

        {{-- Métricas --}}
        @php $ip = $s['interes_esperado']>0?round($s['interes_cobrado']/$s['interes_esperado']*100,1):0; @endphp
        <div class="rnd-detail-grid" id="stats-{{ $admin->id }}">
            <div class="rnd-stat-box">
                <div class="rnd-stat-label">Capital desplegado</div>
                <div class="rnd-stat-value" style="color:#2563eb" id="sv-{{ $admin->id }}-capdes">${{ number_format($s['capital_desplegado'],2,'.',',') }}</div>
                <div class="rnd-stat-sub">dinero entregado a clientes</div>
            </div>
            <div class="rnd-stat-box">
                <div class="rnd-stat-label">Interés acordado</div>
                <div class="rnd-stat-value" style="color:#7c3aed" id="sv-{{ $admin->id }}-intesp">${{ number_format($s['interes_esperado'],2,'.',',') }}</div>
                <div class="rnd-stat-sub">ganancia proyectada total</div>
            </div>
            <div class="rnd-stat-box">
                <div class="rnd-stat-label">Interés cobrado</div>
                <div class="rnd-stat-value" style="color:#16a34a" id="sv-{{ $admin->id }}-intcob">${{ number_format($s['interes_cobrado'],2,'.',',') }}</div>
                <div class="rnd-stat-sub" id="sv-{{ $admin->id }}-intcob-sub">{{ $ip }}% del interés acordado</div>
                <div class="rnd-bar-wrap" style="margin-top:7px"><div class="rnd-bar-fill" id="sv-{{ $admin->id }}-intcob-bar" style="width:{{ min(100,$ip) }}%;background:#16a34a"></div></div>
            </div>
            <div class="rnd-stat-box">
                <div class="rnd-stat-label">Total cobrado</div>
                <div class="rnd-stat-value" id="sv-{{ $admin->id }}-totcob">${{ number_format($s['total_cobrado'],2,'.',',') }}</div>
                <div class="rnd-stat-sub" id="sv-{{ $admin->id }}-totcob-sub">{{ $pct }}% del total acordado</div>
                <div class="rnd-bar-wrap" style="margin-top:7px"><div class="rnd-bar-fill" id="sv-{{ $admin->id }}-totcob-bar" style="width:{{ $pct }}%;background:{{ $barColor }}"></div></div>
            </div>
            <div class="rnd-stat-box">
                <div class="rnd-stat-label">Saldo pendiente</div>
                <div class="rnd-stat-value" style="color:#d97706" id="sv-{{ $admin->id }}-saldo">${{ number_format($s['saldo_pendiente'],2,'.',',') }}</div>
                <div class="rnd-stat-sub">en préstamos activos/atrasados</div>
            </div>
            <div class="rnd-stat-box">
                <div class="rnd-stat-label">Mora acumulada</div>
                <div class="rnd-stat-value" id="sv-{{ $admin->id }}-mora" style="color:{{ $s['mora_pendiente']>0?'#dc2626':'#16a34a' }}">${{ number_format($s['mora_pendiente'],2,'.',',') }}</div>
                <div class="rnd-stat-sub">interés moratorio sin cobrar</div>
            </div>
            <div class="rnd-stat-box" style="border-color:rgba(59,130,246,.2);background:rgba(59,130,246,.03)">
                <div class="rnd-stat-label" style="color:#2563eb">Rentabilidad promedio</div>
                <div class="rnd-stat-value" style="color:#2563eb" id="sv-{{ $admin->id }}-rentab">{{ $rentab }}%</div>
                <div class="rnd-stat-sub">interés acordado / capital desplegado</div>
            </div>
            <div class="rnd-stat-box" style="border-color:rgba(124,58,237,.2);background:rgba(124,58,237,.03)">
                <div class="rnd-stat-label" style="color:#7c3aed">Rendimiento real</div>
                <div class="rnd-stat-value" id="sv-{{ $admin->id }}-rnd" style="color:{{ $rndVal > 0 ? '#16a34a' : ($rndVal < 0 ? '#dc2626' : '#9ca3af') }}">{{ $rndVal > 0 ? '+' : '' }}{{ $rndVal }}%</div>
                <div class="rnd-stat-sub">(cobrado − capital) / capital</div>
            </div>
        </div>
    </div>
</div>

{{-- ── JSON data para Chart.js ─────────────────────────────── --}}
<script type="application/json" id="pie-data-{{ $admin->id }}">{!! json_encode([
    'labels'     => $pieLabels,
    'values'     => $pieValues,
    'colors'     => $pieColors,
    'by_estatus' => $s['by_estatus'] ?? [],
], JSON_UNESCAPED_UNICODE) !!}</script>

<script type="application/json" id="line-data-{{ $admin->id }}">{!! json_encode([
    'labels'      => $s['chart_labels'],
    'desembolsos' => $s['chart_desembolsos'],
    'cobros'      => $s['chart_cobros'],
], JSON_UNESCAPED_UNICODE) !!}</script>

@endforeach
@endif

{{-- ── JSON data para la gráfica de portafolio ────────────── --}}
<script type="application/json" id="portfolio-json">{!! json_encode([
    'dates'  => $globales['chart_dates'],
    'labels' => $globales['chart_labels'],
    'from'   => $globales['chart_from'],
    'global' => [
        'desembolsos' => $globales['chart_desembolsos'],
        'cobros'      => $globales['chart_cobros'],
    ],
    'admins' => $stats->map(fn($s) => [
        'id'          => $s['admin']->id,
        'nombre'      => $s['admin']->alias ?: ($s['admin']->nombre ?: $s['admin']->usuario),
        'desembolsos' => $s['chart_desembolsos'],
        'cobros'      => $s['chart_cobros'],
    ])->values(),
], JSON_UNESCAPED_UNICODE) !!}</script>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// ── Buscador de admins ────────────────────────────────────────
function filtrarRendimientos(q) {
    q = q.toLowerCase().trim();
    const cards  = document.querySelectorAll('.rnd-card');
    let visible  = 0;
    cards.forEach(function(c) {
        const match = !q || (c.dataset.search || '').includes(q);
        c.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    const noRes  = document.getElementById('rndNoResult');
    const header = document.querySelector('.rnd-list-header');
    if (noRes) noRes.style.display = visible === 0 ? '' : 'none';
    if (header) header.style.display = visible === 0 ? 'none' : '';
}

// ════════════════════════════════════════════════════════════
// PORTFOLIO CHART — filtros interactivos
// ════════════════════════════════════════════════════════════
const PF = JSON.parse(document.getElementById('portfolio-json').textContent);
let portfolioChart = null;
let pfMode = 'comparar';

// Modos de la gráfica
function setPfMode(btn) {
    pfMode = btn.dataset.mode;
    document.querySelectorAll('#pfModeBtns .pf-mbtn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Actualizar leyenda
    const legend = document.getElementById('pf-legend');
    if (pfMode === 'comparar') {
        legend.innerHTML = '<span class="pf-legend-item"><span class="pf-legend-dot" style="background:#ef4444"></span>Desembolsos</span>' +
                           '<span class="pf-legend-item"><span class="pf-legend-dot" style="background:#16a34a"></span>Cobros</span>';
    } else {
        legend.innerHTML = '<span class="pf-legend-item"><span class="pf-legend-dot" style="background:#7c3aed"></span>Flujo neto (Cobros − Desembolsos) · verde=positivo · rojo=negativo</span>';
    }
    updatePortfolioChart();
}

// Rango rápido de días
function setPfRange(days, btn) {
    document.querySelectorAll('#pfQuickBtns .pf-qbtn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const today  = PF.dates[PF.dates.length - 1];
    const desde  = PF.dates[Math.max(0, PF.dates.length - days)];
    document.getElementById('pf-desde').value = desde;
    document.getElementById('pf-hasta').value = today;
    updatePortfolioChart();
}

// Construir/actualizar gráfica de portafolio
function updatePortfolioChart() {
    // Quitar el active de los botones rápidos si cambian las fechas manualmente
    const desdeEl = document.getElementById('pf-desde');
    const hastaEl = document.getElementById('pf-hasta');
    const adminId = document.getElementById('pf-admin').value;
    const desde   = desdeEl.value;
    const hasta   = hastaEl.value;

    // Obtener datos del admin seleccionado
    let rawDes, rawCob;
    if (adminId === 'all') {
        rawDes = PF.global.desembolsos;
        rawCob = PF.global.cobros;
    } else {
        const admin = PF.admins.find(a => a.id == adminId);
        rawDes = admin ? admin.desembolsos : PF.global.desembolsos;
        rawCob = admin ? admin.cobros      : PF.global.cobros;
    }

    // Filtrar por rango de fechas
    let startIdx = 0;
    let endIdx   = PF.dates.length - 1;
    if (desde) {
        const si = PF.dates.indexOf(desde);
        if (si !== -1) startIdx = si;
    }
    if (hasta) {
        const ei = PF.dates.indexOf(hasta);
        if (ei !== -1) endIdx = Math.max(startIdx, ei);
    }

    const filtLabels = PF.labels.slice(startIdx, endIdx + 1);
    const filtDes    = rawDes.slice(startIdx, endIdx + 1);
    const filtCob    = rawCob.slice(startIdx, endIdx + 1);

    // Construir datasets según modo
    let datasets;
    if (pfMode === 'comparar') {
        datasets = [
            {
                label: 'Desembolsos',
                data: filtDes,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239,68,68,.08)',
                fill: true, tension: 0.38, pointRadius: 0, pointHoverRadius: 5,
                pointHoverBackgroundColor: '#ef4444', borderWidth: 2,
            },
            {
                label: 'Cobros',
                data: filtCob,
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22,163,74,.08)',
                fill: true, tension: 0.38, pointRadius: 0, pointHoverRadius: 5,
                pointHoverBackgroundColor: '#16a34a', borderWidth: 2,
            }
        ];
    } else {
        // Flujo neto: cobros - desembolsos (positivo = ganamos ese día, negativo = salió más dinero)
        const netData = filtCob.map((c, i) => c - filtDes[i]);
        // Color dinámico por punto: verde si >= 0, rojo si < 0
        const pointColors = netData.map(v => v >= 0 ? '#16a34a' : '#ef4444');
        datasets = [
            {
                label: 'Flujo neto',
                data: netData,
                borderColor: '#7c3aed',
                backgroundColor: netData.map(v => v >= 0 ? 'rgba(22,163,74,.06)' : 'rgba(239,68,68,.06)'),
                fill: false,
                tension: 0.35,
                pointRadius: netData.map(v => v !== 0 ? 3 : 0),
                pointBackgroundColor: pointColors,
                pointBorderColor: pointColors,
                pointHoverRadius: 6,
                borderWidth: 2,
                segment: {
                    borderColor: ctx => ctx.p0.parsed.y >= 0 ? '#16a34a' : '#ef4444',
                }
            }
        ];
    }

    // Reusar o crear
    if (portfolioChart) {
        portfolioChart.data.labels   = filtLabels;
        portfolioChart.data.datasets = datasets;
        portfolioChart.update('active');
        return;
    }

    const ctx = document.getElementById('portfolioChart').getContext('2d');
    portfolioChart = new Chart(ctx, {
        type: 'line',
        data: { labels: filtLabels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15,23,42,.92)',
                    titleColor: '#e2e8f0',
                    bodyColor: '#94a3b8',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        title: items => items[0].label,
                        label: ctx => {
                            if (ctx.raw === 0) return null;
                            if (pfMode === 'sumatoria') {
                                const sign = ctx.raw >= 0 ? '▲ +' : '▼ ';
                                const abs  = Math.abs(ctx.raw);
                                return sign + '$' + abs.toLocaleString('es-MX', {minimumFractionDigits:0, maximumFractionDigits:0});
                            }
                            const prefix = ctx.datasetIndex === 0 ? '↑ ' : '↓ ';
                            return prefix + ctx.dataset.label + ': $' +
                                   ctx.raw.toLocaleString('es-MX', {minimumFractionDigits:0, maximumFractionDigits:0});
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxTicksLimit: 12, font: { size: 10 }, color: '#9ca3af', maxRotation: 0 },
                    border: { display: false },
                },
                y: {
                    grid: { color: 'rgba(0,0,0,.04)' },
                    ticks: {
                        font: { size: 10 }, color: '#9ca3af', maxTicksLimit: 6,
                        callback: v => v === 0 ? '$0' : (v >= 1000 ? '$'+(v/1000).toFixed(0)+'k' : '$'+v)
                    },
                    border: { display: false },
                    beginAtZero: true,
                }
            }
        }
    });
}

// Inicializar la gráfica de portafolio al cargar
document.addEventListener('DOMContentLoaded', function () {
    // Defaults: últimos 90 días
    const today = PF.dates[PF.dates.length - 1];
    const from  = PF.dates[0];
    document.getElementById('pf-desde').value = from;
    document.getElementById('pf-hasta').value = today;
    updatePortfolioChart();
});

// ════════════════════════════════════════════════════════════
// PER-ADMIN CHARTS (donut + line por card)
// ════════════════════════════════════════════════════════════
const ownerCharts = {};

// ── Recalcular stat boxes según estatus visibles ──────────
function recalcStats(adminId, chart, byEstatus) {
    var meta   = chart.getDatasetMeta(0);
    var labels = chart.data.labels;

    // Acumular solo los estatus cuyo segmento NO está oculto
    var capDes = 0, intEsp = 0, saldo = 0, mora = 0, cobrado = 0, intCob = 0, capAcord = 0;
    labels.forEach(function(est, i) {
        if (meta.data[i] && meta.data[i].hidden) return;
        var d = byEstatus[est];
        if (!d) return;
        capDes   += d.capDes;
        capAcord += d.capAcord;
        intEsp   += d.intEsp;
        saldo    += d.saldo;
        mora     += d.mora;
        cobrado  += d.cobrado;
        intCob   += d.intCob;
    });

    var fmt  = function(n) { return '$' + Math.round(n * 100) / 100 .toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2}); };
    var ip   = intEsp > 0 ? Math.round(intCob / intEsp * 1000) / 10 : 0;
    var pct  = capAcord > 0 ? Math.round(cobrado / capAcord * 1000) / 10 : 0;
    var rentab = capDes > 0 ? Math.round(intEsp / capDes * 1000) / 10 : 0;
    var rnd    = capDes > 0 ? Math.round((cobrado - capDes) / capDes * 1000) / 10 : 0;
    var barColor = pct >= 75 ? '#16a34a' : pct >= 40 ? '#f59e0b' : '#ef4444';

    var set = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
    var setStyle = function(id, prop, val) { var el = document.getElementById(id); if (el) el.style[prop] = val; };
    var setWidth = function(id, w) { var el = document.getElementById(id); if (el) el.style.width = Math.min(100, w) + '%'; };

    var p = 'sv-' + adminId + '-';
    set(p + 'capdes',      fmt(capDes));
    set(p + 'intesp',      fmt(intEsp));
    set(p + 'intcob',      fmt(intCob));
    set(p + 'intcob-sub',  ip + '% del interés acordado');
    setWidth(p + 'intcob-bar', ip);
    set(p + 'totcob',      fmt(cobrado));
    set(p + 'totcob-sub',  pct + '% del total acordado');
    setWidth(p + 'totcob-bar', pct);
    setStyle(p + 'totcob-bar', 'background', barColor);
    set(p + 'saldo',       fmt(saldo));
    set(p + 'mora',        fmt(mora));
    setStyle(p + 'mora',   'color', mora > 0 ? '#dc2626' : '#16a34a');
    set(p + 'rentab',      rentab + '%');
    set(p + 'rnd',         (rnd > 0 ? '+' : '') + rnd + '%');
    setStyle(p + 'rnd',    'color', rnd > 0 ? '#16a34a' : rnd < 0 ? '#dc2626' : '#9ca3af');
}

// ── Toggle panel + lazy chart init ───────────────────────
function toggleDetalle(adminId) {
    var panel = document.getElementById('detail-' + adminId);
    var chev  = document.getElementById('chev-' + adminId);
    if (!panel) return;
    var isOpen = panel.classList.contains('open');

    document.querySelectorAll('.rnd-detail.open').forEach(function(el){ el.classList.remove('open'); });
    document.querySelectorAll('.rnd-chevron.open').forEach(function(el){ el.classList.remove('open'); });

    if (!isOpen) {
        panel.classList.add('open');
        if (chev) chev.classList.add('open');
        // Init charts lazily
        setTimeout(function(){ initCharts(adminId); }, 60);
    }
}

// ── Chart.js init ─────────────────────────────────────────
function initCharts(adminId) {
    if (ownerCharts[adminId]) return;
    ownerCharts[adminId] = {};

    // ── Donut ─────────────────────────────────────────────
    var pieEl  = document.getElementById('pie-' + adminId);
    var pieRaw = document.getElementById('pie-data-' + adminId);
    if (pieEl && pieRaw) {
        var pd = JSON.parse(pieRaw.textContent);
        if (pd.values.length > 0) {
            ownerCharts[adminId].pie = new Chart(pieEl, {
                type: 'doughnut',
                data: {
                    labels: pd.labels,
                    datasets: [{
                        data: pd.values,
                        backgroundColor: pd.colors,
                        borderWidth: 3,
                        borderColor: '#f9fafb',
                        hoverOffset: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: { size: 11, family: 'DM Sans, sans-serif' },
                                padding: 12,
                                boxWidth: 10,
                                usePointStyle: true,
                                pointStyle: 'circle',
                            },
                            onClick: function(e, legendItem, legend) {
                                // Comportamiento default (toggle visibilidad del segmento)
                                Chart.defaults.plugins.legend.onClick.call(this, e, legendItem, legend);
                                // Recalcular stats con los estatus visibles
                                recalcStats(adminId, legend.chart, pd.by_estatus);
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    var total = ctx.dataset.data.reduce(function(a,b){ return a+b; }, 0);
                                    var pct   = Math.round(ctx.raw / total * 100);
                                    return ' ' + ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // ── Line ──────────────────────────────────────────────
    var lineEl  = document.getElementById('line-' + adminId);
    var lineRaw = document.getElementById('line-data-' + adminId);
    if (lineEl && lineRaw) {
        var ld = JSON.parse(lineRaw.textContent);

        ownerCharts[adminId].line = new Chart(lineEl, {
            type: 'line',
            data: {
                labels: ld.labels,
                datasets: [
                    {
                        label: 'Desembolsos',
                        data: ld.desembolsos,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239,68,68,.07)',
                        fill: true,
                        tension: 0.38,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#ef4444',
                        borderWidth: 2,
                    },
                    {
                        label: 'Cobros',
                        data: ld.cobros,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22,163,74,.07)',
                        fill: true,
                        tension: 0.38,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#16a34a',
                        borderWidth: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,.92)',
                        titleColor: '#e2e8f0',
                        bodyColor: '#94a3b8',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            title: function(items) { return items[0].label; },
                            label: function(ctx) {
                                if (ctx.raw === 0) return null;
                                var sym = ctx.datasetIndex === 0 ? '↑ ' : '↓ ';
                                return sym + ctx.dataset.label + ': $' + ctx.raw.toLocaleString('es-MX', {minimumFractionDigits:0, maximumFractionDigits:0});
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxTicksLimit: 10,
                            font: { size: 10, family: 'DM Sans, sans-serif' },
                            color: '#9ca3af',
                            maxRotation: 0,
                        },
                        border: { display: false },
                    },
                    y: {
                        grid: { color: 'rgba(0,0,0,.04)', drawBorder: false },
                        ticks: {
                            font: { size: 10, family: 'DM Sans, sans-serif' },
                            color: '#9ca3af',
                            callback: function(v) {
                                if (v === 0) return '$0';
                                return v >= 1000 ? '$' + (v/1000).toFixed(0) + 'k' : '$' + v;
                            },
                            maxTicksLimit: 6,
                        },
                        border: { display: false },
                        beginAtZero: true,
                    }
                }
            }
        });
    }
}
</script>
@endpush

@endsection
