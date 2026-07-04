@extends('layouts.app')

@section('title', 'Inversiones externas')

@push('styles')
<style>
/* ── Layout ─────────────────────────────────────────────── */
.fin-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}

/* ── KPI globales ────────────────────────────────────────── */
.fin-kpi-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px}
.fin-kpi{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;position:relative;overflow:hidden;box-shadow:var(--shadow-sm);transition:transform .25s cubic-bezier(.2,.7,.2,1),box-shadow .25s}
.fin-kpi:hover{transform:translateY(-3px);box-shadow:var(--shadow-md)}
.fin-kpi-accent{position:absolute;top:0;left:0;width:3px;height:100%;border-radius:3px 0 0 3px;box-shadow:0 0 14px 0 currentColor;opacity:.95}
.fin-kpi-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:6px}
.fin-kpi-value{font-size:21px;font-weight:800;letter-spacing:-.03em;color:var(--text);line-height:1;font-variant-numeric:tabular-nums}
.fin-kpi-sub{font-size:11px;color:var(--text2);margin-top:4px}

/* ── Cards ───────────────────────────────────────────────── */
:root{--fin-cols:210px 1fr 1fr 1fr 1fr 96px 36px}
.fin-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:12px;overflow:hidden;box-shadow:var(--shadow-sm);transition:box-shadow .2s,border-color .2s}
.fin-card:hover{box-shadow:var(--shadow-md);border-color:rgba(16,185,129,.25)}
.fin-card.finalizado{opacity:.72}
.fin-list-header{display:grid;grid-template-columns:var(--fin-cols);gap:0;padding:8px 20px;background:#f9fafb;border:1px solid var(--border);border-radius:var(--radius) var(--radius) 0 0;border-bottom:none}
.fin-list-hcell{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)}
.fin-card-header{display:grid;grid-template-columns:var(--fin-cols);align-items:center;gap:0;padding:16px 20px;cursor:pointer;user-select:none}
.fin-card-header:hover .fin-admin-name{color:var(--accent)}
.fin-col-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:3px}
.fin-col-value{font-size:14px;font-weight:700;color:var(--text);font-variant-numeric:tabular-nums}
.fin-col-sub{font-size:11px;color:var(--text2);margin-top:1px}
.fin-avatar{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0}
.fin-admin-name{font-size:13px;font-weight:700;color:var(--text);transition:color .1s}
.fin-admin-sub{font-size:11px;color:var(--text3);margin-top:2px}
.fin-chevron{transition:transform .2s;color:var(--text3);flex-shrink:0;justify-self:end}
.fin-chevron.open{transform:rotate(180deg)}

