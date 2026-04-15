@extends('layouts.app')

@section('title', 'Préstamo #' . $prestamo->id)

@section('content')

@php
$pagados   = $pagos->where('estatus', 'Pagado');
$pendientes= $pagos->whereIn('estatus', ['Pendiente','Atrasado']);

$cobrosEfectivos  = $pagos->whereIn('estatus', ['Pagado','Parcial']);
$totalCobrado     = $cobrosEfectivos->sum('monto_cobrado');
$principalPagado  = max(0, (float)$prestamo->monto - (float)$prestamo->saldo_actual);
$interesMoraPagado= max(0, $totalCobrado - $principalPagado);
$interesPendiente = (float)($prestamo->interes_acumulado ?? 0);
$totalAcordado    = $totalCobrado + (float)$prestamo->saldo_actual + $interesPendiente;
$pct              = $totalAcordado > 0 ? min(100, round($totalCobrado / $totalAcordado * 100)) : 0;
$totalAdeudadoKpi = (float)$prestamo->saldo_actual + $interesPendiente;

$ultimaFechaPago = null;
foreach ($pagos->sortByDesc('numero_pago') as $pg) {
    if (!empty($pg->fecha_pago)) { $ultimaFechaPago = substr($pg->fecha_pago, 0, 10); break; }
}

$badgeClass = match($prestamo->estatus) {
    'Activo'     => 'badge-green',
    'Atrasado'   => 'badge-red',
    'Finalizado' => 'badge-gray',
    'Retirado'   => 'badge-gray',
    default      => 'badge-yellow',
};

$estatusColor = match($prestamo->estatus) {
    'Activo'     => ['#dcfce7','#166534'],
    'Atrasado'   => ['#fee2e2','#991b1b'],
    'Finalizado' => ['#f1f5f9','#475569'],
    'Pendiente'  => ['#fef9c3','#854d0e'],
    default      => ['#f1f5f9','#64748b'],
};
[$estatusBg, $estatusTx] = $estatusColor;

$puesto = auth()->user()->puesto;
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="{{ route('prestamos.index') }}" class="btn btn-sm" style="background:#f3f4f6;color:var(--text)">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M8 2L4 6l4 4"/></svg>
            Volver
        </a>
        <div>
            <h2 style="font-size:20px;font-weight:700;margin-bottom:2px">Préstamo #{{ $prestamo->id }}</h2>
            <p style="color:var(--text2);font-size:13px">{{ $prestamo->cliente?->nombre ?? '—' }}</p>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
        <span class="badge {{ $badgeClass }}" style="font-size:13px;padding:6px 14px">{{ $prestamo->estatus }}</span>
        @if($puesto === 'admin')
        <a href="{{ route('prestamos.edit', $prestamo->id) }}" class="btn btn-sm" style="background:#f3f4f6;color:var(--text)">Editar</a>
        @endif
    </div>
</div>

