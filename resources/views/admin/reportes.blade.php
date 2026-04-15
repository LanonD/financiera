@extends('layouts.app')

@section('title', 'Reportes')

@section('content')

@php
$hoy = now()->toDateString();

function fmtM($v) { return '$'.number_format((float)$v, 2, '.', ','); }

$rangoLabel = ($fecha_desde === $fecha_hasta)
    ? \Carbon\Carbon::parse($fecha_desde)->format('d/m/Y')
    : \Carbon\Carbon::parse($fecha_desde)->format('d/m/Y') . ' — ' . \Carbon\Carbon::parse($fecha_hasta)->format('d/m/Y');

// Build days map for chart
$diasMap = [];
$cur = strtotime($fecha_desde);
$end = strtotime($fecha_hasta);
while ($cur <= $end) {
    $diasMap[date('Y-m-d', $cur)] = ['total' => 0, 'principal' => 0, 'interes_dia' => 0];
    $cur = strtotime('+1 day', $cur);
}
foreach ($cobros_rango as $row) {
    $dia = $row->dia ?? null;
    if ($dia && isset($diasMap[$dia])) {
        $diasMap[$dia] = ['total' => $row->total, 'principal' => $row->principal, 'interes_dia' => $row->interes_dia];
    }
}
$maxVal = max(1, max(array_column($diasMap, 'total') ?: [1]));

$totalCobros = max(1, (int)($resumen->total_cobros ?? 0));
$aTiempoNum  = (int)($resumen->a_tiempo_num ?? 0);
$tardeNum    = (int)($resumen->tarde_num ?? 0);
$aTiempoPct  = round($aTiempoNum / $totalCobros * 100);
$tardePct    = 100 - $aTiempoPct;

$totalMonto  = max(1, (float)($resumen->total_monto ?? 0));
$capPct      = $totalMonto > 0 ? round((float)($resumen->total_capital ?? 0) / $totalMonto * 100) : 0;
$intPct      = 100 - $capPct;

$colorMap = ['Activo'=>'#16a34a','Atrasado'=>'#dc2626','Pendiente'=>'#ca8a04','Finalizado'=>'#3b82f6','Retirado'=>'#94a3b8','Cancelado'=>'#6b7280'];

// Quick shortcut links
$atajos = [
    'Hoy'      => [$hoy, $hoy],
    'Esta sem' => [\Carbon\Carbon::now()->startOfWeek()->toDateString(), $hoy],
    'Este mes' => [\Carbon\Carbon::now()->startOfMonth()->toDateString(), $hoy],
    'Mes ant'  => [\Carbon\Carbon::now()->subMonth()->startOfMonth()->toDateString(), \Carbon\Carbon::now()->subMonth()->endOfMonth()->toDateString()],
];
@endphp

