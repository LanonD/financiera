@extends('layouts.app')

@section('title', 'Administrador — ' . ($admin->alias ?: ($admin->nombre ?: $admin->usuario)))

@push('styles')
<style>
/* ── Variables locales ──────────────────────────────────── */
:root{
    --blue:#3b82f6;--blue-bg:#eff6ff;--blue-dk:#1d4ed8;
    --green:#16a34a;--green-bg:#f0fdf4;
    --yellow:#d97706;--yellow-bg:#fefce8;
    --red:#dc2626;--red-bg:#fef2f2;
    --purple:#7c3aed;--purple-bg:#f5f3ff;
    --orange:#ea580c;--orange-bg:#fff7ed;
}

/* ── Breadcrumb ─────────────────────────────────────────── */
.ad-bc{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text3);margin-bottom:20px}
.ad-bc a{color:var(--text3);text-decoration:none;transition:color .15s}
.ad-bc a:hover{color:var(--accent)}
.ad-bc-sep{opacity:.4}

/* ── Header ─────────────────────────────────────────────── */
.ad-hdr{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:22px 24px;margin-bottom:20px;display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.ad-avatar{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#fff;flex-shrink:0}
.ad-hdr-info{flex:1;min-width:0}
.ad-hdr-name{font-size:20px;font-weight:800;letter-spacing:-.03em;color:var(--text);line-height:1.1}
.ad-hdr-meta{display:flex;gap:12px;flex-wrap:wrap;margin-top:6px;align-items:center}
.ad-hdr-meta span{font-size:12px;color:var(--text3);display:flex;align-items:center;gap:4px}
.ad-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700}
.ad-badge-green{background:var(--green-bg);color:var(--green)}
.ad-badge-red{background:var(--red-bg);color:var(--red)}
.ad-hdr-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}

/* ── KPI grid ───────────────────────────────────────────── */
.ad-kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px}
.ad-kpi{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;position:relative;overflow:hidden;transition:box-shadow .15s}
.ad-kpi:hover{box-shadow:0 4px 16px rgba(0,0,0,.06)}
.ad-kpi-accent{position:absolute;top:0;left:0;width:3px;height:100%}
.ad-kpi-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:8px}
.ad-kpi-value{font-size:22px;font-weight:800;letter-spacing:-.03em;line-height:1}
.ad-kpi-sub{font-size:11px;color:var(--text2);margin-top:5px}
.ad-kpi-trend{display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:600;margin-top:4px;padding:2px 6px;border-radius:5px}
.ad-kpi-trend-up{background:var(--green-bg);color:var(--green)}
.ad-kpi-trend-dn{background:var(--red-bg);color:var(--red)}
.ad-kpi-trend-neu{background:#f3f4f6;color:var(--text3)}

/* ── Section title ──────────────────────────────────────── */
.ad-section{margin-bottom:20px}
.ad-section-title{font-size:13px;font-weight:700;color:var(--text);letter-spacing:-.01em;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.ad-section-badge{padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600}

/* ── Chart cards ─────────────────────────────────────────── */
.ad-chart-grid{display:grid;grid-template-columns:1fr 320px;gap:16px;margin-bottom:20px}
.ad-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:20px}
.ad-card-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.ad-card-title{font-size:13px;font-weight:700;color:var(--text)}
.ad-card-sub{font-size:11px;color:var(--text3);margin-top:2px}

/* ── Risk panel ─────────────────────────────────────────── */
.ad-risk-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.ad-risk-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px}
.ad-risk-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:8px}
.ad-risk-value{font-size:28px;font-weight:900;letter-spacing:-.04em;line-height:1}
.ad-risk-sub{font-size:11px;color:var(--text2);margin-top:4px}
.ad-bar{height:6px;background:#f3f4f6;border-radius:99px;overflow:hidden;margin-top:8px}
.ad-bar-fill{height:100%;border-radius:99px;transition:width .4s ease}

/* ── Stats strip ─────────────────────────────────────────── */
.ad-stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px}
.ad-stat{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;text-align:center}
.ad-stat-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px}
.ad-stat-value{font-size:18px;font-weight:800;letter-spacing:-.02em;color:var(--text)}
.ad-stat-sub{font-size:11px;color:var(--text2);margin-top:3px}

