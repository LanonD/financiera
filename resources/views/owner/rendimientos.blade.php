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
.rnd-pill-green{background:#f0fdf4;color:#10b981}
.rnd-pill-yellow{background:#fefce8;color:#ca8a04}
.rnd-pill-gray{background:#f3f4f6;color:#6b7280}

/* % badge */
.rnd-pct-badge{display:inline-flex;align-items:center;justify-content:center;padding:4px 10px;border-radius:8px;font-size:14px;font-weight:800;min-width:56px}
.rnd-pct-green{background:#f0fdf4;color:#10b981}
.rnd-pct-yellow{background:#fefce8;color:#d97706}
.rnd-pct-red{background:#fef2f2;color:#dc2626}
.rnd-pct-blue{background:#eff6ff;color:#4f46e5}
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

/* ═══════════════════════ PULIDO — sistema de gráficas profesional ═══════════════════════ */
.rnd-kpi{box-shadow:var(--shadow-sm);transition:transform .25s cubic-bezier(.2,.7,.2,1),box-shadow .25s}
.rnd-kpi:hover{transform:translateY(-3px);box-shadow:var(--shadow-md)}
.rnd-kpi-accent{box-shadow:0 0 14px 0 currentColor;opacity:.95}
.rnd-kpi-value{font-variant-numeric:tabular-nums}

/* Secciones de gráficas: tarjeta elevada con cabecera sutil */
.pf-section{box-shadow:var(--shadow-sm);border-radius:var(--radius-lg);transition:box-shadow .25s}
.pf-section:hover{box-shadow:var(--shadow-md)}
.pf-title{font-family:var(--display);font-weight:500;font-size:17px;letter-spacing:-.01em}
.pf-chart-wrap{height:320px}

/* Controles: pills de rango y modo */
.pf-qbtn{border-radius:8px;transition:background .15s,color .15s,transform .12s}
.pf-qbtn:hover{transform:translateY(-1px)}
.pf-qbtn.active{box-shadow:0 6px 14px -6px rgba(16,185,129,.6)}
.pf-mode-btns{border-radius:9px}
.pf-mbtn.active{box-shadow:inset 0 0 0 1px rgba(255,255,255,.15)}
.pf-select:focus,.pf-date:focus{box-shadow:0 0 0 3px rgba(16,185,129,.12)}

/* Leyendas tipo "chip" */
.pf-legend-item{padding:3px 10px 3px 8px;background:#f6f7f5;border:1px solid var(--border);border-radius:99px}
.pf-legend-dot{height:8px;width:8px;border-radius:99px}

/* Cajas de gráfica internas (donut / línea por admin) */
.rnd-chart-box{box-shadow:var(--shadow-sm);border-radius:12px}
.rnd-stat-box{transition:transform .2s,box-shadow .2s}
.rnd-stat-box:hover{transform:translateY(-2px);box-shadow:var(--shadow-sm)}
.rnd-stat-value{font-variant-numeric:tabular-nums}

/* Tarjetas de admin: realce esmeralda al hover/activar */
.rnd-card{box-shadow:var(--shadow-sm);transition:box-shadow .2s,border-color .2s}
.rnd-card:hover{box-shadow:var(--shadow-md);border-color:rgba(16,185,129,.25)}
.rnd-card-header:hover .rnd-admin-name{color:var(--accent)}
.rnd-pct-badge{font-variant-numeric:tabular-nums}

@media(prefers-reduced-motion:reduce){
    .rnd-kpi,.pf-section,.rnd-stat-box,.pf-qbtn{transition:none}
}

/* ═══════════════════════ Contabilidad consolidada ═══════════════════════ */
.acc-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:14px}
.acc-kpi{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:15px 16px;position:relative;overflow:hidden;box-shadow:var(--shadow-sm);transition:transform .25s cubic-bezier(.2,.7,.2,1),box-shadow .25s}
.acc-kpi:hover{transform:translateY(-3px);box-shadow:var(--shadow-md)}
.acc-accent{position:absolute;top:0;left:0;width:3px;height:100%;box-shadow:0 0 14px 0 currentColor;opacity:.95}
.acc-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:6px}
.acc-value{font-size:21px;font-weight:800;letter-spacing:-.03em;line-height:1;font-variant-numeric:tabular-nums}
.acc-sub{font-size:11px;color:var(--text2);margin-top:5px;line-height:1.35}
.acc-mini{height:4px;background:#eef0f3;border-radius:99px;overflow:hidden;margin-top:7px}
.acc-mini-fill{height:100%;border-radius:99px}

.acc-panel{background:var(--card);border:1px solid var(--border);border-radius:var(--radius-lg,16px);box-shadow:var(--shadow-sm);padding:18px 20px;margin-bottom:22px}
.acc-panel-title{font-size:13px;font-weight:800;letter-spacing:-.01em;color:var(--text);margin-bottom:2px}
.acc-panel-sub{font-size:11px;color:var(--text3);margin-bottom:15px}
.acc-stack{display:flex;height:28px;border-radius:8px;overflow:hidden;background:#f3f4f6;margin-bottom:11px}
.acc-stack-seg{display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#fff;white-space:nowrap;min-width:0;transition:flex-basis .5s cubic-bezier(.2,.7,.2,1)}
.acc-stack-legend{display:flex;flex-wrap:wrap;gap:14px 20px;align-items:center;margin-bottom:18px}
.acc-leg{display:flex;align-items:center;gap:8px}
.acc-leg-dot{width:11px;height:11px;border-radius:3px;flex-shrink:0;margin-top:2px}
.acc-leg-k{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text3)}
.acc-leg-v{font-size:14px;font-weight:800;color:var(--text);font-variant-numeric:tabular-nums;line-height:1.1}
.acc-dual{display:grid;grid-template-columns:1fr 1fr;gap:14px 26px;padding-top:15px;border-top:1px dashed var(--border)}
.acc-prog-head{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px}
.acc-prog-name{font-size:12px;font-weight:700;color:var(--text2)}
.acc-prog-pct{font-size:14px;font-weight:800;font-variant-numeric:tabular-nums}
.acc-prog-bar{height:9px;background:#f3f4f6;border-radius:99px;overflow:hidden}
.acc-prog-fill{height:100%;border-radius:99px;transition:width .5s cubic-bezier(.2,.7,.2,1)}
.acc-prog-foot{display:flex;justify-content:space-between;margin-top:6px;font-size:11px;color:var(--text3);font-variant-numeric:tabular-nums}

@media(max-width:1200px){.acc-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:768px){.acc-grid{grid-template-columns:repeat(2,1fr)}.acc-dual{grid-template-columns:1fr}}
@media(max-width:480px){.acc-grid{grid-template-columns:1fr}}
/* Modo periodo: 4 KPIs (sin saldo ni mora) */
.acc-grid.cols-4{grid-template-columns:repeat(4,1fr)}
@media(max-width:1100px){.acc-grid.cols-4{grid-template-columns:repeat(2,1fr)}}
@media(max-width:480px){.acc-grid.cols-4{grid-template-columns:1fr}}
/* Filtro de fechas de la contabilidad consolidada */
.acc-filtro{display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px;margin-bottom:14px}
.acc-filtro-badge{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:99px;background:#eff6ff;color:#1d4ed8;font-size:11px;font-weight:700}
@media(max-width:640px){.acc-filtro{align-items:stretch}.acc-filtro .acc-filtro-controls{margin-left:0!important;width:100%}}
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

{{-- ── Filtro de fechas · contabilidad consolidada ─────────── --}}
<form method="GET" action="{{ route('owner.rendimientos') }}" id="accFiltro" class="acc-filtro">
    <div>
        <div style="font-size:14px;font-weight:800;letter-spacing:-.01em;color:var(--text)">Contabilidad consolidada</div>
        <div style="font-size:11px;color:var(--text3);margin-top:2px">
            @if($cuenta['modo'] === 'periodo')
                <span class="acc-filtro-badge">📅 Flujo del periodo · {{ \Carbon\Carbon::parse($cuenta['desde'])->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($cuenta['hasta'])->format('d/m/Y') }}</span>
            @else
                Histórico completo · todas las fechas
            @endif
        </div>
    </div>
    <div class="acc-filtro-controls" style="display:flex;align-items:flex-end;gap:8px;flex-wrap:wrap;margin-left:auto">
        <div class="pf-quick-btns" style="margin-right:4px">
            <button type="button" class="pf-qbtn" onclick="accQuick(30)">30D</button>
            <button type="button" class="pf-qbtn" onclick="accQuick(90)">90D</button>
            <button type="button" class="pf-qbtn" onclick="accQuick('year')">Año</button>
        </div>
        <label style="display:flex;flex-direction:column;gap:3px">
            <span class="pf-filter-label">Desde</span>
            <input type="date" name="desde" value="{{ request('desde') }}" class="pf-date">
        </label>
        <label style="display:flex;flex-direction:column;gap:3px">
            <span class="pf-filter-label">Hasta</span>
            <input type="date" name="hasta" value="{{ request('hasta') }}" class="pf-date">
        </label>
        <button type="submit" class="btn btn-primary" style="font-size:12px">Aplicar</button>
        @if($cuenta['modo'] === 'periodo')
        <a href="{{ route('owner.rendimientos') }}" class="btn" style="background:#f3f4f6;color:var(--text2);font-size:12px">Ver histórico</a>
        @endif
    </div>
</form>

{{-- ── Contabilidad consolidada · KPIs ─────────────────────── --}}
@php
    $g                = $cuenta;
    // Modelo contable capital-primero: lo cobrado primero regresa el capital, el excedente es interés.
    $capRecuperado    = max(0, $g['total_cobrado'] - $g['interes_cobrado']);
    $capPendiente     = max(0, $g['capital_desplegado'] - $capRecuperado);
    $capRecPct        = $g['capital_desplegado'] > 0 ? round($capRecuperado / $g['capital_desplegado'] * 100, 1) : 0;
    $interesEsperado  = max(0, $g['total_acordado'] - $g['capital_desplegado']);
    $interesPorCobrar = max(0, $interesEsperado - $g['interes_cobrado']);
    $interesPct       = $interesEsperado > 0 ? round($g['interes_cobrado'] / $interesEsperado * 100, 1) : 0;
    $totCobBase       = max(1, $g['total_cobrado']);
    $wCap             = round($capRecuperado / $totCobBase * 100, 2);
    $wInt             = round($g['interes_cobrado'] / $totCobBase * 100, 2);
@endphp

@php $esPeriodo = $g['modo'] === 'periodo'; @endphp
<div class="acc-grid {{ $esPeriodo ? 'cols-4' : '' }}">
    {{-- Capital desplegado --}}
    <div class="acc-kpi">
        <div class="acc-accent" style="color:#6366f1;background:#6366f1"></div>
        <div class="acc-label">Capital desplegado</div>
        <div class="acc-value" style="color:#4f46e5">${{ number_format($g['capital_desplegado'],0,'.',',') }}</div>
        <div class="acc-sub">{{ $g['total_prestamos'] }} préstamos · {{ $esPeriodo ? 'entregados en el periodo' : 'dinero prestado' }}</div>
    </div>
    {{-- Capital recuperado --}}
    <div class="acc-kpi">
        <div class="acc-accent" style="color:#0ea5e9;background:#0ea5e9"></div>
        <div class="acc-label">Capital recuperado</div>
        <div class="acc-value" style="color:#0284c7">${{ number_format($capRecuperado,0,'.',',') }}</div>
        @if($esPeriodo)
        <div class="acc-sub">capital cobrado en el periodo</div>
        @else
        <div class="acc-sub">{{ $capRecPct }}% del capital ya regresó</div>
        <div class="acc-mini"><div class="acc-mini-fill" style="width:{{ min(100,$capRecPct) }}%;background:#0ea5e9"></div></div>
        @endif
    </div>
    {{-- Interés cobrado / ganado --}}
    <div class="acc-kpi">
        <div class="acc-accent" style="color:#7c3aed;background:#7c3aed"></div>
        <div class="acc-label">Interés {{ $esPeriodo ? 'ganado' : 'cobrado' }}</div>
        <div class="acc-value" style="color:#7c3aed">${{ number_format($g['interes_cobrado'],0,'.',',') }}</div>
        @if($esPeriodo)
        <div class="acc-sub">ganancia del periodo</div>
        @else
        <div class="acc-sub">ganancia real · {{ $interesPct }}% del esperado</div>
        <div class="acc-mini"><div class="acc-mini-fill" style="width:{{ min(100,$interesPct) }}%;background:#7c3aed"></div></div>
        @endif
    </div>
    {{-- Total cobrado --}}
    <div class="acc-kpi">
        <div class="acc-accent" style="color:#10b981;background:#10b981"></div>
        <div class="acc-label">Total cobrado</div>
        <div class="acc-value" style="color:#10b981">${{ number_format($g['total_cobrado'],0,'.',',') }}</div>
        <div class="acc-sub">capital + interés · {{ $esPeriodo ? 'cobrado en el periodo' : 'entró a caja' }}</div>
    </div>
    @unless($esPeriodo)
    {{-- En cartera (por cobrar) · solo histórico (es saldo a hoy) --}}
    <div class="acc-kpi">
        <div class="acc-accent" style="color:#f59e0b;background:#f59e0b"></div>
        <div class="acc-label">En cartera (por cobrar)</div>
        <div class="acc-value" style="color:#d97706">${{ number_format($g['saldo_pendiente'],0,'.',',') }}</div>
        <div class="acc-sub">saldo vivo · activos + atrasados</div>
    </div>
    {{-- Mora acumulada · solo histórico (es saldo a hoy) --}}
    <div class="acc-kpi">
        <div class="acc-accent" style="color:#ef4444;background:#ef4444"></div>
        <div class="acc-label">Mora acumulada</div>
        <div class="acc-value" style="color:#dc2626">${{ number_format($g['mora_pendiente'],0,'.',',') }}</div>
        <div class="acc-sub">interés moratorio sin cobrar</div>
    </div>
    @endunless
</div>

{{-- ── Estado de cuenta: composición de caja + progreso ────── --}}
<div class="acc-panel">
    <div class="acc-panel-title">Estado de cuenta consolidado</div>
    <div class="acc-panel-sub">
        @if($esPeriodo)
            Composición de lo cobrado entre el {{ \Carbon\Carbon::parse($g['desde'])->format('d/m/Y') }} y el {{ \Carbon\Carbon::parse($g['hasta'])->format('d/m/Y') }}
        @else
            Cómo se compone el dinero que ya entró a caja y cuánto falta por recuperar
        @endif
    </div>

    @if($g['total_cobrado'] > 0)
    {{-- Barra apilada: Total cobrado = Capital recuperado + Interés ganado --}}
    <div class="acc-stack">
        <div class="acc-stack-seg" style="flex:0 0 {{ $wCap }}%;background:#0ea5e9" title="Capital recuperado: ${{ number_format($capRecuperado,2,'.',',') }}">{{ $wCap >= 14 ? '$'.number_format($capRecuperado,0,'.',',') : '' }}</div>
        <div class="acc-stack-seg" style="flex:0 0 {{ $wInt }}%;background:#7c3aed" title="Interés ganado: ${{ number_format($g['interes_cobrado'],2,'.',',') }}">{{ $wInt >= 14 ? '$'.number_format($g['interes_cobrado'],0,'.',',') : '' }}</div>
    </div>
    <div class="acc-stack-legend">
        <div class="acc-leg"><span class="acc-leg-dot" style="background:#0ea5e9"></span><div><div class="acc-leg-k">Capital recuperado</div><div class="acc-leg-v">${{ number_format($capRecuperado,0,'.',',') }}</div></div></div>
        <div class="acc-leg"><span class="acc-leg-dot" style="background:#7c3aed"></span><div><div class="acc-leg-k">Interés ganado</div><div class="acc-leg-v">${{ number_format($g['interes_cobrado'],0,'.',',') }}</div></div></div>
        <div class="acc-leg" style="margin-left:auto"><span class="acc-leg-dot" style="background:#10b981"></span><div><div class="acc-leg-k">Total cobrado</div><div class="acc-leg-v" style="color:#10b981">${{ number_format($g['total_cobrado'],0,'.',',') }}</div></div></div>
    </div>
    @endif

    {{-- Progreso dual: recuperación de capital + cobro de interés (solo histórico) --}}
    @unless($esPeriodo)
    <div class="acc-dual">
        <div>
            <div class="acc-prog-head">
                <span class="acc-prog-name">Recuperación de capital</span>
                <span class="acc-prog-pct" style="color:#0284c7">{{ $capRecPct }}%</span>
            </div>
            <div class="acc-prog-bar"><div class="acc-prog-fill" style="width:{{ min(100,$capRecPct) }}%;background:linear-gradient(90deg,#38bdf8,#0ea5e9)"></div></div>
            <div class="acc-prog-foot">
                <span>Recuperado ${{ number_format($capRecuperado,0,'.',',') }}</span>
                <span>Falta ${{ number_format($capPendiente,0,'.',',') }}</span>
            </div>
        </div>
        <div>
            <div class="acc-prog-head">
                <span class="acc-prog-name">Cobro de interés</span>
                <span class="acc-prog-pct" style="color:#7c3aed">{{ $interesPct }}%</span>
            </div>
            <div class="acc-prog-bar"><div class="acc-prog-fill" style="width:{{ min(100,$interesPct) }}%;background:linear-gradient(90deg,#a78bfa,#7c3aed)"></div></div>
            <div class="acc-prog-foot">
                <span>Cobrado ${{ number_format($g['interes_cobrado'],0,'.',',') }}</span>
                <span>Por cobrar ${{ number_format($interesPorCobrar,0,'.',',') }}</span>
            </div>
        </div>
    </div>
    @endunless

    {{-- Origen de lo cobrado: cuentas abiertas (en curso) vs finalizadas (liquidadas) --}}
    @php
        $cobAb     = $g['cobrado_abiertas']    ?? 0;
        $cobFin    = $g['cobrado_finalizadas'] ?? 0;
        $intAb     = $g['interes_abiertas']    ?? 0;
        $intFin    = $g['interes_finalizadas'] ?? 0;
        $capAb     = max(0, $cobAb  - $intAb);
        $capFin    = max(0, $cobFin - $intFin);
        $origenTot = max(1, $cobAb + $cobFin);
        $wAb       = round($cobAb  / $origenTot * 100, 2);
        $wFin      = round($cobFin / $origenTot * 100, 2);
    @endphp
    @if(($cobAb + $cobFin) > 0)
    <div style="margin-top:16px;padding-top:15px;border-top:1px dashed var(--border)">
        <div class="acc-panel-title" style="font-size:12px;margin-bottom:1px">Origen de lo cobrado</div>
        <div class="acc-panel-sub" style="margin-bottom:11px">Cuánto del dinero que entró viene de cuentas abiertas (en curso) vs finalizadas (liquidadas)</div>
        <div class="acc-stack">
            <div class="acc-stack-seg" style="flex:0 0 {{ $wAb }}%;background:#6366f1" title="Cuentas abiertas: ${{ number_format($cobAb,2,'.',',') }}">{{ $wAb >= 16 ? '$'.number_format($cobAb,0,'.',',') : '' }}</div>
            <div class="acc-stack-seg" style="flex:0 0 {{ $wFin }}%;background:#10b981" title="Cuentas finalizadas: ${{ number_format($cobFin,2,'.',',') }}">{{ $wFin >= 16 ? '$'.number_format($cobFin,0,'.',',') : '' }}</div>
        </div>
        <div class="acc-stack-legend" style="margin-bottom:0">
            <div class="acc-leg">
                <span class="acc-leg-dot" style="background:#6366f1"></span>
                <div>
                    <div class="acc-leg-k">Cuentas abiertas{{ isset($g['n_abiertas']) ? ' · '.$g['n_abiertas'] : '' }}</div>
                    <div class="acc-leg-v">${{ number_format($cobAb,0,'.',',') }}</div>
                    <div style="font-size:10px;color:var(--text3);margin-top:1px">capital ${{ number_format($capAb,0,'.',',') }} · interés ${{ number_format($intAb,0,'.',',') }}</div>
                </div>
            </div>
            <div class="acc-leg">
                <span class="acc-leg-dot" style="background:#10b981"></span>
                <div>
                    <div class="acc-leg-k">Cuentas finalizadas{{ isset($g['n_finalizadas']) ? ' · '.$g['n_finalizadas'] : '' }}</div>
                    <div class="acc-leg-v">${{ number_format($cobFin,0,'.',',') }}</div>
                    <div style="font-size:10px;color:var(--text3);margin-top:1px">capital ${{ number_format($capFin,0,'.',',') }} · interés ${{ number_format($intFin,0,'.',',') }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- ── Barra de cobranza global ────────────────────────────── --}}
@php $gPct = $globales['recuperado_pct']; $gBarColor = $gPct>=75?'#10b981':($gPct>=40?'#f59e0b':'#ef4444'); @endphp
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
            <div style="font-size:16px;font-weight:800;color:{{ $globales['rendimiento_pct']>0?'#10b981':($globales['rendimiento_pct']===0.0?'#9ca3af':'#dc2626') }}">{{ $globales['rendimiento_pct'] > 0 ? '+' : '' }}{{ $globales['rendimiento_pct'] }}%</div>
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
        <span class="pf-legend-item"><span class="pf-legend-dot" style="background:#6366f1"></span>Desembolsos</span>
        <span class="pf-legend-item"><span class="pf-legend-dot" style="background:#10b981"></span>Cobros</span>
    </div>

    {{-- Canvas --}}
    <div class="pf-chart-wrap">
        <canvas id="portfolioChart"></canvas>
    </div>
</div>

{{-- ── Posición neta acumulada ─────────────────────────────── --}}
<div class="pf-section">
    <div class="pf-section-head">
        <div>
            <div class="pf-title">Posición neta acumulada</div>
            <div class="pf-subtitle">Suma corrida de (cobros − desembolsos) · usa los mismos filtros de arriba · verde = zona positiva · rojo = zona negativa</div>
        </div>
        {{-- KPI chip: valor actual --}}
        <div id="accumKpi" style="display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:9px;background:#f0fdf4;border:1.5px solid #bbf7d0;min-width:160px;justify-content:center">
            <div>
                <div id="accumKpiLabel" style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#15803d">Posición actual</div>
                <div id="accumKpiValue" style="font-size:18px;font-weight:800;color:#15803d;letter-spacing:-.02em">$0</div>
            </div>
        </div>
    </div>
    <div class="pf-legend">
        <span class="pf-legend-item"><span class="pf-legend-dot" style="background:#10b981"></span>Zona positiva (cobros &gt; desembolsos)</span>
        <span class="pf-legend-item"><span class="pf-legend-dot" style="background:#f43f5e"></span>Zona negativa (desembolsos &gt; cobros)</span>
    </div>
    <div class="pf-chart-wrap">
        <canvas id="accumChart"></canvas>
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
    $colors   = ['#6366f1','#10b981','#8b5cf6','#ec4899','#0ea5e9','#f59e0b','#14b8a6','#f43f5e'];
    $color    = $colors[crc32($admin->usuario) % count($colors)];
    $pct      = $s['recuperado_pct'];
    $barColor = $pct>=75?'#10b981':($pct>=40?'#f59e0b':'#ef4444');
    // Rentabilidad pactada: tasa de rendimiento acordada (interés/capital)
    $rentab    = $s['rentabilidad_pct'] ?? 0;
    $rentabColor = $rentab >= 40 ? 'rnd-pct-green' : ($rentab >= 20 ? 'rnd-pct-blue' : ($rentab > 0 ? 'rnd-pct-yellow' : 'rnd-pct-gray'));
    // Rendimiento real: total cobrado / capital desplegado
    $rndVal   = $s['rendimiento_pct'];
    // Positivo = recuperaste capital + ganancia; negativo = aún en déficit
    $rndColor = $rndVal > 0 ? 'rnd-pct-green' : ($rndVal === 0.0 ? 'rnd-pct-gray' : 'rnd-pct-red');

    // Pie chart data (only non-zero statuses)
    $pieLabels = []; $pieValues = []; $pieColors = [];
    $statusColors = ['Activo'=>'#6366f1','Atrasado'=>'#f43f5e','Finalizado'=>'#10b981','Pendiente'=>'#f59e0b','Retirado'=>'#94a3b8'];
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
            <div class="rnd-col-value" style="color:#10b981">${{ number_format($s['total_cobrado'],0,'.',',') }}</div>
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
            <div style="font-size:13px;font-weight:700;color:{{ $s['mora_pendiente']>0?'#dc2626':'#10b981' }}">${{ number_format($s['mora_pendiente'],0,'.',',') }}</div>
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
                        <span style="display:flex;align-items:center;gap:4px"><span style="width:20px;height:2px;background:#6366f1;display:inline-block;border-radius:2px"></span>Desembolsos</span>
                        <span style="display:flex;align-items:center;gap:4px"><span style="width:20px;height:2px;background:#10b981;display:inline-block;border-radius:2px"></span>Cobros</span>
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
                <div class="rnd-stat-value" style="color:#4f46e5" id="sv-{{ $admin->id }}-capdes">${{ number_format($s['capital_desplegado'],2,'.',',') }}</div>
                <div class="rnd-stat-sub">dinero entregado a clientes</div>
            </div>
            <div class="rnd-stat-box">
                <div class="rnd-stat-label">Interés acordado</div>
                <div class="rnd-stat-value" style="color:#7c3aed" id="sv-{{ $admin->id }}-intesp">${{ number_format($s['interes_esperado'],2,'.',',') }}</div>
                <div class="rnd-stat-sub">ganancia proyectada total</div>
            </div>
            <div class="rnd-stat-box">
                <div class="rnd-stat-label">Interés cobrado</div>
                <div class="rnd-stat-value" style="color:#10b981" id="sv-{{ $admin->id }}-intcob">${{ number_format($s['interes_cobrado'],2,'.',',') }}</div>
                <div class="rnd-stat-sub" id="sv-{{ $admin->id }}-intcob-sub">{{ $ip }}% del interés acordado</div>
                <div class="rnd-bar-wrap" style="margin-top:7px"><div class="rnd-bar-fill" id="sv-{{ $admin->id }}-intcob-bar" style="width:{{ min(100,$ip) }}%;background:#10b981"></div></div>
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
                <div class="rnd-stat-value" id="sv-{{ $admin->id }}-mora" style="color:{{ $s['mora_pendiente']>0?'#dc2626':'#10b981' }}">${{ number_format($s['mora_pendiente'],2,'.',',') }}</div>
                <div class="rnd-stat-sub">interés moratorio sin cobrar</div>
            </div>
            <div class="rnd-stat-box" style="border-color:rgba(59,130,246,.2);background:rgba(59,130,246,.03)">
                <div class="rnd-stat-label" style="color:#4f46e5">Rentabilidad promedio</div>
                <div class="rnd-stat-value" style="color:#4f46e5" id="sv-{{ $admin->id }}-rentab">{{ $rentab }}%</div>
                <div class="rnd-stat-sub">interés acordado / capital desplegado</div>
            </div>
            <div class="rnd-stat-box" style="border-color:rgba(124,58,237,.2);background:rgba(124,58,237,.03)">
                <div class="rnd-stat-label" style="color:#7c3aed">Rendimiento real</div>
                <div class="rnd-stat-value" id="sv-{{ $admin->id }}-rnd" style="color:{{ $rndVal > 0 ? '#10b981' : ($rndVal < 0 ? '#dc2626' : '#9ca3af') }}">{{ $rndVal > 0 ? '+' : '' }}{{ $rndVal }}%</div>
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
    'labels'         => $s['chart_labels'],
    'desembolsos'    => $s['chart_desembolsos'],
    'cobros'         => $s['chart_cobros'],
    'des_by_estatus' => $s['chart_des_by_estatus'],
    'cob_by_estatus' => $s['chart_cob_by_estatus'],
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
// ════════════════════════════════════════════════════════════
// SISTEMA DE GRÁFICAS — paleta, tipografía y helpers profesionales
// ════════════════════════════════════════════════════════════
const CHART_FONT = "Sora, system-ui, sans-serif";
// Paleta cohesiva con la marca (esmeralda) + acentos profesionales
const CC = {
    cobro:     '#10b981',  // ingresos / cobros (verde marca)
    desembolso:'#6366f1',  // capital desplegado / desembolsos (índigo)
    pos:       '#10b981',  // zona positiva
    neg:       '#f43f5e',  // zona negativa (rosa, menos agresivo que rojo puro)
    grid:      'rgba(15,22,35,.05)',
    gridZero:  'rgba(15,22,35,.16)',
    tick:      '#9aa3b2',
    tipBg:     'rgba(11,15,23,.94)',
};
if (window.Chart) {
    Chart.defaults.font.family = CHART_FONT;
    Chart.defaults.font.size   = 11;
    Chart.defaults.color       = CC.tick;
    Chart.defaults.animation.duration = 700;
    Chart.defaults.animation.easing   = 'easeOutQuart';
}
// hex → rgba con alfa
function rgba(hex, a){ const n=parseInt(hex.slice(1),16); return `rgba(${(n>>16)&255},${(n>>8)&255},${n&255},${a})`; }
// Degradado vertical para el área bajo la línea (firma de gráfica profesional)
function areaFill(hex, a0){
    a0 = a0 || 0.26;
    return function(context){
        const chart = context.chart, area = chart.chartArea;
        if(!area) return rgba(hex, a0*0.4);
        const g = chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
        g.addColorStop(0, rgba(hex, a0));
        g.addColorStop(1, rgba(hex, 0));
        return g;
    };
}
// Tooltip profesional reutilizable
const TIP = {
    backgroundColor: CC.tipBg,
    titleColor: '#f1f5f9',
    bodyColor: '#cbd5e1',
    borderColor: 'rgba(255,255,255,.08)',
    borderWidth: 1,
    padding: 12,
    cornerRadius: 10,
    boxPadding: 6,
    usePointStyle: true,
    titleFont:{ family: CHART_FONT, weight: '600', size: 12 },
    bodyFont:{ family: CHART_FONT, size: 12 },
};

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

// ── Rango rápido para el filtro de la contabilidad consolidada ──
function accQuick(r) {
    var form  = document.getElementById('accFiltro');
    var hasta = new Date();
    var desde = new Date();
    if (r === 'year') { desde = new Date(hasta.getFullYear(), 0, 1); }
    else { desde.setDate(hasta.getDate() - (r - 1)); }
    var iso = function(d) {
        return d.getFullYear() + '-' +
               String(d.getMonth() + 1).padStart(2, '0') + '-' +
               String(d.getDate()).padStart(2, '0');
    };
    form.querySelector('[name=desde]').value = iso(desde);
    form.querySelector('[name=hasta]').value = iso(hasta);
    form.submit();
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
        legend.innerHTML = '<span class="pf-legend-item"><span class="pf-legend-dot" style="background:#6366f1"></span>Desembolsos</span>' +
                           '<span class="pf-legend-item"><span class="pf-legend-dot" style="background:#10b981"></span>Cobros</span>';
    } else {
        legend.innerHTML = '<span class="pf-legend-item"><span class="pf-legend-dot" style="background:linear-gradient(90deg,#10b981,#f43f5e)"></span>Flujo neto (Cobros − Desembolsos) · verde=positivo · rosa=negativo</span>';
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
                borderColor: CC.desembolso,
                backgroundColor: areaFill(CC.desembolso),
                fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 5,
                pointHoverBackgroundColor: CC.desembolso, pointHoverBorderColor:'#fff', pointHoverBorderWidth:2,
                borderWidth: 2.4, borderCapStyle:'round',
            },
            {
                label: 'Cobros',
                data: filtCob,
                borderColor: CC.cobro,
                backgroundColor: areaFill(CC.cobro),
                fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 5,
                pointHoverBackgroundColor: CC.cobro, pointHoverBorderColor:'#fff', pointHoverBorderWidth:2,
                borderWidth: 2.4, borderCapStyle:'round',
            }
        ];
    } else {
        // Flujo neto: cobros - desembolsos (positivo = ganamos ese día, negativo = salió más dinero)
        const netData = filtCob.map((c, i) => c - filtDes[i]);
        // Color dinámico por punto: verde si >= 0, rosa si < 0
        const pointColors = netData.map(v => v >= 0 ? CC.pos : CC.neg);
        datasets = [
            {
                label: 'Flujo neto',
                data: netData,
                borderColor: CC.pos,
                backgroundColor: areaFill(CC.pos, 0.18),
                fill: 'origin',
                tension: 0.4,
                pointRadius: netData.map(v => v !== 0 ? 2.5 : 0),
                pointBackgroundColor: pointColors,
                pointBorderColor: '#fff',
                pointBorderWidth: 1,
                pointHoverRadius: 6,
                pointHoverBorderColor: '#fff',
                borderWidth: 2.4,
                borderCapStyle:'round',
                segment: {
                    borderColor: ctx => ctx.p0.parsed.y >= 0 ? CC.pos : CC.neg,
                }
            }
        ];
    }

    // Actualizar gráfica acumulada (usa los mismos datos filtrados)
    updateAccumChart(filtLabels, filtDes, filtCob);

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
                    ...TIP,
                    callbacks: {
                        title: items => items[0].label,
                        labelColor: ctx => ({ borderColor:'transparent', backgroundColor: ctx.dataset.borderColor, borderRadius:3 }),
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
                    ticks: { maxTicksLimit: 12, font: { size: 10 }, color: CC.tick, maxRotation: 0, padding: 6 },
                    border: { display: false },
                },
                y: {
                    grid: { color: CC.grid },
                    ticks: {
                        font: { size: 10 }, color: CC.tick, maxTicksLimit: 6, padding: 8,
                        callback: v => v === 0 ? '$0' : (v >= 1000 ? '$'+(v/1000).toFixed(0)+'k' : '$'+v)
                    },
                    border: { display: false },
                    beginAtZero: true,
                }
            }
        }
    });
}

// ════════════════════════════════════════════════════════════
// CUMULATIVE NET CHART
// ════════════════════════════════════════════════════════════
let accumChart = null;

function updateAccumChart(labels, rawDes, rawCob) {
    // Calcular suma corrida: accum[i] = Σ(cobros[0..i]) - Σ(desembolsos[0..i])
    let running = 0;
    const accumData = rawCob.map((c, i) => {
        running += c - rawDes[i];
        return running;
    });

    // Valor final (posición actual)
    const lastVal = accumData.length > 0 ? accumData[accumData.length - 1] : 0;
    const isPos   = lastVal >= 0;

    // Actualizar KPI chip — usar IDs directos, sin firstChild
    const kpiEl    = document.getElementById('accumKpi');
    const kpiLabel = document.getElementById('accumKpiLabel');
    const kpiVal   = document.getElementById('accumKpiValue');
    const color    = isPos ? '#15803d' : '#b91c1c';
    const bg       = isPos ? '#f0fdf4' : '#fef2f2';
    const border   = isPos ? '#bbf7d0' : '#fca5a5';
    kpiEl.style.background  = bg;
    kpiEl.style.borderColor = border;
    kpiLabel.style.color    = color;
    kpiVal.style.color      = color;
    const sign = lastVal > 0 ? '+' : '';
    kpiVal.textContent = sign + '$' + Math.abs(lastVal).toLocaleString('es-MX', {minimumFractionDigits:0, maximumFractionDigits:0});
    if (lastVal > 0) {
        kpiLabel.textContent = '✅ Posición actual (positiva)';
    } else if (lastVal < 0) {
        kpiLabel.textContent = '🔴 Posición actual (negativa)';
    } else {
        kpiLabel.textContent = 'Posición actual (en cero)';
    }

    // Colores por punto
    const ptColors = accumData.map(v => v >= 0 ? CC.pos : CC.neg);

    const dataset = {
        label: 'Posición acumulada',
        data: accumData,
        fill: {
            target: 'origin',
            above: rgba(CC.pos, .14),
            below: rgba(CC.neg, .14),
        },
        segment: {
            borderColor: ctx => ctx.p0.parsed.y >= 0 ? CC.pos : CC.neg,
        },
        tension: 0.4,
        pointRadius: accumData.map(v => v !== 0 ? 2.5 : 0),
        pointBackgroundColor: ptColors,
        pointBorderColor: '#fff',
        pointBorderWidth: 1,
        pointHoverRadius: 6,
        pointHoverBorderColor: '#fff',
        borderWidth: 2.6,
        borderCapStyle: 'round',
        borderColor: CC.pos, // fallback; segment overrides per-segment
    };

    if (accumChart) {
        accumChart.data.labels      = labels;
        accumChart.data.datasets[0] = dataset;
        accumChart.update('active');
        return;
    }

    const ctx2 = document.getElementById('accumChart').getContext('2d');
    accumChart = new Chart(ctx2, {
        type: 'line',
        data: { labels, datasets: [dataset] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...TIP,
                    callbacks: {
                        title: items => items[0].label,
                        labelColor: ctx => ({ borderColor:'transparent', backgroundColor: ctx.raw >= 0 ? CC.pos : CC.neg, borderRadius:3 }),
                        label: ctx => {
                            const v   = ctx.raw;
                            const abs = Math.abs(v);
                            const formatted = '$' + abs.toLocaleString('es-MX', {minimumFractionDigits:0, maximumFractionDigits:0});
                            if (v > 0)  return '▲ Positivo: +' + formatted;
                            if (v < 0)  return '▼ Negativo: −' + formatted;
                            return 'En cero: $0';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxTicksLimit: 12, font: { size: 10 }, color: CC.tick, maxRotation: 0, padding: 6 },
                    border: { display: false },
                },
                y: {
                    grid: {
                        color: ctx => ctx.tick.value === 0 ? CC.gridZero : CC.grid,
                        lineWidth: ctx => ctx.tick.value === 0 ? 1.5 : 1,
                    },
                    ticks: {
                        font: { size: 10 }, color: CC.tick, maxTicksLimit: 6, padding: 8,
                        callback: v => {
                            if (v === 0) return '$0';
                            const abs = Math.abs(v);
                            const fmt = abs >= 1000 ? '$'+(abs/1000).toFixed(0)+'k' : '$'+abs;
                            return v < 0 ? '−'+fmt : '+'+fmt;
                        }
                    },
                    border: { display: false },
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
    var labels = chart.data.labels;

    // Acumular solo los estatus cuyo segmento NO está oculto
    var capDes = 0, intEsp = 0, saldo = 0, mora = 0, cobrado = 0, intCob = 0, capAcord = 0;
    labels.forEach(function(est, i) {
        // getDataVisibility() es el método correcto para doughnut en Chart.js 4
        if (!chart.getDataVisibility(i)) return;
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

    var fmt  = function(n) { return '$' + (Math.round(n * 100) / 100).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2}); };
    var ip   = intEsp > 0 ? Math.round(intCob / intEsp * 1000) / 10 : 0;
    var pct  = capAcord > 0 ? Math.round(cobrado / capAcord * 1000) / 10 : 0;
    var rentab = capDes > 0 ? Math.round(intEsp / capDes * 1000) / 10 : 0;
    var rnd    = capDes > 0 ? Math.round((cobrado - capDes) / capDes * 1000) / 10 : 0;
    var barColor = pct >= 75 ? '#10b981' : pct >= 40 ? '#f59e0b' : '#ef4444';

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
    setStyle(p + 'mora',   'color', mora > 0 ? '#dc2626' : '#10b981');
    set(p + 'rentab',      rentab + '%');
    set(p + 'rnd',         (rnd > 0 ? '+' : '') + rnd + '%');
    setStyle(p + 'rnd',    'color', rnd > 0 ? '#10b981' : rnd < 0 ? '#dc2626' : '#9ca3af');
}

// ── Filtrar la gráfica de "Flujo diario" según los estatus visibles del donut ──
function updateLineFromStatus(adminId, donut) {
    var oc = ownerCharts[adminId];
    if (!oc || !oc.line || !oc.lineData) return;
    var ld     = oc.lineData;
    var labels = donut.data.labels;            // nombres de estatus (mismos del donut)
    var n      = ld.labels.length;
    var des = new Array(n).fill(0);
    var cob = new Array(n).fill(0);
    labels.forEach(function(est, i) {
        if (!donut.getDataVisibility(i)) return;   // estatus oculto → no suma
        var ds = ld.des_by_estatus[est];
        var cs = ld.cob_by_estatus[est];
        if (ds) for (var k = 0; k < n; k++) des[k] += ds[k] || 0;
        if (cs) for (var k = 0; k < n; k++) cob[k] += cs[k] || 0;
    });
    oc.line.data.datasets[0].data = des;
    oc.line.data.datasets[1].data = cob;
    oc.line.update('active');
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
                        borderWidth: 4,
                        borderColor: '#ffffff',
                        borderRadius: 5,
                        hoverOffset: 8,
                        spacing: 1,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: { size: 11, family: CHART_FONT },
                                padding: 14,
                                boxWidth: 8,
                                boxHeight: 8,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                color: '#5b6472',
                            },
                            onClick: function(e, legendItem, legend) {
                                var chart = legend.chart;
                                // Toggle correcto para doughnut en Chart.js 4
                                chart.toggleDataVisibility(legendItem.index);
                                chart.update();
                                recalcStats(adminId, chart, pd.by_estatus);
                                updateLineFromStatus(adminId, chart);
                            }
                        },
                        tooltip: {
                            ...TIP,
                            callbacks: {
                                labelColor: ctx => ({ borderColor:'transparent', backgroundColor: ctx.element.options.backgroundColor, borderRadius:3 }),
                                label: function(ctx) {
                                    // Solo sumar los segmentos visibles para que los % sumen 100%
                                    var chart = ctx.chart;
                                    var visibleTotal = ctx.dataset.data.reduce(function(sum, val, i) {
                                        return sum + (chart.getDataVisibility(i) ? val : 0);
                                    }, 0);
                                    var pct = visibleTotal > 0 ? Math.round(ctx.raw / visibleTotal * 100) : 0;
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
        ownerCharts[adminId].lineData = ld;   // guardar para el filtro por estatus

        ownerCharts[adminId].line = new Chart(lineEl, {
            type: 'line',
            data: {
                labels: ld.labels,
                datasets: [
                    {
                        label: 'Desembolsos',
                        data: ld.desembolsos,
                        borderColor: CC.desembolso,
                        backgroundColor: areaFill(CC.desembolso, 0.2),
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: CC.desembolso,
                        pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2,
                        borderWidth: 2.2, borderCapStyle: 'round',
                    },
                    {
                        label: 'Cobros',
                        data: ld.cobros,
                        borderColor: CC.cobro,
                        backgroundColor: areaFill(CC.cobro, 0.2),
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: CC.cobro,
                        pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2,
                        borderWidth: 2.2, borderCapStyle: 'round',
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
                        ...TIP,
                        callbacks: {
                            title: function(items) { return items[0].label; },
                            labelColor: ctx => ({ borderColor:'transparent', backgroundColor: ctx.dataset.borderColor, borderRadius:3 }),
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
                            font: { size: 10, family: CHART_FONT },
                            color: CC.tick,
                            maxRotation: 0, padding: 6,
                        },
                        border: { display: false },
                    },
                    y: {
                        grid: { color: CC.grid, drawBorder: false },
                        ticks: {
                            font: { size: 10, family: CHART_FONT },
                            color: CC.tick, padding: 8,
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