@push('styles')
<style>
.rpt-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:16px}
.rpt-card-header{padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:space-between}
.rpt-card-body{padding:16px 18px}
.rpt-kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px}
.rpt-kpi{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px}
.rpt-kpi-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text3);margin-bottom:6px}
.rpt-kpi-value{font-size:24px;font-weight:700;font-family:monospace;letter-spacing:-.02em;line-height:1}
.rpt-kpi-sub{font-size:11px;color:var(--text2);margin-top:4px;font-family:monospace}
.rpt-grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px}
.rpt-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
.split-bar{display:flex;height:8px;border-radius:4px;overflow:hidden;margin:10px 0}
.decomp-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
.decomp-row:last-child{border-bottom:none}
.bar-wrap{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.bar-label{width:100px;flex-shrink:0;font-size:12px;color:var(--text2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar-track{flex:1;height:7px;background:#f3f4f6;border-radius:4px;overflow:hidden}
.bar-fill{height:100%;border-radius:4px}
.chart-bars{display:flex;align-items:flex-end;gap:3px;height:100px;overflow-x:auto}
.chart-bar-wrap{min-width:26px;flex:1;display:flex;flex-direction:column;align-items:center;gap:2px}
.chart-day{font-size:9px;color:var(--text3);text-align:center;white-space:nowrap}
.status-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)}
.status-row:last-child{border-bottom:none}
.punct-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px}
.punct-box{border-radius:8px;padding:12px;text-align:center}
.punct-num{font-size:20px;font-weight:700;font-family:monospace;line-height:1}
.punct-label{font-size:11px;margin-top:3px}
</style>
@endpush

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
        <h2 style="font-size:20px;font-weight:700;margin-bottom:4px">Reportes</h2>
        <p style="color:var(--text2);font-size:13px">Análisis de cobros, cartera y desempeño</p>
    </div>
    <span style="font-size:11px;color:var(--text2);background:var(--card);padding:4px 10px;border-radius:999px;border:1px solid var(--border)">{{ $rangoLabel }}</span>
</div>

{{-- Date filter --}}
<div class="rpt-card" style="padding:0;overflow:hidden;margin-bottom:16px">
<form method="GET" action="{{ route('reportes.index') }}">
<div style="padding:14px 18px;display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap">
    <div>
        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:4px">Desde</label>
        <input type="date" name="desde" value="{{ $fecha_desde }}" max="{{ $hoy }}"
               style="background:#f9fafb;border:1px solid var(--border);border-radius:6px;padding:6px 10px;font-size:13px;min-width:140px;color:var(--text)">
    </div>
    <div>
        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:4px">Hasta</label>
        <input type="date" name="hasta" value="{{ $fecha_hasta }}" max="{{ $hoy }}"
               style="background:#f9fafb;border:1px solid var(--border);border-radius:6px;padding:6px 10px;font-size:13px;min-width:140px;color:var(--text)">
    </div>
    <button type="submit" class="btn btn-primary">Filtrar</button>
    <div style="display:flex;gap:6px;flex-wrap:wrap;align-self:flex-end">
        @foreach($atajos as $label => [$d, $h])
        <a href="?desde={{ $d }}&hasta={{ $h }}"
           style="font-size:11px;padding:4px 10px;border-radius:999px;border:1px solid {{ ($d===$fecha_desde && $h===$fecha_hasta) ? 'var(--accent)' : 'var(--border)' }};color:{{ ($d===$fecha_desde && $h===$fecha_hasta) ? '#fff' : 'var(--text2)' }};background:{{ ($d===$fecha_desde && $h===$fecha_hasta) ? 'var(--accent)' : '#f9fafb' }};text-decoration:none">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>
</form>
</div>

{{-- KPIs --}}
<div class="rpt-kpi-grid">
    <div class="rpt-kpi">
        <div class="rpt-kpi-label">Cobrado en período</div>
        <div class="rpt-kpi-value" style="color:#16a34a">{{ fmtM($resumen->total_monto ?? 0) }}</div>
        <div class="rpt-kpi-sub">{{ (int)($resumen->total_cobros ?? 0) }} cobro(s)</div>
    </div>
    <div class="rpt-kpi">
        <div class="rpt-kpi-label">Principal cobrado</div>
        <div class="rpt-kpi-value" style="color:#3b82f6">{{ fmtM($resumen->total_capital ?? 0) }}</div>
        <div class="rpt-kpi-sub">Interés: {{ fmtM($resumen->total_interes ?? 0) }}</div>
    </div>
    <div class="rpt-kpi">
        <div class="rpt-kpi-label">Saldo en cartera</div>
        <div class="rpt-kpi-value">{{ fmtM($cartera->saldo_total ?? 0) }}</div>
        <div class="rpt-kpi-sub">{{ (int)($cartera->num_prestamos ?? 0) }} préstamos activos</div>
    </div>
    <div class="rpt-kpi">
        <div class="rpt-kpi-label">Interés acumulado total</div>
        <div class="rpt-kpi-value" style="color:#f59e0b">{{ fmtM($cartera->interes_total ?? 0) }}</div>
        <div class="rpt-kpi-sub">Deuda total: {{ fmtM($cartera->deuda_total ?? 0) }}</div>
    </div>
</div>

{{-- Punctuality + Composition + Today --}}
<div class="rpt-grid-3">
    <div class="rpt-card">
        <div class="rpt-card-header">Puntualidad de cobros</div>
        <div class="rpt-card-body">
            <div class="split-bar">
                <div style="background:#16a34a;width:{{ $aTiempoPct }}%;height:100%"></div>
                <div style="background:#dc2626;width:{{ $tardePct }}%;height:100%"></div>
            </div>
            <div class="punct-grid">
                <div class="punct-box" style="background:rgba(22,163,74,.1)">
                    <div class="punct-num" style="color:#16a34a">{{ $aTiempoPct }}%</div>
                    <div class="punct-label" style="color:#16a34a">A tiempo</div>
                    <div style="font-size:11px;color:var(--text3);margin-top:4px">{{ $aTiempoNum }} cobros · {{ fmtM($resumen->a_tiempo_monto ?? 0) }}</div>
                </div>
                <div class="punct-box" style="background:rgba(220,38,38,.08)">
                    <div class="punct-num" style="color:#dc2626">{{ $tardePct }}%</div>
                    <div class="punct-label" style="color:#dc2626">Con atraso</div>
                    <div style="font-size:11px;color:var(--text3);margin-top:4px">{{ $tardeNum }} cobros · {{ fmtM($resumen->tarde_monto ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="rpt-card">
        <div class="rpt-card-header">Composición de cobros</div>
        <div class="rpt-card-body">
            <div class="split-bar">
                <div style="background:#3b82f6;width:{{ $capPct }}%;height:100%;border-radius:4px 0 0 4px"></div>
                <div style="background:#f59e0b;width:{{ $intPct }}%;height:100%;border-radius:0 4px 4px 0"></div>
            </div>
            <div style="display:flex;gap:12px;margin-bottom:8px;font-size:13px">
                <span style="display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:2px;background:#3b82f6;flex-shrink:0"></span>Principal</span>
                <span style="display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:2px;background:#f59e0b;flex-shrink:0"></span>Interés</span>
            </div>
            <div class="decomp-row">
                <span>Principal</span>
                <span style="font-family:monospace;font-weight:600;color:#3b82f6">{{ fmtM($resumen->total_capital ?? 0) }} <small style="color:var(--text3)">({{ $capPct }}%)</small></span>
            </div>
            <div class="decomp-row">
                <span>Interés</span>
                <span style="font-family:monospace;font-weight:600;color:#f59e0b">{{ fmtM($resumen->total_interes ?? 0) }} <small style="color:var(--text3)">({{ $intPct }}%)</small></span>
            </div>
            <div class="decomp-row" style="font-weight:600">
                <span>Total</span>
                <span style="font-family:monospace;color:#16a34a">{{ fmtM($resumen->total_monto ?? 0) }}</span>
            </div>
        </div>
    </div>

    <div class="rpt-card">
        <div class="rpt-card-header">Actividad de hoy</div>
        <div class="rpt-card-body">
            <div class="decomp-row">
                <span>Cobrado hoy</span>
                <div>
                    <span style="font-family:monospace;font-weight:700;color:#16a34a">{{ fmtM($cobros_hoy->total ?? 0) }}</span>
                    <div style="font-size:11px;color:var(--text3)">{{ (int)($cobros_hoy->num ?? 0) }} cobro(s)</div>
                </div>
            </div>
            <div class="decomp-row">
                <span>Desembolsado hoy</span>
                <div>
                    <span style="font-family:monospace;font-weight:700;color:#3b82f6">{{ fmtM($desembolsos_hoy->total ?? 0) }}</span>
                    <div style="font-size:11px;color:var(--text3)">{{ (int)($desembolsos_hoy->num ?? 0) }} préstamo(s)</div>
                </div>
            </div>
            <div class="decomp-row" style="border-bottom:none">
                <span>Interés pend. cartera</span>
                <div>
                    <span style="font-family:monospace;font-weight:700;color:#f59e0b">{{ fmtM($cartera->interes_total ?? 0) }}</span>
                    <div style="font-size:11px;color:var(--text3)">acumulado sin cobrar</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Chart + Cobros por cobrador --}}
<div class="rpt-grid-2">
    <div class="rpt-card">
        <div class="rpt-card-header">
            Cobros por día
            <span style="font-size:11px;color:var(--text2);font-weight:400">{{ $rangoLabel }}</span>
        </div>
        <div class="rpt-card-body" style="padding-top:20px">
            <div class="chart-bars">
                @foreach($diasMap as $fecha => $data)
                <div class="chart-bar-wrap" title="{{ \Carbon\Carbon::parse($fecha)->format('d/m') }} · {{ fmtM($data['total']) }}">
                    <div style="font-size:9px;color:var(--text3);font-family:monospace;text-align:center">{{ $data['total'] > 0 ? '$'.number_format((float)$data['total']/1000,0).'k' : '' }}</div>
                    <div style="width:100%;height:{{ $maxVal > 0 ? max(2, round((float)$data['total']/$maxVal*80)) : 2 }}px;border-radius:3px 3px 0 0;background:{{ $fecha === $hoy ? 'var(--accent)' : '#93c5fd' }}"></div>
                    <div class="chart-day">{{ \Carbon\Carbon::parse($fecha)->format('d/m') }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="rpt-card">
        <div class="rpt-card-header">
            Cobros por cobrador
            <span style="font-size:11px;color:var(--text2);font-weight:400">{{ $rangoLabel }}</span>
        </div>
        <div class="rpt-card-body">
            @if($cobros_por_cobrador->isEmpty())
            <p style="color:var(--text3);font-size:13px;text-align:center;padding:16px 0">Sin cobros en este período</p>
            @else
            @php $maxC = max($cobros_por_cobrador->max('total'), 1) @endphp
            @foreach($cobros_por_cobrador as $cc)
            @php $aTiempoP = $cc->num > 0 ? round($cc->a_tiempo / $cc->num * 100) : 0; @endphp
            <div class="bar-wrap">
                <div class="bar-label" title="{{ $cc->nombre }}">{{ $cc->nombre }}</div>
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px">
                        <div class="bar-track" style="flex:1"><div class="bar-fill" style="width:{{ $maxC > 0 ? round($cc->total/$maxC*100) : 0 }}%;background:#16a34a"></div></div>
                        <span style="font-size:12px;font-weight:600;font-family:monospace;color:#16a34a;width:80px;text-align:right">{{ fmtM($cc->total) }}</span>
                    </div>
                    <div style="font-size:11px;color:var(--text3)">
                        <span>Principal: <b style="color:var(--text2)">{{ fmtM($cc->principal) }}</b></span> ·
                        <span>Interés: <b style="color:#f59e0b">{{ fmtM($cc->interes_cobrador) }}</b></span> ·
                        <span style="color:{{ $aTiempoP >= 80 ? '#16a34a' : ($aTiempoP >= 50 ? '#ca8a04' : '#dc2626') }}">{{ $aTiempoP }}% a tiempo</span>
                    </div>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
</div>

{{-- Status + Top delayed --}}
<div class="rpt-grid-2">
    <div class="rpt-card">
        <div class="rpt-card-header">Préstamos por estatus</div>
        <div style="padding:0 18px">
            @foreach($por_estatus as $st)
            @php $clr = $colorMap[$st->estatus] ?? '#6b7280'; @endphp
            <div class="status-row">
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="width:8px;height:8px;border-radius:50%;background:{{ $clr }};flex-shrink:0"></span>
                    <span style="font-size:13px;font-weight:500">{{ $st->estatus }}</span>
                </div>
                <div style="display:flex;gap:20px;align-items:center">
                    <span style="font-size:12px;color:var(--text3)">{{ $st->num }} préstamo(s)</span>
                    <span style="font-size:13px;font-weight:600;font-family:monospace;color:{{ $clr }}">{{ fmtM($st->saldo) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="rpt-card">
        <div class="rpt-card-header">
            Con mayor atraso
            <span style="font-size:11px;color:var(--text2);font-weight:400">Top 10</span>
        </div>
        @if($atrasados->isEmpty())
        <p style="color:var(--text3);font-size:13px;text-align:center;padding:20px">Sin préstamos atrasados</p>
        @else
        <div style="overflow-x:auto">
        <table>
            <thead><tr>
                <th>Cliente</th>
                <th style="text-align:right">Saldo</th>
                <th style="text-align:right">Interés</th>
                <th style="text-align:right">Días</th>
            </tr></thead>
            <tbody>
            @foreach($atrasados as $a)
            @php $clrA = $a->dias_atraso > 15 ? '#dc2626' : ($a->dias_atraso > 7 ? '#d97706' : '#ca8a04'); @endphp
            <tr>
                <td><a href="{{ route('prestamos.show', $a->id) }}" style="color:var(--accent);text-decoration:none;font-size:13px">{{ $a->cliente?->nombre ?? '—' }}</a></td>
                <td style="text-align:right;font-family:monospace;font-size:12px">{{ fmtM($a->saldo_actual) }}</td>
                <td style="text-align:right;font-family:monospace;font-size:12px;color:#f59e0b">{{ fmtM($a->interes_acumulado) }}</td>
                <td style="text-align:right;font-weight:600;color:{{ $clrA }}">{{ $a->dias_atraso }}d</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</div>

@endsection