/* ── Table ───────────────────────────────────────────────── */
.ad-table-wrap{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:20px}
.ad-table-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.ad-tbl{width:100%;border-collapse:collapse;font-size:13px}
.ad-tbl th{padding:9px 14px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);background:#f9fafb;border-bottom:1px solid var(--border);text-align:left;white-space:nowrap}
.ad-tbl td{padding:11px 14px;border-bottom:1px solid var(--border);vertical-align:middle}
.ad-tbl tr:last-child td{border-bottom:none}
.ad-tbl tr:hover td{background:#f9fafb}
.ad-tbl-wrap{overflow-x:auto}
.ad-empty{padding:48px 24px;text-align:center;color:var(--text3);font-size:13px}

/* ── Pill estatus ────────────────────────────────────────── */
.pill{display:inline-flex;align-items:center;gap:3px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}
.pill-blue{background:var(--blue-bg);color:var(--blue-dk)}
.pill-red{background:var(--red-bg);color:var(--red)}
.pill-yellow{background:var(--yellow-bg);color:var(--yellow)}
.pill-green{background:var(--green-bg);color:var(--green)}
.pill-gray{background:#f3f4f6;color:#6b7280}
.pill-purple{background:var(--purple-bg);color:var(--purple)}

/* ── Alerts ──────────────────────────────────────────────── */
.ad-alert{display:flex;align-items:flex-start;gap:12px;padding:13px 16px;border-radius:10px;margin-bottom:10px}
.ad-alert-danger{background:#fef2f2;border:1px solid #fca5a5}
.ad-alert-warning{background:#fffbeb;border:1px solid #fde68a}
.ad-alert-success{background:#f0fdf4;border:1px solid #86efac}
.ad-alert-icon{font-size:16px;flex-shrink:0;margin-top:1px}
.ad-alert-titulo{font-size:13px;font-weight:700;margin-bottom:2px}
.ad-alert-danger .ad-alert-titulo{color:#991b1b}
.ad-alert-warning .ad-alert-titulo{color:#92400e}
.ad-alert-success .ad-alert-titulo{color:#166534}
.ad-alert-msg{font-size:12px;color:var(--text2)}

/* ── Timeline notas ─────────────────────────────────────── */
.ad-timeline{position:relative;padding-left:24px}
.ad-timeline::before{content:'';position:absolute;left:8px;top:0;bottom:0;width:2px;background:var(--border)}
.ad-tl-item{position:relative;margin-bottom:14px}
.ad-tl-dot{position:absolute;left:-20px;top:4px;width:10px;height:10px;border-radius:50%;background:var(--accent);border:2px solid #fff;box-shadow:0 0 0 2px var(--border)}
.ad-tl-date{font-size:10px;font-weight:600;color:var(--text3);margin-bottom:3px;text-transform:uppercase;letter-spacing:.05em}
.ad-tl-text{font-size:12px;color:var(--text);line-height:1.55;background:#f9fafb;border:1px solid var(--border);border-radius:8px;padding:10px 13px}

/* ── Donut legend ─────────────────────────────────────────── */
.ad-donut-legend{display:flex;flex-direction:column;gap:8px;margin-top:14px}
.ad-donut-item{display:flex;align-items:center;justify-content:space-between;font-size:12px}
.ad-donut-dot{width:10px;height:10px;border-radius:3px;flex-shrink:0}

/* ── Payments calendar strip ─────────────────────────────── */
.ad-cal-item{display:grid;grid-template-columns:64px 1fr auto;gap:12px;align-items:center;padding:10px 16px;border-bottom:1px solid var(--border);font-size:13px}
.ad-cal-item:last-child{border-bottom:none}
.ad-cal-date{font-size:11px;font-weight:700;color:var(--text3);text-align:center;line-height:1.3}
.ad-cal-day{font-size:20px;font-weight:900;color:var(--text);line-height:1}

/* ── Search input ─────────────────────────────────────────── */
.ad-search{padding:7px 12px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:var(--font);outline:none;background:var(--card);color:var(--text);transition:border-color .15s}
.ad-search:focus{border-color:var(--accent)}

/* ── Responsive ─────────────────────────────────────────── */
@media(max-width:1100px){
    .ad-kpi-grid{grid-template-columns:repeat(2,1fr)}
    .ad-chart-grid{grid-template-columns:1fr}
    .ad-risk-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:640px){
    .ad-kpi-grid{grid-template-columns:1fr 1fr}
    .ad-stats-grid{grid-template-columns:repeat(3,1fr)}
    .ad-hdr{flex-wrap:wrap}
}
@media(max-width:400px){
    .ad-kpi-grid{grid-template-columns:1fr}
    .ad-stats-grid{grid-template-columns:1fr 1fr}
}
</style>
@endpush

@section('content')

@php
$fmt = fn($n) => '$' . number_format((float)$n, 0, '.', ',');
$pct = fn($n) => number_format((float)$n, 1, '.', ',') . '%';

$colors   = ['#3b82f6','#6366f1','#8b5cf6','#ec4899','#10b981','#f59e0b'];
$initial  = strtoupper(substr($admin->usuario, 0, 1));
$color    = $colors[crc32($admin->usuario) % count($colors)];
$nombre   = $admin->alias ?: ($admin->nombre ?: $admin->usuario);

$estatusCfg = [
    'Activo'     => ['pill-blue',   '#3b82f6'],
    'Atrasado'   => ['pill-red',    '#dc2626'],
    'Pendiente'  => ['pill-yellow', '#f59e0b'],
    'Finalizado' => ['pill-green',  '#16a34a'],
    'Retirado'   => ['pill-gray',   '#9ca3af'],
];
@endphp

{{-- Breadcrumb --}}
<nav class="ad-bc">
    <a href="{{ route('owner.dashboard') }}">Dashboard</a>
    <span class="ad-bc-sep">/</span>
    <span>{{ $nombre }}</span>
</nav>

{{-- Header --}}
<div class="ad-hdr">
    <div class="ad-avatar" style="background:{{ $color }}">{{ $initial }}</div>
    <div class="ad-hdr-info">
        <div class="ad-hdr-name">{{ $nombre }}</div>
        <div class="ad-hdr-meta">
            @if($admin->activo)
                <span class="ad-badge ad-badge-green">● Activo</span>
            @else
                <span class="ad-badge ad-badge-red">● Inactivo</span>
            @endif
            @if($admin->nombre && $admin->alias)
                <span>
                    <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:11px;height:11px"><circle cx="7" cy="4" r="2.5"/><path d="M1 12c0-3 2.686-4.5 6-4.5s6 1.5 6 4.5"/></svg>
                    {{ $admin->nombre }}
                </span>
            @endif
            <span>
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:11px;height:11px"><rect x="1" y="3" width="12" height="9" rx="1.5"/><path d="M4 7h2M4 10h4"/></svg>
                ID #{{ $admin->id }}
            </span>
            <span>
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:11px;height:11px"><rect x="1" y="2" width="12" height="11" rx="1.5"/><path d="M4 1v3M10 1v3M1 6h12"/></svg>
                Alta: {{ $admin->created_at?->format('d/m/Y') ?? '—' }}
            </span>
            @if($admin->celular)
            <span>
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:11px;height:11px"><rect x="4" y="1" width="6" height="12" rx="1.5"/><circle cx="7" cy="11" r=".6" fill="currentColor" stroke="none"/></svg>
                {{ $admin->celular }}
            </span>
            @endif
            <span>
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:11px;height:11px"><rect x="1" y="3" width="12" height="9" rx="1.5"/><path d="M1 6l6 4 6-4"/></svg>
                {{ $admin->usuario }}
            </span>
        </div>
    </div>
    <div class="ad-hdr-actions">
        <a href="{{ route('owner.dashboard') }}" class="btn btn-sm" style="background:#f3f4f6;color:var(--text2)">
            <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:12px;height:12px"><path d="M8 2L4 6l4 4"/></svg>
            Volver
        </a>
        <button class="btn btn-sm btn-primary"
            onclick="document.getElementById('modalEditarDet').classList.add('open')">
            <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" style="width:12px;height:12px"><path d="M9.5 2l2.5 2.5-7 7H2.5V9l7-7z"/></svg>
            Editar
        </button>
        <form method="POST" action="{{ route('owner.admins.toggle', $admin->id) }}" style="margin:0">
            @csrf
            <button type="submit" class="btn btn-sm"
                style="{{ $admin->activo ? 'background:#fee2e2;color:#dc2626' : 'background:#dcfce7;color:#16a34a' }}"
                onclick="return confirm('¿{{ $admin->activo ? 'Desactivar' : 'Activar' }} este administrador?')">
                {{ $admin->activo ? '⏸ Suspender' : '▶ Activar' }}
            </button>
        </form>
    </div>
</div>

{{-- ── KPIs Fila 1: Capital ───────────────────────────────── --}}
<div class="ad-kpi-grid">
    <div class="ad-kpi">
        <div class="ad-kpi-accent" style="background:var(--blue)"></div>
        <div class="ad-kpi-label">Capital Total Prestado</div>
        <div class="ad-kpi-value" style="color:var(--blue)">{{ $fmt($capitalDesplegado) }}</div>
        <div class="ad-kpi-sub">Desembolsado en préstamos</div>
    </div>
    <div class="ad-kpi">
        <div class="ad-kpi-accent" style="background:var(--green)"></div>
        <div class="ad-kpi-label">Capital Recuperado</div>
        <div class="ad-kpi-value" style="color:var(--green)">{{ $fmt($capitalRecuperado) }}</div>
        <div class="ad-kpi-sub">Capital principal cobrado</div>
    </div>
    <div class="ad-kpi">
        <div class="ad-kpi-accent" style="background:var(--yellow)"></div>
        <div class="ad-kpi-label">Capital Pendiente</div>
        <div class="ad-kpi-value" style="color:var(--yellow)">{{ $fmt($capitalPendiente) }}</div>
        <div class="ad-kpi-sub">Saldo activo en cartera</div>
    </div>
    <div class="ad-kpi">
        <div class="ad-kpi-accent" style="background:var(--red)"></div>
        <div class="ad-kpi-label">Capital en Riesgo</div>
        <div class="ad-kpi-value" style="color:var(--red)">{{ $fmt($capitalRiesgo) }}</div>
        <div class="ad-kpi-sub">Saldo de préstamos atrasados</div>
    </div>
</div>

{{-- ── KPIs Fila 2: Rendimiento ─────────────────────────────── --}}
<div class="ad-kpi-grid" style="margin-bottom:20px">
    <div class="ad-kpi">
        <div class="ad-kpi-accent" style="background:#a855f7"></div>
        <div class="ad-kpi-label">Total Cobrado</div>
        <div class="ad-kpi-value" style="color:#7c3aed">{{ $fmt($totalCobrado) }}</div>
        <div class="ad-kpi-sub">Capital + Intereses cobrados</div>
    </div>
    <div class="ad-kpi">
        <div class="ad-kpi-accent" style="background:var(--green)"></div>
        <div class="ad-kpi-label">Intereses Cobrados</div>
        <div class="ad-kpi-value" style="color:var(--green)">{{ $fmt($interesCobranzaReal) }}</div>
        <div class="ad-kpi-sub">De {{ $fmt($interesEsperado) }} esperados</div>
    </div>
    <div class="ad-kpi">
        <div class="ad-kpi-accent" style="background:var(--orange)"></div>
        <div class="ad-kpi-label">Mora Acumulada</div>
        <div class="ad-kpi-value" style="color:var(--orange)">{{ $fmt($moraPendiente) }}</div>
        <div class="ad-kpi-sub">Interés moratorio pendiente</div>
    </div>
    <div class="ad-kpi">
        <div class="ad-kpi-accent" style="background:{{ $roi >= 0 ? 'var(--green)' : 'var(--red)' }}"></div>
        <div class="ad-kpi-label">ROI Acumulado</div>
        <div class="ad-kpi-value" style="color:{{ $roi >= 0 ? 'var(--green)' : 'var(--red)' }}">
            {{ $roi >= 0 ? '+' : '' }}{{ $pct($roi) }}
        </div>
        <div class="ad-kpi-sub">Ganancia neta: {{ $fmt($gananciaNetaAprox) }}</div>
    </div>
</div>

{{-- ── Riesgo PAR ─────────────────────────────────────────── --}}
@php
$parColor = fn($v) => $v < 5 ? 'var(--green)' : ($v < 15 ? 'var(--yellow)' : 'var(--red)');
$parBarColor = fn($v) => $v < 5 ? '#16a34a' : ($v < 15 ? '#d97706' : '#dc2626');
@endphp
<div class="ad-section">
    <div class="ad-section-title">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:var(--red)"><path d="M8 2l6 11H2L8 2z"/><path d="M8 7v3M8 11.5v.5"/></svg>
        Análisis de Riesgo — Cartera en Mora
    </div>
    <div class="ad-risk-grid">
        <div class="ad-risk-card">
            <div class="ad-risk-label">PAR 30 días</div>
            <div class="ad-risk-value" style="color:{{ $parColor($par30) }}">{{ $pct($par30) }}</div>
            <div class="ad-risk-sub">{{ $fmt($par30Saldo) }} en atraso &gt;30d</div>
            <div class="ad-bar"><div class="ad-bar-fill" style="width:{{ min(100,$par30) }}%;background:{{ $parBarColor($par30) }}"></div></div>
        </div>
        <div class="ad-risk-card">
            <div class="ad-risk-label">PAR 60 días</div>
            <div class="ad-risk-value" style="color:{{ $parColor($par60) }}">{{ $pct($par60) }}</div>
            <div class="ad-risk-sub">{{ $fmt($par60Saldo) }} en atraso &gt;60d</div>
            <div class="ad-bar"><div class="ad-bar-fill" style="width:{{ min(100,$par60) }}%;background:{{ $parBarColor($par60) }}"></div></div>
        </div>
        <div class="ad-risk-card">
            <div class="ad-risk-label">PAR 90 días</div>
            <div class="ad-risk-value" style="color:{{ $parColor($par90) }}">{{ $pct($par90) }}</div>
            <div class="ad-risk-sub">{{ $fmt($par90Saldo) }} en atraso &gt;90d</div>
            <div class="ad-bar"><div class="ad-bar-fill" style="width:{{ min(100,$par90) }}%;background:{{ $parBarColor($par90) }}"></div></div>
        </div>
        <div class="ad-risk-card">
            <div class="ad-risk-label">NPL Ratio</div>
            <div class="ad-risk-value" style="color:{{ $parColor($npl) }}">{{ $pct($npl) }}</div>
            <div class="ad-risk-sub">{{ $nAtrasados }} de {{ $nActivos }} préstamos atrasados</div>
            <div class="ad-bar"><div class="ad-bar-fill" style="width:{{ min(100,$npl) }}%;background:{{ $parBarColor($npl) }}"></div></div>
        </div>
    </div>
</div>

{{-- ── Gráfica + Donut ──────────────────────────────────────── --}}
<div class="ad-chart-grid">
    {{-- Línea temporal --}}
    <div class="ad-card">
        <div class="ad-card-hd">
            <div>
                <div class="ad-card-title">Flujo de Capital — Últimos 12 Meses</div>
                <div class="ad-card-sub">Capital colocado vs cobrado por mes</div>
            </div>
            <div style="display:flex;gap:10px;font-size:11px;align-items:center">
                <span style="display:flex;align-items:center;gap:4px"><span style="width:10px;height:3px;background:#3b82f6;border-radius:2px;display:inline-block"></span>Desembolsado</span>
                <span style="display:flex;align-items:center;gap:4px"><span style="width:10px;height:3px;background:#10b981;border-radius:2px;display:inline-block"></span>Cobrado</span>
            </div>
        </div>
        <canvas id="chartFlujo" height="260"></canvas>
    </div>

    {{-- Donut distribución --}}
    <div class="ad-card">
        <div class="ad-card-hd">
            <div>
                <div class="ad-card-title">Distribución de Cartera</div>
                <div class="ad-card-sub">{{ $totalPrestamos }} préstamos en total</div>
            </div>
        </div>
        <canvas id="chartDonut" height="180"></canvas>
        <div class="ad-donut-legend">
            @foreach($porEstatus as $est => $cnt)
            @if($cnt > 0)
            @php $cfg = $estatusCfg[$est] ?? ['pill-gray','#9ca3af']; @endphp
            <div class="ad-donut-item">
                <div style="display:flex;align-items:center;gap:6px">
                    <div class="ad-donut-dot" style="background:{{ $cfg[1] }}"></div>
                    <span style="font-size:12px;color:var(--text2)">{{ $est }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="font-size:13px;font-weight:700;color:var(--text)">{{ $cnt }}</span>
                    <span style="font-size:11px;color:var(--text3)">{{ $totalPrestamos > 0 ? round($cnt/$totalPrestamos*100,0) : 0 }}%</span>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</div>

{{-- ── Métricas de cartera ──────────────────────────────────── --}}
<div class="ad-section">
    <div class="ad-section-title">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:var(--blue)"><rect x="1" y="3" width="14" height="11" rx="1.5"/><path d="M1 7h14"/><path d="M5 7v7M11 7v7"/></svg>
        Métricas de Cartera
    </div>
    <div class="ad-stats-grid">
        <div class="ad-stat">
            <div class="ad-stat-label">Activos</div>
            <div class="ad-stat-value" style="color:var(--blue)">{{ $nActivos }}</div>
            <div class="ad-stat-sub">en cobranza</div>
        </div>
        <div class="ad-stat">
            <div class="ad-stat-label">Finalizados</div>
            <div class="ad-stat-value" style="color:var(--green)">{{ $finalizados->count() }}</div>
            <div class="ad-stat-sub">liquidados</div>
        </div>
        <div class="ad-stat">
            <div class="ad-stat-label">Atrasados</div>
            <div class="ad-stat-value" style="color:var(--red)">{{ $nAtrasados }}</div>
            <div class="ad-stat-sub">en mora</div>
        </div>
        <div class="ad-stat">
            <div class="ad-stat-label">Ticket Promedio</div>
            <div class="ad-stat-value">{{ $fmt($ticketPromedio) }}</div>
            <div class="ad-stat-sub">préstamo medio</div>
        </div>
        <div class="ad-stat">
            <div class="ad-stat-label">Cobrado (30d)</div>
            <div class="ad-stat-value" style="color:var(--green)">{{ $fmt($montoCobradoUlt30) }}</div>
            <div class="ad-stat-sub">últimos 30 días</div>
        </div>
    </div>
    <div class="ad-stats-grid">
        <div class="ad-stat">
            <div class="ad-stat-label">Monto Máximo</div>
            <div class="ad-stat-value">{{ $fmt($montoMax) }}</div>
            <div class="ad-stat-sub">préstamo más alto</div>
        </div>
        <div class="ad-stat">
            <div class="ad-stat-label">Monto Mínimo</div>
            <div class="ad-stat-value">{{ $fmt($montoMin) }}</div>
            <div class="ad-stat-sub">préstamo más bajo</div>
        </div>
        <div class="ad-stat">
            <div class="ad-stat-label">Duración Prom.</div>
            <div class="ad-stat-value">{{ $duracionPromedio }}</div>
            <div class="ad-stat-sub">pagos por préstamo</div>
        </div>
        <div class="ad-stat">
            <div class="ad-stat-label">Monto Acordado</div>
            <div class="ad-stat-value">{{ $fmt($totalAcordado) }}</div>
            <div class="ad-stat-sub">capital + intereses</div>
        </div>
        <div class="ad-stat">
            <div class="ad-stat-label">Presupuesto</div>
            <div class="ad-stat-value">{{ $fmt($admin->presupuesto ?? 0) }}</div>
            <div class="ad-stat-sub">asignado por owner</div>
        </div>
    </div>
</div>

{{-- ── Alertas inteligentes ─────────────────────────────────── --}}
<div class="ad-section">
    <div class="ad-section-title">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:var(--yellow)"><path d="M8 2l6 11H2L8 2z"/><path d="M8 7v3"/><circle cx="8" cy="12" r=".5" fill="currentColor" stroke="none"/></svg>
        Alertas Inteligentes
    </div>
    @foreach($alertas as $a)
    <div class="ad-alert ad-alert-{{ $a['tipo'] }}">
        <div class="ad-alert-icon">{{ $a['icon'] }}</div>
        <div>
            <div class="ad-alert-titulo">{{ $a['titulo'] }}</div>
            <div class="ad-alert-msg">{{ $a['msg'] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Tabla de clientes ─────────────────────────────────────── --}}
<div class="ad-section">
    <div class="ad-section-title">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:var(--green)"><circle cx="6" cy="5" r="3"/><path d="M1 15c0-3.314 2.239-5 5-5"/><circle cx="12" cy="10" r="2.5"/><path d="M12 12.5v1.5l1 1"/></svg>
        Clientes Activos — Cartera
        <span class="ad-section-badge" style="background:var(--blue-bg);color:var(--blue-dk)">{{ $clientesActivos->count() }}</span>
    </div>
    <div class="ad-table-wrap">
        <div class="ad-table-head">
            <div style="font-size:13px;font-weight:600;color:var(--text)">Clientes con préstamos activos</div>
            <input type="text" class="ad-search" id="clientSearch" placeholder="Buscar cliente…" oninput="filtrarClientes(this.value)" style="width:220px">
        </div>
        <div class="ad-tbl-wrap">
            <table class="ad-tbl" id="tablaClientes">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Monto Prestado</th>
                        <th>Saldo Actual</th>
                        <th>Cuota</th>
                        <th>Próximo Vencimiento</th>
                        <th>Atraso</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($clientesActivos as $cliente)
                @php
                    $prestamo = $clientesConPrestamo->get($cliente->id);
                    $pago     = $prestamo ? ($proximoPorPrestamo->get($prestamo->id)) : null;
                    $hoy      = \Carbon\Carbon::today();
                    $diasAtraso = 0;
                    if ($pago && $pago->fecha_programada && $pago->fecha_programada < $hoy) {
                        $diasAtraso = $pago->fecha_programada->diffInDays($hoy);
                    }
                    $cfg = $prestamo ? ($estatusCfg[$prestamo->estatus] ?? ['pill-gray','#9ca3af']) : ['pill-gray','#9ca3af'];
                @endphp
                <tr data-search="{{ strtolower($cliente->nombre . ' ' . ($cliente->celular ?? '')) }}">
                    <td>
                        <div style="font-weight:600;color:var(--text)">{{ $cliente->nombre }}</div>
                        @if($cliente->celular)
                        <div style="font-size:11px;color:var(--text3)">{{ $cliente->celular }}</div>
                        @endif
                    </td>
                    <td style="font-weight:600">{{ $prestamo ? $fmt($prestamo->monto_entregado) : '—' }}</td>
                    <td style="color:var(--yellow);font-weight:600">{{ $prestamo ? $fmt($prestamo->saldo_actual) : '—' }}</td>
                    <td>{{ $prestamo ? $fmt($prestamo->cuota) : '—' }}</td>
                    <td>
                        @if($pago)
                            <span style="font-size:12px">{{ $pago->fecha_programada?->format('d/m/Y') ?? '—' }}</span>
                        @else
                            <span style="color:var(--text3)">—</span>
                        @endif
                    </td>
                    <td>
                        @if($diasAtraso > 0)
                            <span class="pill pill-red">{{ $diasAtraso }}d</span>
                        @else
                            <span style="color:var(--text3);font-size:12px">Al día</span>
                        @endif
                    </td>
                    <td>
                        @if($prestamo)
                            <span class="pill {{ $cfg[0] }}">{{ $prestamo->estatus }}</span>
                        @else
                            <span class="pill pill-gray">Sin préstamo</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="ad-empty">Sin clientes activos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Próximos cobros (30 días) ────────────────────────────── --}}
<div class="ad-section">
    <div class="ad-section-title">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:var(--purple)"><rect x="1" y="2" width="14" height="13" rx="1.5"/><path d="M5 1v3M11 1v3M1 6h14"/></svg>
        Próximos Cobros — 30 Días
        <span class="ad-section-badge" style="background:var(--purple-bg);color:var(--purple)">{{ $proximosPagos->count() }}</span>
    </div>
    <div class="ad-table-wrap">
        @if($proximosPagos->isEmpty())
        <div class="ad-empty">Sin cobros programados en los próximos 30 días.</div>
        @else
        @foreach($proximosPagos->take(20) as $pago)
        @php
            $hoy    = \Carbon\Carbon::today();
            $fecha  = $pago->fecha_programada;
            $dias   = $hoy->diffInDays($fecha, false);
            $esHoy  = $dias === 0;
            $tarde  = $dias < 0;
        @endphp
        <div class="ad-cal-item" style="{{ $esHoy ? 'background:#eff6ff' : ($tarde ? 'background:#fef2f2' : '') }}">
            <div style="text-align:center;min-width:60px">
                <div class="ad-cal-day" style="color:{{ $tarde ? 'var(--red)' : ($esHoy ? 'var(--blue)' : 'var(--text)') }}">
                    {{ $fecha?->format('d') ?? '—' }}
                </div>
                <div class="ad-cal-date">{{ $fecha?->format('M') ?? '' }}</div>
            </div>
            <div>
                <div style="font-size:13px;font-weight:600;color:var(--text)">
                    {{ $pago->prestamo?->cliente?->nombre ?? '—' }}
                </div>
                <div style="font-size:11px;color:var(--text3)">Pago #{{ $pago->numero_pago }}</div>
            </div>
            <div style="text-align:right">
                <div style="font-size:14px;font-weight:700;color:var(--text)">{{ $fmt($pago->monto_cuota) }}</div>
                @if($tarde)
                    <span class="pill pill-red">{{ abs($dias) }}d atraso</span>
                @elseif($esHoy)
                    <span class="pill pill-blue">Hoy</span>
                @else
                    <span style="font-size:11px;color:var(--text3)">En {{ $dias }}d</span>
                @endif
            </div>
        </div>
        @endforeach
        @if($proximosPagos->count() > 20)
        <div style="padding:10px 16px;font-size:12px;color:var(--text3);text-align:center;border-top:1px solid var(--border)">
            + {{ $proximosPagos->count() - 20 }} cobros más en los próximos 30 días
        </div>
        @endif
        @endif
    </div>
</div>

{{-- ── Empleados ──────────────────────────────────────────────── --}}
@if($empleados->isNotEmpty())
<div class="ad-section">
    <div class="ad-section-title">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:var(--text2)"><circle cx="8" cy="5" r="3"/><path d="M2 15c0-3.866 2.686-6 6-6s6 2.134 6 6"/></svg>
        Equipo de Trabajo
        <span class="ad-section-badge" style="background:#f3f4f6;color:var(--text2)">{{ $empleados->count() }}</span>
    </div>
    <div class="ad-table-wrap" style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
        <div class="ad-tbl-wrap">
            <table class="ad-tbl">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Puesto</th>
                        <th>Teléfono</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($empleados as $e)
                <tr>
                    <td style="font-weight:600">{{ $e->nombre }}</td>
                    <td><span class="pill pill-gray">{{ ucfirst($e->puesto ?? '—') }}</span></td>
                    <td style="color:var(--text3)">{{ $e->celular ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ── Todos los préstamos ───────────────────────────────────── --}}
<div class="ad-section">
    <div class="ad-section-title">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:var(--yellow)"><rect x="1" y="3" width="14" height="11" rx="1.5"/><path d="M4 8h8M4 11h5"/></svg>
        Todos los Préstamos
        <span class="ad-section-badge" style="background:var(--yellow-bg);color:var(--yellow)">{{ $totalPrestamos }}</span>
    </div>
    <div class="ad-table-wrap">
        <div class="ad-table-head">
            <div style="font-size:13px;font-weight:600;color:var(--text)">Historial completo</div>
            <input type="text" class="ad-search" id="prestamoSearch" placeholder="Buscar…" oninput="filtrarPrestamos(this.value)" style="width:200px">
        </div>
        <div class="ad-tbl-wrap">
            <table class="ad-tbl" id="tablaPrestamos">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Capital</th>
                        <th>Monto Total</th>
                        <th>Cuota</th>
                        <th>Saldo</th>
                        <th>Inicio</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($allPrestamos as $p)
                @php $cfg = $estatusCfg[$p->estatus] ?? ['pill-gray','#9ca3af']; @endphp
                <tr data-search="{{ strtolower(($p->cliente?->nombre ?? '') . ' ' . $p->estatus) }}">
                    <td style="font-weight:600">{{ $p->cliente?->nombre ?? '—' }}</td>
                    <td>{{ $fmt($p->monto_entregado) }}</td>
                    <td>{{ $fmt($p->monto) }}</td>
                    <td>{{ $fmt($p->cuota) }}</td>
                    <td style="color:{{ in_array($p->estatus,['Activo','Atrasado']) ? 'var(--yellow)' : 'var(--text3)' }};font-weight:600">
                        {{ in_array($p->estatus,['Activo','Atrasado']) ? $fmt($p->saldo_actual) : '—' }}
                    </td>
                    <td style="font-size:12px;color:var(--text3)">{{ $p->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
                    <td><span class="pill {{ $cfg[0] }}">{{ $p->estatus }}</span></td>
                </tr>
                @empty
                <tr><td colspan="7" class="ad-empty">Sin préstamos registrados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Notas / Auditoría ─────────────────────────────────────── --}}
<div class="ad-section">
    <div class="ad-section-title" style="justify-content:space-between">
        <div style="display:flex;align-items:center;gap:8px">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:var(--text2)"><path d="M13 2H3a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h4l2 2 2-2h2a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z"/><path d="M5 6h6M5 9h4"/></svg>
            Notas del Administrador
            @if($notas->count() > 0)
            <span class="ad-section-badge" style="background:#f3f4f6;color:var(--text2)">{{ $notas->count() }}</span>
            @endif
        </div>
        <button class="btn btn-sm" style="background:var(--blue-bg);color:var(--blue-dk)"
            onclick="document.getElementById('notaForm').style.display = document.getElementById('notaForm').style.display==='none'?'block':'none'">
            + Nueva nota
        </button>
    </div>

    {{-- Formulario nueva nota --}}
    <div id="notaForm" style="display:none;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:14px">
        <form method="POST" action="{{ route('owner.admins.notas.store', $admin->id) }}">
            @csrf
            <textarea name="contenido" required maxlength="2000" placeholder="Escribe una nota…"
                style="width:100%;min-height:80px;padding:10px 13px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:var(--font);resize:vertical;outline:none;background:#f9fafb;color:var(--text)"></textarea>
            <div style="display:flex;justify-content:flex-end;margin-top:10px">
                <button type="submit" class="btn btn-primary btn-sm">Guardar nota</button>
            </div>
        </form>
    </div>

    @if($notas->isEmpty())
    <div class="ad-empty">Sin notas registradas.</div>
    @else
    <div class="ad-timeline">
        @foreach($notas as $nota)
        <div class="ad-tl-item">
            <div class="ad-tl-dot"></div>
            <div class="ad-tl-date">{{ $nota->created_at->format('d/m/Y H:i') }}</div>
            <div class="ad-tl-text">{{ $nota->contenido }}</div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- ── Modal Editar desde esta página ──────────────────────── --}}
<div class="ow-modal-overlay" id="modalEditarDet">
    <div style="background:#fff;border-radius:18px;width:440px;max-width:calc(100vw - 24px);box-shadow:0 20px 60px rgba(0,0,0,.18);overflow:hidden;max-height:90vh;overflow-y:auto">
        <div style="padding:22px 28px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
            <div style="font-size:17px;font-weight:700">Editar administrador</div>
            <button onclick="document.getElementById('modalEditarDet').classList.remove('open')"
                style="background:#f1f5f9;border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:18px;color:var(--text3);display:flex;align-items:center;justify-content:center">&times;</button>
        </div>
        <form method="POST" action="{{ route('owner.admins.update', $admin->id) }}">
            @csrf @method('PUT')
            <div style="padding:24px 28px;display:grid;gap:16px">
                @foreach([['nombre','Nombre completo','text',$admin->nombre,'ej. Juan Pérez'],['alias','Alias','text',$admin->alias,'ej. Zona Norte'],['usuario','Usuario (login)','text',$admin->usuario,''],['celular','Teléfono / WhatsApp','tel',$admin->celular,'5512345678'],['presupuesto','Presupuesto ($)','number',$admin->presupuesto,'0']] as [$name,$label,$type,$val,$ph])
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);margin-bottom:5px">{{ $label }}</label>
                    <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $val) }}" placeholder="{{ $ph }}"
                        style="width:100%;padding:10px 13px;background:#f9fafb;border:1.5px solid var(--border);border-radius:8px;font-size:14px;font-family:var(--font);outline:none">
                </div>
                @endforeach
            </div>
            <div style="padding:16px 28px;background:#f8fafc;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end">
                <button type="button" class="btn" style="background:#f3f4f6;color:var(--text)"
                    onclick="document.getElementById('modalEditarDet').classList.remove('open')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// ── Chart: Flujo mensual ─────────────────────────────────────
(function(){
    const labels = @json($chartLabels);
    const des    = @json($chartDesembolsos);
    const cob    = @json($chartCobros);

    new Chart(document.getElementById('chartFlujo'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Desembolsado',
                    data: des,
                    backgroundColor: 'rgba(59,130,246,.18)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    borderRadius: 5,
                    type: 'bar',
                    order: 2,
                },
                {
                    label: 'Cobrado',
                    data: cob,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,.12)',
                    borderWidth: 2.5,
                    tension: .4,
                    fill: true,
                    type: 'line',
                    order: 1,
                    pointRadius: 3,
                    pointBackgroundColor: '#10b981',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' $' + Number(ctx.raw).toLocaleString('es-MX',{maximumFractionDigits:0})
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af' } },
                y: {
                    grid: { color: 'rgba(0,0,0,.04)' },
                    ticks: {
                        font: { size: 10 },
                        color: '#9ca3af',
                        callback: v => '$' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v)
                    }
                }
            }
        }
    });
})();

