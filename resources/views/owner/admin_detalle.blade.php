@extends('layouts.app')

@section('title', 'Administrador — ' . ($admin->alias ?: ($admin->nombre ?: $admin->usuario)))

@push('styles')
<style>
/* ── Paleta de datos (validada para daltonismo) ─────────────
   azul = desembolsos / capital en la calle · verde = cobros / positivo
   rojo = negativo / riesgo · ámbar = atención                        */
:root{
    --c-cob:#059669;--c-cob-bg:#ecfdf5;--c-cob-tx:#047857;--c-cob-track:#d1fae5;
    --c-des:#2a78d6;--c-des-bg:#eff6ff;
    --c-neg:#dc2626;--c-neg-bg:#fef2f2;--c-neg-tx:#b91c1c;--c-neg-track:#fee2e2;
    --c-warn:#d97706;--c-warn-bg:#fffbeb;--c-warn-tx:#b45309;--c-warn-track:#fef3c7;
    --c-muted:#6b7280;--c-hair:#e9e8e2;--c-axis:#c3c2b7;
}

/* ── Breadcrumb / header ────────────────────────────────── */
.ad-bc{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text2);margin-bottom:14px}
.ad-bc a{color:var(--text2);text-decoration:none;transition:color .15s}
.ad-bc a:hover{color:var(--accent-hover)}
.ad-bc-sep{opacity:.5}
.ad-bc-cur{color:var(--text);font-weight:600}

