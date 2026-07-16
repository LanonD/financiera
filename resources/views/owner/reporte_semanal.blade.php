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
.wr-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}
.wr-kpi{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:14px}
.wr-kpi-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)}
.wr-kpi-value{font-size:22px;font-weight:800;color:var(--text);margin-top:6px}
.wr-kpi-sub{font-size:11px;color:var(--text2);margin-top:4px;line-height:1.35}
.wr-panel{background:var(--card);border:1px solid var(--border);border-radius:8px;margin-bottom:16px;overflow:hidden}
.wr-panel-head{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border);background:#f9fafb}
.wr-panel-title{font-size:14px;font-weight:800;color:var(--text)}
.wr-panel-body{padding:16px}
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
.wr-alerts{display:grid;gap:4px;max-width:300px;white-space:normal}
.wr-alert{font-size:11px;color:var(--text2);line-height:1.35}
.wr-rec{font-size:12px;color:var(--text);line-height:1.45;white-space:normal;max-width:330px}
.wr-print-note{display:none}
@media(max-width:900px){
    .wr-head{flex-direction:column}
    .wr-grid,.wr-three{grid-template-columns:1fr 1fr}
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
    .wr-panel,.wr-kpi,.wr-card{break-inside:avoid;border-color:#d1d5db}
    .wr-print-note{display:block;font-size:11px;color:#6b7280;margin-bottom:10px}
    .wr-table{font-size:10px;min-width:0}
    .wr-table th,.wr-table td{padding:6px}
}
</style>
@endpush

@section('content')
@php
    $fmt = fn($n) => '$' . number_format((float)$n, 0, '.', ',');
    $pct = fn($n) => number_format((float)$n, 1) . '%';
    $scoreClass = fn($s) => $s >= 80 ? 'good' : ($s >= 60 ? 'warn' : 'bad');
    $riskClass = fn($s) => $s < 10 ? 'green' : ($s < 25 ? 'yellow' : 'red');
@endphp

<div class="wr-head">
    <div>
        <h1 class="wr-title">Reporte semanal financiero</h1>
        <div class="wr-sub">
            Semana del {{ $inicio->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }}.
            Analisis por administrador para decidir crecimiento, cobranza y riesgo.
        </div>
        <div class="wr-print-note">Generado el {{ now()->format('d/m/Y H:i') }} desde el panel owner.</div>
    </div>
    <div class="wr-actions">
        <form class="wr-filter" method="GET" action="{{ route('owner.reporteSemanal') }}">
            <label style="font-size:11px;font-weight:800;color:var(--text3);text-transform:uppercase">Semana</label>
            <input type="date" name="semana" value="{{ request('semana', now()->toDateString()) }}">
            <button class="btn btn-sm btn-primary" type="submit">Actualizar</button>
        </form>
        <button class="btn btn-sm" type="button" onclick="window.print()" style="background:#f3f4f6;color:var(--text)">Imprimir / PDF</button>
    </div>
</div>

<div class="wr-grid">
    <div class="wr-kpi">
        <div class="wr-kpi-label">Cobrado semana</div>
        <div class="wr-kpi-value">{{ $fmt($globales['cobrado_semana']) }}</div>
        <div class="wr-kpi-sub">Interes estimado: {{ $fmt($globales['interes_semana']) }}</div>
    </div>
    <div class="wr-kpi">
        <div class="wr-kpi-label">Eficiencia cobranza</div>
        <div class="wr-kpi-value">{{ $pct($globales['eficiencia']) }}</div>
        <div class="wr-kpi-sub">Programado: {{ $fmt($globales['programado_semana']) }}</div>
    </div>
    <div class="wr-kpi">
        <div class="wr-kpi-label">Capital en calle</div>
        <div class="wr-kpi-value">{{ $fmt($globales['saldo_activo']) }}</div>
        <div class="wr-kpi-sub">Atrasado: {{ $fmt($globales['saldo_atrasado']) }} ({{ $pct($globales['riesgo_pct']) }})</div>
    </div>
    <div class="wr-kpi">
        <div class="wr-kpi-label">Proxima semana</div>
        <div class="wr-kpi-value">{{ $fmt($globales['proximo_programado']) }}</div>
        <div class="wr-kpi-sub">Vencido actual: {{ $fmt($globales['vencido_monto']) }}</div>
    </div>
</div>

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

<div class="wr-panel">
    <div class="wr-panel-head">
        <div class="wr-panel-title">Detalle por administrador</div>
        <span class="wr-muted">Ordenado por score financiero</span>
    </div>
    <div class="wr-table-wrap">
        <table class="wr-table">
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
                        <div class="wr-muted">Desembolsado {{ $fmt($r['desembolsado_semana']) }}</div>
                        <div class="wr-muted">{{ $r['pagos_semana'] }} pagos · {{ $r['nuevos_prestamos'] }} nuevos</div>
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
                <div class="wr-card-note">Admins con PAR30 alto o saldo atrasado fuerte deben pausar colocacion nueva y trabajar recuperacion por monto, no por cantidad de clientes.</div>
            </div>
            <div class="wr-card">
                <div class="wr-card-label">Para cobrar mejor</div>
                <div class="wr-card-note">Comparar lo programado contra lo cobrado. Si la eficiencia baja de 90%, revisar rutas, promesas incumplidas y clientes con parcialidades repetidas.</div>
            </div>
        </div>
    </div>
</div>
@endsection