{{-- KPI cards --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:14px">
    <div class="card" style="padding:16px 18px">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:8px">Estatus</div>
        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:999px;font-size:14px;font-weight:700;background:{{ $estatusBg }};color:{{ $estatusTx }}">
            <span style="width:7px;height:7px;border-radius:50%;background:{{ $estatusTx }};display:inline-block"></span>
            {{ $prestamo->estatus }}
        </span>
    </div>
    <div class="card" style="padding:16px 18px">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:6px">Balance total</div>
        <div style="font-size:22px;font-weight:700;font-family:monospace;color:#dc2626">${{ number_format($totalAdeudadoKpi, 2, '.', ',') }}</div>
        @if($interesPendiente > 0)
        <div style="font-size:11px;color:var(--text2);margin-top:4px;font-family:monospace">
            ${{ number_format($prestamo->saldo_actual, 2, '.', ',') }} principal + ${{ number_format($interesPendiente, 2, '.', ',') }} interés
        </div>
        @else
        <div style="font-size:11px;color:var(--text2);margin-top:4px">Solo principal</div>
        @endif
    </div>
    <div class="card" style="padding:16px 18px">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:6px">Fecha último pago</div>
        @if($ultimaFechaPago)
        <div style="font-size:22px;font-weight:700;font-family:monospace;color:#16a34a">{{ \Carbon\Carbon::parse($ultimaFechaPago)->format('d/m/Y') }}</div>
        @else
        <div style="font-size:18px;font-weight:600;color:var(--text3)">Sin pagos</div>
        @endif
        <div style="font-size:11px;color:var(--text2);margin-top:4px">{{ $pagados->count() }} de {{ $pagos->count() }} pagos realizados</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px">
    @foreach([['Monto entregado','$'.number_format($prestamo->monto,2,'.',','),'var(--text)'],['Cuota','$'.number_format($prestamo->cuota,2,'.',','),'var(--text)'],['Total cobrado','$'.number_format($totalCobrado,2,'.',','),'#16a34a']] as [$label, $val, $color])
    <div class="card" style="padding:14px 18px">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">{{ $label }}</div>
        <div style="font-size:19px;font-weight:600;font-family:monospace;color:{{ $color }}">{{ $val }}</div>
    </div>
    @endforeach
</div>

{{-- Interest panel --}}
@if($interesInfo && in_array($prestamo->estatus, ['Activo','Atrasado']))
<div class="card" style="padding:0;overflow:hidden;margin-bottom:16px">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:13px;font-weight:600">Saldo con interés en tiempo real</span>
            @if(!$prestamo->interes_activo)
            <span style="font-size:11px;padding:2px 8px;background:#fef3c7;border:1px solid #fcd34d;border-radius:999px;color:#92400e;font-weight:600">Interés pausado</span>
            @endif
        </div>
        @if($puesto === 'admin')
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <form method="POST" action="{{ route('prestamos.toggleInteres', $prestamo->id) }}" style="margin:0">
                @csrf
                <button type="submit"
                    style="font-size:11px;padding:4px 12px;border-radius:999px;border:1px solid {{ $prestamo->interes_activo ? '#fca5a5' : '#86efac' }};background:{{ $prestamo->interes_activo ? 'rgba(220,38,38,.08)' : 'rgba(22,163,74,.08)' }};color:{{ $prestamo->interes_activo ? '#dc2626' : '#16a34a' }};cursor:pointer;font-weight:600"
                    onclick="return confirm('{{ $prestamo->interes_activo ? '¿Pausar el interés diario?' : '¿Reanudar el interés diario?' }}')">
                    {{ $prestamo->interes_activo ? '⏸ Pausar interés' : '▶ Reanudar interés' }}
                </button>
            </form>
            <form method="POST" action="{{ route('prestamos.toggleMora', $prestamo->id) }}" style="margin:0">
                @csrf
                <button type="submit"
                    style="font-size:11px;padding:4px 12px;border-radius:999px;border:1px solid {{ $prestamo->interes_mora_activo ? '#fcd34d' : '#d1d5db' }};background:{{ $prestamo->interes_mora_activo ? 'rgba(245,158,11,.12)' : '#f9fafb' }};color:{{ $prestamo->interes_mora_activo ? '#92400e' : 'var(--text2)' }};cursor:pointer;font-weight:600"
                    onclick="return confirm('{{ $prestamo->interes_mora_activo ? '¿Desactivar interés por mora?' : '¿Activar interés por mora?' }}')">
                    {{ $prestamo->interes_mora_activo ? '⚠ Mora activa' : '+ Activar mora' }}
                </button>
            </form>
        </div>
        @endif
    </div>
    <div style="padding:14px 18px">
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px 20px">
            <div>
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text3);margin-bottom:3px">Interés acumulado</div>
                <div style="font-size:18px;font-weight:700;font-family:monospace;color:#f59e0b">${{ number_format($prestamo->interes_acumulado,2,'.',',') }}</div>
            </div>
            <div>
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text3);margin-bottom:3px">Interés por día</div>
                <div style="font-size:18px;font-weight:700;font-family:monospace;color:#8b5cf6">${{ number_format($prestamo->interes_diario,2,'.',',') }}</div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Progress --}}
