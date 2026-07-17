@extends('layouts.app')

@section('title', 'Reporte semanal owner')

@push('styles')
<style>
.wr-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;margin-bottom:18px}
.wr-title{font-size:24px;font-weight:800;letter-spacing:-.02em;margin:0;color:var(--text)}
.wr-sub{font-size:13px;color:var(--text2);margin-top:4px}
.wr-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.wr-filter{display:flex;gap:8px;align-items:center;background:var(--card);border:1px solid var(--border);border-radius:8px;padding:8px}
.wr-filter input{border:1px solid var(--border);border-radius:6px;padding:8px 10px;font-size:13px;color:var(--text);background:#fff}
.wr-week-nav{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid var(--border);border-radius:6px;background:#fff;color:var(--text2);font-weight:800;text-decoration:none;font-size:15px}
.wr-week-nav:hover{background:#f3f4f6;color:var(--text)}
.wr-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}
.wr-kpi{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:14px}
.wr-kpi-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)}
.wr-kpi-value{font-size:22px;font-weight:800;color:var(--text);margin-top:6px}
.wr-kpi-value.pos{color:#047857}
.wr-kpi-value.neg{color:#b91c1c}
.wr-kpi-sub{font-size:11px;color:var(--text2);margin-top:4px;line-height:1.35}
.wr-delta{display:inline-flex;align-items:center;gap:3px;font-size:10.5px;font-weight:800;margin-top:5px;padding:2px 7px;border-radius:999px}
.wr-delta.up{background:#dcfce7;color:#166534}
.wr-delta.down{background:#fee2e2;color:#991b1b}
.wr-delta.flat{background:#f3f4f6;color:#4b5563}
.wr-panel{background:var(--card);border:1px solid var(--border);border-radius:8px;margin-bottom:16px;overflow:hidden}
.wr-panel-head{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border);background:#f9fafb;flex-wrap:wrap}
.wr-panel-title{font-size:14px;font-weight:800;color:var(--text)}
.wr-panel-sub{font-size:11px;color:var(--text3);font-weight:600}
.wr-panel-body{padding:16px}
.wr-charts{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
.wr-chart-box{position:relative;height:260px}
.wr-select{border:1px solid var(--border);border-radius:6px;padding:6px 9px;font-size:12px;color:var(--text);background:#fff;font-weight:600}
.wr-three{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.wr-card{border:1px solid var(--border);border-radius:8px;padding:13px;background:#fff}
.wr-card-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--text3)}
.wr-card-name{font-size:15px;font-weight:800;color:var(--text);margin-top:6px}
.wr-card-note{font-size:12px;color:var(--text2);line-height:1.45;margin-top:5px}
.wr-table-wrap{overflow:auto}
.wr-table{width:100%;border-collapse:collapse;font-size:12px;min-width:1180px}
.wr-table th{background:#f9fafb;border-bottom:1px solid var(--border);padding:9px 10px;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);white-space:nowrap}
.wr-table td{border-bottom:1px solid var(--border);padding:10px;vertical-align:top;white-space:nowrap}
.wr-table tr:last-child td{border-bottom:none}
.wr-table tfoot td{background:#f9fafb;font-weight:800;border-top:2px solid var(--border)}
.wr-admin{font-weight:800;color:var(--text)}
.wr-muted{font-size:11px;color:var(--text3);margin-top:2px}
.wr-score{display:inline-flex;align-items:center;justify-content:center;min-width:42px;border-radius:999px;padding:4px 8px;font-weight:800}
.wr-score.good{background:#dcfce7;color:#166534}
.wr-score.warn{background:#fef3c7;color:#92400e}
.wr-score.bad{background:#fee2e2;color:#991b1b}
.wr-pill{display:inline-flex;border-radius:999px;padding:3px 8px;font-size:11px;font-weight:700}
.wr-pill.green{background:#dcfce7;color:#166534}
.wr-pill.yellow{background:#fef3c7;color:#92400e}
.wr-pill.red{background:#fee2e2;color:#991b1b}
.wr-pill.gray{background:#f3f4f6;color:#4b5563}
.wr-alerts{display:grid;gap:4px;max-width:300px;white-space:normal}
.wr-alert{font-size:11px;color:var(--text2);line-height:1.35}
.wr-rec{font-size:12px;color:var(--text);line-height:1.45;white-space:normal;max-width:330px}
.wr-aging-table{min-width:820px}
.wr-aging-crit{color:#b91c1c;font-weight:800}
.wr-bar-track{height:6px;background:#f3f4f6;border-radius:999px;overflow:hidden;margin-top:5px;min-width:90px}
.wr-bar-fill{height:100%;border-radius:999px}
.wr-details{border:1px solid var(--border);border-radius:8px;background:#fff;margin-bottom:10px;overflow:hidden}
.wr-details summary{display:flex;align-items:center;gap:10px;padding:12px 14px;cursor:pointer;list-style:none;flex-wrap:wrap}
.wr-details summary::-webkit-details-marker{display:none}
.wr-details summary::before{content:'▸';font-size:12px;color:var(--text3);transition:transform .15s}
.wr-details[open] summary::before{transform:rotate(90deg)}
.wr-details-body{padding:0 14px 14px}
.wr-mini-table{width:100%;border-collapse:collapse;font-size:12px}
.wr-mini-table th{text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);border-bottom:1px solid var(--border);padding:6px 8px}
.wr-mini-table td{border-bottom:1px solid var(--border);padding:7px 8px}
.wr-mini-table tr:last-child td{border-bottom:none}
.wr-print-note{display:none}
@media(max-width:900px){
    .wr-head{flex-direction:column}
    .wr-grid,.wr-three{grid-template-columns:1fr 1fr}
    .wr-charts{grid-template-columns:1fr}
}
@media(max-width:560px){
    .wr-grid,.wr-three{grid-template-columns:1fr}
    .wr-filter{width:100%;flex-direction:column;align-items:stretch}
    .wr-actions{width:100%}
}
@media print{
    .sidebar,.topbar,.wr-actions,.btn{display:none!important}
    body{background:#fff!important}
    .main-content{margin:0!important;padding:0!important}
    .wr-panel,.wr-kpi,.wr-card,.wr-details{break-inside:avoid;border-color:#d1d5db}
    .wr-print-note{display:block;font-size:11px;color:#6b7280;margin-bottom:10px}
    .wr-table{font-size:10px;min-width:0}
    .wr-table th,.wr-table td{padding:6px}
    .wr-details{page-break-inside:avoid}
    .wr-details summary::before{display:none}
}
</style>
@endpush

@section('content')
@php
    $fmt = fn($n) => '$' . number_format((float)$n, 0, '.', ',');
    $pct = fn($n) => number_format((float)$n, 1) . '%';
    $scoreClass = fn($s) => $s >= 80 ? 'good' : ($s >= 60 ? 'warn' : 'bad');
    $riskClass = fn($s) => $s < 10 ? 'green' : ($s < 25 ? 'yellow' : 'red');

    // Variación % contra la semana anterior. $invert=true cuando subir es malo (p.ej. vencido).
    $deltaTag = function ($actual, $prev, $invert = false) {
        if (abs((float)$prev) < 0.01) return '';
        $ch = round(((float)$actual - (float)$prev) / abs((float)$prev) * 100, 1);
        if (abs($ch) < 0.05) return '<span class="wr-delta flat">= igual que sem. ant.</span>';
        $up    = $ch > 0;
        $good  = $invert ? !$up : $up;
        $cls   = $good ? 'up' : 'down';
        $arrow = $up ? '&#9650;' : '&#9660;';
        return '<span class="wr-delta ' . $cls . '">' . $arrow . ' ' . number_format(abs($ch), 1) . '% vs sem. ant.</span>';
    };
    // Variación en puntos porcentuales (para eficiencia)
    $deltaPts = function ($actual, $prev) {
        if (abs((float)$prev) < 0.01) return '';
        $d = round((float)$actual - (float)$prev, 1);
        if (abs($d) < 0.05) return '<span class="wr-delta flat">= igual que sem. ant.</span>';
        $cls   = $d > 0 ? 'up' : 'down';
        $arrow = $d > 0 ? '&#9650;' : '&#9660;';
        return '<span class="wr-delta ' . $cls . '">' . $arrow . ' ' . number_format(abs($d), 1) . ' pts vs sem. ant.</span>';
    };

    $agingG = $globales['aging'];
    $agingTotalG = $agingG['b1_30'] + $agingG['b31_60'] + $agingG['b61_90'] + $agingG['b90p'];
    $prevSemana = $inicio->copy()->subWeek()->toDateString();
    $sigSemana  = $inicio->copy()->addWeek()->toDateString();
    // Conservar el filtro de admin al navegar entre semanas
    $navExtra   = $adminSel ? ['admin' => $adminSel] : [];
    $adminSelObj = $adminSel ? $adminsLista->firstWhere('id', $adminSel) : null;
@endphp

<div class="wr-head">
    <div>
        <h1 class="wr-title">Reporte semanal financiero</h1>
        <div class="wr-sub">
            Semana del {{ $inicio->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }}.
            Analisis por administrador para decidir crecimiento, cobranza y riesgo.
            @if($adminSelObj)
                <span class="wr-pill gray" style="margin-left:6px">Filtrado: {{ $adminSelObj->alias ?: $adminSelObj->nombre ?: $adminSelObj->usuario }}</span>
                <a href="{{ route('owner.reporteSemanal', ['semana' => request('semana')]) }}" style="font-size:12px;font-weight:700;margin-left:4px">Quitar filtro</a>
            @endif
        </div>
        <div class="wr-print-note">Generado el {{ now()->format('d/m/Y H:i') }} desde el panel owner.</div>
    </div>
    <div class="wr-actions">
        <a class="wr-week-nav" href="{{ route('owner.reporteSemanal', array_merge(['semana' => $prevSemana], $navExtra)) }}" title="Semana anterior">&lsaquo;</a>
        <form class="wr-filter" method="GET" action="{{ route('owner.reporteSemanal') }}">
            <label style="font-size:11px;font-weight:800;color:var(--text3);text-transform:uppercase">Semana</label>
            <input type="date" name="semana" value="{{ request('semana', now()->toDateString()) }}">
            <label style="font-size:11px;font-weight:800;color:var(--text3);text-transform:uppercase">Admin</label>
            <select name="admin" class="wr-select">
                <option value="">Todos</option>
                @foreach($adminsLista as $a)
                    <option value="{{ $a->id }}" @selected($adminSel === $a->id)>{{ $a->alias ?: $a->nombre ?: $a->usuario }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary" type="submit">Actualizar</button>
        </form>
        @if($sigSemana <= now()->toDateString())
            <a class="wr-week-nav" href="{{ route('owner.reporteSemanal', array_merge(['semana' => $sigSemana], $navExtra)) }}" title="Semana siguiente">&rsaquo;</a>
        @endif
        <button class="btn btn-sm" type="button" onclick="wrExportCsv()" style="background:#f3f4f6;color:var(--text)">Exportar CSV</button>
        <button class="btn btn-sm" type="button" onclick="window.print()" style="background:#f3f4f6;color:var(--text)">Imprimir / PDF</button>
    </div>
</div>

{{-- ═══ KPIs con variación vs semana anterior ═══ --}}
<div class="wr-grid">
    <div class="wr-kpi">
        <div class="wr-kpi-label">Cobrado semana</div>
        <div class="wr-kpi-value">{{ $fmt($globales['cobrado_semana']) }}</div>
        {!! $deltaTag($globales['cobrado_semana'], $globales['cobrado_prev']) !!}
        <div class="wr-kpi-sub">Sem. anterior: {{ $fmt($globales['cobrado_prev']) }} · {{ $rows->sum('pagos_semana') }} pagos</div>
    </div>
    <div class="wr-kpi">
        <div class="wr-kpi-label">Interes cobrado (utilidad)</div>
        <div class="wr-kpi-value">{{ $fmt($globales['interes_semana']) }}</div>
        {!! $deltaTag($globales['interes_semana'], $globales['interes_prev']) !!}
        <div class="wr-kpi-sub">Sem. anterior: {{ $fmt($globales['interes_prev']) }}</div>
    </div>
    <div class="wr-kpi">
        <div class="wr-kpi-label">Eficiencia cobranza</div>
        <div class="wr-kpi-value">{{ $pct($globales['eficiencia']) }}</div>
        {!! $deltaPts($globales['eficiencia'], $globales['eficiencia_prev']) !!}
        <div class="wr-kpi-sub">Programado: {{ $fmt($globales['programado_semana']) }}</div>
    </div>
    <div class="wr-kpi">
        <div class="wr-kpi-label">Flujo neto de caja</div>
        <div class="wr-kpi-value {{ $globales['flujo_neto'] >= 0 ? 'pos' : 'neg' }}">{{ $globales['flujo_neto'] < 0 ? '-' : '' }}{{ $fmt(abs($globales['flujo_neto'])) }}</div>
        {!! $deltaTag($globales['flujo_neto'], $globales['flujo_neto_prev']) !!}
        <div class="wr-kpi-sub">Cobrado {{ $fmt($globales['cobrado_semana']) }} &minus; desembolsado {{ $fmt($globales['desembolsado_semana']) }}</div>
    </div>
    <div class="wr-kpi">
        <div class="wr-kpi-label">Capital en calle</div>
        <div class="wr-kpi-value">{{ $fmt($globales['saldo_activo']) }}</div>
        <div class="wr-kpi-sub">Atrasado: {{ $fmt($globales['saldo_atrasado']) }} ({{ $pct($globales['riesgo_pct']) }}) · {{ $globales['prestamos_activos'] }} prestamos activos</div>
    </div>
    <div class="wr-kpi">
        <div class="wr-kpi-label">Vencido acumulado</div>
        <div class="wr-kpi-value">{{ $fmt($globales['vencido_monto']) }}</div>
        <div class="wr-kpi-sub">{{ $globales['vencido_pagos'] }} pagos vencidos · Mora pendiente {{ $fmt($globales['mora_pendiente']) }}</div>
    </div>
    <div class="wr-kpi">
        <div class="wr-kpi-label">Vencido critico (+90 dias)</div>
        <div class="wr-kpi-value {{ $agingG['b90p'] > 0 ? 'neg' : '' }}">{{ $fmt($agingG['b90p']) }}</div>
        <div class="wr-kpi-sub">{{ $agingTotalG > 0 ? $pct($agingG['b90p'] / $agingTotalG * 100) : '0.0%' }} del total vencido. Alta probabilidad de perdida: provisionar.</div>
    </div>
    <div class="wr-kpi">
        <div class="wr-kpi-label">Proxima semana</div>
        <div class="wr-kpi-value">{{ $fmt($globales['proximo_programado']) }}</div>
        <div class="wr-kpi-sub">Carga programada de cobro para planear liquidez y colocacion.</div>
    </div>
</div>

{{-- ═══ Graficas: flujo diario + tendencia 8 semanas ═══ --}}
<div class="wr-charts">
    <div class="wr-panel" style="margin-bottom:0">
        <div class="wr-panel-head">
            <div>
                <div class="wr-panel-title">Flujo de caja diario</div>
                <div class="wr-panel-sub">Cobranza vs desembolso por dia de la semana</div>
            </div>
            <select class="wr-select" id="wr-admin-sel" onchange="wrUpdateDaily()">
                <option value="all">Todos los admins</option>
                @foreach($rows as $i => $r)
                    <option value="{{ $i }}">{{ $r['admin']->alias ?: $r['admin']->nombre ?: $r['admin']->usuario }}</option>
                @endforeach
            </select>
        </div>
        <div class="wr-panel-body">
            <div class="wr-chart-box"><canvas id="wrDailyChart"></canvas></div>
        </div>
    </div>
    <div class="wr-panel" style="margin-bottom:0">
        <div class="wr-panel-head">
            <div>
                <div class="wr-panel-title">Tendencia 8 semanas</div>
                <div class="wr-panel-sub">Cobrado, programado, desembolsado y eficiencia consolidados</div>
            </div>
        </div>
        <div class="wr-panel-body">
            <div class="wr-chart-box"><canvas id="wrTrendChart"></canvas></div>
        </div>
    </div>
</div>

{{-- ═══ Resumen ejecutivo ═══ --}}
<div class="wr-panel">
    <div class="wr-panel-head">
        <div class="wr-panel-title">Resumen ejecutivo</div>
        <span class="wr-muted">{{ $globales['admins'] }} administradores revisados</span>
    </div>
    <div class="wr-panel-body">
        <div class="wr-three">
            <div class="wr-card">
                <div class="wr-card-label">Mejor gestion integral</div>
                <div class="wr-card-name">{{ $mejor ? ($mejor['admin']->alias ?: $mejor['admin']->nombre ?: $mejor['admin']->usuario) : 'Sin datos' }}</div>
                <div class="wr-card-note">Score {{ $mejor['score'] ?? 0 }}/100. Combina cobranza, riesgo y utilidad.</div>
            </div>
            <div class="wr-card">
                <div class="wr-card-label">Mayor utilidad semanal</div>
                <div class="wr-card-name">{{ $mayorUtilidad ? ($mayorUtilidad['admin']->alias ?: $mayorUtilidad['admin']->nombre ?: $mayorUtilidad['admin']->usuario) : 'Sin datos' }}</div>
                <div class="wr-card-note">Interes cobrado estimado: {{ $fmt($mayorUtilidad['interes_semana'] ?? 0) }}.</div>
            </div>
            <div class="wr-card">
                <div class="wr-card-label">Mayor riesgo</div>
                <div class="wr-card-name">{{ $mayorRiesgo ? ($mayorRiesgo['admin']->alias ?: $mayorRiesgo['admin']->nombre ?: $mayorRiesgo['admin']->usuario) : 'Sin datos' }}</div>
                <div class="wr-card-note">PAR30: {{ $pct($mayorRiesgo['par30'] ?? 0) }}. Revisar cartera antes de seguir colocando.</div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ Ranking por administrador ═══ --}}
<div class="wr-panel">
    <div class="wr-panel-head">
        <div class="wr-panel-title">Detalle por administrador</div>
        <span class="wr-muted">Ordenado por score financiero</span>
    </div>
    <div class="wr-table-wrap">
        <table class="wr-table" id="wr-main-table">
            <thead>
                <tr>
                    <th>Admin</th>
                    <th>Score</th>
                    <th>Cartera</th>
                    <th>Semana</th>
                    <th>Cobranza</th>
                    <th>Riesgo</th>
                    <th>Vencido</th>
                    <th>Rentabilidad</th>
                    <th>Decision sugerida</th>
                    <th>Alertas</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                @php $name = $r['admin']->alias ?: $r['admin']->nombre ?: $r['admin']->usuario; @endphp
                <tr>
                    <td>
                        <div class="wr-admin">{{ $name }}</div>
                        <div class="wr-muted">{{ $r['admin']->usuario }} · {{ $r['clientes'] }} clientes · {{ $r['prestamos_activos'] }} activos</div>
                    </td>
                    <td><span class="wr-score {{ $scoreClass($r['score']) }}">{{ $r['score'] }}</span></td>
                    <td>
                        <strong>{{ $fmt($r['saldo_activo']) }}</strong>
                        <div class="wr-muted">Capital historico {{ $fmt($r['capital_desplegado']) }}</div>
                        <div class="wr-muted">{{ $r['prestamos_total'] }} prestamos totales</div>
                    </td>
                    <td>
                        <strong>{{ $fmt($r['cobrado_semana']) }}</strong>
                        {!! $deltaTag($r['cobrado_semana'], $r['cobrado_prev']) !!}
                        <div class="wr-muted">Desembolsado {{ $fmt($r['desembolsado_semana']) }}</div>
                        <div class="wr-muted">Neto {{ $r['flujo_neto'] < 0 ? '-' : '' }}{{ $fmt(abs($r['flujo_neto'])) }} · {{ $r['pagos_semana'] }} pagos · {{ $r['nuevos_prestamos'] }} nuevos</div>
                    </td>
                    <td>
                        <span class="wr-pill {{ $r['eficiencia'] >= 100 ? 'green' : ($r['eficiencia'] >= 80 ? 'yellow' : 'red') }}">{{ $pct($r['eficiencia']) }}</span>
                        <div class="wr-muted">Programado {{ $fmt($r['programado_semana']) }}</div>
                        <div class="wr-muted">Prox. semana {{ $fmt($r['proximo_programado']) }}</div>
                    </td>
                    <td>
                        <span class="wr-pill {{ $riskClass($r['par30']) }}">PAR30 {{ $pct($r['par30']) }}</span>
                        <div class="wr-muted">PAR60 {{ $pct($r['par60']) }} · PAR90 {{ $pct($r['par90']) }}</div>
                        <div class="wr-muted">Saldo atrasado {{ $fmt($r['saldo_atrasado']) }}</div>
                    </td>
                    <td>
                        <strong>{{ $fmt($r['vencido_monto']) }}</strong>
                        <div class="wr-muted">{{ $r['vencido_pagos'] }} pagos vencidos</div>
                        <div class="wr-muted">Mora {{ $fmt($r['mora_pendiente']) }}</div>
                    </td>
                    <td>
                        <strong>{{ $fmt($r['interes_semana']) }}</strong>
                        <div class="wr-muted">Interes total {{ $fmt($r['interes_total']) }}</div>
                        <div class="wr-muted">ROI real {{ $pct($r['roi_real']) }}</div>
                    </td>
                    <td><div class="wr-rec">{{ $r['recomendacion'] }}</div></td>
                    <td>
                        <div class="wr-alerts">
                            @foreach($r['alertas'] as $a)
                                <div class="wr-alert">- {{ $a }}</div>
                            @endforeach
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center;color:var(--text3);padding:30px">Sin administradores para reportar.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ═══ Aging de saldos vencidos ═══ --}}
<div class="wr-panel">
    <div class="wr-panel-head">
        <div>
            <div class="wr-panel-title">Antiguedad de saldos vencidos (aging)</div>
            <div class="wr-panel-sub">Monto de cuotas vencidas por dias de atraso. A mayor antiguedad, menor probabilidad de recuperacion.</div>
        </div>
    </div>
    <div class="wr-table-wrap">
        <table class="wr-table wr-aging-table">
            <thead>
                <tr>
                    <th>Admin</th>
                    <th>1&ndash;30 dias</th>
                    <th>31&ndash;60 dias</th>
                    <th>61&ndash;90 dias</th>
                    <th>+90 dias</th>
                    <th>Total vencido</th>
                    <th>% de la cartera activa</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                @php
                    $ag = $r['aging'];
                    $agTotal = $ag['b1_30'] + $ag['b31_60'] + $ag['b61_90'] + $ag['b90p'];
                    $agPct = $r['saldo_activo'] > 0 ? round($agTotal / $r['saldo_activo'] * 100, 1) : 0;
                @endphp
                <tr>
                    <td><span class="wr-admin">{{ $r['admin']->alias ?: $r['admin']->nombre ?: $r['admin']->usuario }}</span></td>
                    <td>{{ $fmt($ag['b1_30']) }}</td>
                    <td>{{ $fmt($ag['b31_60']) }}</td>
                    <td>{{ $fmt($ag['b61_90']) }}</td>
                    <td class="{{ $ag['b90p'] > 0 ? 'wr-aging-crit' : '' }}">{{ $fmt($ag['b90p']) }}</td>
                    <td><strong>{{ $fmt($agTotal) }}</strong></td>
                    <td>
                        <span class="wr-pill {{ $riskClass($agPct) }}">{{ $pct($agPct) }}</span>
                        <div class="wr-bar-track"><div class="wr-bar-fill" style="width:{{ min(100, $agPct) }}%;background:{{ $agPct < 10 ? '#10b981' : ($agPct < 25 ? '#f6b73c' : '#ef4444') }}"></div></div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;color:var(--text3);padding:30px">Sin datos de aging.</td></tr>
            @endforelse
            </tbody>
            @if($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td>{{ $fmt($agingG['b1_30']) }}</td>
                    <td>{{ $fmt($agingG['b31_60']) }}</td>
                    <td>{{ $fmt($agingG['b61_90']) }}</td>
                    <td class="{{ $agingG['b90p'] > 0 ? 'wr-aging-crit' : '' }}">{{ $fmt($agingG['b90p']) }}</td>
                    <td>{{ $fmt($agingTotalG) }}</td>
                    <td>{{ $globales['saldo_activo'] > 0 ? $pct($agingTotalG / $globales['saldo_activo'] * 100) : '0.0%' }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ═══ Concentracion de riesgo / top morosos ═══ --}}
<div class="wr-panel">
    <div class="wr-panel-head">
        <div>
            <div class="wr-panel-title">Concentracion de riesgo y principales morosos</div>
            <div class="wr-panel-sub">Top 5 prestamos atrasados por saldo. Cobrar por monto, no por cantidad de clientes.</div>
        </div>
    </div>
    <div class="wr-panel-body">
        @forelse($rows as $r)
            @php
                $name = $r['admin']->alias ?: $r['admin']->nombre ?: $r['admin']->usuario;
                $morosos = $r['top_morosos'];
            @endphp
            <details class="wr-details" @if($loop->first && count($morosos)) open @endif>
                <summary>
                    <span class="wr-admin">{{ $name }}</span>
                    <span class="wr-pill {{ $riskClass($r['riesgo_pct']) }}">Saldo atrasado {{ $fmt($r['saldo_atrasado']) }} ({{ $pct($r['riesgo_pct']) }})</span>
                    <span class="wr-pill {{ $r['top5_pct'] >= 50 ? 'red' : ($r['top5_pct'] >= 30 ? 'yellow' : 'gray') }}">Top 5 saldos = {{ $pct($r['top5_pct']) }} de la cartera</span>
                    <span class="wr-muted" style="margin-left:auto">{{ $r['prestamos_atrasados'] }} prestamos atrasados</span>
                </summary>
                <div class="wr-details-body">
                    @if(count($morosos))
                        <table class="wr-mini-table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Prestamo</th>
                                    <th>Saldo pendiente</th>
                                    <th>Mora acumulada</th>
                                    <th>Dias de atraso</th>
                                    <th>Severidad</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($morosos as $m)
                                <tr>
                                    <td><strong>{{ $m['cliente'] }}</strong></td>
                                    <td>#{{ $m['prestamo_id'] }}</td>
                                    <td>{{ $fmt($m['saldo']) }}</td>
                                    <td>{{ $fmt($m['mora']) }}</td>
                                    <td>{{ $m['dias'] }} dias</td>
                                    <td>
                                        @if($m['dias'] > 90)
                                            <span class="wr-pill red">Critico: negociar o castigar</span>
                                        @elseif($m['dias'] > 30)
                                            <span class="wr-pill yellow">Alto: visita y promesa firmada</span>
                                        @else
                                            <span class="wr-pill green">Temprano: cobrar esta semana</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @if($r['top5_pct'] >= 40)
                            <div class="wr-muted" style="margin-top:8px;white-space:normal">
                                Atencion: la cartera esta concentrada. Si uno de estos clientes deja de pagar, el impacto
                                sobre la caja del admin es desproporcionado. Diversificar la colocacion en tickets mas chicos.
                            </div>
                        @endif
                    @else
                        <div class="wr-muted" style="padding:6px 0">Sin prestamos atrasados. Cartera limpia esta semana.</div>
                    @endif
                </div>
            </details>
        @empty
            <div class="wr-muted">Sin administradores para reportar.</div>
        @endforelse
    </div>
</div>

{{-- ═══ Lectura financiera ═══ --}}
<div class="wr-panel">
    <div class="wr-panel-head">
        <div class="wr-panel-title">Lectura financiera para tomar decisiones</div>
    </div>
    <div class="wr-panel-body">
        <div class="wr-three">
            <div class="wr-card">
                <div class="wr-card-label">Para crecer</div>
                <div class="wr-card-note">Subir presupuesto solo a admins con eficiencia igual o mayor a 100%, PAR30 menor a 10% y utilidad semanal positiva. Crecer sin cobranza convierte capital en riesgo.</div>
            </div>
            <div class="wr-card">
                <div class="wr-card-label">Para corregir</div>
                <div class="wr-card-note">Admins con PAR30 alto o saldo atrasado fuerte deben pausar colocacion nueva y trabajar recuperacion por monto, no por cantidad de clientes. El aging manda: lo de +90 dias dificilmente regresa completo.</div>
            </div>
            <div class="wr-card">
                <div class="wr-card-label">Para cobrar mejor</div>
                <div class="wr-card-note">Comparar lo programado contra lo cobrado. Si la eficiencia baja de 90%, revisar rutas, promesas incumplidas y clientes con parcialidades repetidas.</div>
            </div>
            <div class="wr-card">
                <div class="wr-card-label">Flujo de caja</div>
                <div class="wr-card-note">Un flujo neto negativo no es malo si es crecimiento planeado con cobranza sana; es alerta cuando coincide con eficiencia baja o PAR30 en subida. Usar "Proxima semana" para calcular cuanto se puede recolocar sin descapitalizarse.</div>
            </div>
            <div class="wr-card">
                <div class="wr-card-label">Provision de perdidas</div>
                <div class="wr-card-note">Regla practica: provisionar 10% del vencido de 31-60 dias, 30% del de 61-90 y 60% o mas del de +90. Asi la utilidad reportada refleja el riesgo real y no se reparte dinero que quizas no regrese.</div>
            </div>
            <div class="wr-card">
                <div class="wr-card-label">Concentracion</div>
                <div class="wr-card-note">Si el top 5 de saldos supera el 40% de la cartera de un admin, su resultado depende de pocos clientes. Preferir mas tickets chicos con buen historial que pocos tickets grandes.</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
@php
    $wrAdminsJs = $rows->map(fn($r) => [
        'name' => $r['admin']->alias ?: $r['admin']->nombre ?: $r['admin']->usuario,
        'cob'  => $r['daily_cobrado'],
        'des'  => $r['daily_desembolsado'],
    ])->values();
    $wrCsvJs = $rows->map(fn($r) => [
        'Admin'               => $r['admin']->alias ?: $r['admin']->nombre ?: $r['admin']->usuario,
        'Usuario'             => $r['admin']->usuario,
        'Score'               => $r['score'],
        'Clientes'            => $r['clientes'],
        'Prestamos activos'   => $r['prestamos_activos'],
        'Prestamos atrasados' => $r['prestamos_atrasados'],
        'Saldo activo'        => $r['saldo_activo'],
        'Saldo atrasado'      => $r['saldo_atrasado'],
        'Cobrado semana'      => $r['cobrado_semana'],
        'Cobrado sem anterior'=> $r['cobrado_prev'],
        'Interes semana'      => $r['interes_semana'],
        'Desembolsado semana' => $r['desembolsado_semana'],
        'Flujo neto semana'   => $r['flujo_neto'],
        'Programado semana'   => $r['programado_semana'],
        'Eficiencia %'        => $r['eficiencia'],
        'PAR30 %'             => $r['par30'],
        'PAR60 %'             => $r['par60'],
        'PAR90 %'             => $r['par90'],
        'Vencido total'       => $r['vencido_monto'],
        'Vencido 1-30'        => $r['aging']['b1_30'],
        'Vencido 31-60'       => $r['aging']['b31_60'],
        'Vencido 61-90'       => $r['aging']['b61_90'],
        'Vencido +90'         => $r['aging']['b90p'],
        'Mora pendiente'      => $r['mora_pendiente'],
        'ROI real %'          => $r['roi_real'],
        'Proxima semana'      => $r['proximo_programado'],
        'Recomendacion'       => $r['recomendacion'],
    ])->values();
@endphp
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
const WR = {
    dailyLabels: @json($globales['daily_labels']),
    global: { cob: @json($globales['daily_cobrado']), des: @json($globales['daily_desembolsado']) },
    admins: @json($wrAdminsJs),
    trend: @json($tendencia),
    csv: @json($wrCsvJs),
    semana: @json($inicio->format('Y-m-d')),
};

const wrMoney = v => '$' + Number(v).toLocaleString('es-MX', { maximumFractionDigits: 0 });

// ── Flujo diario (cobrado vs desembolsado) con selector de admin ──
let wrDaily = null;
function wrUpdateDaily() {
    const sel = document.getElementById('wr-admin-sel').value;
    const src = sel === 'all' ? WR.global : WR.admins[Number(sel)];
    if (!src) return;
    wrDaily.data.datasets[0].data = src.cob;
    wrDaily.data.datasets[1].data = src.des;
    wrDaily.update();
}

document.addEventListener('DOMContentLoaded', () => {
    const gridColor = 'rgba(15,22,35,0.06)';
    const fontStack = getComputedStyle(document.documentElement).getPropertyValue('--font') || 'Sora, sans-serif';
    Chart.defaults.font.family = fontStack;
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#5b6472';

    // Flujo diario
    wrDaily = new Chart(document.getElementById('wrDailyChart'), {
        type: 'bar',
        data: {
            labels: WR.dailyLabels,
            datasets: [
                { label: 'Cobrado', data: WR.global.cob, backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 4 },
                { label: 'Desembolsado', data: WR.global.des, backgroundColor: 'rgba(246,183,60,0.8)', borderRadius: 4 },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10 } },
                tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + wrMoney(c.parsed.y) } },
            },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: gridColor }, ticks: { callback: v => wrMoney(v) }, beginAtZero: true },
            },
        },
    });

    // Tendencia 8 semanas: barras cobrado/desembolsado + lineas programado y eficiencia
    new Chart(document.getElementById('wrTrendChart'), {
        data: {
            labels: WR.trend.labels,
            datasets: [
                { type: 'bar',  label: 'Cobrado',      data: WR.trend.cobrado,      backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 4, order: 3 },
                { type: 'bar',  label: 'Desembolsado', data: WR.trend.desembolsado, backgroundColor: 'rgba(246,183,60,0.8)',  borderRadius: 4, order: 4 },
                { type: 'line', label: 'Programado',   data: WR.trend.programado,   borderColor: '#9aa3b2', borderDash: [5,4], borderWidth: 2, pointRadius: 2, tension: .3, fill: false, order: 2 },
                { type: 'line', label: 'Eficiencia %', data: WR.trend.eficiencia,   borderColor: '#6366f1', borderWidth: 2, pointRadius: 3, tension: .3, fill: false, yAxisID: 'y2', order: 1 },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10 } },
                tooltip: { callbacks: { label: c => c.dataset.yAxisID === 'y2'
                    ? ' Eficiencia: ' + c.parsed.y + '%'
                    : ' ' + c.dataset.label + ': ' + wrMoney(c.parsed.y) } },
            },
            scales: {
                x: { grid: { display: false } },
                y:  { grid: { color: gridColor }, ticks: { callback: v => wrMoney(v) }, beginAtZero: true },
                y2: { position: 'right', grid: { drawOnChartArea: false }, ticks: { callback: v => v + '%' }, beginAtZero: true },
            },
        },
    });
});

// ── Exportar CSV del reporte ──
function wrExportCsv() {
    if (!WR.csv.length) return;
    const cols = Object.keys(WR.csv[0]);
    const esc = v => {
        const s = String(v ?? '');
        return /[",\n;]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    };
    const lines = [cols.join(',')].concat(WR.csv.map(r => cols.map(c => esc(r[c])).join(',')));
    const blob = new Blob(['﻿' + lines.join('\n')], { type: 'text/csv;charset=utf-8' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'reporte_semanal_' + WR.semana + '.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(a.href);
}
</script>
@endpush