.ad-hdr{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 22px;margin-bottom:16px;display:flex;align-items:center;gap:18px;flex-wrap:wrap}
.ad-avatar{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:19px;font-weight:800;color:#fff;flex-shrink:0;letter-spacing:-.02em}
.ad-hdr-info{flex:1;min-width:0;display:flex;flex-direction:column;gap:6px}
.ad-hdr-name{font-size:20px;font-weight:800;letter-spacing:-.03em;color:var(--text);line-height:1.1;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.ad-hdr-meta{display:flex;gap:16px;flex-wrap:wrap;align-items:center;font-size:12px;color:var(--text2)}
.ad-hdr-meta span{display:inline-flex;align-items:center;gap:5px}
.ad-hdr-meta svg{width:12px;height:12px}
.ad-badge{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700}
.ad-badge-dot{width:6px;height:6px;border-radius:50%;background:currentColor}
.ad-badge-green{background:var(--c-cob-bg);color:var(--c-cob-tx)}
.ad-badge-red{background:var(--c-neg-bg);color:var(--c-neg-tx)}
.ad-hdr-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.ad-hdr-actions .btn svg{width:12px;height:12px}

/* ── Tarjetas y tipografía común ─────────────────────────── */
.ad-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:22px 24px}
.ad-card-title{font-size:15px;font-weight:700;letter-spacing:-.01em;color:var(--text);margin:0}
.ad-card-sub{font-size:12px;color:var(--text2);margin-top:3px}
.ad-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text2)}
.ad-muted{font-size:11px;color:var(--c-muted)}
.ad-strong{color:var(--text);font-weight:700}
.ad-num{font-variant-numeric:tabular-nums}
.ad-meter{height:6px;border-radius:99px;overflow:hidden;background:#e5e7eb}
.ad-meter>div{height:100%;border-radius:99px;background:#9aa3b2;transition:width .4s ease}
.ad-sem-ok .ad-meter,.ad-meter.ad-sem-ok{background:var(--c-cob-track)}
.ad-sem-ok .ad-meter>div,.ad-meter.ad-sem-ok>div{background:var(--c-cob)}
.ad-sem-warn .ad-meter,.ad-meter.ad-sem-warn{background:var(--c-warn-track)}
.ad-sem-warn .ad-meter>div,.ad-meter.ad-sem-warn>div{background:var(--c-warn)}
.ad-sem-bad .ad-meter,.ad-meter.ad-sem-bad{background:var(--c-neg-track)}
.ad-sem-bad .ad-meter>div,.ad-meter.ad-sem-bad>div{background:var(--c-neg)}
.ad-sem-ok .ad-sem-tx{color:var(--c-cob-tx)}
.ad-sem-warn .ad-sem-tx{color:var(--c-warn-tx)}
.ad-sem-bad .ad-sem-tx{color:var(--c-neg-tx)}
.ad-sem-neutral .ad-sem-tx{color:var(--text2)}
.ad-key{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--text2)}
.ad-key i{display:inline-block;width:10px;height:10px;border-radius:3px}
.ad-key i.line{width:12px;height:3px;border-radius:2px}
.ad-dot{display:inline-block;width:9px;height:9px;border-radius:3px;flex-shrink:0}

/* ── Posición (hero) ─────────────────────────────────────── */
.ad-hero{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:32px;margin-bottom:16px}
.ad-hero-main{display:flex;flex-direction:column;gap:10px;min-width:0}
.ad-hero-num{font-size:54px;font-weight:800;letter-spacing:-.04em;line-height:1;color:var(--text)}
.ad-hero-row{display:flex;align-items:baseline;gap:14px;flex-wrap:wrap}
.ad-chip{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:12px;font-weight:600}
.ad-hero-facts{display:flex;align-items:center;gap:18px;flex-wrap:wrap;margin-top:4px;font-size:12px;color:var(--text2)}
.ad-hero-facts span{display:inline-flex;align-items:center;gap:6px}
.ad-hero-facts svg{width:13px;height:13px}
.ad-hero-side{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;border-left:1px solid var(--border);padding-left:32px}
.ad-fact{display:flex;flex-direction:column;gap:5px}
.ad-fact-val{display:flex;align-items:baseline;gap:6px}
.ad-fact-val b{font-size:26px;font-weight:800;letter-spacing:-.03em;line-height:1;color:var(--text)}
.ad-fact-val span{font-size:12px;color:var(--c-muted)}
.ad-fact-estado{display:inline-flex;align-items:center;gap:7px;font-size:20px;font-weight:800;letter-spacing:-.02em;color:var(--text)}
.ad-fact-estado svg{width:18px;height:18px}

/* ── KPIs ────────────────────────────────────────────────── */
.ad-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:16px}
.ad-kpi{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;display:flex;flex-direction:column;gap:6px;min-width:0}
.ad-kpi-value{font-size:27px;font-weight:800;letter-spacing:-.03em;line-height:1;color:var(--text)}
.ad-kpi-delta{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#374151}
.ad-kpi-delta svg{width:12px;height:12px;flex-shrink:0}
.ad-kpi-delta.up{color:var(--c-cob-tx)}
.ad-kpi-delta.down{color:var(--c-neg-tx)}
.ad-kpi-sub{font-size:12px;color:var(--c-muted)}
.ad-kpi-meter{display:flex;align-items:center;gap:8px}
.ad-kpi-meter .ad-meter{flex:1;height:5px}

/* ── Alertas ─────────────────────────────────────────────── */
.ad-alerts{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:12px;margin-bottom:16px}
.ad-alert{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;font-size:12px;color:var(--text);border:1px solid}
.ad-alert svg{width:16px;height:16px;flex-shrink:0}
.ad-alert-danger{background:var(--c-neg-bg);border-color:#fecaca}
.ad-alert-danger svg{color:var(--c-neg-tx)}
.ad-alert-warning{background:var(--c-warn-bg);border-color:#fde68a}
.ad-alert-warning svg{color:var(--c-warn-tx)}
.ad-alert-success{background:var(--c-cob-bg);border-color:#a7f3d0}
.ad-alert-success svg{color:var(--c-cob-tx)}

/* ── Flujo de capital ────────────────────────────────────── */
.ad-flujo{margin-bottom:16px}
.ad-flujo-hd{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:14px}
.ad-flujo-ctl{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.ad-seg{display:inline-flex;border:1px solid rgba(15,22,35,.1);border-radius:9px;padding:3px;gap:2px}
.ad-seg button{height:28px;padding:0 12px;border:none;border-radius:7px;background:transparent;color:var(--text2);font-size:12px;font-weight:600;font-family:var(--font);cursor:pointer;transition:background .15s,color .15s}
.ad-seg button:hover{background:#f3f4f6}
.ad-seg button.is-active{background:var(--text);color:#fff}
.ad-flujo-body{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:24px}
.ad-plot-hd{display:flex;align-items:center;justify-content:space-between;height:20px;gap:12px}
.ad-plot-hd b{font-size:11px;font-weight:700;color:#374151}
.ad-plot-keys{display:flex;align-items:center;gap:14px}
.ad-plot{position:relative;width:100%}
.ad-read{display:flex;flex-direction:column;gap:12px;border-left:1px solid var(--border);padding-left:24px}
.ad-read-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.ad-read-box{padding:10px 12px;border-radius:10px;display:flex;flex-direction:column;gap:2px}
.ad-read-box small{font-size:11px;font-weight:600}
.ad-read-box b{font-size:24px;font-weight:800;letter-spacing:-.03em;line-height:1;color:var(--text)}
.ad-read-box em{font-style:normal;font-size:11px}
.ad-read-box.pos{background:var(--c-cob-bg)}.ad-read-box.pos small,.ad-read-box.pos em{color:#065f46}
.ad-read-box.neg{background:var(--c-neg-bg)}.ad-read-box.neg small,.ad-read-box.neg em{color:#991b1b}
.ad-days-bar{display:flex;height:8px;border-radius:99px;overflow:hidden;gap:2px}
.ad-days-bar div{transition:width .4s ease}
.ad-read-rows{display:flex;flex-direction:column;gap:8px;margin-top:2px}
.ad-read-rows>div{display:flex;justify-content:space-between;align-items:baseline;gap:8px;font-size:12px}
.ad-read-rows>div>span:first-child{color:var(--text2)}
.ad-read-rows>div>span:last-child{text-align:right}
.ad-streak{margin-top:auto;padding:10px 12px;border-radius:10px;background:#f9fafb;border:1px solid rgba(15,22,35,.06);display:flex;align-items:center;gap:10px}
.ad-streak svg{width:16px;height:16px;flex-shrink:0;color:var(--c-cob-tx)}
.ad-streak b{display:block;font-size:12px;font-weight:700;color:var(--text)}
.ad-streak small{font-size:11px;color:var(--c-muted)}

/* ── Filas de dos tarjetas ───────────────────────────────── */
.ad-row-7-5{display:grid;grid-template-columns:minmax(0,7fr) minmax(0,5fr);gap:16px;margin-bottom:16px}
.ad-row-6-6{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px}
.ad-card.col{display:flex;flex-direction:column;gap:14px}

/* Cobranza */
.ad-efi-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.ad-efi{display:flex;flex-direction:column;gap:6px;padding:12px 14px;border-radius:10px;background:#f9fafb;border:1px solid rgba(15,22,35,.06)}
.ad-efi-hd{display:flex;justify-content:space-between;align-items:baseline;gap:8px}
.ad-efi-tag{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700}
.ad-efi-tag svg{width:12px;height:12px}
.ad-efi-val{display:flex;align-items:baseline;gap:8px}
.ad-efi-val b{font-size:30px;font-weight:800;letter-spacing:-.03em;line-height:1;color:var(--text)}
.ad-efi-val span{font-size:12px;color:var(--c-muted)}
.ad-cols{display:grid;gap:10px;align-items:end;border-bottom:1px solid var(--c-hair)}
.ad-col{position:relative;height:100%;display:flex;align-items:flex-end;justify-content:center;gap:2px}
.ad-col>i{display:block;border-radius:3px 3px 0 0}
.ad-col>em{position:absolute;left:0;right:0;text-align:center;font-style:normal;font-size:10px;font-weight:600;white-space:nowrap}
.ad-cols-x{display:grid;gap:10px;font-size:10px;color:var(--c-muted);text-align:center}
.ad-inline-facts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:auto}
.ad-inline-facts>div{display:flex;align-items:center;gap:10px;font-size:12px;color:var(--text2)}
.ad-inline-facts svg{width:15px;height:15px;flex-shrink:0}

/* Riesgo */
.ad-par-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.ad-par{display:flex;flex-direction:column;gap:5px}
.ad-par b{font-size:22px;font-weight:800;letter-spacing:-.03em;line-height:1;color:var(--text)}
.ad-par .ad-meter{height:5px}
.ad-stack{display:flex;gap:2px;overflow:hidden}
.ad-stack div{min-width:0}
.ad-stack div:first-child{border-radius:4px 0 0 4px}
.ad-stack div:last-child{border-radius:0 4px 4px 0}
.ad-stack-legend{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;font-size:11px;color:var(--text2)}
.ad-stack-legend>span{display:flex;flex-direction:column;gap:1px}

/* Composición */
.ad-comp-rows{display:flex;flex-direction:column;font-size:12px}
.ad-comp-row{display:grid;grid-template-columns:1.4fr .6fr 1fr 1.3fr;gap:8px;padding:7px 0;border-bottom:1px solid #f0f0ec;align-items:center}
.ad-comp-row:last-child{border-bottom:none}
.ad-comp-row.head{padding:6px 0;border-bottom:1px solid var(--c-hair);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text2)}
.ad-comp-row>span:nth-child(n+2){text-align:right}
.ad-comp-row>span:first-child{display:inline-flex;align-items:center;gap:7px}
.ad-mini-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:auto;padding-top:12px;border-top:1px solid var(--c-hair)}
.ad-mini-stats>div{display:flex;flex-direction:column;gap:2px}
.ad-mini-stats b{font-size:15px;font-weight:700;color:var(--text)}
.ad-mini-stats small{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text2)}

/* Rendimiento */
.ad-rend-row{display:grid;grid-template-columns:150px minmax(0,1fr) 64px;gap:10px;align-items:center;font-size:12px}
.ad-rend-row>span:first-child{color:var(--text2)}
.ad-rend-row>span:last-child{text-align:right;font-weight:700}
.ad-rend-bar{height:14px;width:100%}
.ad-rend-bar>div{height:100%;border-radius:4px}

/* Tablas nuevas */
.ad-table-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;display:flex;flex-direction:column}
.ad-table-card .ad-table-head{padding:16px 20px}
.ad-days-pill{display:inline-flex;align-items:center;gap:6px;font-weight:700}
.ad-days-pill i{display:inline-block;width:8px;height:8px;border-radius:2px}
.ad-prox-row{display:grid;grid-template-columns:64px minmax(0,1fr) 84px;gap:12px;align-items:center;padding:8px 0;border-bottom:1px solid #f0f0ec;font-size:12px}
.ad-prox-row:last-child{border-bottom:none}
.ad-prox-day{display:flex;flex-direction:column;line-height:1.1}
.ad-prox-day small{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text2)}
.ad-prox-day b{font-size:15px;font-weight:800;color:var(--text)}
.ad-prox-bar{height:8px;border-radius:99px;background:#eef2f7;overflow:hidden}
.ad-prox-bar>div{height:100%;border-radius:99px;background:var(--c-des)}
.ad-prox-row.hoy .ad-prox-day b{color:var(--c-des)}

/* ── Secciones existentes (detalle expandible, tablas, notas) ── */
.ad-section{margin-bottom:20px}
.ad-section-title{font-size:13px;font-weight:700;color:var(--text);letter-spacing:-.01em;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.ad-section-badge{padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600}
.ad-table-wrap{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:20px}
.ad-table-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.ad-tbl{width:100%;border-collapse:collapse;font-size:13px}
.ad-tbl th{padding:9px 14px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text2);background:#f9fafb;border-bottom:1px solid var(--border);text-align:left;white-space:nowrap}
.ad-tbl td{padding:11px 14px;border-bottom:1px solid var(--border);vertical-align:middle}
.ad-tbl tr:last-child td{border-bottom:none}
.ad-tbl tr:hover td{background:#f9fafb}
.ad-tbl th.r,.ad-tbl td.r{text-align:right}
.ad-tbl-wrap{overflow-x:auto}
.ad-empty{padding:40px 24px;text-align:center;color:var(--text2);font-size:13px}
.ad-mono{font-family:var(--font-mono);font-size:12px}

.pill{display:inline-flex;align-items:center;gap:3px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}
.pill-blue{background:var(--c-des-bg);color:#1d4ed8}
.pill-red{background:var(--c-neg-bg);color:var(--c-neg-tx)}
.pill-yellow{background:var(--c-warn-bg);color:var(--c-warn-tx)}
.pill-green{background:var(--c-cob-bg);color:var(--c-cob-tx)}
.pill-gray{background:#f3f4f6;color:#6b7280}
.pill-purple{background:#f5f3ff;color:#7c3aed}

.ad-timeline{position:relative;padding-left:24px}
.ad-timeline::before{content:'';position:absolute;left:8px;top:0;bottom:0;width:2px;background:var(--border)}
.ad-tl-item{position:relative;margin-bottom:14px}
.ad-tl-dot{position:absolute;left:-20px;top:4px;width:10px;height:10px;border-radius:50%;background:var(--accent);border:2px solid #fff;box-shadow:0 0 0 2px var(--border)}
.ad-tl-date{font-size:10px;font-weight:600;color:var(--text2);margin-bottom:3px;text-transform:uppercase;letter-spacing:.05em}
.ad-tl-text{font-size:12px;color:var(--text);line-height:1.55;background:#f9fafb;border:1px solid var(--border);border-radius:8px;padding:10px 13px}

.ad-cal-item{display:grid;grid-template-columns:64px 1fr auto;gap:12px;align-items:center;padding:10px 16px;border-bottom:1px solid var(--border);font-size:13px}
.ad-cal-item:last-child{border-bottom:none}
.ad-cal-date{font-size:11px;font-weight:700;color:var(--text2);text-align:center;line-height:1.3}
.ad-cal-day{font-size:20px;font-weight:900;color:var(--text);line-height:1}

.ad-search{padding:7px 12px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:var(--font);outline:none;background:var(--card);color:var(--text);transition:border-color .15s}
.ad-search:focus{border-color:var(--accent)}

.ad-sum{display:flex;align-items:stretch;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;flex-wrap:wrap}
.ad-sum-grid{display:flex;flex:1;flex-wrap:wrap;min-width:0}
.ad-sum-item{flex:1;min-width:140px;padding:15px 20px;border-right:1px solid var(--border)}
.ad-sum-item:last-child{border-right:none}
.ad-sum-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text2);margin-bottom:7px}
.ad-sum-value{font-size:21px;font-weight:800;letter-spacing:-.03em;color:var(--text);line-height:1}
.ad-sum-sub{font-size:11px;color:var(--text2);margin-top:4px}
.ad-sum-chips{display:flex;flex-wrap:wrap;gap:6px;align-items:center;padding:15px 20px;flex:1;min-width:0}
.ad-sum-foot{display:flex;align-items:center;border-left:1px solid var(--border)}
.ad-toggle{display:inline-flex;align-items:center;gap:6px;padding:0 18px;height:100%;min-height:52px;border:none;background:transparent;font-size:12px;font-weight:700;color:var(--accent-hover);cursor:pointer;font-family:var(--font);white-space:nowrap;transition:background .15s}
.ad-toggle:hover{background:var(--c-cob-bg)}
.ad-toggle svg{width:13px;height:13px;transition:transform .25s}
.ad-toggle.open svg{transform:rotate(180deg)}
.ad-detail{margin-top:12px}

/* ── Responsive ─────────────────────────────────────────── */
@media(max-width:1180px){
    .ad-hero{grid-template-columns:1fr}
    .ad-hero-side{border-left:none;padding-left:0;border-top:1px solid var(--border);padding-top:16px}
    .ad-flujo-body{grid-template-columns:1fr}
    .ad-read{border-left:none;padding-left:0;border-top:1px solid var(--border);padding-top:16px}
    .ad-row-7-5,.ad-row-6-6{grid-template-columns:1fr}
}
@media(max-width:900px){
    .ad-kpi-grid{grid-template-columns:repeat(2,1fr)}
    .ad-hero-num{font-size:42px}
}
@media(max-width:640px){
    .ad-hero-side,.ad-read-grid,.ad-efi-grid,.ad-inline-facts{grid-template-columns:1fr}
    .ad-par-grid,.ad-mini-stats,.ad-stack-legend{grid-template-columns:repeat(2,1fr)}
    .ad-rend-row{grid-template-columns:110px minmax(0,1fr) 56px}
    .ad-sum-foot{border-left:none;border-top:1px solid var(--border);width:100%}
    .ad-toggle{justify-content:center;width:100%;min-height:46px}
}
@media(max-width:420px){
    .ad-kpi-grid{grid-template-columns:1fr}
    .ad-hero-num{font-size:36px}
}
</style>
@endpush

@section('content')

@php
$fmt  = fn($n) => '$' . number_format((float)$n, 0, '.', ',');
$fmtS = fn($n) => ((float)$n < 0 ? '−' : '') . '$' . number_format(abs((float)$n), 0, '.', ',');
$fmtSigned = fn($n) => ((float)$n > 0 ? '+' : '') . $fmtS($n);
$fmtK = function ($n) {
    $a = abs((float)$n); $s = (float)$n < 0 ? '−' : '';
    if ($a >= 1e6) return $s . '$' . rtrim(rtrim(number_format($a / 1e6, 2, '.', ''), '0'), '.') . 'M';
    if ($a >= 1e3) return $s . '$' . round($a / 1e3) . 'k';
    return $s . '$' . round($a);
};
$pct = fn($n) => number_format((float)$n, 1, '.', ',') . '%';
// Semáforo PAR/NPL: <5 sano · 5–15 atención · >15 crítico
$sem = fn($v) => $v < 5 ? 'ad-sem-ok' : ($v < 15 ? 'ad-sem-warn' : 'ad-sem-bad');
// Semáforo de eficiencia de cobranza: >=90 en meta · 75–90 atención · <75 crítico
$semEfi = fn($v) => $v === null ? 'ad-sem-neutral' : ($v >= 90 ? 'ad-sem-ok' : ($v >= 75 ? 'ad-sem-warn' : 'ad-sem-bad'));
$semEstado = ['Saludable' => 'ad-sem-ok', 'Atención' => 'ad-sem-warn', 'Crítico' => 'ad-sem-bad'][$estadoCartera] ?? 'ad-sem-neutral';

$colors  = ['#2a78d6','#4f46e5','#7c3aed','#db2777','#059669','#d97706'];
$initial = strtoupper(substr($admin->usuario, 0, 1));
$color   = $colors[crc32($admin->usuario) % count($colors)];
$nombre  = $admin->alias ?: ($admin->nombre ?: $admin->usuario);

$estatusCfg = [
    'Activo'     => ['pill-blue',   '#2a78d6'],
    'Atrasado'   => ['pill-red',    '#dc2626'],
    'Pendiente'  => ['pill-yellow', '#d97706'],
    'Finalizado' => ['pill-green',  '#059669'],
    'Retirado'   => ['pill-gray',   '#9aa3b2'],
];

$ico = [
    'up'    => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 13V3M4 7l4-4 4 4"/></svg>',
    'trend' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 11.5 6 7.5l3 3 5-5.5"/><path d="M10 5h4v4"/></svg>',
    'trendDown' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4.5 6 8.5l3-3 5 5.5"/><path d="M10 11h4V7"/></svg>',
    'warn'  => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2 14.5 13.5h-13L8 2z"/><path d="M8 6.5v3.2M8 11.8v.4"/></svg>',
    'ok'    => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="6"/><path d="M5.5 8.2 7.2 9.9l3.4-3.6"/></svg>',
    'clock' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="6"/><path d="M8 4.5V8l2.5 1.5"/></svg>',
    'shield'=> '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M8 1.5 13.5 4v4c0 3.2-2.3 5.6-5.5 6.5C4.8 13.6 2.5 11.2 2.5 8V4L8 1.5z"/><path d="M5.8 8.2 7.3 9.7l3-3.2"/></svg>',
    'flame' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 1.5c1 2.5 4 3.5 4 7a4 4 0 0 1-8 0c0-1.5.6-2.4 1.2-3.2.4 1 1 1.6 1.8 1.7C7.5 5.5 7.6 3.3 8 1.5z"/></svg>',
    'cal'   => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="3" width="12" height="11" rx="1.5"/><path d="M5 1.5v3M11 1.5v3M2 7h12"/></svg>',
    'id'    => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="3.5" width="12" height="9" rx="1.5"/><path d="M5 7.5h2M5 10h4"/></svg>',
    'user'  => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="5" r="2.5"/><path d="M2.5 14c0-3 2.5-4.5 5.5-4.5s5.5 1.5 5.5 4.5"/></svg>',
    'users' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6" cy="5" r="2.2"/><circle cx="11" cy="6" r="1.8"/><path d="M1.5 13.5c0-2.8 2-4.3 4.5-4.3s4.5 1.5 4.5 4.3M11 9.3c2 0 3.5 1.2 3.5 3.5"/></svg>',
    'phone' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="5" y="1.5" width="6" height="13" rx="1.5"/><circle cx="8" cy="12" r=".6" fill="currentColor" stroke="none"/></svg>',
    'back'  => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9.5 3 5 8l4.5 5"/></svg>',
    'eye'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    'edit'  => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M11 2.5 13.5 5l-7.5 7.5H3.5V10l7.5-7.5z"/></svg>',
];
$alertIcon = ['danger' => 'warn', 'warning' => 'warn', 'success' => 'ok'];
@endphp

{{-- Breadcrumb --}}
<nav class="ad-bc" aria-label="Ruta">
    <a href="{{ route('owner.dashboard') }}">Dashboard</a>
    <span class="ad-bc-sep">/</span>
    <a href="{{ route('owner.dashboard') }}">Administradores</a>
    <span class="ad-bc-sep">/</span>
    <span class="ad-bc-cur">{{ $nombre }}</span>
</nav>

{{-- Header --}}
<div class="ad-hdr">
    <div class="ad-avatar" style="background:{{ $color }}">{{ $initial }}</div>
    <div class="ad-hdr-info">
        <div class="ad-hdr-name">
            {{ $nombre }}
            @if($admin->activo)
                <span class="ad-badge ad-badge-green"><i class="ad-badge-dot"></i>Activo</span>
            @else
                <span class="ad-badge ad-badge-red"><i class="ad-badge-dot"></i>Inactivo</span>
            @endif
        </div>
        <div class="ad-hdr-meta">
            @if($admin->nombre && $admin->alias)
                <span>{!! $ico['user'] !!}{{ $admin->nombre }}</span>
            @endif
            <span>{!! $ico['id'] !!}ID #{{ $admin->id }}</span>
            <span>{!! $ico['cal'] !!}Alta {{ $admin->created_at?->format('d/m/Y') ?? '—' }}@if($admin->created_at) · {{ $admin->created_at->locale('es')->diffForHumans() }}@endif</span>
            <span>{!! $ico['user'] !!}{{ $admin->usuario }}</span>
            <span>{!! $ico['users'] !!}{{ $empleados->count() }} en el equipo · {{ $clientesActivos->count() }} clientes activos</span>
            @if($admin->celular)
                <span>{!! $ico['phone'] !!}{{ $admin->celular }}</span>
            @endif
        </div>
    </div>
    <div class="ad-hdr-actions">
        <a href="{{ route('owner.dashboard') }}" class="btn btn-sm" style="background:#f3f4f6;color:var(--text2)">{!! $ico['back'] !!} Volver</a>
        @if($admin->activo)
        <form method="POST" action="{{ route('owner.admins.impersonar', $admin->id) }}" style="margin:0">
            @csrf
            <button type="submit" class="btn btn-sm" style="background:#eef2ff;color:#4338ca" title="Entrar al sistema como este admin (vista admin)">{!! $ico['eye'] !!} Ver como admin</button>
        </form>
        @endif
        <button class="btn btn-sm btn-primary" onclick="document.getElementById('modalEditarDet').classList.add('open')">{!! $ico['edit'] !!} Editar</button>
        <form method="POST" action="{{ route('owner.admins.toggle', $admin->id) }}" style="margin:0">
            @csrf
            <button type="submit" class="btn btn-sm"
                style="{{ $admin->activo ? 'background:#fee2e2;color:#b91c1c' : 'background:#dcfce7;color:#047857' }}"
                onclick="return confirm('¿{{ $admin->activo ? 'Desactivar' : 'Activar' }} este administrador?')">
                {{ $admin->activo ? 'Suspender' : 'Activar' }}
            </button>
        </form>
    </div>
</div>

{{-- ── Posición actual (hero) ─────────────────────────────── --}}
<section class="ad-card ad-hero" aria-label="Posición actual">
    <div class="ad-hero-main">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <span class="ad-label">Posición neta actual</span>
            <span class="ad-muted">todo lo cobrado − todo lo desembolsado, histórico</span>
        </div>
        <div class="ad-hero-row">
            <span class="ad-hero-num">{{ $fmtS($posicionNeta) }}</span>
            <span class="ad-chip">{{ $posicionNeta >= 0 ? ($posicionNeta > 0 ? 'Excedente recuperado' : 'En cero') : 'Capital en la calle' }}</span>
        </div>
        <div style="font-size:13px;color:var(--text2)">
            Cobrado <b class="ad-strong">{{ $fmt($totalCobrado) }}</b> − Desembolsado <b class="ad-strong">{{ $fmt($capitalDesplegado) }}</b>
        </div>
        <div class="ad-hero-facts">
            <span id="flHeroDelta" style="font-weight:600;color:var(--text2)">{!! $ico['trend'] !!}<em style="font-style:normal">—</em></span>
            @if($coberturaSaldo !== null)
                <span>{!! $ico['shield'] !!}El saldo por cobrar ({{ $fmt($capitalPendiente) }}) cubre {{ number_format($coberturaSaldo, 1) }}× este capital</span>
            @elseif($posicionNeta > 0)
                <span>{!! $ico['shield'] !!}El capital ya regresó completo; el excedente es ganancia realizada</span>
            @endif
        </div>
    </div>
    <div class="ad-hero-side">
        <div class="ad-fact">
            <span class="ad-label">Ranking</span>
            <div class="ad-fact-val"><b>#{{ $ranking['posicion'] }}</b><span>de {{ $ranking['total'] }} {{ $ranking['total'] === 1 ? 'admin' : 'admins' }}</span></div>
            <span class="ad-muted">por rendimiento real (interés cobrado ÷ capital)</span>
        </div>
        <div class="ad-fact">
            <span class="ad-label">Rendimiento real</span>
            <div class="ad-fact-val"><b>{{ $roi > 0 ? '+' : '' }}{{ $pct($roi) }}</b><span>de {{ $pct($rentabilidadPactada) }} pactado</span></div>
            <span class="ad-muted">ganancia realizada ÷ capital desplegado</span>
        </div>
        <div class="ad-fact ad-sem-ok">
            <span class="ad-label">Capital recuperado</span>
            <div class="ad-fact-val"><b>{{ $pct($recuperadoPct) }}</b><span>{{ $fmt($capitalRecuperado) }} de {{ $fmt($capitalDesplegado) }}</span></div>
            <div class="ad-meter"><div style="width:{{ min(100, $recuperadoPct) }}%"></div></div>
        </div>
        <div class="ad-fact {{ $semEstado }}">
            <span class="ad-label">Estado de cartera</span>
            <div class="ad-fact-estado"><span class="ad-sem-tx">{!! $ico[$estadoCartera === 'Saludable' ? 'ok' : ($estadoCartera === 'Sin cartera activa' ? 'clock' : 'warn')] !!}</span>{{ $estadoCartera }}</div>
            <span class="ad-muted">PAR30 {{ $pct($par30) }} · NPL {{ $pct($npl) }}</span>
        </div>
    </div>
</section>

{{-- ── KPIs ───────────────────────────────────────────────── --}}
<section class="ad-kpi-grid" aria-label="Indicadores principales">
    <div class="ad-kpi">
        <span class="ad-label">Capital desplegado</span>
        <span class="ad-kpi-value">{{ $fmt($capitalDesplegado) }}</span>
        <span class="ad-kpi-delta">{!! $ico['up'] !!}{{ $fmt($desembolsadoUlt30) }} colocados en 30 días</span>
        <span class="ad-kpi-sub">{{ $activos->count() + $finalizados->count() }} préstamos desembolsados · ticket promedio {{ $fmt($ticketPromedio) }}</span>
    </div>
    <div class="ad-kpi">
        <span class="ad-label">Cobrado total</span>
        <span class="ad-kpi-value">{{ $fmt($totalCobrado) }}</span>
        <span class="ad-kpi-delta up">{!! $ico['up'] !!}+{{ $fmt($cobradoUlt30) }} en los últimos 30 días</span>
        <span class="ad-kpi-sub">Capital {{ $fmt($capitalRecuperado) }} · Interés {{ $fmt($interesCobranzaReal) }}</span>
    </div>
    <div class="ad-kpi ad-sem-ok">
        <span class="ad-label">Ganancia realizada</span>
        <span class="ad-kpi-value">{{ $fmt($interesCobranzaReal) }}</span>
        <span class="ad-kpi-delta up">{!! $ico['up'] !!}+{{ $fmt($interesUlt30) }} de interés en 30 días</span>
        <div class="ad-kpi-meter">
            <div class="ad-meter"><div style="width:{{ min(100, $interesCobradoPct) }}%"></div></div>
            <span class="ad-muted" style="white-space:nowrap">{{ $pct($interesCobradoPct) }} de {{ $fmt($interesEsperado) }} esperados</span>
        </div>
    </div>
    <div class="ad-kpi">
        <span class="ad-label">Saldo por cobrar</span>
        <span class="ad-kpi-value">{{ $fmt($capitalPendiente) }}</span>
        @if($capitalRiesgo > 0)
            <span class="ad-kpi-delta down">{!! $ico['warn'] !!}{{ $fmt($capitalRiesgo) }} en préstamos atrasados ({{ $pct($saldoActivo > 0 ? $capitalRiesgo / $saldoActivo * 100 : 0) }})</span>
        @else
            <span class="ad-kpi-delta up">{!! $ico['ok'] !!}Sin saldo en préstamos atrasados</span>
        @endif
        <span class="ad-kpi-sub">{{ $nActivos }} préstamos activos · mora acumulada {{ $fmt($moraPendiente) }}</span>
    </div>
</section>

{{-- ── Alertas ────────────────────────────────────────────── --}}
@if(!empty($alertas))
<section class="ad-alerts" aria-label="Alertas">
    @foreach($alertas as $a)
    <div class="ad-alert ad-alert-{{ $a['tipo'] }}">
        {!! $ico[$alertIcon[$a['tipo']] ?? 'warn'] !!}
        <span><b>{{ $a['titulo'] }}.</b> {{ $a['msg'] }}</span>
    </div>
    @endforeach
</section>
@endif

{{-- ── Flujo de capital y posición ────────────────────────── --}}
<section class="ad-card ad-flujo" aria-label="Flujo de capital">
    <div class="ad-flujo-hd">
        <div>
            <h2 class="ad-card-title">Flujo de capital y posición</h2>
            <div class="ad-card-sub">Un día es <b style="color:var(--c-cob-tx)">positivo</b> cuando lo cobrado supera lo desembolsado, y <b style="color:var(--c-neg-tx)">negativo</b> cuando se prestó más de lo que entró. La línea acumula esos días: es la posición.</div>
        </div>
        <div class="ad-flujo-ctl">
            <div class="ad-seg" role="group" aria-label="Vista">
                <button type="button" data-mode="neto" class="is-active" onclick="flujoSetMode('neto')">Flujo neto</button>
                <button type="button" data-mode="comparar" onclick="flujoSetMode('comparar')">Cobros vs desembolsos</button>
            </div>
            <div class="ad-seg" role="group" aria-label="Rango">
                <button type="button" data-range="30" onclick="flujoSetRange(30)">30 d</button>
                <button type="button" data-range="90" class="is-active" onclick="flujoSetRange(90)">90 d</button>
                <button type="button" data-range="180" onclick="flujoSetRange(180)">180 d</button>
            </div>
        </div>
    </div>
    <div class="ad-flujo-body">
        <div id="flujoCharts">
            <div class="ad-plot-hd">
                <b>Posición acumulada</b>
                <div class="ad-plot-keys">
                    <span class="ad-key"><i class="line" style="background:var(--c-des)"></i>Capital en la calle</span>
                    <span class="ad-key"><i class="line" style="background:var(--c-cob)"></i>Excedente</span>
                    <span class="ad-muted" data-range-caption>últimos 90 días</span>
                </div>
            </div>
            <div class="ad-plot" style="height:190px"><canvas id="chartPosicion"></canvas></div>
            <div class="ad-plot-hd" style="margin-top:10px">
                <b id="flujoPlot2Title">Flujo neto por día (cobros − desembolsos)</b>
                <div class="ad-plot-keys" id="flujoKeysNeto">
                    <span class="ad-key"><i style="background:var(--c-cob)"></i>Día positivo</span>
                    <span class="ad-key"><i style="background:var(--c-neg)"></i>Día negativo</span>
                </div>
                <div class="ad-plot-keys" id="flujoKeysComparar" style="display:none">
                    <span class="ad-key"><i class="line" style="background:var(--c-cob)"></i>Cobros</span>
                    <span class="ad-key"><i class="line" style="background:var(--c-des)"></i>Desembolsos</span>
                </div>
            </div>
            <div class="ad-plot" style="height:190px"><canvas id="chartFlujoNeto"></canvas></div>
        </div>

        <aside class="ad-read" aria-label="Lectura del periodo">
            <span class="ad-label">Lectura · <span data-range-caption>últimos 90 días</span></span>
            <div class="ad-read-grid">
                <div class="ad-read-box pos"><small>Días positivos</small><b id="flPosDays">—</b><em><span id="flPosPct">—</span> de los días</em></div>
                <div class="ad-read-box neg"><small>Días negativos</small><b id="flNegDays">—</b><em><span id="flNegPct">—</span> de los días</em></div>
            </div>
            <div class="ad-days-bar">
                <div id="flBarPos" style="width:0;background:var(--c-cob)"></div>
                <div id="flBarNeg" style="width:0;background:var(--c-neg)"></div>
                <div id="flBarZero" style="width:0;background:#d1d5db"></div>
            </div>
            <div class="ad-muted" style="margin-top:-6px"><span id="flZeroDays">—</span> días sin movimiento (domingos y festivos)</div>
            <div class="ad-read-rows">
                <div><span>Mejor día</span><span><b class="ad-strong" id="flBest">—</b><span class="ad-muted"> · <span id="flBestDate"></span></span></span></div>
                <div><span>Peor día</span><span><b class="ad-strong" id="flWorst">—</b><span class="ad-muted"> · <span id="flWorstDate"></span></span></span></div>
                <div><span>Promedio neto diario</span><b class="ad-strong" id="flAvg">—</b></div>
                <div><span>Cobrado en el periodo</span><b class="ad-strong" id="flCob">—</b></div>
                <div><span>Desembolsado en el periodo</span><b class="ad-strong" id="flDes">—</b></div>
            </div>
            <div class="ad-streak">
                {!! $ico['flame'] !!}
                <div><b>Racha actual: <span id="flStreak">—</span></b><small>sin contar días sin movimiento</small></div>
            </div>
        </aside>
    </div>
</section>

{{-- ── Cobranza + Riesgo ──────────────────────────────────── --}}
<div class="ad-row-7-5">
    @php
        $wMax = max(1, max(array_merge(array_column($semanasCobranza, 'programado'), array_column($semanasCobranza, 'cobrado'))));
        $efiSem = $cobranza['semana']['eficiencia'];
        $efiMes = $cobranza['mes']['eficiencia'];
        $efiTag = fn($v) => $v === null ? ['clock', 'Sin cuotas programadas'] : ($v >= 90 ? ['ok', 'En meta'] : ($v >= 75 ? ['warn', 'Por debajo de 90%'] : ['warn', 'Crítico: menos de 75%']));
    @endphp
    <section class="ad-card col" aria-label="Cobranza">
        <div>
            <h2 class="ad-card-title">Cobranza</h2>
            <div class="ad-card-sub">Cuánto de lo programado en cuotas realmente entró (al día de hoy)</div>
        </div>
        <div class="ad-efi-grid">
            <div class="ad-efi {{ $semEfi($efiSem) }}">
                <div class="ad-efi-hd">
                    <span class="ad-label">Esta semana</span>
                    <span class="ad-efi-tag ad-sem-tx">{!! $ico[$efiTag($efiSem)[0]] !!}{{ $efiTag($efiSem)[1] }}</span>
                </div>
                <div class="ad-efi-val"><b>{{ $efiSem === null ? '—' : $pct($efiSem) }}</b><span>{{ $fmt($cobranza['semana']['cobrado']) }} de {{ $fmt($cobranza['semana']['programado']) }}</span></div>
                <div class="ad-meter"><div style="width:{{ min(100, (float)$efiSem) }}%"></div></div>
            </div>
            <div class="ad-efi {{ $semEfi($efiMes) }}">
                <div class="ad-efi-hd">
                    <span class="ad-label">Este mes</span>
                    <span class="ad-efi-tag ad-sem-tx">{!! $ico[$efiTag($efiMes)[0]] !!}{{ $efiTag($efiMes)[1] }}</span>
                </div>
                <div class="ad-efi-val"><b>{{ $efiMes === null ? '—' : $pct($efiMes) }}</b><span>{{ $fmt($cobranza['mes']['cobrado']) }} de {{ $fmt($cobranza['mes']['programado']) }}</span></div>
                <div class="ad-meter"><div style="width:{{ min(100, (float)$efiMes) }}%"></div></div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px">
            <div class="ad-plot-hd">
                <b>Últimas 8 semanas</b>
                <div class="ad-plot-keys">
                    <span class="ad-key"><i style="background:#d1d5db"></i>Programado</span>
                    <span class="ad-key"><i style="background:var(--c-cob)"></i>Cobrado</span>
                </div>
            </div>
            <div class="ad-cols" style="grid-template-columns:repeat(8,minmax(0,1fr));height:118px">
                @foreach($semanasCobranza as $w)
                @php
                    $hp = $w['programado'] / $wMax * 82; $hc = $w['cobrado'] / $wMax * 82;
                    $e  = $w['eficiencia'];
                @endphp
                <div class="ad-col">
                    <i style="width:18px;height:{{ round($hp, 1) }}%;background:#d1d5db"></i>
                    <i style="width:18px;height:{{ round($hc, 1) }}%;background:var(--c-cob)"></i>
                    @if($e !== null)
                    <em style="bottom:calc({{ round(max($hp, $hc), 1) }}% + 4px);color:{{ $e >= 90 ? 'var(--c-cob-tx)' : ($e >= 75 ? 'var(--c-warn-tx)' : 'var(--c-neg-tx)') }};{{ $loop->last ? 'font-weight:700' : '' }}">{{ $pct($e) }}</em>
                    @endif
                </div>
                @endforeach
            </div>
            <div class="ad-cols-x" style="grid-template-columns:repeat(8,minmax(0,1fr))">
                @foreach($semanasCobranza as $w)<span>{{ $w['label'] }}</span>@endforeach
            </div>
        </div>
        <div class="ad-inline-facts">
            <div><span style="color:var(--c-neg-tx)">{!! $ico['clock'] !!}</span><span>Vencido a hoy: <b class="ad-strong">{{ $vencido['n'] }} {{ $vencido['n'] === 1 ? 'cuota' : 'cuotas' }} · {{ $fmt($vencido['monto']) }}</b></span></div>
            <div><span style="color:var(--c-des)">{!! $ico['cal'] !!}</span><span>Próximos 7 días: <b class="ad-strong">{{ $fmt($proximos7Total) }} en {{ $proximos7Cuotas }} cuotas</b></span></div>
        </div>
    </section>

    <section class="ad-card col" aria-label="Riesgo de cartera">
        <div>
            <h2 class="ad-card-title">Riesgo de cartera</h2>
            <div class="ad-card-sub">Porción del saldo activo con cuotas atrasadas</div>
        </div>
        <div class="ad-par-grid">
            @foreach([['PAR 30', $par30, $fmt($par30Saldo)], ['PAR 60', $par60, $fmt($par60Saldo)], ['PAR 90', $par90, $fmt($par90Saldo)], ['NPL', $npl, "{$nAtrasados} de {$nActivos} activos"]] as [$lbl, $val, $sub])
            <div class="ad-par {{ $sem($val) }}">
                <span class="ad-label" style="font-size:10px">{{ $lbl }}</span>
                <b>{{ $pct($val) }}</b>
                <div class="ad-meter"><div style="width:{{ min(100, $val / 30 * 100) }}%"></div></div>
                <span class="ad-muted" style="font-size:10px">{{ $sub }}</span>
            </div>
            @endforeach
        </div>
        <div class="ad-muted" style="margin-top:-6px">Semáforo: menos de 5% sano · 5–15% atención · más de 15% crítico. La barra se llena a 30%.</div>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:auto">
            <div class="ad-plot-hd"><b>Antigüedad del saldo vencido</b><span class="ad-muted">{{ $fmt($vencido['monto']) }} en {{ $vencido['n'] }} {{ $vencido['n'] === 1 ? 'cuota' : 'cuotas' }}</span></div>
            @if($vencido['monto'] > 0)
            <div class="ad-stack" style="height:14px">
                @foreach($aging as $b)
                    @if($b['monto'] > 0)<div style="width:{{ round($b['monto'] / $vencido['monto'] * 100, 2) }}%;background:{{ $b['color'] }}"></div>@endif
                @endforeach
            </div>
            @else
            <div class="ad-stack" style="height:14px"><div style="width:100%;background:#eef2f7"></div></div>
            @endif
            <div class="ad-stack-legend">
                @foreach($aging as $b)
                <span><span class="ad-key"><i style="background:{{ $b['color'] }}"></i>{{ $b['label'] }}</span><b class="ad-strong">{{ $fmt($b['monto']) }}</b></span>
                @endforeach
            </div>
        </div>
    </section>
</div>

{{-- ── Composición + Rendimiento ──────────────────────────── --}}
<div class="ad-row-6-6">
    <section class="ad-card col" aria-label="Composición de la cartera">
        <div>
            <h2 class="ad-card-title">Composición de la cartera</h2>
            <div class="ad-card-sub">{{ $totalPrestamos }} préstamos en total · {{ $activos->count() + $finalizados->count() }} desembolsados</div>
        </div>
        @if($totalPrestamos > 0)
        <div class="ad-stack" style="height:16px">
            @foreach($composicion as $c)
                @if($c['n'] > 0)<div style="width:{{ $c['pct'] }}%;background:{{ $c['color'] }}"></div>@endif
            @endforeach
        </div>
        @endif
        <div class="ad-comp-rows">
            <div class="ad-comp-row head"><span>Estatus</span><span>Préstamos</span><span>Monto</span><span>Nota</span></div>
            @foreach($composicion as $c)
            <div class="ad-comp-row">
                <span><i class="ad-dot" style="background:{{ $c['color'] }}"></i>{{ $c['estatus'] }}</span>
                <span class="ad-num" style="font-weight:700">{{ $c['n'] }}</span>
                <span class="ad-num">{{ $c['monto'] === null ? '—' : $fmt($c['monto']) }}</span>
                <span class="ad-muted" style="font-size:12px">{{ $c['nota'] }}</span>
            </div>
            @endforeach
        </div>
        <div class="ad-mini-stats">
            <div><small>Ticket promedio</small><b>{{ $fmt($ticketPromedio) }}</b></div>
            <div><small>Rango</small><b>{{ $fmtK($montoMin) }} – {{ $fmtK($montoMax) }}</b></div>
            <div><small>Plazo promedio</small><b>{{ $duracionPromedio }} cuotas</b></div>
            <div><small>Frecuencia</small><b>{{ $frecuencias->isEmpty() ? '—' : $frecuencias->map(fn($n, $f) => "$n $f")->implode(' · ') }}</b></div>
        </div>
    </section>

    <section class="ad-card col" aria-label="Rendimiento">
        <div>
            <h2 class="ad-card-title">Rendimiento</h2>
            <div class="ad-card-sub">Lo pactado en los contratos contra lo que ya se ganó</div>
        </div>
        @php
            $realPct = $rentabilidadPactada > 0 ? min(100, max(0, $roi) / $rentabilidadPactada * 100) : 0;
            $mMax = max(1, max(array_column($interesMensual, 'valor')));
        @endphp
        <div style="display:flex;flex-direction:column;gap:10px">
            <div class="ad-rend-row"><span>Rentabilidad pactada</span><div class="ad-rend-bar"><div style="width:100%;background:#e5e7eb"></div></div><span class="ad-num">{{ $pct($rentabilidadPactada) }}</span></div>
            <div class="ad-rend-row"><span>Rendimiento real a hoy</span><div class="ad-rend-bar"><div style="width:{{ round($realPct, 1) }}%;background:var(--c-cob)"></div></div><span class="ad-num">{{ $roi > 0 ? '+' : '' }}{{ $pct($roi) }}</span></div>
            <div class="ad-rend-row"><span>Por realizar</span><div class="ad-rend-bar"><div style="width:{{ round(100 - $realPct, 1) }}%;background:var(--c-cob-track)"></div></div><span class="ad-num" style="color:var(--text2)">{{ $pct(max(0, $rentabilidadPactada - $roi)) }}</span></div>
        </div>
        <div style="font-size:12px;color:var(--text2)">Interés esperado <b class="ad-strong">{{ $fmt($interesEsperado) }}</b> · cobrado <b class="ad-strong">{{ $fmt($interesCobranzaReal) }}</b> ({{ $pct($interesCobradoPct) }}) · por cobrar <b class="ad-strong">{{ $fmt($interesPorCobrar) }}</b></div>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:auto">
            <div class="ad-plot-hd"><b>Interés cobrado por mes</b><span class="ad-muted">{{ now()->locale('es')->isoFormat('MMMM') }} al día {{ now()->day }}</span></div>
            <div class="ad-cols" style="grid-template-columns:repeat(6,minmax(0,1fr));height:96px;gap:14px">
                @foreach($interesMensual as $m)
                @php $h = $m['valor'] / $mMax * 78; $isMax = $m['valor'] === $mMax && $mMax > 0; @endphp
                <div class="ad-col">
                    <i style="width:22px;height:{{ round($h, 1) }}%;background:{{ $m['parcial'] ? '#a7f3d0' : 'var(--c-cob)' }}"></i>
                    @if(($isMax || $m['parcial']) && $m['valor'] > 0)
                    <em style="bottom:calc({{ round($h, 1) }}% + 4px);color:var(--text);font-weight:700">{{ $fmtK($m['valor']) }}</em>
                    @endif
                </div>
                @endforeach
            </div>
            <div class="ad-cols-x" style="grid-template-columns:repeat(6,minmax(0,1fr));gap:14px">
                @foreach($interesMensual as $m)<span>{{ $m['label'] }}{{ $m['parcial'] ? ' (parcial)' : '' }}</span>@endforeach
            </div>
        </div>
    </section>
</div>

{{-- ── Clientes en atraso + Próximos 7 días ──────────────── --}}
<div class="ad-row-7-5">
    <section class="ad-table-card" aria-label="Clientes en atraso">
        <div class="ad-table-head">
            <div>
                <h2 class="ad-card-title">Clientes en atraso</h2>
                <div class="ad-card-sub">{{ $morosos->count() }} {{ $morosos->count() === 1 ? 'préstamo' : 'préstamos' }} · {{ $fmt($capitalRiesgo) }} de saldo · ordenados por días de atraso</div>
            </div>
            <button type="button" class="btn btn-sm" style="background:var(--c-cob-bg);color:var(--c-cob-tx)" onclick="abrirDetalle('prestamosDetail', 'prestamosToggle')">Ver todos los préstamos</button>
        </div>
        <div class="ad-tbl-wrap">
            <table class="ad-tbl" style="font-size:12px">
                <thead><tr><th>Cliente</th><th>Préstamo</th><th>Atraso</th><th class="r">Cuotas venc.</th><th class="r">Saldo</th><th class="r">Mora</th><th>Promotor</th></tr></thead>
                <tbody>
                @forelse($morosos->take(8) as $m)
                @php $agingColor = $m['dias'] > 90 ? '#7f1d1d' : ($m['dias'] > 60 ? '#b91c1c' : ($m['dias'] > 30 ? '#e34948' : '#f98080')); @endphp
                <tr>
                    <td style="font-weight:600">{{ $m['cliente'] }}</td>
                    <td class="ad-mono" style="color:var(--text2)">#{{ $m['prestamo_id'] }}</td>
                    <td><span class="ad-days-pill ad-num"><i style="background:{{ $agingColor }}"></i>{{ $m['dias'] }} {{ $m['dias'] === 1 ? 'día' : 'días' }}</span></td>
                    <td class="r ad-num">{{ $m['cuotas'] }}</td>
                    <td class="r ad-num" style="font-weight:700">{{ $fmt($m['saldo']) }}</td>
                    <td class="r ad-num">{{ $fmt($m['mora']) }}</td>
                    <td style="color:var(--text2)">{{ $m['promotor'] ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="ad-empty">Sin préstamos atrasados. La cartera activa está al corriente.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($morosos->count() > 8)
        <div style="padding:10px 20px;font-size:12px;color:var(--text2);border-top:1px solid var(--border)">+ {{ $morosos->count() - 8 }} préstamos atrasados más en el historial completo</div>
        @endif
    </section>

    <section class="ad-table-card" aria-label="Próximos cobros">
        <div class="ad-table-head">
            <div>
                <h2 class="ad-card-title">Próximos 7 días</h2>
                <div class="ad-card-sub">Cuotas programadas por día</div>
            </div>
            <span style="font-size:12px;font-weight:700;color:var(--text)">{{ $fmt($proximos7Total) }} · {{ $proximos7Cuotas }} cuotas</span>
        </div>
        @php $pMax = max(1, max(array_column($proximos7, 'monto'))); @endphp
        <div style="display:flex;flex-direction:column;padding:6px 20px 12px">
            @foreach($proximos7 as $p)
            <div class="ad-prox-row {{ $p['hoy'] ? 'hoy' : '' }}">
                <div class="ad-prox-day"><small>{{ $p['hoy'] ? 'hoy' : $p['dow'] }}</small><b>{{ $p['dia'] }}</b></div>
                <div style="display:flex;flex-direction:column;gap:4px">
                    <div class="ad-prox-bar"><div style="width:{{ round($p['monto'] / $pMax * 100, 1) }}%"></div></div>
                    <span class="ad-muted">{{ $p['n'] }} {{ $p['n'] === 1 ? 'cuota' : 'cuotas' }}</span>
                </div>
                <span class="ad-num" style="text-align:right;font-weight:700">{{ $fmt($p['monto']) }}</span>
            </div>
            @endforeach
        </div>
    </section>
</div>

{{-- ── Tabla de clientes ─────────────────────────────────────── --}}
<div class="ad-section">
    <div class="ad-section-title">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:var(--c-cob)"><circle cx="6" cy="5" r="3"/><path d="M1 15c0-3.314 2.239-5 5-5"/><circle cx="12" cy="10" r="2.5"/><path d="M12 12.5v1.5l1 1"/></svg>
        Clientes Activos — Cartera
        <span class="ad-section-badge" style="background:var(--c-des-bg);color:#1d4ed8">{{ $clientesActivos->count() }}</span>
    </div>
    @php
        $clientesConActivo   = $activos->pluck('cliente_id')->unique()->count();
        $montoPrestadoActivo = (float) $activos->sum('monto_entregado');
        $saldoCartera        = (float) $activos->sum('saldo_actual');
        $cuotaTotalActiva    = (float) $activos->sum('cuota');
        $clientesAlDia       = max(0, $nActivos - $nAtrasados);
    @endphp
    <div class="ad-sum">
        <div class="ad-sum-grid">
            <div class="ad-sum-item">
                <div class="ad-sum-label">Clientes Activos</div>
                <div class="ad-sum-value" style="color:var(--c-des)">{{ $clientesActivos->count() }}</div>
                <div class="ad-sum-sub">{{ $clientesConActivo }} con préstamo vigente</div>
            </div>
            <div class="ad-sum-item">
                <div class="ad-sum-label">Préstamos Activos</div>
                <div class="ad-sum-value">{{ $nActivos }}</div>
                <div class="ad-sum-sub">{{ $clientesAlDia }} al día · <span style="color:var(--c-neg-tx)">{{ $nAtrasados }} atrasados</span></div>
            </div>
            <div class="ad-sum-item">
                <div class="ad-sum-label">Monto Prestado</div>
                <div class="ad-sum-value">{{ $fmt($montoPrestadoActivo) }}</div>
                <div class="ad-sum-sub">capital activo colocado</div>
            </div>
            <div class="ad-sum-item">
                <div class="ad-sum-label">Saldo en Cartera</div>
                <div class="ad-sum-value" style="color:var(--c-warn-tx)">{{ $fmt($saldoCartera) }}</div>
                <div class="ad-sum-sub">cuota total {{ $fmt($cuotaTotalActiva) }}</div>
            </div>
        </div>
        <div class="ad-sum-foot">
            <button class="ad-toggle" type="button" onclick="toggleDetail('clientesDetail', this)">
                <span class="ad-toggle-txt">Ver detalle</span>
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 5l4 4 4-4"/></svg>
            </button>
        </div>
    </div>
    <div class="ad-detail" id="clientesDetail" style="display:none">
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
                        $diasAtraso = (int) $pago->fecha_programada->diffInDays($hoy);
                    }
                    $cfg = $prestamo ? ($estatusCfg[$prestamo->estatus] ?? ['pill-gray','#9ca3af']) : ['pill-gray','#9ca3af'];
                @endphp
                <tr data-search="{{ strtolower($cliente->nombre . ' ' . ($cliente->celular ?? '')) }}">
                    <td>
                        <div style="font-weight:600;color:var(--text)">{{ $cliente->nombre }}</div>
                        @if($cliente->celular)
                        <div style="font-size:11px;color:var(--text2)">{{ $cliente->celular }}</div>
                        @endif
                    </td>
                    <td style="font-weight:600">{{ $prestamo ? $fmt($prestamo->monto_entregado) : '—' }}</td>
                    <td style="color:var(--c-warn-tx);font-weight:600">{{ $prestamo ? $fmt($prestamo->saldo_actual) : '—' }}</td>
                    <td>{{ $prestamo ? $fmt($prestamo->cuota) : '—' }}</td>
                    <td>
                        @if($pago)
                            <span style="font-size:12px">{{ $pago->fecha_programada?->format('d/m/Y') ?? '—' }}</span>
                        @else
                            <span style="color:var(--text2)">—</span>
                        @endif
                    </td>
                    <td>
                        @if($diasAtraso > 0)
                            <span class="pill pill-red">{{ $diasAtraso }}d</span>
                        @else
                            <span style="color:var(--text2);font-size:12px">Al día</span>
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
</div>

{{-- ── Próximos cobros (30 días) ────────────────────────────── --}}
<div class="ad-section">
    <div class="ad-section-title">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:#7c3aed"><rect x="1" y="2" width="14" height="13" rx="1.5"/><path d="M5 1v3M11 1v3M1 6h14"/></svg>
        Próximos Cobros — 30 Días
        <span class="ad-section-badge" style="background:#f5f3ff;color:#7c3aed">{{ $proximosPagos->count() }}</span>
    </div>
    @php
        $totalCobrar30   = (float) $proximosPagos->sum('monto_cuota');
        $cobroPromedio30 = $proximosPagos->count() > 0 ? $totalCobrar30 / $proximosPagos->count() : 0;
    @endphp
    <div class="ad-sum">
        <div class="ad-sum-grid">
            <div class="ad-sum-item">
                <div class="ad-sum-label">A Cobrar — 30 Días</div>
                <div class="ad-sum-value" style="color:#7c3aed">{{ $fmt($totalCobrar30) }}</div>
                <div class="ad-sum-sub">monto total programado</div>
            </div>
            <div class="ad-sum-item">
                <div class="ad-sum-label">Cobros Programados</div>
                <div class="ad-sum-value">{{ $proximosPagos->count() }}</div>
                <div class="ad-sum-sub">cobro promedio {{ $fmt($cobroPromedio30) }}</div>
            </div>
        </div>
        @if($proximosPagos->isNotEmpty())
        <div class="ad-sum-foot">
            <button class="ad-toggle" type="button" onclick="toggleDetail('cobrosDetail', this)">
                <span class="ad-toggle-txt">Ver detalle</span>
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 5l4 4 4-4"/></svg>
            </button>
        </div>
        @endif
    </div>
    <div class="ad-detail" id="cobrosDetail" style="display:none">
    <div class="ad-table-wrap">
        @if($proximosPagos->isEmpty())
        <div class="ad-empty">Sin cobros programados en los próximos 30 días.</div>
        @else
        @foreach($proximosPagos->take(20) as $pago)
        @php
            $hoy    = \Carbon\Carbon::today();
            $fecha  = $pago->fecha_programada;
            // Carbon 3: diffInDays devuelve float — castear a int para que $dias === 0 funcione
            $dias   = (int) $hoy->diffInDays($fecha, false);
            $esHoy  = $dias === 0;
            $tarde  = $dias < 0;
        @endphp
        <div class="ad-cal-item" style="{{ $esHoy ? 'background:#eff6ff' : ($tarde ? 'background:#fef2f2' : '') }}">
            <div style="text-align:center;min-width:60px">
                <div class="ad-cal-day" style="color:{{ $tarde ? 'var(--c-neg-tx)' : ($esHoy ? 'var(--c-des)' : 'var(--text)') }}">
                    {{ $fecha?->format('d') ?? '—' }}
                </div>
                <div class="ad-cal-date">{{ $fecha?->format('M') ?? '' }}</div>
            </div>
            <div>
                <div style="font-size:13px;font-weight:600;color:var(--text)">
                    {{ $pago->prestamo?->cliente?->nombre ?? '—' }}
                </div>
                <div style="font-size:11px;color:var(--text2)">Pago #{{ $pago->numero_pago }}</div>
            </div>
            <div style="text-align:right">
                <div style="font-size:14px;font-weight:700;color:var(--text)">{{ $fmt($pago->monto_cuota) }}</div>
                @if($tarde)
                    <span class="pill pill-red">{{ abs($dias) }}d atraso</span>
                @elseif($esHoy)
                    <span class="pill pill-blue">Hoy</span>
                @else
                    <span style="font-size:11px;color:var(--text2)">En {{ $dias }}d</span>
                @endif
            </div>
        </div>
        @endforeach
        @if($proximosPagos->count() > 20)
        <div style="padding:10px 16px;font-size:12px;color:var(--text2);text-align:center;border-top:1px solid var(--border)">
            + {{ $proximosPagos->count() - 20 }} cobros más en los próximos 30 días
        </div>
        @endif
        @endif
    </div>
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
    @php $porPuesto = $empleados->groupBy(fn($e) => ucfirst($e->puesto ?: '—'))->map->count()->sortDesc(); @endphp
    <div class="ad-sum">
        <div class="ad-sum-item" style="flex:0 0 auto;border-right:1px solid var(--border)">
            <div class="ad-sum-label">Integrantes</div>
            <div class="ad-sum-value">{{ $empleados->count() }}</div>
            <div class="ad-sum-sub">{{ $porPuesto->count() }} {{ $porPuesto->count() === 1 ? 'puesto' : 'puestos' }}</div>
        </div>
        <div class="ad-sum-chips">
            @foreach($porPuesto as $puesto => $cnt)
            <span class="pill pill-gray" style="font-size:12px">{{ $puesto }} <strong style="margin-left:3px;color:var(--text)">{{ $cnt }}</strong></span>
            @endforeach
        </div>
        <div class="ad-sum-foot">
            <button class="ad-toggle" type="button" onclick="toggleDetail('equipoDetail', this)">
                <span class="ad-toggle-txt">Ver detalle</span>
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 5l4 4 4-4"/></svg>
            </button>
        </div>
    </div>
    <div class="ad-detail" id="equipoDetail" style="display:none">
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
                    <td style="color:var(--text2)">{{ $e->celular ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>
@endif

{{-- ── Todos los préstamos ───────────────────────────────────── --}}
<div class="ad-section">
    <div class="ad-section-title">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:var(--c-warn-tx)"><rect x="1" y="3" width="14" height="11" rx="1.5"/><path d="M4 8h8M4 11h5"/></svg>
        Todos los Préstamos
        <span class="ad-section-badge" style="background:var(--c-warn-bg);color:var(--c-warn-tx)">{{ $totalPrestamos }}</span>
    </div>
    <div class="ad-sum">
        <div class="ad-sum-grid">
            <div class="ad-sum-item">
                <div class="ad-sum-label">Total Préstamos</div>
                <div class="ad-sum-value">{{ $totalPrestamos }}</div>
                <div class="ad-sum-sub">historial completo</div>
            </div>
            <div class="ad-sum-item">
                <div class="ad-sum-label">Activos</div>
                <div class="ad-sum-value" style="color:var(--c-des)">{{ $porEstatus['Activo'] }}</div>
                <div class="ad-sum-sub"><span style="color:var(--c-neg-tx)">{{ $porEstatus['Atrasado'] }} atrasados</span></div>
            </div>
            <div class="ad-sum-item">
                <div class="ad-sum-label">Finalizados</div>
                <div class="ad-sum-value" style="color:var(--c-cob-tx)">{{ $porEstatus['Finalizado'] }}</div>
                <div class="ad-sum-sub">{{ $porEstatus['Pendiente'] }} pendientes · {{ $porEstatus['Retirado'] }} retirados</div>
            </div>
            <div class="ad-sum-item">
                <div class="ad-sum-label">Capital Colocado</div>
                <div class="ad-sum-value">{{ $fmt($capitalDesplegado) }}</div>
                <div class="ad-sum-sub">desembolsado histórico</div>
            </div>
        </div>
        <div class="ad-sum-foot">
            <button class="ad-toggle" type="button" id="prestamosToggle" onclick="toggleDetail('prestamosDetail', this)">
                <span class="ad-toggle-txt">Ver detalle</span>
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 5l4 4 4-4"/></svg>
            </button>
        </div>
    </div>
    <div class="ad-detail" id="prestamosDetail" style="display:none">
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
                    <td style="color:{{ in_array($p->estatus,['Activo','Atrasado']) ? 'var(--c-warn-tx)' : 'var(--text2)' }};font-weight:600">
                        {{ in_array($p->estatus,['Activo','Atrasado']) ? $fmt($p->saldo_actual) : '—' }}
                    </td>
                    <td style="font-size:12px;color:var(--text2)">{{ $p->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
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
        <button class="btn btn-sm" style="background:var(--c-des-bg);color:#1d4ed8"
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
                style="background:#f1f5f9;border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:18px;color:var(--text2);display:flex;align-items:center;justify-content:center">&times;</button>
        </div>
        <form method="POST" action="{{ route('owner.admins.update', $admin->id) }}">
            @csrf @method('PUT')
            <div style="padding:24px 28px;display:grid;gap:16px">
                @foreach([['nombre','Nombre completo','text',$admin->nombre,'ej. Juan Pérez'],['alias','Alias','text',$admin->alias,'ej. Zona Norte'],['usuario','Usuario (login)','text',$admin->usuario,''],['celular','Teléfono / WhatsApp','tel',$admin->celular,'5512345678'],['presupuesto','Presupuesto ($)','number',$admin->presupuesto,'0']] as [$name,$label,$type,$val,$ph])
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text2);margin-bottom:5px">{{ $label }}</label>
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
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
// ═══ Flujo de capital y posición ═════════════════════════════
// El servidor entrega 180 días de {f, cob, des} y la posición neta actual.
// Todo lo demás (flujo neto, posición acumulada, días positivos/negativos,
// rachas) se calcula aquí para cambiar de rango sin recargar.
const FLUJO = {
    serie:    @json($serieFlujo),
    posicion: @json($posicionNeta),
};
const CC = { cob:'#059669', des:'#2a78d6', neg:'#dc2626', hair:'#e9e8e2', axis:'#c3c2b7', muted:'#6b7280', ink:'#0f1623' };
const MESES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
const DIAS  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];

const parseF = f => { const [y, m, d] = f.split('-').map(Number); return new Date(y, m - 1, d); };
const money  = n => (n < 0 ? '−' : '') + '$' + Math.abs(Math.round(n)).toLocaleString('es-MX');
const signed = n => (n > 0 ? '+' : '') + money(n);
const moneyK = n => {
    const a = Math.abs(n), s = n < 0 ? '−' : '';
    if (a >= 1e6) return s + '$' + (a / 1e6).toFixed(2).replace(/\.?0+$/, '') + 'M';
    if (a >= 1e3) return s + '$' + Math.round(a / 1e3) + 'k';
    return s + '$' + Math.round(a);
};
const fechaCorta = f => { const d = parseF(f); return d.getDate() + ' ' + MESES[d.getMonth()]; };
const fechaLarga = f => { const d = parseF(f); return DIAS[d.getDay()] + ' ' + d.getDate() + ' de ' + MESES[d.getMonth()] + ' ' + d.getFullYear(); };

const flujo = { range: 90, mode: 'neto', win: null, chartPos: null, chartNet: null, hoverIdx: -1 };

// Ventana visible: la posición conserva el saldo de apertura anterior al rango
// (posición actual − neto del rango), así nunca "arranca en cero" al filtrar.
function flujoWindow(range) {
    const win = FLUJO.serie.slice(-range);
    let sumNet = 0; win.forEach(x => sumNet += x.cob - x.des);
    let run = FLUJO.posicion - sumNet;
    const opening = run;
    const cum = win.map(x => run += x.cob - x.des);
    return { win, cum, opening, labels: win.map(x => x.f) };
}

// Línea de cero y cursor vertical compartido entre ambas gráficas
const flujoDecor = {
    id: 'flujoDecor',
    afterDraw(chart) {
        const { ctx, chartArea, scales } = chart;
        if (!chartArea) return;
        ctx.save();
        const y0 = scales.y.getPixelForValue(0);
        if (y0 >= chartArea.top && y0 <= chartArea.bottom) {
            ctx.strokeStyle = CC.axis; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(chartArea.left, y0); ctx.lineTo(chartArea.right, y0); ctx.stroke();
        }
        const i = flujo.hoverIdx;
        const el = i >= 0 ? chart.getDatasetMeta(0).data[i] : null;
        if (el) {
            ctx.strokeStyle = '#9aa3b2'; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(el.x, chartArea.top); ctx.lineTo(el.x, chartArea.bottom); ctx.stroke();
        }
        ctx.restore();
    }
};

function flujoTooltipLines(ctx) {
    if (ctx.datasetIndex !== 0) return null;
    const x = flujo.win.win[ctx.dataIndex];
    return [
        'Cobrado            ' + money(x.cob),
        'Desembolsado       ' + money(x.des),
        'Neto del día       ' + signed(x.cob - x.des),
        'Posición al cierre ' + money(flujo.win.cum[ctx.dataIndex]),
    ];
}

function flujoOnHover(evt, _active, chart) {
    const pts = chart.getElementsAtEventForMode(evt, 'index', { intersect: false }, false);
    const idx = pts.length ? pts[0].index : -1;
    if (idx === flujo.hoverIdx) return;
    flujo.hoverIdx = idx;
    const other = chart === flujo.chartPos ? flujo.chartNet : flujo.chartPos;
    if (other) other.draw();
}

function flujoBaseOptions() {
    return {
        responsive: true, maintainAspectRatio: false, animation: { duration: 250 },
        interaction: { mode: 'index', intersect: false },
        onHover: flujoOnHover,
        layout: { padding: { right: 8 } },
        plugins: {
            legend: { display: false },
            tooltip: {
                displayColors: false, backgroundColor: CC.ink, padding: 10, cornerRadius: 8,
                titleFont: { family: 'Sora', weight: '700', size: 11 },
                bodyFont: { family: 'DM Mono', size: 11 },
                callbacks: { title: items => fechaLarga(flujo.win.win[items[0].dataIndex].f), label: flujoTooltipLines },
            },
        },
        scales: {
            x: { grid: { display: false }, border: { display: false },
                 ticks: { color: CC.muted, font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 7,
                          callback: function (v) { return fechaCorta(this.getLabelForValue(v)); } } },
            y: { grid: { color: CC.hair }, border: { display: false },
                 ticks: { color: CC.muted, font: { size: 10 }, maxTicksLimit: 5, callback: v => moneyK(v) },
                 afterFit: scale => { scale.width = 58; } },   // mismo ancho en ambas gráficas → mismo eje X
        },
    };
}

function flujoDatasetsNet() {
    const w = flujo.win;
    if (flujo.mode === 'comparar') {
        return [
            { label: 'Cobros', type: 'line', data: w.win.map(x => x.cob), borderColor: CC.cob, backgroundColor: 'rgba(5,150,105,.10)',
              fill: 'origin', borderWidth: 2, tension: .25, pointRadius: 0, pointHoverRadius: 5, pointHoverBackgroundColor: CC.cob, pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2, order: 1 },
            { label: 'Desembolsos', type: 'line', data: w.win.map(x => x.des), borderColor: CC.des, backgroundColor: 'transparent',
              borderWidth: 2, tension: .25, pointRadius: 0, pointHoverRadius: 5, pointHoverBackgroundColor: CC.des, pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2, order: 2 },
        ];
    }
    return [{
        label: 'Flujo neto', type: 'bar', data: w.win.map(x => x.cob - x.des),
        backgroundColor: ctx => (ctx.raw ?? 0) >= 0 ? CC.cob : CC.neg,
        borderRadius: 3, maxBarThickness: 22, categoryPercentage: .85, barPercentage: .9,
    }];
}

function flujoRender() {
    const w = flujo.win = flujoWindow(flujo.range);

    // Posición acumulada: azul por debajo de cero (capital en la calle), verde por encima
    const posData = {
        labels: w.labels,
        datasets: [{
            data: w.cum, borderWidth: 2, tension: .25, pointRadius: 0, pointHoverRadius: 5,
            pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2,
            pointHoverBackgroundColor: ctx => (ctx.raw ?? 0) >= 0 ? CC.cob : CC.des,
            borderColor: CC.des,
            segment: { borderColor: ctx => (ctx.p0.parsed.y >= 0 && ctx.p1.parsed.y >= 0) ? CC.cob : CC.des },
            fill: { target: 'origin', above: 'rgba(5,150,105,.12)', below: 'rgba(42,120,214,.12)' },
        }],
    };
    if (!flujo.chartPos) {
        const opt = flujoBaseOptions();
        opt.scales.x.ticks.display = false;
        flujo.chartPos = new Chart(document.getElementById('chartPosicion'), { type: 'line', data: posData, options: opt, plugins: [flujoDecor] });
    } else {
        flujo.chartPos.data = posData;
        flujo.chartPos.update('none');
    }

    const netData = { labels: w.labels, datasets: flujoDatasetsNet() };
    if (flujo.chartNet) { flujo.chartNet.destroy(); flujo.chartNet = null; }
    flujo.chartNet = new Chart(document.getElementById('chartFlujoNeto'), {
        type: flujo.mode === 'comparar' ? 'line' : 'bar', data: netData, options: flujoBaseOptions(), plugins: [flujoDecor],
    });

    flujoLectura(w);
}

// Panel "Lectura del periodo" + delta del hero
function flujoLectura(w) {
    let pos = 0, neg = 0, zero = 0, best = 0, worst = 0, bi = -1, wi = -1, sumNet = 0, cob = 0, des = 0;
    w.win.forEach((x, i) => {
        const n = x.cob - x.des;
        if (n > 0) pos++; else if (n < 0) neg++; else zero++;
        if (n > best)  { best = n;  bi = i; }
        if (n < worst) { worst = n; wi = i; }
        sumNet += n; cob += x.cob; des += x.des;
    });
    const total = w.win.length || 1, moving = (pos + neg) || 1;
    let streak = 0, sign = 0;
    for (let j = w.win.length - 1; j >= 0; j--) {
        const n = w.win[j].cob - w.win[j].des; if (n === 0) continue;
        const s = n > 0 ? 1 : -1;
        if (sign === 0) sign = s;
        if (s !== sign) break;
        streak++;
    }
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('flPosDays', pos);  set('flPosPct', Math.round(pos / total * 100) + '%');
    set('flNegDays', neg);  set('flNegPct', Math.round(neg / total * 100) + '%');
    set('flZeroDays', zero);
    document.getElementById('flBarPos').style.width  = (pos  / total * 100).toFixed(1) + '%';
    document.getElementById('flBarNeg').style.width  = (neg  / total * 100).toFixed(1) + '%';
    document.getElementById('flBarZero').style.width = (zero / total * 100).toFixed(1) + '%';
    set('flBest',  bi >= 0 ? signed(best)  : '—'); set('flBestDate',  bi >= 0 ? fechaCorta(w.win[bi].f) : '');
    set('flWorst', wi >= 0 ? signed(worst) : '—'); set('flWorstDate', wi >= 0 ? fechaCorta(w.win[wi].f) : '');
    set('flAvg', signed(Math.round(sumNet / moving)));
    set('flCob', money(cob)); set('flDes', money(des));
    set('flStreak', streak === 0 ? 'sin movimientos' : streak === 1 ? (sign > 0 ? '1 día positivo' : '1 día negativo')
        : streak + (sign > 0 ? ' días positivos seguidos' : ' días negativos seguidos'));
    document.querySelectorAll('[data-range-caption]').forEach(el => el.textContent = 'últimos ' + flujo.range + ' días');

    const delta = FLUJO.posicion - w.opening;
    const hero  = document.getElementById('flHeroDelta');
    if (hero) {
        hero.querySelector('em').textContent = (delta >= 0 ? 'Mejoró ' : 'Retrocedió ') + money(Math.abs(delta)) + ' en los últimos ' + flujo.range + ' días';
        hero.style.color = delta >= 0 ? '#047857' : '#b91c1c';
        hero.querySelector('svg path').setAttribute('d', delta >= 0 ? 'M2 11.5 6 7.5l3 3 5-5.5' : 'M2 4.5 6 8.5l3-3 5 5.5');
        hero.querySelectorAll('svg path')[1].setAttribute('d', delta >= 0 ? 'M10 5h4v4' : 'M10 11h4V7');
    }
}

function flujoSetRange(r) {
    flujo.range = r; flujo.hoverIdx = -1;
    document.querySelectorAll('.ad-seg [data-range]').forEach(b => b.classList.toggle('is-active', Number(b.dataset.range) === r));
    flujoRender();
}
function flujoSetMode(m) {
    flujo.mode = m; flujo.hoverIdx = -1;
    document.querySelectorAll('.ad-seg [data-mode]').forEach(b => b.classList.toggle('is-active', b.dataset.mode === m));
    document.getElementById('flujoPlot2Title').textContent = m === 'neto' ? 'Flujo neto por día (cobros − desembolsos)' : 'Cobros y desembolsos por día';
    document.getElementById('flujoKeysNeto').style.display     = m === 'neto' ? '' : 'none';
    document.getElementById('flujoKeysComparar').style.display = m === 'neto' ? 'none' : '';
    flujoRender();
}

document.getElementById('flujoCharts').addEventListener('mouseleave', () => {
    flujo.hoverIdx = -1;
    if (flujo.chartPos) flujo.chartPos.draw();
    if (flujo.chartNet) flujo.chartNet.draw();
});

flujoRender();

// ── Expandir / contraer detalle de sección ───────────────────
function abrirDetalle(id, btnId) {
    const el = document.getElementById(id);
    if (el && (el.style.display === 'none' || !el.style.display)) toggleDetail(id, document.getElementById(btnId));
    el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function toggleDetail(id, btn) {
    const el = document.getElementById(id);
    if (!el) return;
    const show = el.style.display === 'none' || !el.style.display;
    el.style.display = show ? 'block' : 'none';
    if (!btn) return;
    btn.classList.toggle('open', show);
    const txt = btn.querySelector('.ad-toggle-txt');
    if (txt) txt.textContent = show ? 'Ocultar detalle' : 'Ver detalle';
}

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