<div class="card" style="padding:18px 20px;margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
        <span style="font-size:13px;color:var(--text2)">Progreso del préstamo</span>
        <span style="font-size:13px;font-weight:600;font-family:monospace;color:var(--accent)">{{ $pct }}% pagado</span>
    </div>
    <div style="height:8px;background:#f3f4f6;border-radius:4px;overflow:hidden">
        <div style="height:100%;width:{{ $pct }}%;background:var(--accent);border-radius:4px"></div>
    </div>
    <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:11px;color:var(--text3);font-family:monospace">
        <span>Cobrado: ${{ number_format($totalCobrado,2,'.',',') }}</span>
        <span>Pendiente: ${{ number_format($totalAdeudadoKpi,2,'.',',') }}</span>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    {{-- Credit details --}}
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Detalles del crédito</div>
        <div style="padding:16px 18px;display:grid;grid-template-columns:1fr 1fr;gap:10px 20px">
            @foreach([
                ['Frecuencia',   $prestamo->frecuencia],
                ['Num. pagos',   $prestamo->num_pagos],
                ['Tasa diaria',  $prestamo->tasa_diaria > 0 ? $prestamo->tasa_diaria.'%' : '— (pago fijo)'],
                ['Fecha inicio', $prestamo->fecha_inicio ?? '—'],
                ['Promotor',     $prestamo->promotor?->nombre ?? '—'],
            ] as [$l, $v])
            <div>
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)">{{ $l }}</div>
                <div style="font-size:13px;font-weight:500;font-family:monospace;color:var(--text);margin-top:2px">{{ $v }}</div>
            </div>
            @endforeach

            {{-- Cobrador: con botón de auto-asignación para promo --}}
            <div>
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)">Cobrador</div>
                @php
                    $empleadoActual = auth()->user()->empleado;
                    $esCobradorActual = $empleadoActual && $prestamo->cobrador_id == $empleadoActual->id;
                    $puedeAsignarse = in_array($puesto, ['promo','admin'])
                        && in_array($prestamo->estatus, ['Pendiente','Activo','Atrasado'])
                        && ($puesto === 'admin' || ($prestamo->promotor_id == $empleadoActual?->id));
                @endphp
                @if($prestamo->cobrador)
                    <div style="display:flex;align-items:center;gap:8px;margin-top:2px;flex-wrap:wrap">
                        <span style="font-size:13px;font-weight:500;font-family:monospace;color:var(--text)">
                            {{ $prestamo->cobrador->nombre }}
                        </span>
                        @if($esCobradorActual)
                            <span style="font-size:10px;padding:1px 7px;border-radius:999px;background:#dcfce7;color:#166534;font-weight:600">Tú</span>
                        @endif
                        @if($puedeAsignarse && !$esCobradorActual)
                            <form method="POST" action="{{ route('prestamos.asignarme', $prestamo->id) }}" style="margin:0">
                                @csrf
                                <button type="submit" style="font-size:10px;padding:2px 10px;border-radius:999px;border:1px solid var(--accent);background:transparent;color:var(--accent);cursor:pointer;font-weight:600;font-family:var(--font)"
                                    onclick="return confirm('¿Reemplazar al cobrador actual y asignarte tú?')">
                                    Asignarme
                                </button>
                            </form>
                        @endif
                    </div>
                @else
                    <div style="display:flex;align-items:center;gap:8px;margin-top:2px;flex-wrap:wrap">
                        <span style="font-size:13px;color:var(--text3)">Sin cobrador</span>
                        @if($puedeAsignarse)
                            <form method="POST" action="{{ route('prestamos.asignarme', $prestamo->id) }}" style="margin:0">
                                @csrf
                                <button type="submit" style="font-size:10px;padding:2px 10px;border-radius:999px;border:none;background:var(--accent);color:#fff;cursor:pointer;font-weight:600;font-family:var(--font)">
                                    Asignarme como cobrador
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
    {{-- Client info --}}
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Datos del cliente</div>
        <div style="padding:16px 18px;display:grid;gap:10px">
            @foreach([
                ['Nombre',    $prestamo->cliente?->nombre ?? '—'],
                ['Celular',   $prestamo->cliente?->celular ?? '—'],
                ['Dirección', $prestamo->cliente?->direccion ?? '—'],
            ] as [$l, $v])
            <div>
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)">{{ $l }}</div>
                <div style="font-size:13px;font-weight:500;font-family:monospace;color:var(--text);margin-top:2px">{{ $v }}</div>
            </div>
            @endforeach
            <a href="{{ route('clientes.show', $prestamo->cliente_id) }}" class="btn btn-sm" style="background:#f3f4f6;color:var(--text);width:fit-content">Ver cliente</a>
        </div>
    </div>
</div>

{{-- Payment table --}}
<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Tabla de pagos</div>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha programada</th>
                <th>Fecha de pago</th>
                <th style="text-align:right">Cuota</th>
                <th style="text-align:right">Cobrado</th>
                <th style="text-align:right">Capital</th>
                <th style="text-align:right">Interés</th>
                <th style="text-align:right">Saldo</th>
                <th>Estatus</th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody>
        @foreach($pagos as $p)
        @php
            $paid = in_array($p->estatus, ['Pagado','Parcial']);
            $rowBg = match($p->estatus) {
                'Pagado'   => 'background:#f0fdf4',
                'Parcial'  => 'background:#fffbeb',
                'Atrasado' => 'background:#fff5f5',
                default    => '',
            };
            $statusColors = match($p->estatus) {
                'Pagado'   => ['#dcfce7','#166534'],
                'Parcial'  => ['#fef9c3','#854d0e'],
                'Atrasado' => ['#fee2e2','#991b1b'],
                default    => ['#f3f4f6','var(--text2)'],
            };
        @endphp
        <tr style="{{ $rowBg }}">
            <td style="font-weight:600;font-size:12px;text-align:center">{{ $p->numero_pago }}</td>
            <td style="font-family:monospace;font-size:12px">{{ \Carbon\Carbon::parse($p->fecha_programada)->format('d/m/Y') }}</td>
            <td style="font-family:monospace;font-size:12px">{{ $p->fecha_pago ? \Carbon\Carbon::parse($p->fecha_pago)->format('d/m/Y') : '—' }}</td>
            <td style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($p->monto_cuota,2,'.',',') }}</td>
            <td style="text-align:right;font-family:monospace;font-size:12px">{{ $p->monto_cobrado ? '$'.number_format($p->monto_cobrado,2,'.',',') : '—' }}</td>
            <td style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($p->capital,2,'.',',') }}</td>
            <td style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($p->interes,2,'.',',') }}</td>
            <td style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($p->saldo_restante,2,'.',',') }}</td>
            <td><span style="display:inline-flex;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:600;background:{{ $statusColors[0] }};color:{{ $statusColors[1] }}">{{ $p->estatus }}</span></td>
            <td style="font-size:12px;color:var(--text2);max-width:160px">{{ $p->nota_cobro ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

@endsection