/* Pills */
.fin-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:10px;font-weight:700;white-space:nowrap}
.fin-pill-green{background:#f0fdf4;color:#10b981}
.fin-pill-gray{background:#f3f4f6;color:#6b7280}
.fin-pill-blue{background:#eff6ff;color:#1d4ed8}
.fin-pill-purple{background:#f5f3ff;color:#7c3aed}
.fin-pill-red{background:#fef2f2;color:#dc2626}
.fin-pill-yellow{background:#fefce8;color:#ca8a04}
.fin-pill-orange{background:#fff7ed;color:#ea580c}

/* ── Panel expandido ─────────────────────────────────────── */
.fin-detail{display:none;border-top:1px solid var(--border);padding:20px;background:#f9fafb}
.fin-detail.open{display:block}
.fin-detail-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
.fin-stat-box{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:13px 15px;transition:transform .2s,box-shadow .2s}
.fin-stat-box:hover{transform:translateY(-2px);box-shadow:var(--shadow-sm)}
.fin-stat-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:4px}
.fin-stat-value{font-size:17px;font-weight:800;letter-spacing:-.02em;color:var(--text);font-variant-numeric:tabular-nums}
.fin-stat-sub{font-size:11px;color:var(--text2);margin-top:2px}

/* Barra de reparto */
.fin-split-bar{display:flex;height:26px;border-radius:8px;overflow:hidden;background:#f3f4f6;margin:6px 0 8px}
.fin-split-seg{display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:#fff;white-space:nowrap;min-width:0;overflow:hidden}

/* Tabla de inversores */
.fin-inv{background:var(--card);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:16px}
.fin-inv-head{display:flex;align-items:center;justify-content:space-between;padding:13px 18px;border-bottom:1px solid var(--border);background:#fcfcfb}
.fin-inv-title{font-size:12px;font-weight:800;color:var(--text)}
.fin-inv-table{width:100%;border-collapse:collapse}
.fin-inv-table th{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);text-align:left;padding:8px 14px;background:#f9fafb;border-bottom:1px solid var(--border)}
.fin-inv-table td{font-size:12px;color:var(--text);padding:10px 14px;border-bottom:1px solid var(--border);font-variant-numeric:tabular-nums;vertical-align:middle}
.fin-inv-table tr:last-child td{border-bottom:none}
.fin-inv-table tr:hover td{background:#f9fafb}
.fin-inv-table tr.inactivo td{opacity:.55}
.fin-inv-add{padding:14px 18px;border-top:1px dashed var(--border);background:#fcfcfb}
.fin-inv-add-grid{display:grid;grid-template-columns:1.3fr .9fr 1fr .7fr .9fr auto auto;gap:10px;align-items:end}

/* Forms de movimiento */
.fin-forms-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px}
.fin-form-box{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:16px 18px}
.fin-form-title{font-size:12px;font-weight:800;letter-spacing:-.01em;color:var(--text);margin-bottom:12px;display:flex;align-items:center;gap:7px}
.fin-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.fin-field label{display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);margin-bottom:4px}
.fin-field input,.fin-field select,.fin-field textarea{width:100%;padding:8px 11px;background:#f9fafb;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:var(--font);color:var(--text);outline:none;transition:border-color .15s,box-shadow .15s}
.fin-field input:focus,.fin-field select:focus,.fin-field textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(16,185,129,.12);background:#fff}
.fin-split-preview{grid-column:1/-1;background:#f8fafc;border:1px dashed var(--border);border-radius:8px;padding:9px 12px;font-size:11px;color:var(--text2);display:flex;gap:6px 16px;flex-wrap:wrap;font-variant-numeric:tabular-nums}
.fin-split-preview b{color:var(--text)}
.fin-check{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text2);cursor:pointer;grid-column:1/-1}
.fin-check input{width:15px;height:15px;accent-color:var(--accent);cursor:pointer}
.fin-usar-btn{background:#eff6ff;color:#1d4ed8;border:none;border-radius:6px;padding:4px 9px;font-size:10px;font-weight:700;cursor:pointer;white-space:nowrap;transition:background .12s}
.fin-usar-btn:hover{background:#dbeafe}

/* Historial */
.fin-hist{background:var(--card);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:14px}
.fin-hist-title{font-size:12px;font-weight:800;color:var(--text);padding:13px 18px;border-bottom:1px solid var(--border);background:#fcfcfb}
.fin-hist-table{width:100%;border-collapse:collapse}
.fin-hist-table th{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);text-align:left;padding:8px 14px;background:#f9fafb;border-bottom:1px solid var(--border)}
.fin-hist-table td{font-size:12px;color:var(--text);padding:9px 14px;border-bottom:1px solid var(--border);font-variant-numeric:tabular-nums;vertical-align:middle}
.fin-hist-table tr:last-child td{border-bottom:none}
.fin-hist-table tr:hover td{background:#f9fafb}
.fin-hist-empty{padding:26px;text-align:center;color:var(--text3);font-size:12px}
.fin-mov-del{background:none;border:none;cursor:pointer;color:#d1d5db;padding:3px 6px;border-radius:5px;font-size:13px;transition:color .15s}
.fin-mov-del:hover{color:#ef4444}
.fin-mov-detalle{font-size:10px;color:var(--text3);margin-top:2px}

/* Acciones */
.fin-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}

/* Modal */
.fin-modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(6px);z-index:1000;align-items:center;justify-content:center}
.fin-modal-overlay.open{display:flex}
.fin-modal{background:#fff;border-radius:18px;width:520px;max-width:calc(100vw - 24px);box-shadow:0 20px 60px rgba(0,0,0,.18);max-height:90vh;overflow-y:auto;overflow-x:hidden}
.fin-modal-header{padding:22px 28px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.fin-modal-title{font-size:17px;font-weight:700}
.fin-modal-close{background:#f1f5f9;border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:18px;color:var(--text3);display:flex;align-items:center;justify-content:center}
.fin-modal-body{padding:24px 28px;display:grid;gap:16px}
.fin-modal-footer{padding:16px 28px;background:#f8fafc;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
.fin-modal-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:end}
.fin-modal-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;align-items:end}
/* Bloque de participante (owner / inversor) dentro del modal */
.fin-part{border:1px solid var(--border);border-radius:12px;padding:14px 16px 16px;background:#fbfbfa}
.fin-part-head{display:flex;align-items:center;gap:8px;margin-bottom:12px}
.fin-part-dot{width:9px;height:9px;border-radius:99px;flex-shrink:0}
.fin-part-name{font-size:12px;font-weight:800;color:var(--text)}
.fin-part-hint{font-size:11px;color:var(--text3);margin-left:auto;font-weight:500}
.fin-part-grid{display:grid;grid-template-columns:1.3fr 1.1fr 1fr .8fr;gap:12px;align-items:end}
.fin-modal-section{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);padding-top:6px;border-top:1px dashed var(--border)}
.fin-error{background:#fef2f2;border:1px solid rgba(220,38,38,.2);color:#b91c1c;font-size:12px;border-radius:8px;padding:10px 14px}
.fin-empty{background:var(--card);border:1px dashed var(--border);border-radius:var(--radius);padding:54px 24px;text-align:center;color:var(--text3);font-size:13px}

/* Responsive */
@media(max-width:1200px){
    .fin-kpi-grid{grid-template-columns:repeat(3,1fr)}
    :root{--fin-cols:190px 1fr 1fr 96px 36px}
    .fin-col-hide{display:none!important}
    .fin-detail-grid{grid-template-columns:repeat(2,1fr)}
    .fin-forms-row{grid-template-columns:1fr}
    .fin-inv-add-grid{grid-template-columns:1fr 1fr;gap:10px}
}
@media(max-width:768px){
    .fin-kpi-grid{grid-template-columns:1fr 1fr}
    .fin-list-header{display:none}
    :root{--fin-cols:1fr auto}
    .fin-card-header > *:not(:first-child):not(:last-child){display:none}
    .fin-detail-grid{grid-template-columns:1fr 1fr}
    .fin-form-grid{grid-template-columns:1fr}
    .fin-inv{overflow-x:auto}
    .fin-hist{overflow-x:auto}
    .fin-modal-row,.fin-modal-row-3,.fin-part-grid{grid-template-columns:1fr}
    .fin-inv-add-grid{grid-template-columns:1fr}
}
@media(max-width:480px){
    .fin-kpi-grid{grid-template-columns:1fr}
    .fin-detail-grid{grid-template-columns:1fr}
}
@media(prefers-reduced-motion:reduce){.fin-kpi,.fin-stat-box{transition:none}}

/* ═════════════ Pestañas y vista previa del modal crear ═════════════ */
.fin-tabs{display:flex;gap:0;padding:0 28px;border-bottom:1px solid var(--border);background:#fcfcfb}
.fin-tab{background:none;border:none;border-bottom:2.5px solid transparent;padding:12px 14px;font-size:12px;font-weight:700;color:var(--text3);cursor:pointer;font-family:var(--font);display:inline-flex;align-items:center;gap:7px;transition:color .15s,border-color .15s;margin-bottom:-1px}
.fin-tab:hover{color:var(--text2)}
.fin-tab.active{color:var(--accent);border-bottom-color:var(--accent)}
.fin-tab-num{width:17px;height:17px;border-radius:99px;background:#e5e7eb;color:var(--text2);font-size:10px;font-weight:800;display:inline-flex;align-items:center;justify-content:center}
.fin-tab.active .fin-tab-num{background:var(--accent);color:#fff}
.fin-prev-chips{display:grid;grid-template-columns:1fr 1fr;gap:8px;min-width:0}
.fin-prev-chip{background:#f8fafc;border:1px solid var(--border);border-radius:9px;padding:9px 12px;min-width:0}
.fin-prev-chip-k{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.fin-prev-chip-v{font-size:15px;font-weight:800;color:var(--text);font-variant-numeric:tabular-nums;margin-top:2px}
.fin-prev-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;min-width:0}
.fin-prev-box{background:#fbfbfa;border:1px solid var(--border);border-radius:12px;padding:12px 14px;min-width:0;overflow:hidden}
.fin-prev-title{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:8px}
.fin-prev-canvas{position:relative;height:195px;width:100%;min-width:0}
.fin-prev-canvas-lg{position:relative;height:250px;width:100%;min-width:0}
.fin-prev-canvas canvas,.fin-prev-canvas-lg canvas{max-width:100%!important}
#finTabPane2{min-width:0;max-width:100%;overflow-x:hidden}
#finPrevContenido{min-width:0}
#finPrevContenido > *{min-width:0}
.fin-prev-note{font-size:11px;color:var(--text3);line-height:1.5}
.fin-prev-empty{padding:34px 20px;text-align:center;color:var(--text3);font-size:12px;background:#fbfbfa;border:1px dashed var(--border);border-radius:12px}
@media(max-width:560px){.fin-prev-grid{grid-template-columns:1fr}.fin-prev-chips{grid-template-columns:1fr 1fr}}

/* ═════════════ Tutorial de primera vez (tour guiado) ═════════════ */
.tour-lock{overflow:hidden!important}
.tour-spot{position:fixed;z-index:3000;border-radius:14px;pointer-events:none;
    box-shadow:0 0 0 9999px rgba(11,15,23,.62),0 0 0 3px rgba(16,185,129,.9),0 0 24px 4px rgba(16,185,129,.35);
    transition:top .35s cubic-bezier(.2,.7,.2,1),left .35s cubic-bezier(.2,.7,.2,1),width .35s cubic-bezier(.2,.7,.2,1),height .35s cubic-bezier(.2,.7,.2,1),border-radius .35s}
.tour-spot.tour-center{box-shadow:0 0 0 9999px rgba(11,15,23,.62);width:0!important;height:0!important;top:50%!important;left:50%!important}
.tour-blocker{position:fixed;inset:0;z-index:3001;background:transparent}
.tour-pop{position:fixed;z-index:3002;width:340px;max-width:calc(100vw - 28px);background:#fff;border-radius:16px;
    box-shadow:0 24px 60px -12px rgba(0,0,0,.35);padding:18px 20px 16px;opacity:0;transform:translateY(8px);
    transition:opacity .25s,transform .25s cubic-bezier(.2,.7,.2,1)}
.tour-pop.show{opacity:1;transform:translateY(0)}
.tour-pop.tour-pop-center{left:50%!important;top:50%!important;transform:translate(-50%,calc(-50% + 8px));text-align:center;width:380px}
.tour-pop.tour-pop-center.show{transform:translate(-50%,-50%)}
.tour-step-chip{display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;color:#10b981;font-size:10px;font-weight:800;
    text-transform:uppercase;letter-spacing:.07em;padding:4px 10px;border-radius:99px;margin-bottom:9px}
.tour-title{font-size:16px;font-weight:800;letter-spacing:-.02em;color:var(--text);margin-bottom:6px;line-height:1.3}
.tour-text{font-size:13px;color:var(--text2);line-height:1.55;margin-bottom:14px}
.tour-dots{display:flex;gap:5px;justify-content:center;margin-bottom:13px}
.tour-pop:not(.tour-pop-center) .tour-dots{justify-content:flex-start}
.tour-dot{width:7px;height:7px;border-radius:99px;background:#e5e7eb;transition:background .2s,width .2s}
.tour-dot.act{background:var(--accent);width:18px}
.tour-btns{display:flex;align-items:center;gap:8px}
.tour-skip{background:none;border:none;color:var(--text3);font-size:12px;font-weight:600;cursor:pointer;padding:7px 4px;margin-right:auto;font-family:var(--font)}
.tour-skip:hover{color:var(--text2);text-decoration:underline}
.tour-prev{background:#f3f4f6;color:var(--text2);border:none;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:var(--font)}
.tour-prev:hover{background:#e5e7eb}
.tour-next{background:var(--accent);color:#fff;border:none;border-radius:8px;padding:8px 16px;font-size:12px;font-weight:700;cursor:pointer;font-family:var(--font);box-shadow:0 6px 14px -6px rgba(16,185,129,.6);transition:background .15s}
.tour-next:hover{background:var(--accent-hover)}
.tour-emoji{font-size:34px;line-height:1;margin-bottom:10px}
.tour-help-btn{width:30px;height:30px;border-radius:99px;border:1px solid var(--border);background:var(--card);color:var(--text3);
    font-size:14px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:color .15s,border-color .15s;font-family:var(--font)}
.tour-help-btn:hover{color:var(--accent);border-color:rgba(16,185,129,.4)}
@media(max-width:480px){.tour-pop{width:calc(100vw - 28px)}.tour-pop.tour-pop-center{width:calc(100vw - 28px)}}
@media(prefers-reduced-motion:reduce){.tour-spot,.tour-pop{transition:none}}
</style>
@endpush

@section('content')

@php
    $money  = fn($v) => '$' . number_format($v, 2);
    $fmtPct = fn($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
    $avatarColors = ['#10b981','#6366f1','#f59e0b','#ec4899','#14b8a6','#8b5cf6','#ef4444','#0ea5e9'];
    $invColors    = ['#f59e0b','#0ea5e9','#ec4899','#14b8a6','#8b5cf6','#ef4444','#84cc16','#f97316'];
    $ownerDefault = auth()->user()->alias ?: (auth()->user()->nombre ?: auth()->user()->usuario);
    $tipoLabels = [
        'rendimiento'         => ['Rendimiento',      'fin-pill-green'],
        'aporte'              => ['Aporte',           'fin-pill-blue'],
        'retiro'              => ['Retiro owner',     'fin-pill-red'],
        'salida_inversor'     => ['Salida inversor',  'fin-pill-orange'],
        'transferencia_owner' => ['Transf. a owner',  'fin-pill-purple'],
    ];
@endphp

{{-- ── Header ─────────────────────────────────────────────── --}}
<div class="fin-header">
    <div>
        <h2 style="font-size:22px;font-weight:800;letter-spacing:-.03em;margin-bottom:3px">Inversiones externas</h2>
        <p style="font-size:13px;color:var(--text2)">Capital de inversión conjunta hacia administradores · interés por periodo, retornos a inversores y reinversión</p>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
        <button type="button" class="tour-help-btn" title="Ver tutorial" onclick="finTourStart(true)">?</button>
        <a href="{{ route('owner.rendimientos') }}" class="btn" style="background:#f3f4f6;color:var(--text2);font-size:12px">Rendimientos</a>
        <button class="btn btn-primary" id="finBtnNueva" onclick="document.getElementById('finModalCrear').classList.add('open')">
            <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:13px;height:13px"><path d="M7 2v10M2 7h10"/></svg>
            Nueva cuenta de inversión
        </button>
    </div>
</div>

@if($errors->any() && !session('open_financiamiento'))
    <div class="fin-error" style="margin-bottom:14px">
        @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
    </div>
@endif

{{-- ── KPIs globales ───────────────────────────────────────── --}}
<div class="fin-kpi-grid">
    <div class="fin-kpi">
        <div class="fin-kpi-accent" style="color:#10b981;background:#10b981"></div>
        <div class="fin-kpi-label">Capital en inversión</div>
        <div class="fin-kpi-value">{{ $money($globales['capital_vigente']) }}</div>
        <div class="fin-kpi-sub">{{ $globales['activos'] }} cuenta{{ $globales['activos'] === 1 ? '' : 's' }} activa{{ $globales['activos'] === 1 ? '' : 's' }}</div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-accent" style="color:#6366f1;background:#6366f1"></div>
        <div class="fin-kpi-label">Rendimiento esperado</div>
        <div class="fin-kpi-value">
            @if($globales['rendimiento_semana'] > 0){{ $money($globales['rendimiento_semana']) }}<span style="font-size:12px;color:var(--text3)"> /sem</span>
            @elseif($globales['rendimiento_mes'] > 0){{ $money($globales['rendimiento_mes']) }}<span style="font-size:12px;color:var(--text3)"> /mes</span>
            @else $0.00 @endif
        </div>
        <div class="fin-kpi-sub">
            @if($globales['rendimiento_semana'] > 0 && $globales['rendimiento_mes'] > 0)
                + {{ $money($globales['rendimiento_mes']) }} /mes en cuentas mensuales
            @else según tasa pactada sobre capital vigente @endif
        </div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-accent" style="color:#0ea5e9;background:#0ea5e9"></div>
        <div class="fin-kpi-label">Rendimientos cobrados</div>
        <div class="fin-kpi-value">{{ $money($globales['total_generado']) }}</div>
        <div class="fin-kpi-sub">histórico de todas las cuentas</div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-accent" style="color:#7c3aed;background:#7c3aed"></div>
        <div class="fin-kpi-label">Reinvertido</div>
        <div class="fin-kpi-value">{{ $money($globales['total_reinvertido']) }}</div>
        <div class="fin-kpi-sub">ganancia capitalizada a la inversión</div>
    </div>
    <div class="fin-kpi">
        <div class="fin-kpi-accent" style="color:#f59e0b;background:#f59e0b"></div>
        <div class="fin-kpi-label">Retornado a inversores</div>
        <div class="fin-kpi-value">{{ $money($globales['total_retornado']) }}</div>
        <div class="fin-kpi-sub">ganancia pagada fuera de la cuenta</div>
    </div>
</div>

{{-- ── Lista ───────────────────────────────────────────────── --}}
@if($financiamientos->isEmpty())
    <div class="fin-empty">
        Aún no hay cuentas de inversión registradas.<br>
        <button class="btn btn-primary" style="margin-top:14px" onclick="document.getElementById('finModalCrear').classList.add('open')">Crear la primera</button>
    </div>
@else

<div class="fin-list-header">
    <div class="fin-list-hcell">Administrador</div>
    <div class="fin-list-hcell">Capital en inversión</div>
    <div class="fin-list-hcell fin-col-hide">Tasa del periodo</div>
    <div class="fin-list-hcell">Inversores</div>
    <div class="fin-list-hcell fin-col-hide">Generado</div>
    <div class="fin-list-hcell">Estatus</div>
    <div></div>
</div>

@foreach($financiamientos as $f)
@php
    $color    = $avatarColors[$f->admin_id % count($avatarColors)];
    $nombre   = $f->admin->alias ?: ($f->admin->nombre ?: $f->admin->usuario);
    $rendPer  = $f->rendimiento_periodo;
    $activosI = $f->inversoresActivos();
@endphp
<div class="fin-card {{ $f->estatus === 'Finalizado' ? 'finalizado' : '' }}" id="finCard{{ $f->id }}">
    <div class="fin-card-header" onclick="finToggle({{ $f->id }})">
        <div style="display:flex;align-items:center;gap:11px;min-width:0">
            <div class="fin-avatar" style="background:{{ $color }}">{{ strtoupper(substr($nombre, 0, 1)) }}</div>
            <div style="min-width:0">
                <div class="fin-admin-name">{{ $nombre }}</div>
                <div class="fin-admin-sub">desde {{ $f->fecha_inicio->format('d/m/Y') }} · convenio {{ $f->plazo_meses }} meses</div>
            </div>
        </div>
        <div>
            <div class="fin-col-label">Capital</div>
            <div class="fin-col-value">{{ $money($f->capital_actual) }}</div>
            <div class="fin-col-sub">aportado {{ $money($f->stats['aportado_activo']) }}</div>
        </div>
        <div class="fin-col-hide">
            <div class="fin-col-label">Tasa</div>
            <div class="fin-col-value" style="color:#10b981">{{ $fmtPct($f->rendimiento_pct) }}% {{ $f->frecuencia }}</div>
            <div class="fin-col-sub">≈ {{ $money($rendPer) }} / {{ $f->periodo_label }}</div>
        </div>
        <div>
            <div class="fin-col-label">Inversores</div>
            <div style="display:flex;gap:5px;margin-top:2px;flex-wrap:wrap">
                @forelse($activosI->take(3) as $inv)
                    <span class="fin-pill {{ $inv->es_owner ? 'fin-pill-purple' : 'fin-pill-yellow' }}">{{ \Illuminate\Support\Str::limit($inv->nombre, 12) }}</span>
                @empty
                    <span class="fin-pill fin-pill-gray">sin inversores</span>
                @endforelse
                @if($activosI->count() > 3)<span class="fin-pill fin-pill-gray">+{{ $activosI->count() - 3 }}</span>@endif
            </div>
        </div>
        <div class="fin-col-hide">
            <div class="fin-col-label">Generado</div>
            <div class="fin-col-value">{{ $money($f->stats['rendimientos_cobrados']) }}</div>
            <div class="fin-col-sub">{{ $f->stats['n_rendimientos'] }} cobro{{ $f->stats['n_rendimientos'] === 1 ? '' : 's' }}</div>
        </div>
        <div>
            <span class="fin-pill {{ $f->estatus === 'Activo' ? 'fin-pill-green' : 'fin-pill-gray' }}">{{ $f->estatus }}</span>
        </div>
        <svg class="fin-chevron" id="finChevron{{ $f->id }}" viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6l4 4 4-4"/></svg>
    </div>

    <div class="fin-detail" id="finDetail{{ $f->id }}">

        {{-- Stats --}}
        <div class="fin-detail-grid">
            <div class="fin-stat-box">
                <div class="fin-stat-label">Capital en inversión</div>
                <div class="fin-stat-value" style="color:#10b981">{{ $money($f->capital_actual) }}</div>
                <div class="fin-stat-sub">aportes activos {{ $money($f->stats['aportado_activo']) }} + reinversión</div>
            </div>
            <div class="fin-stat-box">
                <div class="fin-stat-label">Rendimiento por {{ $f->periodo_label }}</div>
                <div class="fin-stat-value">{{ $money($rendPer) }}</div>
                <div class="fin-stat-sub">{{ $fmtPct($f->rendimiento_pct) }}% {{ $f->frecuencia }} del capital</div>
            </div>
            <div class="fin-stat-box">
                <div class="fin-stat-label">Reinvertido acumulado</div>
                <div class="fin-stat-value" style="color:#7c3aed">{{ $money($f->stats['total_reinvertido']) }}</div>
                <div class="fin-stat-sub">retiros owner −{{ $money($f->stats['total_retiros_owner']) }}</div>
            </div>
            <div class="fin-stat-box">
                <div class="fin-stat-label">Retornado a inversores</div>
                <div class="fin-stat-value" style="color:#d97706">{{ $money($f->stats['total_retornado']) }}</div>
                <div class="fin-stat-sub">{{ $f->stats['rendimientos_cobrados'] > 0 ? $money($f->stats['rendimientos_cobrados']) . ' generados en total' : 'sin rendimientos aún' }}</div>
            </div>
        </div>

        {{-- Barra de reparto del rendimiento (retornos fijos sobre el aporte de cada inversor) --}}
        @php
            // retorno_mensual es mensual; en cuentas semanales se prorratea entre 4 semanas
            $ppm = $f->periodos_por_mes;
            $retFijos = $activosI->where('retorno_mensual', '>', 0)->values();
            $totalRetFijo = (float) $retFijos->sum(fn($i) => $i->retorno_mensual / $ppm);
            $reinvPer = max(0, $rendPer - $totalRetFijo);
        @endphp
        <div style="background:var(--card);border:1px solid var(--border);border-radius:10px;padding:13px 16px;margin-bottom:16px">
            <div class="fin-stat-label" style="margin-bottom:0">
                Reparto del rendimiento del {{ $fmtPct($f->rendimiento_pct) }}% {{ $f->frecuencia }} ≈ {{ $money($rendPer) }} por {{ $f->periodo_label }}
            </div>
            <div class="fin-split-bar">
                @if($reinvPer > 0)
                <div class="fin-split-seg" style="background:#7c3aed;flex:{{ $reinvPer }}" title="Reinversión {{ $money($reinvPer) }}">
                    @if($rendPer > 0 && $reinvPer / $rendPer >= 0.22) Reinversión {{ $money($reinvPer) }} @endif
                </div>
                @endif
                @foreach($retFijos as $ix => $inv)
                @php $fijo = $inv->retorno_mensual / $ppm; @endphp
                <div class="fin-split-seg" style="background:{{ $invColors[$ix % count($invColors)] }};flex:{{ $fijo }}" title="{{ $inv->nombre }} {{ $money($fijo) }}">
                    @if($rendPer > 0 && $fijo / $rendPer >= 0.22) {{ \Illuminate\Support\Str::limit($inv->nombre, 10) }} {{ $money($fijo) }} @endif
                </div>
                @endforeach
            </div>
            <div style="font-size:11px;color:var(--text2);font-variant-numeric:tabular-nums">
                Cada {{ $f->periodo_label }}:
                <b style="color:#7c3aed">{{ $money($reinvPer) }}</b> se reinvierte
                @foreach($retFijos as $ix => $inv)
                    · <b style="color:{{ $invColors[$ix % count($invColors)] }}">{{ $money($inv->retorno_mensual / $ppm) }}</b> a {{ $inv->nombre }} ({{ $money($inv->retorno_mensual) }} fijos/mes ≈ {{ $fmtPct($inv->pct_retorno) }}% de su aporte{{ $ppm > 1 ? ' ÷ 4 semanas' : '' }})
                @endforeach
            </div>
        </div>

        @if($errors->any() && (int) session('open_financiamiento') === $f->id)
            <div class="fin-error" style="margin-bottom:14px">
                @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
            </div>
        @endif

        {{-- Inversores --}}
        <div class="fin-inv">
            <div class="fin-inv-head">
                <div class="fin-inv-title">Inversores de la cuenta</div>
                <span style="font-size:11px;color:var(--text3)">convenio de salida: {{ $f->plazo_meses }} meses · después el capital pasa al owner</span>
            </div>
            <table class="fin-inv-table">
                <thead>
                    <tr>
                        <th>Inversor</th>
                        <th>Aporte</th>
                        <th>Retorno mensual fijo</th>
                        <th>Retorno / {{ $f->periodo_label }}</th>
                        <th>Ingreso</th>
                        <th>Límite convenio</th>
                        <th>Estatus</th>
                        <th style="width:150px"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($f->inversores as $inv)
                    <tr class="{{ $inv->estatus !== 'Activo' ? 'inactivo' : '' }}">
                        <td style="font-weight:700">
                            {{ $inv->nombre }}
                            @if($inv->es_owner)<span class="fin-pill fin-pill-purple" style="margin-left:5px">owner</span>@endif
                        </td>
                        <td style="font-weight:700">{{ $money($inv->aporte) }}</td>
                        <td>
                            @if($inv->estatus === 'Activo' && $inv->retorno_mensual > 0)
                                <b>{{ $money($inv->retorno_mensual) }}</b>
                                <div style="font-size:10px;color:var(--text3)">≈ {{ $fmtPct($inv->pct_retorno) }}% de su aporte</div>
                            @else — @endif
                        </td>
                        <td>
                            @if($inv->estatus === 'Activo' && $inv->retorno_mensual > 0)
                                {{ $money($inv->retorno_mensual / $f->periodos_por_mes) }} fijo
                                @if($f->periodos_por_mes > 1)
                                    <div style="font-size:10px;color:var(--text3)">= {{ $money($inv->retorno_mensual) }} /mes ÷ 4 semanas</div>
                                @endif
                            @else — @endif
                        </td>
                        <td>{{ $inv->fecha_ingreso->format('d/m/Y') }}</td>
                        <td>
                            @if($inv->fecha_limite)
                                {{ $inv->fecha_limite->format('d/m/Y') }}
                                @if($inv->estatus === 'Activo' && $inv->convenio_vencido)
                                    <span class="fin-pill fin-pill-red" style="margin-left:4px" title="Si se retira ahora, su capital pasa al owner">vencido</span>
                                @endif
                            @else — @endif
                        </td>
                        <td>
                            @if($inv->estatus === 'Activo')<span class="fin-pill fin-pill-green">Activo</span>
                            @elseif($inv->estatus === 'Retirado')<span class="fin-pill fin-pill-gray">Retirado {{ $inv->fecha_salida?->format('d/m/y') }}</span>
                            @else<span class="fin-pill fin-pill-purple">Transferido {{ $inv->fecha_salida?->format('d/m/y') }}</span>@endif
                        </td>
                        <td>
                            @if($f->estatus === 'Activo' && $inv->estatus === 'Activo')
                            <div style="display:flex;gap:5px;justify-content:flex-end">
                                @php
                                    $invEdit = ['fid' => $f->id, 'iid' => $inv->id, 'nombre' => $inv->nombre, 'pct_retorno' => $inv->pct_retorno, 'retorno_mensual' => $inv->retorno_mensual, 'aporte' => $inv->aporte];
                                @endphp
                                <button class="btn btn-sm" style="background:#eff6ff;color:#1d4ed8;font-size:11px;padding:4px 9px"
                                    onclick='finOpenEditInversor(@json($invEdit))'>Editar</button>
                                @if(!$inv->es_owner)
                                <form method="POST" action="{{ route('owner.financiamientos.inversores.salida', [$f->id, $inv->id]) }}"
                                      onsubmit="return confirm('{{ $inv->convenio_vencido
                                        ? 'El convenio venció el ' . $inv->fecha_limite->format('d/m/Y') . '. El capital de ' . $money($inv->aporte) . ' se transferirá al OWNER (el inversor pierde el derecho a reclamarlo). ¿Continuar?'
                                        : 'Se devolverá el aporte de ' . $money($inv->aporte) . ' a ' . $inv->nombre . ' y saldrá de la inversión. Su retorno fijo pasará a reinversión. ¿Continuar?' }}')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm" style="background:{{ $inv->convenio_vencido ? '#f5f3ff' : '#fff7ed' }};color:{{ $inv->convenio_vencido ? '#7c3aed' : '#ea580c' }};font-size:11px;padding:4px 9px">
                                        {{ $inv->convenio_vencido ? 'Transferir al owner' : 'Retirar' }}
                                    </button>
                                </form>
                                @endif
                            </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @if($f->estatus === 'Activo')
            <div class="fin-inv-add">
                <form method="POST" action="{{ route('owner.financiamientos.inversores.store', $f->id) }}">
                    @csrf
                    <div class="fin-inv-add-grid">
                        <div class="fin-field">
                            <label>Nuevo inversor</label>
                            <input type="text" name="nombre" required maxlength="120" placeholder="Nombre del inversor">
                        </div>
                        <div class="fin-field">
                            <label>Aporte</label>
                            <input type="number" name="aporte" id="finAddAporte{{ $f->id }}" step="0.01" min="0.01" required placeholder="0.00"
                                   oninput="finSyncRet('finAddAporte{{ $f->id }}', 'finAddPct{{ $f->id }}', 'finAddRet{{ $f->id }}', 'aporte')">
                        </div>
                        @php
                            // Margen mensual disponible: rendimiento mensual − retornos fijos ya comprometidos
                            $dispMes = max(0, $f->capital_actual * $f->rendimiento_pct * $f->periodos_por_mes / 100 - $f->fijos_mensuales);
                        @endphp
                        <div class="fin-field">
                            <label>Retorno fijo $/mes <span style="text-transform:none;font-weight:600">(disponible ≈ {{ $money($dispMes) }}/mes)</span></label>
                            <input type="number" name="retorno_mensual" id="finAddRet{{ $f->id }}" step="0.01" min="0" placeholder="5000"
                                   oninput="finSyncRet('finAddAporte{{ $f->id }}', 'finAddPct{{ $f->id }}', 'finAddRet{{ $f->id }}', 'ret')">
                        </div>
                        <div class="fin-field">
                            <label>≈ % mensual</label>
                            <input type="number" name="pct_retorno" id="finAddPct{{ $f->id }}" step="0.01" min="0" max="100" placeholder="10"
                                   oninput="finSyncRet('finAddAporte{{ $f->id }}', 'finAddPct{{ $f->id }}', 'finAddRet{{ $f->id }}', 'pct')">
                        </div>
                        <div class="fin-field">
                            <label>Fecha de ingreso</label>
                            <input type="date" name="fecha_ingreso" value="{{ now()->toDateString() }}" required>
                        </div>
                        <label class="fin-check" style="grid-column:auto;white-space:nowrap;margin-bottom:8px">
                            <input type="checkbox" name="es_owner" value="1"> Es del owner
                        </label>
                        <button type="submit" class="btn btn-primary" style="font-size:12px;white-space:nowrap">Agregar inversor</button>
                    </div>
                </form>
            </div>
            @endif
        </div>

        {{-- Forms --}}
        @if($f->estatus === 'Activo')
        <div class="fin-forms-row">
            {{-- Registrar rendimiento --}}
            <div class="fin-form-box">
                <div class="fin-form-title">
                    <span style="width:8px;height:8px;border-radius:99px;background:#10b981;display:inline-block"></span>
                    Registrar rendimiento de la {{ $f->periodo_label }} (cobro al admin)
                </div>
                <form method="POST" action="{{ route('owner.financiamientos.movimientos.store', $f->id) }}">
                    @csrf
                    <input type="hidden" name="tipo" value="rendimiento">
                    <div class="fin-form-grid">
                        <div class="fin-field">
                            <label style="display:flex;align-items:center;justify-content:space-between">
                                <span>Monto cobrado</span>
                                <button type="button" class="fin-usar-btn" onclick="finUsarEsperado({{ $f->id }}, {{ $rendPer }})">usar {{ $money($rendPer) }}</button>
                            </label>
                            <input type="number" name="monto" id="finRendMonto{{ $f->id }}" step="0.01" min="0.01" required placeholder="0.00"
                                   oninput="finRendPreview({{ $f->id }})">
                        </div>
                        <div class="fin-field">
                            <label>Fecha</label>
                            <input type="date" name="fecha" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="fin-split-preview" id="finRendPreviewBox{{ $f->id }}">
                            <span>Reinversión: <b id="finPrevReinv{{ $f->id }}">$0.00</b></span>
                            <span id="finPrevInvList{{ $f->id }}"></span>
                        </div>
                        <label class="fin-check">
                            <input type="checkbox" name="capitalizar" value="1" checked>
                            Capitalizar la reinversión (sumarla al capital para la siguiente {{ $f->periodo_label }})
                        </label>
                        <div class="fin-field" style="grid-column:1/-1">
                            <label>Nota (opcional)</label>
                            <input type="text" name="nota" maxlength="255" placeholder="Ej. rendimiento {{ $f->periodo_label }} {{ now()->locale('es')->isoFormat($f->frecuencia === 'semanal' ? 'w' : 'MMMM') }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:12px;font-size:12px">Registrar rendimiento</button>
                </form>
            </div>

            {{-- Movimiento de capital (owner) --}}
            <div class="fin-form-box">
                <div class="fin-form-title">
                    <span style="width:8px;height:8px;border-radius:99px;background:#6366f1;display:inline-block"></span>
                    Movimiento de capital del owner
                </div>
                <form method="POST" action="{{ route('owner.financiamientos.movimientos.store', $f->id) }}">
                    @csrf
                    <div class="fin-form-grid">
                        <div class="fin-field">
                            <label>Tipo</label>
                            <select name="tipo" required>
                                <option value="retiro">Retiro (− capital)</option>
                                <option value="aporte">Aporte a la cuenta (+ capital)</option>
                            </select>
                        </div>
                        <div class="fin-field">
                            <label>Monto</label>
                            <input type="number" name="monto" step="0.01" min="0.01" required placeholder="0.00">
                        </div>
                        <div class="fin-field">
                            <label>Fecha</label>
                            <input type="date" name="fecha" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="fin-field">
                            <label>Nota (opcional)</label>
                            <input type="text" name="nota" maxlength="255" placeholder="Ej. retiro para nuevo proyecto">
                        </div>
                    </div>
                    <div style="font-size:11px;color:var(--text3);margin-top:8px">El retiro descuenta del capital en inversión (de la ganancia reinvertida). Queda registrado para cuadrar con lo que el admin paga.</div>
                    <button type="submit" class="btn" style="width:100%;justify-content:center;margin-top:10px;font-size:12px;background:#eef2ff;color:#4f46e5">Registrar movimiento</button>
                </form>
            </div>
        </div>
        @endif

        {{-- Historial --}}
        <div class="fin-hist">
            <div class="fin-hist-title">Historial de movimientos</div>
            @if($f->movimientos->isEmpty())
                <div class="fin-hist-empty">Sin movimientos registrados todavía.</div>
            @else
            <table class="fin-hist-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Reinversión</th>
                        <th>Retornado</th>
                        <th>Detalle</th>
                        <th style="width:36px"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($f->movimientos as $m)
                    @php [$tLabel, $tClass] = $tipoLabels[$m->tipo] ?? [$m->tipo, 'fin-pill-gray']; @endphp
                    <tr>
                        <td>{{ $m->fecha->format('d/m/Y') }}</td>
                        <td><span class="fin-pill {{ $tClass }}">{{ $tLabel }}</span></td>
                        <td style="font-weight:700">{{ $money($m->monto) }}</td>
                        <td>
                            @if($m->tipo === 'rendimiento')
                                {{ $money($m->monto_reinversion) }}
                                @if($m->capitalizado)<span class="fin-pill fin-pill-purple" style="margin-left:4px" title="Sumado al capital">capitalizado</span>@endif
                            @else — @endif
                        </td>
                        <td>{{ $m->tipo === 'rendimiento' ? $money($m->monto_retornado) : '—' }}</td>
                        <td style="color:var(--text2);max-width:260px">
                            @if($m->tipo === 'rendimiento' && $m->detalle)
                                @foreach($m->detalle as $d)
                                    <div class="fin-mov-detalle">→ {{ $d['nombre'] }}: {{ $money($d['monto']) }} ({{ $fmtPct($d['pct']) }}%)</div>
                                @endforeach
                            @endif
                            @if($m->inversor)
                                <div class="fin-mov-detalle">{{ $m->inversor->nombre }}</div>
                            @endif
                            @if($m->nota)<div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $m->nota }}</div>@endif
                            @if(!$m->nota && !$m->detalle && !$m->inversor) — @endif
                        </td>
                        <td>
                            @if($m->inversor_id === null && !in_array($m->tipo, ['salida_inversor', 'transferencia_owner']))
                            <form method="POST" action="{{ route('owner.financiamientos.movimientos.destroy', [$f->id, $m->id]) }}"
                                  onsubmit="return confirm('¿Eliminar este movimiento? El capital se ajustará automáticamente.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="fin-mov-del" title="Eliminar movimiento">✕</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- Acciones --}}
        <div class="fin-actions">
            @if($f->notas)
                <span style="font-size:11px;color:var(--text3);margin-right:auto;align-self:center;max-width:50%">📝 {{ $f->notas }}</span>
            @endif
            @php
                $editData = [
                    'id'              => $f->id,
                    'rendimiento_pct' => $f->rendimiento_pct,
                    'frecuencia'      => $f->frecuencia,
                    'plazo_meses'     => $f->plazo_meses,
                    'fecha_inicio'    => $f->fecha_inicio->toDateString(),
                    'notas'           => $f->notas,
                    'nombre'          => $nombre,
                    'fijos_mensuales' => $f->fijos_mensuales,
                ];
            @endphp
            <button class="btn btn-sm" style="background:#eff6ff;color:#1d4ed8"
                onclick='finOpenEditar(@json($editData))'>Editar acuerdo</button>
            <form method="POST" action="{{ route('owner.financiamientos.toggle', $f->id) }}"
                  onsubmit="return confirm('{{ $f->estatus === 'Activo' ? '¿Marcar esta cuenta de inversión como finalizada?' : '¿Reactivar esta cuenta de inversión?' }}')">
                @csrf
                <button type="submit" class="btn btn-sm" style="background:#f3f4f6;color:var(--text2)">
                    {{ $f->estatus === 'Activo' ? 'Finalizar' : 'Reactivar' }}
                </button>
            </form>
            <form method="POST" action="{{ route('owner.financiamientos.destroy', $f->id) }}"
                  onsubmit="return confirm('¿Eliminar esta cuenta de inversión, sus inversores y TODO su historial? Esta acción no se puede deshacer.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm" style="background:#fef2f2;color:#dc2626">Eliminar</button>
            </form>
        </div>
    </div>
</div>
@endforeach
@endif

{{-- ── Modal: nueva cuenta de inversión ────────────────────── --}}
<div class="fin-modal-overlay" id="finModalCrear">
    <div class="fin-modal" style="width:760px">
        <div class="fin-modal-header">
            <div class="fin-modal-title">Nueva cuenta de inversión</div>
            <button class="fin-modal-close" onclick="document.getElementById('finModalCrear').classList.remove('open')">×</button>
        </div>
        <form method="POST" action="{{ route('owner.financiamientos.store') }}">
            @csrf
            <div class="fin-tabs">
                <button type="button" class="fin-tab active" id="finTabBtn1" onclick="finTab(1)">
                    <span class="fin-tab-num">1</span> Datos del acuerdo
                </button>
                <button type="button" class="fin-tab" id="finTabBtn2" onclick="finTab(2)">
                    <span class="fin-tab-num">2</span> Vista previa 📊
                </button>
            </div>
            <div class="fin-modal-body" id="finTabPane1">
                <div class="fin-field">
                    <label>Administrador financiado</label>
                    <select name="admin_id" required>
                        <option value="">Selecciona un admin…</option>
                        @foreach($admins as $a)
                            <option value="{{ $a->id }}" {{ old('admin_id') == $a->id ? 'selected' : '' }}>
                                {{ $a->alias ?: ($a->nombre ?: $a->usuario) }} ({{ $a->usuario }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="fin-modal-row-3">
                    <div class="fin-field">
                        <label>Tasa por periodo %</label>
                        <input type="number" name="rendimiento_pct" id="finCrearPct" step="0.01" min="0.01" max="100" required placeholder="18" value="{{ old('rendimiento_pct', 18) }}" oninput="finCrearPreview()">
                    </div>
                    <div class="fin-field">
                        <label>Frecuencia</label>
                        <select name="frecuencia" required>
                            <option value="semanal" {{ old('frecuencia', 'semanal') === 'semanal' ? 'selected' : '' }}>Semanal</option>
                            <option value="mensual" {{ old('frecuencia') === 'mensual' ? 'selected' : '' }}>Mensual</option>
                        </select>
                    </div>
                    <div class="fin-field">
                        <label>Convenio (meses)</label>
                        <input type="number" name="plazo_meses" min="1" max="120" required value="{{ old('plazo_meses', 12) }}">
                    </div>
                </div>
                <div class="fin-field">
                    <label>Fecha de inicio</label>
                    <input type="date" name="fecha_inicio" required value="{{ old('fecha_inicio', now()->toDateString()) }}">
                </div>

                <div class="fin-part">
                    <div class="fin-part-head">
                        <span class="fin-part-dot" style="background:#7c3aed"></span>
                        <span class="fin-part-name">Participación del owner</span>
                        <span class="fin-part-hint">retorno fijo en $0 = todo se reinvierte</span>
                    </div>
                    <div class="fin-part-grid">
                        <div class="fin-field">
                            <label>Nombre</label>
                            <input type="text" name="owner_nombre" required maxlength="120" value="{{ old('owner_nombre', $ownerDefault) }}">
                        </div>
                        <div class="fin-field">
                            <label>Aporte</label>
                            <input type="number" name="owner_aporte" id="finCrearOwnerAporte" step="0.01" min="0" required placeholder="500000" value="{{ old('owner_aporte') }}"
                                   oninput="finSyncRet('finCrearOwnerAporte', 'finCrearOwnerPct', 'finCrearOwnerRet', 'aporte'); finCrearPreview()">
                        </div>
                        <div class="fin-field">
                            <label>Retorno fijo $/mes</label>
                            <input type="number" name="owner_retorno" id="finCrearOwnerRet" step="0.01" min="0" placeholder="0" value="{{ old('owner_retorno') }}"
                                   oninput="finSyncRet('finCrearOwnerAporte', 'finCrearOwnerPct', 'finCrearOwnerRet', 'ret'); finCrearPreview()">
                        </div>
                        <div class="fin-field">
                            <label>≈ % mensual</label>
                            <input type="number" name="owner_pct" id="finCrearOwnerPct" step="0.01" min="0" max="100" placeholder="0" value="{{ old('owner_pct', 0) }}"
                                   oninput="finSyncRet('finCrearOwnerAporte', 'finCrearOwnerPct', 'finCrearOwnerRet', 'pct'); finCrearPreview()">
                        </div>
                    </div>
                </div>

                <div class="fin-part">
                    <div class="fin-part-head">
                        <span class="fin-part-dot" style="background:#f59e0b"></span>
                        <span class="fin-part-name">Primer inversor externo</span>
                        <span class="fin-part-hint">opcional · puedes agregar más después</span>
                    </div>
                    <div class="fin-part-grid">
                        <div class="fin-field">
                            <label>Nombre</label>
                            <input type="text" name="inv_nombre" maxlength="120" placeholder="Inversor X" value="{{ old('inv_nombre') }}">
                        </div>
                        <div class="fin-field">
                            <label>Aporte</label>
                            <input type="number" name="inv_aporte" id="finCrearInvAporte" step="0.01" min="0" placeholder="500000" value="{{ old('inv_aporte') }}"
                                   oninput="finSyncRet('finCrearInvAporte', 'finCrearInvPct', 'finCrearInvRet', 'aporte'); finCrearPreview()">
                        </div>
                        <div class="fin-field">
                            <label>Retorno fijo $/mes</label>
                            <input type="number" name="inv_retorno" id="finCrearInvRet" step="0.01" min="0" placeholder="5000" value="{{ old('inv_retorno') }}"
                                   oninput="finSyncRet('finCrearInvAporte', 'finCrearInvPct', 'finCrearInvRet', 'ret'); finCrearPreview()">
                        </div>
                        <div class="fin-field">
                            <label>≈ % mensual</label>
                            <input type="number" name="inv_pct" id="finCrearInvPct" step="0.01" min="0" max="100" placeholder="10" value="{{ old('inv_pct') }}"
                                   oninput="finSyncRet('finCrearInvAporte', 'finCrearInvPct', 'finCrearInvRet', 'pct'); finCrearPreview()">
                        </div>
                    </div>
                </div>

                <div class="fin-split-preview" id="finCrearPreviewBox" style="grid-column:auto;gap:8px 18px">
                    <span>Inversión total: <b id="finCrearPrevCap">$0.00</b></span>
                    <span>Rendimiento / periodo: <b id="finCrearPrevRend">$0.00</b></span>
                    <span style="color:#7c3aed">Reinversión: <b id="finCrearPrevReinv">$0.00</b></span>
                    <span style="color:#d97706">Retornos: <b id="finCrearPrevRet">$0.00</b></span>
                </div>

                <div class="fin-field">
                    <label>Notas del acuerdo (opcional)</label>
                    <textarea name="notas" rows="2" maxlength="2000" placeholder="Condiciones, plazos, observaciones…">{{ old('notas') }}</textarea>
                </div>
            </div>

            {{-- Pestaña 2: vista previa gráfica de la inversión --}}
            <div class="fin-modal-body" id="finTabPane2" style="display:none">
                <div class="fin-prev-empty" id="finPrevVacio" style="display:none">
                    Captura la <b>tasa</b> y al menos un <b>aporte</b> en la pestaña "Datos del acuerdo"<br>para ver la representación gráfica de la inversión.
                </div>
                <div id="finPrevContenido" style="display:grid;gap:14px">
                    <div class="fin-prev-chips">
                        <div class="fin-prev-chip">
                            <div class="fin-prev-chip-k">Inversión total</div>
                            <div class="fin-prev-chip-v" id="finPrevChipCap">$0.00</div>
                        </div>
                        <div class="fin-prev-chip">
                            <div class="fin-prev-chip-k">Rendimiento / <span id="finPrevChipPer">semana</span></div>
                            <div class="fin-prev-chip-v" id="finPrevChipRend">$0.00</div>
                        </div>
                        <div class="fin-prev-chip">
                            <div class="fin-prev-chip-k">Se reinvierte cada periodo</div>
                            <div class="fin-prev-chip-v" style="color:#7c3aed" id="finPrevChipReinv">$0.00</div>
                        </div>
                        <div class="fin-prev-chip">
                            <div class="fin-prev-chip-k">Retorno a inversores / periodo</div>
                            <div class="fin-prev-chip-v" style="color:#d97706" id="finPrevChipRet">$0.00</div>
                        </div>
                    </div>
                    <div class="fin-prev-grid">
                        <div class="fin-prev-box">
                            <div class="fin-prev-title">¿Quién pone el capital?</div>
                            <div class="fin-prev-canvas"><canvas id="finChartCap"></canvas></div>
                        </div>
                        <div class="fin-prev-box">
                            <div class="fin-prev-title">Reparto de la tasa <span id="finPrevTasaLbl"></span></div>
                            <div class="fin-prev-canvas"><canvas id="finChartTasa"></canvas></div>
                        </div>
                    </div>
                    <div class="fin-prev-box">
                        <div class="fin-prev-title">Proyección a 12 <span id="finPrevProyLbl">semanas</span> con reinversión compuesta</div>
                        <div class="fin-prev-canvas-lg"><canvas id="finChartProy"></canvas></div>
                    </div>
                    <div class="fin-prev-note" id="finPrevNota"></div>
                </div>
            </div>

            <div class="fin-modal-footer">
                <button type="button" class="btn" style="background:#f3f4f6;color:var(--text2)" onclick="document.getElementById('finModalCrear').classList.remove('open')">Cancelar</button>
                <button type="button" class="btn" id="finBtnVistaPrev" style="background:#f5f3ff;color:#7c3aed" onclick="finTab(2)">Ver gráficas →</button>
                <button type="submit" class="btn btn-primary">Crear cuenta</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal: editar acuerdo ───────────────────────────────── --}}
<div class="fin-modal-overlay" id="finModalEditar">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <div class="fin-modal-title">Editar acuerdo · <span id="finEditNombre"></span></div>
            <button class="fin-modal-close" onclick="document.getElementById('finModalEditar').classList.remove('open')">×</button>
        </div>
        <form method="POST" id="finEditForm" action="">
            @csrf @method('PUT')
            <div class="fin-modal-body">
                <div class="fin-modal-row-3">
                    <div class="fin-field">
                        <label>Tasa por periodo %</label>
                        <input type="number" name="rendimiento_pct" id="finEditPct" step="0.01" min="0.01" max="100" required>
                    </div>
                    <div class="fin-field">
                        <label>Frecuencia</label>
                        <select name="frecuencia" id="finEditFrecuencia" required>
                            <option value="semanal">Semanal</option>
                            <option value="mensual">Mensual</option>
                        </select>
                    </div>
                    <div class="fin-field">
                        <label>Convenio (meses)</label>
                        <input type="number" name="plazo_meses" id="finEditPlazo" min="1" max="120" required>
                    </div>
                </div>
                <div class="fin-field">
                    <label>Fecha de inicio</label>
                    <input type="date" name="fecha_inicio" id="finEditFecha" required>
                </div>
                <div class="fin-field">
                    <label>Notas del acuerdo (opcional)</label>
                    <textarea name="notas" id="finEditNotas" rows="2" maxlength="2000"></textarea>
                </div>
                <div style="font-size:11px;color:var(--text3)">
                    El rendimiento mensual con la nueva tasa debe alcanzar para los retornos fijos activos (<span id="finEditRetActivos"></span>/mes).
                    El capital se modifica con aportes, retiros y salidas de inversores.
                </div>
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="btn" style="background:#f3f4f6;color:var(--text2)" onclick="document.getElementById('finModalEditar').classList.remove('open')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal: editar inversor ──────────────────────────────── --}}
<div class="fin-modal-overlay" id="finModalInversor">
    <div class="fin-modal" style="width:400px">
        <div class="fin-modal-header">
            <div class="fin-modal-title">Editar inversor</div>
            <button class="fin-modal-close" onclick="document.getElementById('finModalInversor').classList.remove('open')">×</button>
        </div>
        <form method="POST" id="finInvForm" action="">
            @csrf @method('PUT')
            <div class="fin-modal-body">
                <div class="fin-field">
                    <label>Nombre</label>
                    <input type="text" name="nombre" id="finInvNombre" required maxlength="120">
                </div>
                <div class="fin-field">
                    <label>Retorno fijo $/mes</label>
                    <input type="number" name="retorno_mensual" id="finInvRet" step="0.01" min="0" required
                           oninput="finSyncRet('finInvAporte', 'finInvPct', 'finInvRet', 'ret')">
                </div>
                <div class="fin-field">
                    <label>≈ % mensual de su aporte (<span id="finInvAporteLbl"></span>)</label>
                    <input type="number" name="pct_retorno" id="finInvPct" step="0.01" min="0" max="100"
                           oninput="finSyncRet('finInvAporte', 'finInvPct', 'finInvRet', 'pct')">
                </div>
                <input type="hidden" id="finInvAporte" value="0">
                <div style="font-size:11px;color:var(--text3)">El retorno es un monto fijo mensual (en cuentas semanales se prorratea entre 4 semanas) y se puede ajustar en cualquier momento; lo que no se retorna se reinvierte. Puedes capturarlo en $ o como % de su aporte.</div>
            </div>
            <div class="fin-modal-footer">
                <button type="button" class="btn" style="background:#f3f4f6;color:var(--text2)" onclick="document.getElementById('finModalInversor').classList.remove('open')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

@php
    // Datos por financiamiento para el preview JS del desglose de rendimiento
    $finDatos = $financiamientos->mapWithKeys(function ($f) {
        return [$f->id => [
            'tasa'       => (float) $f->rendimiento_pct,
            'ppm'        => $f->periodos_por_mes,
            'inversores' => $f->inversoresActivos()
                ->filter(fn($i) => $i->retorno_mensual > 0)
                ->map(fn($i) => ['nombre' => $i->nombre, 'ret' => (float) $i->retorno_mensual, 'aporte' => (float) $i->aporte])
                ->values()->all(),
        ]];
    });
@endphp

<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
const finFmt   = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
const finFmt0  = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
const finDatos = @json($finDatos);

function finToggle(id) {
    document.getElementById('finDetail' + id).classList.toggle('open');
    document.getElementById('finChevron' + id).classList.toggle('open');
}

function finRendPreview(id) {
    const datos = finDatos[id];
    if (!datos) return;
    const monto = parseFloat(document.getElementById('finRendMonto' + id).value) || 0;
    // Retorno fijo por inversor: su monto MENSUAL pactado, prorrateado al periodo
    // de la cuenta (÷4 en cuentas semanales). Si el cobro no alcanza, se escala.
    let totalFijo = 0;
    const fijos = datos.inversores.map(inv => {
        const f = Math.round(inv.ret / (datos.ppm || 1) * 100) / 100;
        totalFijo += f;
        return { nombre: inv.nombre, fijo: f };
    });
    const escala = (totalFijo > 0 && totalFijo > monto) ? monto / totalFijo : 1;
    let retornado = 0;
    const partes = fijos.map(x => {
        const r = Math.round(x.fijo * escala * 100) / 100;
        retornado += r;
        return '→ ' + x.nombre + ': <b>' + finFmt.format(r) + '</b>';
    });
    document.getElementById('finPrevReinv' + id).textContent = finFmt.format(Math.max(0, Math.round((monto - retornado) * 100) / 100));
    document.getElementById('finPrevInvList' + id).innerHTML = partes.join(' · ');
}

function finUsarEsperado(id, esperado) {
    const input = document.getElementById('finRendMonto' + id);
    input.value = esperado.toFixed(2);
    input.dispatchEvent(new Event('input'));
}

/*
 * Mantiene sincronizados el retorno fijo en $/mes (dato del acuerdo) y su %
 * mensual equivalente sobre el aporte. `source` indica qué campo editó el
 * usuario: 'ret' actualiza el %, 'pct' actualiza el $, y 'aporte' recalcula
 * dando prioridad al $ si ya está capturado.
 */
function finSyncRet(aporteId, pctId, retId, source) {
    const aporteEl = document.getElementById(aporteId);
    const pctEl    = document.getElementById(pctId);
    const retEl    = document.getElementById(retId);
    const aporte   = parseFloat(aporteEl.value) || 0;
    if (source === 'pct' || (source === 'aporte' && retEl.value === '' && pctEl.value !== '')) {
        const pct = parseFloat(pctEl.value) || 0;
        retEl.value = aporte > 0 ? (Math.round(aporte * pct) / 100).toFixed(2) : retEl.value;
    } else if (source === 'ret' || source === 'aporte') {
        const ret = parseFloat(retEl.value) || 0;
        if (aporte > 0) pctEl.value = (Math.round(ret / aporte * 10000) / 100).toFixed(2);
    }
}

// Retorno mensual fijo en $ de una participación del modal crear (campo $ directo)
function finCrearRetMes(retInputId) {
    return parseFloat(document.getElementById(retInputId).value) || 0;
}

function finCrearPreview() {
    const capO = parseFloat(document.getElementById('finCrearOwnerAporte').value) || 0;
    const capI = parseFloat(document.getElementById('finCrearInvAporte').value) || 0;
    const tasa = parseFloat(document.getElementById('finCrearPct').value) || 0;
    const frec = document.querySelector('#finModalCrear select[name="frecuencia"]').value;
    const ppm  = frec === 'semanal' ? 4 : 1;
    const cap  = capO + capI;
    const rend = cap * tasa / 100;
    // Retornos fijos: monto MENSUAL pactado, prorrateado al periodo
    const ret  = (finCrearRetMes('finCrearOwnerRet') + finCrearRetMes('finCrearInvRet')) / ppm;
    document.getElementById('finCrearPrevCap').textContent   = finFmt.format(cap);
    document.getElementById('finCrearPrevRend').textContent  = finFmt.format(rend);
    document.getElementById('finCrearPrevReinv').textContent = finFmt.format(Math.max(0, rend - ret));
    document.getElementById('finCrearPrevRet').textContent   = finFmt.format(ret);
}

/* ── Pestañas del modal crear + vista previa gráfica ─────────── */
const finCharts = {};

function finTab(n) {
    document.getElementById('finTabPane1').style.display = n === 1 ? '' : 'none';
    document.getElementById('finTabPane2').style.display = n === 2 ? '' : 'none';
    document.getElementById('finTabBtn1').classList.toggle('active', n === 1);
    document.getElementById('finTabBtn2').classList.toggle('active', n === 2);
    document.getElementById('finBtnVistaPrev').style.display = n === 2 ? 'none' : '';
    // Render diferido un frame: así Chart.js mide el ancho real del panel ya visible
    if (n === 2) requestAnimationFrame(() => finPreviewRender());
}

function finChartMake(key, canvasId, config) {
    if (finCharts[key]) finCharts[key].destroy();
    finCharts[key] = new Chart(document.getElementById(canvasId), config);
}

function finPreviewRender() {
    const capO   = parseFloat(document.getElementById('finCrearOwnerAporte').value) || 0;
    const capI   = parseFloat(document.getElementById('finCrearInvAporte').value) || 0;
    const tasa   = parseFloat(document.getElementById('finCrearPct').value) || 0;
    const retOm  = finCrearRetMes('finCrearOwnerRet');
    const retIm  = finCrearRetMes('finCrearInvRet');
    const frec   = document.querySelector('#finModalCrear select[name="frecuencia"]').value;
    const nomO   = document.querySelector('#finModalCrear input[name="owner_nombre"]').value.trim() || 'Owner';
    const nomI   = document.querySelector('#finModalCrear input[name="inv_nombre"]').value.trim() || 'Inversor externo';
    const cap    = capO + capI;
    const perLbl = frec === 'mensual' ? 'mes' : 'semana';
    const perPl  = frec === 'mensual' ? 'meses' : 'semanas';

    // Sin datos suficientes → mensaje en vez de gráficas vacías
    const vacio = cap <= 0 || tasa <= 0;
    document.getElementById('finPrevVacio').style.display     = vacio ? '' : 'none';
    document.getElementById('finPrevContenido').style.display = vacio ? 'none' : 'grid';
    if (vacio) return;

    // Retornos FIJOS: monto MENSUAL pactado (constante), prorrateado al
    // periodo de la cuenta (÷4 si es semanal). El resto se reinvierte compuesto.
    const ppm   = frec === 'semanal' ? 4 : 1;
    const rend  = cap * tasa / 100;
    const retO  = retOm / ppm;
    const retI  = retIm / ppm;
    const retFijo = retO + retI;
    const reinv0  = Math.max(0, rend - retFijo);

    // Chips resumen
    document.getElementById('finPrevChipCap').textContent   = finFmt.format(cap);
    document.getElementById('finPrevChipPer').textContent   = perLbl;
    document.getElementById('finPrevChipRend').textContent  = finFmt.format(rend);
    document.getElementById('finPrevChipReinv').textContent = finFmt.format(reinv0);
    document.getElementById('finPrevChipRet').textContent   = finFmt.format(retFijo);
    document.getElementById('finPrevTasaLbl').textContent   = 'del ' + tasa + '% ' + frec;
    document.getElementById('finPrevProyLbl').textContent   = perPl;

    const tooltipMoney = { callbacks: { label: c => ' ' + (c.dataset.label ? c.dataset.label + ': ' : '') + finFmt.format(c.parsed.y ?? c.parsed) } };
    const legendCfg    = { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, font: { size: 10, family: 'Sora' }, padding: 10 } };

    // 1) Donut: composición del capital
    const capLabels = [], capData = [], capColors = [];
    if (capO > 0) { capLabels.push(nomO + ' (owner)'); capData.push(capO); capColors.push('#7c3aed'); }
    if (capI > 0) { capLabels.push(nomI);              capData.push(capI); capColors.push('#f59e0b'); }
    finChartMake('cap', 'finChartCap', {
        type: 'doughnut',
        data: { labels: capLabels, datasets: [{ data: capData, backgroundColor: capColors, borderWidth: 2, borderColor: '#fbfbfa' }] },
        options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: legendCfg, tooltip: tooltipMoney } },
    });

    // 2) Donut: reparto del rendimiento en pesos (retornos fijos + reinversión)
    const tLabels = [], tData = [], tColors = [];
    if (reinv0 > 0) { tLabels.push('Reinversión');                                  tData.push(reinv0); tColors.push('#7c3aed'); }
    if (retO > 0)   { tLabels.push(nomO + ' (' + finFmt.format(retOm) + ' fijos/mes)'); tData.push(retO); tColors.push('#10b981'); }
    if (retI > 0)   { tLabels.push(nomI + ' (' + finFmt.format(retIm) + ' fijos/mes)'); tData.push(retI); tColors.push('#f59e0b'); }
    finChartMake('tasa', 'finChartTasa', {
        type: 'doughnut',
        data: { labels: tLabels, datasets: [{ data: tData, backgroundColor: tColors, borderWidth: 2, borderColor: '#fbfbfa' }] },
        options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: legendCfg,
            tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + finFmt.format(c.parsed) + ' por ' + perLbl } } } },
    });

    // 3) Proyección 12 periodos: retornos fijos constantes (owner e inversor por
    //    separado) + capital compuesto con lo reinvertido
    const labels = [], serieCap = [], serieRetO = [], serieRetI = [];
    let c = cap, acumO = 0, acumI = 0;
    for (let n = 0; n <= 12; n++) {
        labels.push(n === 0 ? 'Inicio' : n);
        serieCap.push(Math.round(c * 100) / 100);
        acumO += retO; acumI += retI;
        serieRetO.push(Math.round(acumO * 100) / 100);
        serieRetI.push(Math.round(acumI * 100) / 100);
        c = c + Math.max(0, c * tasa / 100 - retFijo); // se reinvierte el rendimiento menos los retornos fijos
    }
    const proyDatasets = [
        { label: 'Capital en inversión', data: serieCap, borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.08)', fill: true, tension: .3, pointRadius: 2, borderWidth: 2 },
    ];
    if (retO > 0) proyDatasets.push({ label: 'Retorno de ' + nomO + ' (acum.)', data: serieRetO, borderColor: '#10b981', backgroundColor: 'transparent', tension: .3, pointRadius: 2, borderWidth: 2, borderDash: [5, 4] });
    if (retI > 0) proyDatasets.push({ label: 'Retorno de ' + nomI + ' (acum.)', data: serieRetI, borderColor: '#f59e0b', backgroundColor: 'transparent', tension: .3, pointRadius: 2, borderWidth: 2, borderDash: [5, 4] });
    finChartMake('proy', 'finChartProy', {
        type: 'line',
        data: { labels, datasets: proyDatasets },
        options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
            plugins: { legend: legendCfg, tooltip: tooltipMoney },
            scales: {
                y: { ticks: { font: { size: 9, family: 'Sora' }, callback: v => finFmt0.format(v) }, grid: { color: 'rgba(15,22,35,.05)' } },
                x: { ticks: { font: { size: 9, family: 'Sora' } }, grid: { display: false } },
            } },
    });

    const capFinal = serieCap[12];
    let partesRet = [];
    if (retO > 0) partesRet.push(nomO + ' habría recibido <b>' + finFmt.format(serieRetO[12]) + '</b>');
    if (retI > 0) partesRet.push(nomI + ' habría recibido <b>' + finFmt.format(serieRetI[12]) + '</b>');
    document.getElementById('finPrevNota').innerHTML =
        'Cada participante recibe un retorno <b>fijo</b>: un monto <b>mensual</b> pactado sobre su inversión' +
        (ppm > 1 ? ', prorrateado entre 4 semanas (' + finFmt.format(retFijo) + ' por semana)' : '') +
        '; el resto del rendimiento se reinvierte. En 12 ' + perPl + ' el capital llegaría a <b>' + finFmt.format(capFinal) + '</b>' +
        (partesRet.length ? ' y ' + partesRet.join(', ') : '') +
        '. Es una proyección estimada, no una garantía.';
}

function finOpenEditar(data) {
    document.getElementById('finEditForm').action        = '{{ url('owner/financiamientos') }}/' + data.id;
    document.getElementById('finEditNombre').textContent = data.nombre;
    document.getElementById('finEditPct').value          = data.rendimiento_pct;
    document.getElementById('finEditFrecuencia').value   = data.frecuencia;
    document.getElementById('finEditPlazo').value        = data.plazo_meses;
    document.getElementById('finEditFecha').value        = data.fecha_inicio;
    document.getElementById('finEditNotas').value        = data.notas || '';
    document.getElementById('finEditRetActivos').textContent = finFmt.format(data.fijos_mensuales || 0);
    document.getElementById('finModalEditar').classList.add('open');
}

function finOpenEditInversor(data) {
    document.getElementById('finInvForm').action  = '{{ url('owner/financiamientos') }}/' + data.fid + '/inversores/' + data.iid;
    document.getElementById('finInvNombre').value = data.nombre;
    document.getElementById('finInvRet').value    = (data.retorno_mensual || 0).toFixed(2);
    document.getElementById('finInvPct').value    = data.pct_retorno;
    document.getElementById('finInvAporte').value = data.aporte || 0;
    document.getElementById('finInvAporteLbl').textContent = finFmt.format(data.aporte || 0);
    document.getElementById('finModalInversor').classList.add('open');
}

// Cerrar modales al hacer click fuera
document.querySelectorAll('.fin-modal-overlay').forEach(ov => {
    ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('open'); });
});

// Reabrir tarjeta tras una acción
@if(session('open_financiamiento'))
    finToggle({{ (int) session('open_financiamiento') }});
    document.getElementById('finCard{{ (int) session('open_financiamiento') }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
@endif

// Reabrir modal crear si hubo errores de validación
@if($errors->any() && old('admin_id') !== null)
    document.getElementById('finModalCrear').classList.add('open');
@endif

finCrearPreview();

/* ═════════════ Tutorial guiado de primera vez ═════════════ */
const finTourPasos = [
    {
        el: null, emoji: '🎉',
        title: '¡Nueva función: Inversiones externas!',
        text: 'Aquí administras el dinero que tú y otros inversores ponen a trabajar con tus administradores. Te mostramos cómo funciona en unos pasos — toma menos de un minuto.',
    },
    {
        el: '.fin-kpi-grid',
        title: '1. Tu resumen global',
        text: 'Estas tarjetas suman todas tus cuentas: el capital invertido, lo que esperas ganar por periodo, y cuánto se ha reinvertido o pagado a inversores.',
    },
    {
        el: '#finBtnNueva',
        title: '2. Crea una cuenta de inversión',
        text: 'Con este botón registras el acuerdo: eliges al administrador, la tasa (ej. 18% semanal), el convenio y los aportes — el tuyo y el del inversor externo.',
    },
    {
        el: '__lista__',
        title: '3. Tus cuentas de inversión',
        text: 'Cada cuenta es una tarjeta. Haz clic sobre ella para abrirla: verás los inversores con su retorno mensual fijo, el reparto de la tasa y el botón para registrar el rendimiento de cada periodo.',
    },
    {
        el: 'a.nav-item[href*="financiamientos"]',
        title: '4. Siempre a la mano',
        text: 'Esta sección vive en el menú lateral como "Financiamientos". Entra cuando quieras para registrar rendimientos, aportes o retiros.',
    },
    {
        el: null, emoji: '✅',
        title: '¡Listo!',
        text: 'Eso es todo. Crea tu primera cuenta cuando quieras. Si necesitas repetir este tutorial, presiona el botón "?" en la parte superior.',
    },
];

let finTourIdx = 0, finTourEls = null, finTourActivo = false;

function finTourTargets() {
    // Resuelve los selectores a elementos reales; omite pasos cuyo objetivo no exista
    return finTourPasos.map(p => {
        if (p.el === null) return { ...p, target: null };
        const sel = p.el === '__lista__' ? (document.querySelector('.fin-card') ? '.fin-card' : '.fin-empty') : p.el;
        return { ...p, target: document.querySelector(sel) };
    }).filter(p => p.el === null || p.target);
}

function finTourStart(manual = false) {
    if (finTourActivo) return;
    finTourEls = finTourTargets();
    if (finTourEls.length === 0) return;
    finTourIdx = 0; finTourActivo = true;

    const spot = document.createElement('div'); spot.className = 'tour-spot'; spot.id = 'tourSpot';
    const blocker = document.createElement('div'); blocker.className = 'tour-blocker'; blocker.id = 'tourBlocker';
    const pop = document.createElement('div'); pop.className = 'tour-pop'; pop.id = 'tourPop';
    document.body.append(spot, blocker, pop);

    blocker.addEventListener('wheel', e => e.preventDefault(), { passive: false });
    blocker.addEventListener('touchmove', e => e.preventDefault(), { passive: false });
    document.addEventListener('keydown', finTourKey);
    window.addEventListener('resize', finTourReposiciona);

    finTourRender();
}

function finTourKey(e) {
    if (e.key === 'Escape') finTourFin();
    if (e.key === 'ArrowRight' || e.key === 'Enter') { e.preventDefault(); finTourSig(); }
    if (e.key === 'ArrowLeft' && finTourIdx > 0) finTourRender(--finTourIdx);
}

function finTourRender(i = finTourIdx) {
    const paso = finTourEls[i];
    const spot = document.getElementById('tourSpot');
    const pop  = document.getElementById('tourPop');
    const n    = finTourEls.length;

    const dots = finTourEls.map((_, d) => `<span class="tour-dot ${d === i ? 'act' : ''}"></span>`).join('');
    const esUltimo = i === n - 1;
    pop.innerHTML = `
        <div class="tour-step-chip">Paso ${i + 1} de ${n}</div>
        ${paso.emoji ? `<div class="tour-emoji">${paso.emoji}</div>` : ''}
        <div class="tour-title">${paso.title}</div>
        <div class="tour-text">${paso.text}</div>
        <div class="tour-dots">${dots}</div>
        <div class="tour-btns">
            ${!esUltimo ? '<button type="button" class="tour-skip" onclick="finTourFin()">Saltar tutorial</button>' : ''}
            ${i > 0 ? '<button type="button" class="tour-prev" onclick="finTourRender(--finTourIdx)">Anterior</button>' : ''}
            <button type="button" class="tour-next" onclick="${esUltimo ? 'finTourFin()' : 'finTourSig()'}" ${esUltimo ? 'style="margin-left:auto"' : ''}>
                ${esUltimo ? '¡Entendido!' : 'Siguiente'}
            </button>
        </div>`;

    pop.classList.remove('show');

    if (paso.target === null) {
        spot.classList.add('tour-center');
        pop.classList.add('tour-pop-center');
        pop.style.left = pop.style.top = '';
        requestAnimationFrame(() => requestAnimationFrame(() => pop.classList.add('show')));
    } else {
        spot.classList.remove('tour-center');
        pop.classList.remove('tour-pop-center');
        paso.target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => { finTourPosiciona(paso.target, spot, pop); pop.classList.add('show'); }, 340);
    }
}

function finTourPosiciona(target, spot, pop) {
    const r = target.getBoundingClientRect(), pad = 8;
    spot.style.top    = (r.top - pad) + 'px';
    spot.style.left   = (r.left - pad) + 'px';
    spot.style.width  = (r.width + pad * 2) + 'px';
    spot.style.height = (r.height + pad * 2) + 'px';

    const pw = 340, ph = pop.offsetHeight || 220, m = 14;
    let left = Math.min(Math.max(r.left, m), window.innerWidth - pw - m);
    let top  = r.bottom + pad + m;
    if (top + ph > window.innerHeight - m) top = Math.max(m, r.top - pad - m - ph);
    pop.style.left = left + 'px';
    pop.style.top  = top + 'px';
}

function finTourReposiciona() {
    if (!finTourActivo) return;
    const paso = finTourEls[finTourIdx];
    if (paso && paso.target) finTourPosiciona(paso.target, document.getElementById('tourSpot'), document.getElementById('tourPop'));
}

function finTourSig() {
    if (!finTourActivo) return;
    if (finTourIdx < finTourEls.length - 1) finTourRender(++finTourIdx);
    else finTourFin();
}

function finTourFin() {
    if (!finTourActivo) return;
    finTourActivo = false;
    ['tourSpot', 'tourBlocker', 'tourPop'].forEach(id => document.getElementById(id)?.remove());
    document.removeEventListener('keydown', finTourKey);
    window.removeEventListener('resize', finTourReposiciona);

    // Marcarlo como visto para este usuario (no vuelve a salir solo)
    fetch('{{ route('owner.financiamientos.tourVisto') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    }).catch(() => {});
}

// Arranque automático solo la primera vez (esperando la animación de bienvenida si existe)
@if($mostrarTour)
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => finTourStart(), document.getElementById('welcomeOverlay') ? 2300 : 500);
});
@endif
</script>

@endsection
