@extends('layouts.app')

@php $nombreAdmin = $f->admin->alias ?: ($f->admin->nombre ?: $f->admin->usuario); @endphp

@section('title', 'Financiamiento · ' . $nombreAdmin)

@push('styles')
<style>
/* ── Layout general ─────────────────────────────────────── */
.fd-back{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--text3);text-decoration:none;margin-bottom:14px;transition:color .15s}
.fd-back:hover{color:var(--accent)}
.fd-header{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:20px 24px;margin-bottom:16px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;box-shadow:var(--shadow-sm)}
.fd-avatar{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:21px;font-weight:800;color:#fff;flex-shrink:0}
.fd-title{font-size:20px;font-weight:800;letter-spacing:-.03em;color:var(--text);line-height:1.2}
.fd-sub{font-size:12px;color:var(--text2);margin-top:3px;display:flex;gap:6px 12px;flex-wrap:wrap;align-items:center}
.fd-actions{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}

/* KPIs */
.fd-kpi-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:16px}
.fd-kpi{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:15px 17px;position:relative;overflow:hidden;box-shadow:var(--shadow-sm)}
.fd-kpi-accent{position:absolute;top:0;left:0;width:3px;height:100%;box-shadow:0 0 14px 0 currentColor;opacity:.95}
.fd-kpi-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:6px}
.fd-kpi-value{font-size:20px;font-weight:800;letter-spacing:-.03em;color:var(--text);line-height:1;font-variant-numeric:tabular-nums}
.fd-kpi-sub{font-size:11px;color:var(--text2);margin-top:5px}

/* Cards de contenido */
.fd-box{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;margin-bottom:16px;box-shadow:var(--shadow-sm);min-width:0}
.fd-box-title{font-size:12px;font-weight:800;color:var(--text);margin-bottom:2px}
.fd-box-sub{font-size:11px;color:var(--text2);margin-bottom:10px}
.fd-canvas{position:relative;height:300px;width:100%;min-width:0}
.fd-canvas-sm{position:relative;height:230px;width:100%;min-width:0}
.fd-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}