// ── Chart: Donut distribución ────────────────────────────────
(function(){
    const est    = @json(array_keys($porEstatus));
    const cnt    = @json(array_values($porEstatus));
    const colors = {'Activo':'#3b82f6','Atrasado':'#dc2626','Pendiente':'#f59e0b','Finalizado':'#16a34a','Retirado':'#9ca3af'};
    const bg     = est.map(e => colors[e] || '#6b7280');

    new Chart(document.getElementById('chartDonut'), {
        type: 'doughnut',
        data: {
            labels: est,
            datasets: [{ data: cnt, backgroundColor: bg, borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
        },
        options: {
            responsive: true,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw}` } }
            }
        }
    });
})();

// ── Buscador clientes ─────────────────────────────────────────
function filtrarClientes(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#tablaClientes tbody tr').forEach(tr => {
        tr.style.display = !q || (tr.dataset.search||'').includes(q) ? '' : 'none';
    });
}

// ── Buscador préstamos ────────────────────────────────────────
function filtrarPrestamos(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#tablaPrestamos tbody tr').forEach(tr => {
        tr.style.display = !q || (tr.dataset.search||'').includes(q) ? '' : 'none';
    });
}

// ── Abrir modal editar si hay errores de validación ───────────
@if($errors->any())
document.getElementById('modalEditarDet').classList.add('open');
@endif

// ── Cerrar modal al click fuera ──────────────────────────────
document.getElementById('modalEditarDet').addEventListener('click', function(e){
    if(e.target === this) this.classList.remove('open');
});
</script>
@endpush

@endsection
