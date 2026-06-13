@extends('layouts.app')

@section('title', 'Vista general')

@push('styles')
<style>
/* ── Header de página ── */
.dash-header{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px}
.dash-header h2{font-family:var(--display);font-weight:500;font-size:24px;letter-spacing:-.02em;margin-bottom:3px}
.dash-header h2 em{font-style:italic;background:linear-gradient(100deg,var(--accent),var(--teal));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.dash-header p{font-size:13px;color:var(--text2)}
.dash-date{font-size:12px;color:var(--text3);background:var(--card);border:1px solid var(--border);padding:6px 14px;border-radius:999px;display:inline-flex;align-items:center;gap:7px;box-shadow:var(--shadow-sm)}
.dash-date svg{width:13px;height:13px;color:var(--accent)}

/* ── KPIs con icono y acento ── */
.kpi{display:flex;flex-direction:column}
.kpi-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.kpi-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.kpi-icon svg{width:15px;height:15px}

/* ── Tabla ── */
.dash-row-link{cursor:pointer}
.dash-avatar{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10.5px;font-weight:700;color:#fff;flex-shrink:0;letter-spacing:-.5px}
.dash-money{font-family:var(--font-mono);font-size:13px}
.dash-empty{text-align:center;padding:48px 24px;color:var(--text3)}
.dash-empty svg{margin:0 auto 12px;display:block;opacity:.3}

@media(max-width:640px){
    .dash-col-promotor,.dash-col-saldo,.dash-col-fecha{display:none}
    .kpi-grid{grid-template-columns:repeat(2,1fr)!important}
}
@media(max-width:380px){
    .kpi-grid{grid-template-columns:1fr!important}
}
</style>
@endpush

@section('content')

@php
    $h = now()->hour;
    $saludo = $h < 12 ? 'Buenos días' : ($h < 19 ? 'Buenas tardes' : 'Buenas noches');
    $nombreCorto = auth()->user()->alias ?: (auth()->user()->nombre ?: auth()->user()->usuario);
@endphp
<div class="dash-header">
    <div>
        <h2>{{ $saludo }}, <em>{{ $nombreCorto }}</em></h2>
        <p>Resumen de la operación al día de hoy.</p>
    </div>
    <span class="dash-date">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="3" width="12" height="11" rx="2"/><path d="M2 6.5h12M5.5 1.5v3M10.5 1.5v3"/></svg>
        {{ now()->translatedFormat('l, d \d\e F Y') }}
    </span>
</div>

<div class="kpi-grid">
    <div class="kpi">
        <div class="kpi-top">
            <div class="kpi-label" style="margin-bottom:0">Préstamos activos</div>
            <div class="kpi-icon" style="background:rgba(16,185,129,.1);color:var(--accent-hover)">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="3" width="12" height="10" rx="1.5"/><path d="M5 7h6M5 10h4"/></svg>
            </div>
        </div>
        <div class="kpi-value">{{ $kpis['prestamos_activos'] }}</div>
        <div class="kpi-sub">de {{ $kpis['total_prestamos'] }} en total</div>
    </div>
    <div class="kpi">
        <div class="kpi-top">
            <div class="kpi-label" style="margin-bottom:0">En mora</div>
            <div class="kpi-icon" style="background:rgba(220,38,38,.08);color:#dc2626">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 2L1.5 13h13L8 2z"/><path d="M8 6.5v3M8 11.5h.01"/></svg>
            </div>
        </div>
        <div class="kpi-value" style="color:#dc2626">{{ $kpis['prestamos_mora'] }}</div>
        <div class="kpi-sub">préstamos atrasados</div>
    </div>
    <div class="kpi">
        <div class="kpi-top">
            <div class="kpi-label" style="margin-bottom:0">Clientes</div>
            <div class="kpi-icon" style="background:rgba(99,102,241,.09);color:#4f46e5">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6" cy="5" r="2.5"/><path d="M1 14c0-2.761 2.239-5 5-5"/><circle cx="11" cy="5" r="2.5"/><path d="M15 14c0-2.761-2.239-5-5-5"/></svg>
            </div>
        </div>
        <div class="kpi-value">{{ $kpis['total_clientes'] }}</div>
        <div class="kpi-sub">clientes activos</div>
    </div>
    <div class="kpi">
        <div class="kpi-top">
            <div class="kpi-label" style="margin-bottom:0">Empleados</div>
            <div class="kpi-icon" style="background:rgba(45,212,191,.12);color:#0d9488">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6"/></svg>
            </div>
        </div>
        <div class="kpi-value">{{ $kpis['total_empleados'] }}</div>
        <div class="kpi-sub">activos</div>
    </div>
    <div class="kpi">
        <div class="kpi-top">
            <div class="kpi-label" style="margin-bottom:0">Cartera total</div>
            <div class="kpi-icon" style="background:rgba(246,183,60,.14);color:#b45309">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="6.5"/><path d="M8 4.5v7M10 6c0-.8-.9-1.5-2-1.5S6 5.2 6 6s.7 1.3 2 1.5c1.3.2 2 .7 2 1.5s-.9 1.5-2 1.5-2-.7-2-1.5"/></svg>
            </div>
        </div>
        <div class="kpi-value" style="font-size:20px">${{ number_format($kpis['cartera_total'], 0) }}</div>
        <div class="kpi-sub">saldo por cobrar</div>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <div class="card-header" style="padding:14px 20px;margin-bottom:0;border-bottom:1px solid var(--border)">
        <span class="card-title">Préstamos recientes</span>
        <a href="{{ route('prestamos.index') }}" class="btn btn-primary btn-sm">Ver todos</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th class="dash-col-promotor">Promotor</th>
                    <th>Monto</th>
                    <th class="dash-col-saldo">Saldo</th>
                    <th>Estatus</th>
                    <th class="dash-col-fecha">Fecha inicio</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prestamos as $p)
                @php
                    $badge = match($p->estatus) {
                        'Activo'    => 'badge-green',
                        'Atrasado'  => 'badge-red',
                        'Pendiente' => 'badge-yellow',
                        'Finalizado'=> 'badge-blue',
                        default     => 'badge-gray',
                    };
                    $nombreCliente = $p->cliente->nombre ?? '—';
                    $avColors = ['#3b82f6','#6366f1','#8b5cf6','#ec4899','#10b981','#f59e0b','#0ea5e9','#14b8a6'];
                    $avColor  = $avColors[crc32($nombreCliente) % count($avColors)];
                @endphp
                <tr class="dash-row-link" onclick="window.location='{{ route('prestamos.show', $p->id) }}'">
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <span class="dash-avatar" style="background:{{ $avColor }}">{{ strtoupper(substr($nombreCliente, 0, 2)) }}</span>
                            <div>
                                <div style="font-size:13px;font-weight:600;color:var(--text)">{{ $nombreCliente }}</div>
                                <div style="font-size:11px;color:var(--text3)">#{{ $p->id }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="dash-col-promotor" style="color:var(--text2)">{{ $p->promotor->nombre ?? '—' }}</td>
                    <td class="dash-money" style="font-weight:600">${{ number_format($p->monto, 0) }}</td>
                    <td class="dash-col-saldo dash-money" style="color:var(--text2)">${{ number_format($p->saldo_actual, 0) }}</td>
                    <td><span class="badge {{ $badge }}">{{ $p->estatus }}</span></td>
                    <td class="dash-col-fecha" style="color:var(--text2);font-size:12px">{{ $p->fecha_inicio->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="dash-empty">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 10h10M7 14h6"/></svg>
                        <p style="font-size:14px;font-weight:600;color:var(--text2);margin-bottom:4px">Sin préstamos registrados aún</p>
                        <p style="font-size:12px">Cuando registres el primer préstamo aparecerá aquí.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