/* Pills */
.fin-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:10px;font-weight:700;white-space:nowrap}
.fin-pill-green{background:#f0fdf4;color:#10b981}
.fin-pill-gray{background:#f3f4f6;color:#6b7280}
.fin-pill-blue{background:#eff6ff;color:#1d4ed8}
.fin-pill-purple{background:#f5f3ff;color:#7c3aed}
.fin-pill-red{background:#fef2f2;color:#dc2626}
.fin-pill-yellow{background:#fefce8;color:#ca8a04}
.fin-pill-orange{background:#fff7ed;color:#ea580c}

/* Barra de reparto */
.fin-split-bar{display:flex;height:26px;border-radius:8px;overflow:hidden;background:#f3f4f6;margin:6px 0 8px}
.fin-split-seg{display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:#fff;white-space:nowrap;min-width:0;overflow:hidden}

/* Tablas */
.fd-table-wrap{overflow:auto;max-height:340px;border-radius:0 0 10px 10px}
.fin-inv{background:var(--card);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:16px;box-shadow:var(--shadow-sm)}
.fin-inv-head{display:flex;align-items:center;justify-content:space-between;padding:13px 18px;border-bottom:1px solid var(--border);background:#fcfcfb;gap:10px;flex-wrap:wrap}
.fin-inv-title{font-size:12px;font-weight:800;color:var(--text)}
.fin-inv-table{width:100%;border-collapse:collapse}
.fin-inv-table th{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);text-align:left;padding:8px 14px;background:#f9fafb;border-bottom:1px solid var(--border);position:sticky;top:0}
.fin-inv-table td{font-size:12px;color:var(--text);padding:10px 14px;border-bottom:1px solid var(--border);font-variant-numeric:tabular-nums;vertical-align:middle}
.fin-inv-table tr:last-child td{border-bottom:none}
.fin-inv-table tr:hover td{background:#f9fafb}
.fin-inv-table tr.inactivo td{opacity:.55}
.fin-inv-add{padding:14px 18px;border-top:1px dashed var(--border);background:#fcfcfb}
.fin-inv-add-grid{display:grid;grid-template-columns:1.3fr .9fr 1fr .7fr .9fr auto auto;gap:10px;align-items:end}
.fin-hist-empty{padding:26px;text-align:center;color:var(--text3);font-size:12px}
.fin-mov-del{background:none;border:none;cursor:pointer;color:#d1d5db;padding:3px 6px;border-radius:5px;font-size:13px;transition:color .15s}
.fin-mov-del:hover{color:#ef4444}
.fin-mov-detalle{font-size:10px;color:var(--text3);margin-top:2px}

/* Actividad · línea de tiempo */
.fd-act-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:13px 18px;border-bottom:1px solid var(--border);background:#fcfcfb}
.fd-act-filters{display:flex;gap:6px;flex-wrap:wrap}
.fd-act-filter{background:#f3f4f6;border:1px solid var(--border);border-radius:99px;padding:5px 12px;font-size:11px;font-weight:700;color:var(--text2);cursor:pointer;font-family:var(--font);transition:background .15s,color .15s,border-color .15s}
.fd-act-filter:hover{background:#e5e7eb}
.fd-act-filter.active{background:var(--accent);border-color:var(--accent);color:#fff}
.fd-act-filter .fd-act-cnt{opacity:.7;font-weight:800;margin-left:3px}
.fd-act-body{padding:4px 18px 10px;max-height:560px;overflow:auto}
.fd-act-day{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);padding:12px 0 2px;position:sticky;top:0;background:var(--card);z-index:1}
.fd-act-item{display:grid;grid-template-columns:28px 1fr;gap:12px;padding:12px 0}
.fd-act-item+.fd-act-item{border-top:1px solid var(--border)}
.fd-act-rail{display:flex;justify-content:center}
.fd-act-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-size:14px;font-weight:800;line-height:1}
.fd-act-main{min-width:0}
.fd-act-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap}
.fd-act-title{font-size:13px;font-weight:800;color:var(--text)}
.fd-act-amount{font-size:14px;font-weight:800;font-variant-numeric:tabular-nums;white-space:nowrap}
.fd-act-date{font-size:11px;color:var(--text3);white-space:nowrap;margin-top:1px}
.fd-act-meta{font-size:11px;color:var(--text2);margin-top:4px;display:flex;gap:4px 12px;flex-wrap:wrap;align-items:center}
.fd-act-reparto{margin-top:8px;background:#f9fafb;border:1px solid var(--border);border-radius:8px;padding:8px 12px}
.fd-act-rrow{display:flex;justify-content:space-between;gap:10px;padding:2px 0;font-size:11px;font-variant-numeric:tabular-nums}
.fd-act-rrow+.fd-act-rrow{border-top:1px dashed var(--border)}
.fd-act-note{margin-top:6px;font-size:11px;color:var(--text2);font-style:italic}
.fd-act-del{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;font-size:11px;font-weight:700;cursor:pointer;padding:3px 9px;border-radius:6px;font-family:var(--font);display:inline-flex;align-items:center;gap:4px}
.fd-act-del:hover{background:#fee2e2;border-color:#fca5a5}
.fd-act-empty{padding:32px;text-align:center;color:var(--text3);font-size:12px;display:none}

/* Forms */
.fin-forms-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px}
.fin-form-box{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:16px 18px;box-shadow:var(--shadow-sm)}
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
.fin-error{background:#fef2f2;border:1px solid rgba(220,38,38,.2);color:#b91c1c;font-size:12px;border-radius:8px;padding:10px 14px;margin-bottom:14px}

/* Modal */
.fin-modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(6px);z-index:1000;align-items:center;justify-content:center}
.fin-modal-overlay.open{display:flex}
.fin-modal{background:#fff;border-radius:18px;width:520px;max-width:calc(100vw - 24px);box-shadow:0 20px 60px rgba(0,0,0,.18);max-height:90vh;overflow-y:auto;overflow-x:hidden}
.fin-modal-header{padding:22px 28px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.fin-modal-title{font-size:17px;font-weight:700}
.fin-modal-close{background:#f1f5f9;border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:18px;color:var(--text3);display:flex;align-items:center;justify-content:center}
.fin-modal-body{padding:24px 28px;display:grid;gap:16px}
.fin-modal-footer{padding:16px 28px;background:#f8fafc;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
.fin-modal-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;align-items:end}

/* Responsive */
@media(max-width:1200px){
    .fd-kpi-grid{grid-template-columns:repeat(3,1fr)}
    .fd-grid-2{grid-template-columns:1fr}
    .fin-forms-row{grid-template-columns:1fr}
    .fin-inv-add-grid{grid-template-columns:1fr 1fr}
}
@media(max-width:768px){
    .fd-kpi-grid{grid-template-columns:1fr 1fr}
    .fd-actions{margin-left:0;width:100%}
    .fin-form-grid{grid-template-columns:1fr}
    .fin-inv{overflow-x:auto}
    .fin-inv-add-grid{grid-template-columns:1fr}
    .fin-modal-row-3{grid-template-columns:1fr}
}
@media(max-width:480px){.fd-kpi-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

@php
    $money  = fn($v) => '$' . number_format($v, 2);
    $fmtPct = fn($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
    $avatarColors = ['#10b981','#6366f1','#f59e0b','#ec4899','#14b8a6','#8b5cf6','#ef4444','#0ea5e9'];
    $invColors    = ['#f59e0b','#0ea5e9','#ec4899','#14b8a6','#8b5cf6','#ef4444','#84cc16','#f97316'];
    $color   = $avatarColors[$f->admin_id % count($avatarColors)];
    $rendPer = $f->rendimiento_periodo;
    $activosI = $f->inversoresActivos();
    $tipoLabels = [
        'rendimiento'         => ['Rendimiento',      'fin-pill-green'],
        'aporte'              => ['Aporte',           'fin-pill-blue'],
        'retiro'              => ['Retiro owner',     'fin-pill-red'],
        'salida_inversor'     => ['Salida inversor',  'fin-pill-orange'],
        'transferencia_owner' => ['Transf. a owner',  'fin-pill-purple'],
    ];
    $estadoPills = [
        'cubierto' => ['Cubierto',  'fin-pill-green'],
        'parcial'  => ['Parcial',   'fin-pill-yellow'],
        'atrasado' => ['Atrasado',  'fin-pill-red'],
        'en_curso' => ['En curso',  'fin-pill-blue'],
        'proximo'  => ['Próximo',   'fin-pill-gray'],
    ];

    // Cumplimiento a hoy: lo cobrado contra lo que la tasa dicta al día de hoy
    $teoHoy  = $serie['teorico_hoy'];
    $realHoy = $serie['real_hoy'];
    $cumpl   = $teoHoy > 0 ? round($realHoy / $teoHoy * 100, 1) : null;
    $dif     = round($realHoy - $teoHoy, 2);
    $cumplColor = $cumpl === null ? '#9aa3b2' : ($cumpl >= 97 ? '#10b981' : ($cumpl >= 85 ? '#d97706' : '#dc2626'));
@endphp

<a href="{{ route('owner.financiamientos.index') }}" class="fd-back">
    <svg viewBox="0 0 14 14" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 2L4 7l5 5"/></svg>
    Inversiones externas
</a>

@if(session('success'))
    <div style="background:#f0fdf4;border:1px solid rgba(16,185,129,.25);color:#047857;font-size:12px;border-radius:8px;padding:10px 14px;margin-bottom:14px">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="fin-error">
        @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
    </div>
@endif

{{-- ── Header ─────────────────────────────────────────────── --}}
<div class="fd-header">
    <div class="fd-avatar" style="background:{{ $color }}">{{ strtoupper(substr($nombreAdmin, 0, 1)) }}</div>
    <div>
        <div class="fd-title">{{ $nombreAdmin }}</div>
        <div class="fd-sub">
            <span class="fin-pill {{ $f->estatus === 'Activo' ? 'fin-pill-green' : 'fin-pill-gray' }}">{{ $f->estatus }}</span>
            <span><b style="color:#10b981">{{ $fmtPct($f->rendimiento_pct) }}%</b> {{ $f->frecuencia }}</span>
            <span>desde {{ $f->fecha_inicio->format('d/m/Y') }}</span>
            <span>convenio {{ $f->plazo_meses }} meses</span>
            @if($f->notas)<span title="{{ $f->notas }}">📝 {{ \Illuminate\Support\Str::limit($f->notas, 60) }}</span>@endif
        </div>
    </div>
    <div class="fd-actions">
        @php
            $editData = [
                'id'              => $f->id,
                'rendimiento_pct' => $f->rendimiento_pct,
                'frecuencia'      => $f->frecuencia,
                'plazo_meses'     => $f->plazo_meses,
                'fecha_inicio'    => $f->fecha_inicio->toDateString(),
                'notas'           => $f->notas,
                'nombre'          => $nombreAdmin,
                'fijos_mensuales' => $f->fijos_mensuales,
            ];
        @endphp
        <button class="btn btn-sm" style="background:#eff6ff;color:#1d4ed8" onclick='finOpenEditar(@json($editData))'>Editar acuerdo</button>
        <form method="POST" action="{{ route('owner.financiamientos.toggle', $f->id) }}"
              onsubmit="return confirm('{{ $f->estatus === 'Activo' ? '¿Marcar esta cuenta de inversión como finalizada?' : '¿Reactivar esta cuenta de inversión?' }}')">
            @csrf
            <button type="submit" class="btn btn-sm" style="background:#f3f4f6;color:var(--text2)">{{ $f->estatus === 'Activo' ? 'Finalizar' : 'Reactivar' }}</button>
        </form>
        <form method="POST" action="{{ route('owner.financiamientos.destroy', $f->id) }}"
              onsubmit="return confirm('¿Eliminar esta cuenta de inversión, sus inversores y TODO su historial? Esta acción no se puede deshacer.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm" style="background:#fef2f2;color:#dc2626">Eliminar</button>
        </form>
    </div>
</div>

{{-- ── KPIs ───────────────────────────────────────────────── --}}
<div class="fd-kpi-grid">
    <div class="fd-kpi">
        <div class="fd-kpi-accent" style="color:#10b981;background:#10b981"></div>
        <div class="fd-kpi-label">Capital en inversión</div>
        <div class="fd-kpi-value">{{ $money($f->capital_actual) }}</div>
        <div class="fd-kpi-sub">aportes activos {{ $money($stats['aportado_activo']) }} + reinversión</div>
    </div>
    <div class="fd-kpi">
        <div class="fd-kpi-accent" style="color:#94a3b8;background:#94a3b8"></div>
        <div class="fd-kpi-label">Teórico a hoy</div>
        <div class="fd-kpi-value">{{ $money($teoHoy) }}</div>
        <div class="fd-kpi-sub">lo que la tasa del {{ $fmtPct($f->rendimiento_pct) }}% dicta al día de hoy</div>
    </div>
    <div class="fd-kpi">
        <div class="fd-kpi-accent" style="color:#0ea5e9;background:#0ea5e9"></div>
        <div class="fd-kpi-label">Cobrado real</div>
        <div class="fd-kpi-value">{{ $money($realHoy) }}</div>
        <div class="fd-kpi-sub">{{ $stats['n_rendimientos'] }} cobro{{ $stats['n_rendimientos'] === 1 ? '' : 's' }} registrados</div>
    </div>
    <div class="fd-kpi">
        <div class="fd-kpi-accent" style="color:{{ $cumplColor }};background:{{ $cumplColor }}"></div>
        <div class="fd-kpi-label">Cumplimiento</div>
        <div class="fd-kpi-value" style="color:{{ $cumplColor }}">{{ $cumpl === null ? '—' : $fmtPct($cumpl) . '%' }}</div>
        <div class="fd-kpi-sub">
            @if($cumpl === null) aún no corre el primer periodo
            @elseif(abs($dif) < 0.01) exactamente al corriente
            @else {{ $dif > 0 ? '+' : '−' }}{{ $money(abs($dif)) }} vs teórico @endif
        </div>
    </div>
    <div class="fd-kpi">
        <div class="fd-kpi-accent" style="color:#6366f1;background:#6366f1"></div>
        <div class="fd-kpi-label">Próximo cobro</div>
        <div class="fd-kpi-value" style="font-size:17px">{{ $estado->proximo->locale('es')->isoFormat('D MMM YYYY') }}</div>
        <div class="fd-kpi-sub">
            @if($estado->atrasados > 0)
                <span class="fin-pill fin-pill-red">{{ $estado->atrasados }} atrasado{{ $estado->atrasados === 1 ? '' : 's' }} · {{ $money($estado->monto_atrasado) }}</span>
            @elseif($estado->parcial)
                restante {{ $money($estado->restante) }} del periodo en curso
            @else
                esperado {{ $money($estado->esperado) }}
            @endif
        </div>
    </div>
</div>

{{-- ── Gráfica teórico vs real ────────────────────────────── --}}
<div class="fd-box">
    <div class="fd-box-title">Rendimiento acumulado · teórico vs real</div>
    <div class="fd-box-sub">
        La línea punteada es lo esperado según la tasa (avanza día con día y se ajusta cuando entra o sale capital).
        Cada punto verde es un cobro <b>en su fecha real</b>; la línea vertical marca hoy.
    </div>
    <div class="fd-canvas"><canvas id="fdChartSerie"></canvas></div>
</div>

<div class="fd-grid-2" style="margin-bottom:16px">
    {{-- Evolución del capital --}}
    <div class="fd-box" style="margin-bottom:0">
        <div class="fd-box-title">Evolución del capital en inversión</div>
        <div class="fd-box-sub">Aportes, retiros, salidas de inversores y reinversiones capitalizadas, en su fecha.</div>
        <div class="fd-canvas-sm"><canvas id="fdChartCapital"></canvas></div>
    </div>

    {{-- Reparto del rendimiento --}}
    @php
        $ppm = $f->periodos_por_mes;
        $retFijos = $activosI->where('retorno_mensual', '>', 0)->values();
        $totalRetFijo = (float) $retFijos->sum(fn($i) => $i->retorno_mensual / $ppm);
        $reinvPer = max(0, $rendPer - $totalRetFijo);
    @endphp
    <div class="fd-box" style="margin-bottom:0">
        <div class="fd-box-title">Reparto del rendimiento por {{ $f->periodo_label }}</div>
        <div class="fd-box-sub">{{ $fmtPct($f->rendimiento_pct) }}% {{ $f->frecuencia }} sobre el capital vigente ≈ {{ $money($rendPer) }}</div>
        <div class="fin-split-bar" style="height:34px">
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
        <div style="font-size:11px;color:var(--text2);font-variant-numeric:tabular-nums;line-height:1.7">
            Cada {{ $f->periodo_label }}: <b style="color:#7c3aed">{{ $money($reinvPer) }}</b> se reinvierte
            @foreach($retFijos as $ix => $inv)
                <br>· <b style="color:{{ $invColors[$ix % count($invColors)] }}">{{ $money($inv->retorno_mensual / $ppm) }}</b> a {{ $inv->nombre }}
                ({{ $money($inv->retorno_mensual) }} fijos/mes ≈ {{ $fmtPct($inv->pct_retorno) }}% de su aporte{{ $ppm > 1 ? ' ÷ 4 semanas' : '' }})
            @endforeach
        </div>
    </div>
</div>

{{-- ── Comparativa por periodo ────────────────────────────── --}}
<div class="fin-inv">
    <div class="fin-inv-head">
        <div class="fin-inv-title">Comparativa por {{ $f->periodo_label }} · programado vs cobrado</div>
        <span style="font-size:11px;color:var(--text3)">la fecha del cobro determina la ventana a la que abona</span>
    </div>
    <div class="fd-table-wrap">
        <table class="fin-inv-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha programada</th>
                    <th>Capital del periodo</th>
                    <th>Esperado</th>
                    <th>Cobrado</th>
                    <th>Diferencia</th>
                    <th>Cobrado el</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            @foreach($periodos as $p)
                @php [$eLabel, $eClass] = $estadoPills[$p['estado']]; $pdif = round($p['cobrado'] - $p['esperado'], 2); @endphp
                <tr>
                    <td style="color:var(--text3)">{{ $p['n'] }}</td>
                    <td style="font-weight:700">{{ $p['programado']->format('d/m/Y') }}</td>
                    <td>{{ $money($p['capital']) }}</td>
                    <td>{{ $money($p['esperado']) }}</td>
                    <td style="font-weight:700">{{ $p['cobrado'] > 0 ? $money($p['cobrado']) : '—' }}</td>
                    <td style="color:{{ abs($pdif) < 0.01 || $p['cobrado'] <= 0 ? 'var(--text3)' : ($pdif > 0 ? '#10b981' : '#dc2626') }}">
                        @if($p['cobrado'] <= 0) — @elseif(abs($pdif) < 0.01) ✓ exacto @else {{ $pdif > 0 ? '+' : '−' }}{{ $money(abs($pdif)) }} @endif
                    </td>
                    <td style="color:var(--text2)">{{ empty($p['fechas']) ? '—' : implode(' · ', $p['fechas']) }}</td>
                    <td><span class="fin-pill {{ $eClass }}">{{ $eLabel }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ── Forms ──────────────────────────────────────────────── --}}
@if($f->estatus === 'Activo')
<div class="fin-forms-row">
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
                    <input type="number" name="monto" id="finRendMonto{{ $f->id }}" step="0.01" min="0.01" required placeholder="0.00" oninput="finRendPreview({{ $f->id }})">
                </div>
                <div class="fin-field">
                    <label>Fecha</label>
                    <input type="date" name="fecha" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
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
                    <input type="date" name="fecha" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
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

{{-- ── Inversores ─────────────────────────────────────────── --}}
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

{{-- ── Actividad de la cuenta (línea de tiempo) ─────────────── --}}
@php
    // Icono, color, etiqueta, signo y categoría (para el filtro) por tipo.
    $actMeta = [
        'rendimiento'         => ['col' => '#10b981', 'ic' => '$', 'sig' => '+', 'cat' => 'cobros'],
        'aporte'              => ['col' => '#1d4ed8', 'ic' => '+', 'sig' => '+', 'cat' => 'capital'],
        'retiro'              => ['col' => '#dc2626', 'ic' => '−', 'sig' => '−', 'cat' => 'capital'],
        'salida_inversor'     => ['col' => '#ea580c', 'ic' => '↩', 'sig' => '−', 'cat' => 'inversores'],
        'transferencia_owner' => ['col' => '#7c3aed', 'ic' => '⇄', 'sig' => '',  'cat' => 'inversores'],
    ];
    $actFallback = ['col' => '#6b7280', 'ic' => '•', 'sig' => '', 'cat' => 'capital'];
    $counts = ['todos' => $f->movimientos->count(), 'cobros' => 0, 'capital' => 0, 'inversores' => 0];
    foreach ($f->movimientos as $m) { $counts[($actMeta[$m->tipo] ?? $actFallback)['cat']]++; }
    // Movimientos ya vienen ordenados por fecha desc, id desc; agrupar por día.
    $gruposAct = $f->movimientos->groupBy(fn($m) => $m->fecha->format('Y-m-d'));
    $filtros = ['todos' => 'Todos', 'cobros' => 'Cobros', 'capital' => 'Capital', 'inversores' => 'Inversores'];
@endphp
<div class="fin-inv">
    <div class="fd-act-head">
        <div class="fin-inv-title">Actividad de la cuenta</div>
        @if($f->movimientos->isNotEmpty())
        <div class="fd-act-filters">
            @foreach($filtros as $key => $lbl)
                <button type="button" class="fd-act-filter {{ $key === 'todos' ? 'active' : '' }}" onclick="fdActFilter('{{ $key }}', this)">
                    {{ $lbl }}<span class="fd-act-cnt">{{ $counts[$key] }}</span>
                </button>
            @endforeach
        </div>
        @endif
    </div>

    @if($f->movimientos->isEmpty())
        <div class="fin-hist-empty">Sin movimientos registrados todavía.</div>
    @else
    <div class="fd-act-body">
        @foreach($gruposAct as $dia => $movs)
        @php $d = \Carbon\Carbon::parse($dia)->startOfDay(); @endphp
        <div class="fd-act-group">
            <div class="fd-act-day">{{ ucfirst($d->locale('es')->isoFormat('dddd D [de] MMMM, YYYY')) }} · {{ $d->locale('es')->diffForHumans() }}</div>

            @foreach($movs as $m)
            @php
                $meta  = $actMeta[$m->tipo] ?? $actFallback;
                $title = match($m->tipo) {
                    'rendimiento'         => 'Cobro de rendimiento',
                    'aporte'              => $m->inversor ? 'Aporte de ' . $m->inversor->nombre . ($m->inversor->es_owner ? ' (owner)' : '') : 'Aporte de capital',
                    'retiro'              => 'Retiro del owner',
                    'salida_inversor'     => 'Salida de ' . ($m->inversor->nombre ?? 'inversor') . ' · devolución de aporte',
                    'transferencia_owner' => 'Convenio vencido · capital de ' . ($m->inversor->nombre ?? 'inversor') . ' al owner',
                    default               => ucfirst($m->tipo),
                };
                $puede_eliminar = $m->inversor_id === null && !in_array($m->tipo, ['salida_inversor', 'transferencia_owner']);
            @endphp
            <div class="fd-act-item" data-cat="{{ $meta['cat'] }}">
                <div class="fd-act-rail"><div class="fd-act-dot" style="background:{{ $meta['col'] }}">{{ $meta['ic'] }}</div></div>
                <div class="fd-act-main">
                    <div class="fd-act-top">
                        <div style="min-width:0">
                            <span class="fd-act-title">{{ $title }}</span>
                            @if($m->tipo === 'rendimiento' && $m->capitalizado)
                                <span class="fin-pill fin-pill-purple" style="margin-left:5px" title="La reinversión se sumó al capital">capitalizado</span>
                            @endif
                        </div>
                        <div style="text-align:right">
                            <div class="fd-act-amount" style="color:{{ $meta['col'] }}">{{ $meta['sig'] }}{{ $money($m->monto) }}</div>
                            <div class="fd-act-date">{{ $m->fecha->format('d/m/Y') }}</div>
                        </div>
                    </div>

                    <div class="fd-act-meta">
                        @if($m->tipo === 'rendimiento')
                            @php $per = $f->ventanaDe($m); @endphp
                            <span class="fin-pill fin-pill-gray">Periodo {{ $per }} · programado {{ $f->fechaCobro($per)->format('d/m/y') }}</span>
                        @endif
                        @if($m->registradoPor)
                            <span>registró <b>{{ $m->registradoPor->alias ?: $m->registradoPor->usuario }}</b>{{ $m->registradoPor->puesto === 'supervisor' ? ' (supervisor)' : '' }}</span>
                        @endif
                        @if($puede_eliminar)
                            <form method="POST" action="{{ route('owner.financiamientos.movimientos.destroy', [$f->id, $m->id]) }}" style="margin-left:auto"
                                  onsubmit="return confirm('¿Eliminar este movimiento? El capital se ajustará automáticamente.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="fd-act-del" title="Eliminar movimiento">🗑 Eliminar cobro</button>
                            </form>
                        @endif
                    </div>

                    @if($m->tipo === 'rendimiento')
                    @php
                        $tieneReinvDesglosada = collect($m->detalle ?? [])->contains(fn($dd) => ($dd['reinversion'] ?? 0) > 0);
                    @endphp
                    <div class="fd-act-reparto">
                        @if($m->monto_reinversion > 0)
                        <div class="fd-act-rrow">
                            <span style="color:#7c3aed;font-weight:700">Reinversión total{{ $m->capitalizado ? ' · sumada al capital' : '' }}</span>
                            <span style="color:#7c3aed;font-weight:800">{{ $money($m->monto_reinversion) }}</span>
                        </div>
                        @endif
                        @foreach($m->detalle ?? [] as $dd)
                            @if(($dd['monto'] ?? 0) > 0)
                            <div class="fd-act-rrow">
                                <span>→ Retorno en efectivo a <b>{{ $dd['nombre'] }}</b> <span style="color:var(--text3)">({{ $fmtPct($dd['pct']) }}% de su aporte)</span></span>
                                <span style="color:#d97706;font-weight:700">{{ $money($dd['monto']) }}</span>
                            </div>
                            @endif
                            @if(($dd['reinversion'] ?? 0) > 0)
                            <div class="fd-act-rrow">
                                <span>→ Reinversión de <b>{{ $dd['nombre'] }}</b> <span style="color:var(--text3)">· capital propio, no toma retorno fijo</span></span>
                                <span style="color:#7c3aed;font-weight:700">{{ $money($dd['reinversion']) }}</span>
                            </div>
                            @endif
                        @endforeach
                        @if($m->monto_reinversion > 0 && !$tieneReinvDesglosada)
                        <div class="fd-act-rrow"><span style="color:var(--text3)">Capital compartido del fondo (cobro registrado antes del desglose por inversor)</span><span></span></div>
                        @endif
                        @if($m->monto_reinversion <= 0 && empty($m->detalle))
                        <div class="fd-act-rrow"><span style="color:var(--text3)">Sin reparto registrado</span><span></span></div>
                        @endif
                        <div class="fd-act-rrow" style="border-top:1px solid var(--border);margin-top:2px;padding-top:5px">
                            <span style="color:var(--text3)">Total cobrado al admin</span>
                            <span style="font-weight:800">{{ $money($m->monto) }}</span>
                        </div>
                    </div>
                    @endif

                    @if($m->nota)<div class="fd-act-note">📝 {{ $m->nota }}</div>@endif
                </div>
            </div>
            @endforeach
        </div>
        @endforeach
        <div class="fd-act-empty" id="fdActEmpty">No hay movimientos de esta categoría.</div>
    </div>
    @endif
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

<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
const finFmt  = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
const finFmt0 = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
const fdSerie   = @json($serie);
const fdCapital = @json($capital);
const fdInversores = @json($inversoresJs);
const fdPpm = {{ $f->periodos_por_mes }};

/* Línea vertical punteada en "Hoy" */
function fdHoyLinePlugin(labels) {
    const idx = labels.indexOf('Hoy');
    return {
        id: 'fdHoyLine',
        afterDatasetsDraw(chart) {
            if (idx < 0) return;
            const x = chart.scales.x.getPixelForValue(idx);
            const { top, bottom } = chart.chartArea;
            const ctx = chart.ctx;
            ctx.save();
            ctx.strokeStyle = 'rgba(99,102,241,.5)';
            ctx.setLineDash([4, 4]);
            ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(x, top); ctx.lineTo(x, bottom); ctx.stroke();
            ctx.fillStyle = 'rgba(99,102,241,.85)';
            ctx.font = '700 9px Sora';
            ctx.textAlign = 'center';
            ctx.fillText('HOY', x, top + 10);
            ctx.restore();
        },
    };
}

const fdLegend = { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, font: { size: 10, family: 'Sora' }, padding: 10 } };
const fdScales = {
    y: { ticks: { font: { size: 9, family: 'Sora' }, callback: v => finFmt0.format(v) }, grid: { color: 'rgba(15,22,35,.05)' } },
    x: { ticks: { font: { size: 9, family: 'Sora' }, maxTicksLimit: 14 }, grid: { display: false } },
};

/* 1) Teórico vs real con fechas reales de cobro */
new Chart(document.getElementById('fdChartSerie'), {
    type: 'line',
    data: {
        labels: fdSerie.labels,
        datasets: [
            { label: 'Teórico (según la tasa)', data: fdSerie.teorico, borderColor: '#94a3b8', backgroundColor: 'transparent',
              borderDash: [6, 4], tension: .2, pointRadius: 0, borderWidth: 2 },
            { label: 'Real cobrado al admin', data: fdSerie.real, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.10)',
              fill: true, tension: .15, borderWidth: 2.5, spanGaps: false,
              pointRadius: ctx => fdSerie.cobros[ctx.dataIndex] ? 4 : 0,
              pointHoverRadius: 6, pointBackgroundColor: '#10b981', pointBorderColor: '#fff', pointBorderWidth: 1.5 },
        ],
    },
    options: {
        maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: fdLegend,
            tooltip: { callbacks: {
                label: c => c.parsed.y === null ? null : ' ' + c.dataset.label + ': ' + finFmt.format(c.parsed.y),
                afterBody: items => (items.some(i => i.datasetIndex === 1) && fdSerie.cobros[items[0].dataIndex]) ? ['● Cobro registrado este día'] : [],
            } },
        },
        scales: fdScales,
    },
    plugins: [fdHoyLinePlugin(fdSerie.labels)],
});

/* 2) Evolución del capital (escalonada: cada movimiento en su fecha) */
new Chart(document.getElementById('fdChartCapital'), {
    type: 'line',
    data: {
        labels: fdCapital.labels,
        datasets: [{ label: 'Capital en inversión', data: fdCapital.data, borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.08)',
            fill: true, stepped: true, pointRadius: ctx => (fdCapital.detalles[ctx.dataIndex] || []).length ? 3.5 : 2,
            pointBackgroundColor: '#7c3aed', pointBorderColor: '#fff', pointBorderWidth: 1, borderWidth: 2 }],
    },
    options: {
        maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: fdLegend,
            tooltip: { callbacks: {
                label: c => ' Capital: ' + finFmt.format(c.parsed.y),
                afterBody: items => fdCapital.detalles[items[0].dataIndex] || [],
            } },
        },
        scales: fdScales,
    },
    plugins: [fdHoyLinePlugin(fdCapital.labels)],
});

/* ── Helpers compartidos con el listado ──────────────────── */
function finRendPreview(id) {
    const monto = parseFloat(document.getElementById('finRendMonto' + id).value) || 0;
    let totalFijo = 0;
    const fijos = fdInversores.map(inv => {
        const f = inv.pend !== undefined ? inv.pend : Math.round(inv.ret / fdPpm * 100) / 100;
        totalFijo += f;
        return { nombre: inv.nombre, fijo: f };
    }).filter(x => x.fijo > 0);
    const escala = (totalFijo > 0 && totalFijo > monto) ? monto / totalFijo : 1;
    let retornado = 0;
    const partes = fijos.map(x => {
        const r = Math.round(x.fijo * escala * 100) / 100;
        retornado += r;
        return '→ ' + x.nombre + ': <b>' + finFmt.format(r) + '</b>';
    });
    document.getElementById('finPrevReinv' + id).textContent = finFmt.format(Math.max(0, Math.round((monto - retornado) * 100) / 100));
    document.getElementById('finPrevInvList' + id).innerHTML = partes.length ? partes.join(' · ') : '(fijos del periodo ya pagados: todo se reinvierte)';
}

function finUsarEsperado(id, esperado) {
    const input = document.getElementById('finRendMonto' + id);
    input.value = esperado.toFixed(2);
    input.dispatchEvent(new Event('input'));
}

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

document.querySelectorAll('.fin-modal-overlay').forEach(ov => {
    ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('open'); });
});

// Filtro de la línea de tiempo de actividad. Oculta los ítems que no son de la
// categoría y las cabeceras de día que quedan sin ítems visibles.
function fdActFilter(cat, btn) {
    document.querySelectorAll('.fd-act-filter').forEach(b => b.classList.toggle('active', b === btn));
    let anyVisible = false;
    document.querySelectorAll('.fd-act-group').forEach(g => {
        let groupVisible = false;
        g.querySelectorAll('.fd-act-item').forEach(it => {
            const show = cat === 'todos' || it.dataset.cat === cat;
            it.style.display = show ? '' : 'none';
            if (show) groupVisible = true;
        });
        g.style.display = groupVisible ? '' : 'none';
        if (groupVisible) anyVisible = true;
    });
    const empty = document.getElementById('fdActEmpty');
    if (empty) empty.style.display = anyVisible ? 'none' : 'block';
}
</script>

@endsection
