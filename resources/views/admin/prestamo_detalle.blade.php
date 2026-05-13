@extends('layouts.app')

@section('title', 'Préstamo #' . $prestamo->id)

@section('content')

@php
// Exclude congelado/diferido pagos from counts and totals (they have monto_cobrado=0)
$pagados    = $pagos->where('estatus', 'Pagado')->filter(fn($p) => !in_array($p->tipo_pago ?? 'plan', ['congelado','liquidado']));
$pendientes = $pagos->whereIn('estatus', ['Pendiente','Atrasado']);
$parciales  = $pagos->where('estatus', 'Parcial');

$cobrosEfectivos = $pagos->whereIn('estatus', ['Pagado','Parcial'])
    ->filter(fn($p) => !in_array($p->tipo_pago ?? 'plan', ['congelado','liquidado']));
$totalCobrado    = $cobrosEfectivos->sum('monto_cobrado');

// Mora interest accumulated (updated in controller on each page load)
$interesPendiente = (float)($prestamo->interes_acumulado ?? 0);

// Balance = remaining principal (saldo_actual) + mora. Reflects extra payments immediately.
$totalAdeudadoKpi = (float)$prestamo->saldo_actual + $interesPendiente;

// Breakdown of what has been collected (capital vs interest/mora)
$capitalCobrado = $cobrosEfectivos->sum('capital');
$interesCobrado = $cobrosEfectivos->sum('interes');

// Remaining interest still owed from pending plan pagos
$interesRestante = $pendientes
    ->filter(fn($p) => ($p->tipo_pago ?? 'plan') === 'plan')
    ->sum('interes');

// Progress: collected vs total agreed (monto = total to return)
$montoTotal = max((float)$prestamo->monto, $totalCobrado);
$pctCapital = $montoTotal > 0 ? min(100, round($capitalCobrado / $montoTotal * 100, 1)) : 0;
$pctInteres = $montoTotal > 0 ? min(100 - $pctCapital, round($interesCobrado / $montoTotal * 100, 1)) : 0;
$pct        = $pctCapital + $pctInteres;

// Last payment date = most recent fecha_pago across all pagos (excludes congelado/liquidado)
$ultimaFechaPago = $pagos
    ->filter(fn($pg) => !empty($pg->fecha_pago) && !in_array($pg->tipo_pago ?? 'plan', ['congelado','liquidado']))
    ->sortByDesc(fn($pg) => $pg->fecha_pago instanceof \Carbon\Carbon
        ? $pg->fecha_pago->toDateString()
        : (string)$pg->fecha_pago)
    ->first()
    ?->fecha_pago
    ?->toDateString();

// Completion date (when loan reached Finalizado)
$fechaCompletado = ($prestamo->estatus === 'Finalizado') ? $ultimaFechaPago : null;

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
    'Retirado'   => ['#f1f5f9','#64748b'],
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
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span class="badge {{ $badgeClass }}" style="font-size:13px;padding:6px 14px">{{ $prestamo->estatus }}</span>
        @if($prestamo->payment_hold ?? false)
        <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:999px;font-size:12px;font-weight:700;background:#fff7ed;border:1.5px solid #fb923c;color:#c2410c">
            ⏸ Pago Diferido Activo
        </span>
        @endif
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
        @if($prestamo->estatus === 'Pendiente')
        @php $diasRestantes = max(0, 5 - (int)$prestamo->created_at->diffInDays(now())); @endphp
        <div style="margin-top:8px;font-size:11px;color:{{ $diasRestantes <= 1 ? '#dc2626' : '#ca8a04' }};font-weight:600">
            ⏳ {{ $diasRestantes }} día(s) para retiro automático
        </div>
        @endif
    </div>
    <div class="card" style="padding:16px 18px">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:6px">Balance restante</div>
        <div style="font-size:22px;font-weight:700;font-family:monospace;color:#dc2626">${{ number_format($totalAdeudadoKpi, 2, '.', ',') }}</div>
        @if($interesPendiente > 0)
        <div style="font-size:11px;color:var(--text2);margin-top:4px;font-family:monospace">
            ${{ number_format((float)$prestamo->saldo_actual, 2, '.', ',') }} capital + ${{ number_format($interesPendiente, 2, '.', ',') }} mora
        </div>
        @else
        <div style="font-size:11px;color:var(--text2);margin-top:4px;font-family:monospace">
            ${{ number_format((float)$prestamo->saldo_actual, 2, '.', ',') }} capital · {{ $pendientes->count() }} cuota(s) pend.
        </div>
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

@php $interesAcordado = round((float)$prestamo->monto - (float)$prestamo->monto_entregado, 2); @endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px">
    @foreach([
        ['Total préstamo',  '$'.number_format($prestamo->monto,2,'.',','), '#2563eb'],
        ['Cuota',           '$'.number_format($prestamo->cuota,2,'.',','), 'var(--text)'],
        ['Total cobrado',   '$'.number_format($totalCobrado,2,'.',','),    '#16a34a'],
    ] as [$label, $val, $color])
    <div class="card" style="padding:14px 18px">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">{{ $label }}</div>
        <div style="font-size:19px;font-weight:600;font-family:monospace;color:{{ $color }}">{{ $val }}</div>
        @if($label === 'Total préstamo')
        <div style="font-size:10px;color:var(--text3);margin-top:3px;font-family:monospace">
            ${{ number_format($prestamo->monto_entregado,2,'.',',') }} principal + ${{ number_format($interesAcordado,2,'.',',') }} interés
        </div>
        @endif
    </div>
    @endforeach
</div>

{{-- Actions panel: cobro extra, agendar, pago diferido, cambiar frecuencia --}}
@if(in_array($prestamo->estatus, ['Activo','Atrasado']) && in_array($puesto, ['admin','promo']))
<div class="card" style="padding:0;overflow:hidden;margin-bottom:16px">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Acciones del préstamo</div>
    <div style="padding:14px 18px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">

        {{-- Cobro Inmediato --}}
        <button onclick="document.getElementById('modal-cobro-extra').showModal()"
            style="padding:8px 16px;border-radius:8px;border:1.5px solid #2563eb;background:rgba(37,99,235,.07);color:#2563eb;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
            + Cobro inmediato
        </button>

        {{-- Agendar Cobro --}}
        <button onclick="document.getElementById('modal-agendar').showModal()"
            style="padding:8px 16px;border-radius:8px;border:1.5px solid #7c3aed;background:rgba(124,58,237,.07);color:#7c3aed;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
            📅 Agendar cobro
        </button>

        {{-- Pago Diferido (Payment Hold) --}}
        <form method="POST" action="{{ route('prestamos.paymentHold', $prestamo->id) }}" style="margin:0"
            onsubmit="return confirm('{{ ($prestamo->payment_hold ?? false) ? '¿Cancelar el pago diferido y restaurar el plan?' : '¿Establecer pago diferido? El siguiente cobro se combinará con el siguiente y se pagará doble.' }}')">
            @csrf
            @if($prestamo->payment_hold ?? false)
            <button type="submit"
                style="padding:8px 16px;border-radius:8px;border:1.5px solid #fb923c;background:rgba(251,146,60,.12);color:#c2410c;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                ↩ Cancelar pago diferido
            </button>
            @else
            <button type="submit"
                style="padding:8px 16px;border-radius:8px;border:1.5px solid #f59e0b;background:rgba(245,158,11,.07);color:#92400e;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                ⏸ Establecer pago diferido
            </button>
            @endif
        </form>

        {{-- Cambiar Frecuencia (admin only) --}}
        @if($puesto === 'admin')
        <button onclick="document.getElementById('modal-frecuencia').showModal()"
            style="padding:8px 16px;border-radius:8px;border:1.5px solid #d1d5db;background:#f9fafb;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
            ⚙ Cambiar frecuencia
        </button>
        @endif

    </div>
</div>
@endif

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
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px 20px;margin-bottom:12px">
            <div>
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text3);margin-bottom:3px">Mora acumulada</div>
                <div style="font-size:18px;font-weight:700;font-family:monospace;color:#f59e0b">${{ number_format($prestamo->interes_acumulado,2,'.',',') }}</div>
            </div>
            <div>
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text3);margin-bottom:3px">Interés diario</div>
                <div style="font-size:18px;font-weight:700;font-family:monospace;color:#8b5cf6">${{ number_format($prestamo->interes_diario,2,'.',',') }}/día</div>
            </div>
            <div>
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text3);margin-bottom:3px">Último cálculo</div>
                <div style="font-size:13px;font-weight:600;font-family:monospace;color:var(--text2);margin-top:3px">
                    {{ $prestamo->fecha_ultimo_interes ? $prestamo->fecha_ultimo_interes->format('d/m/Y') : 'No iniciado' }}
                </div>
            </div>
        </div>
        @if($puesto === 'admin')
        <div style="border-top:1px solid var(--border);padding-top:12px">
            <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text3);margin-bottom:8px">Configurar interés diario por mora</div>
            <form method="POST" action="{{ route('prestamos.setMora', $prestamo->id) }}" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                @csrf
                <span style="font-size:12px;color:var(--text2)">$</span>
                <input type="number" name="interes_diario" step="0.01" min="0"
                    value="{{ number_format((float)$prestamo->interes_diario, 2, '.', '') }}"
                    style="width:90px;padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:monospace;outline:none"
                    placeholder="0.00">
                <span style="font-size:12px;color:var(--text2)">por día</span>
                <button type="submit"
                    style="padding:6px 14px;border-radius:6px;border:1px solid var(--accent);background:rgba(59,130,246,.08);color:var(--accent);font-size:12px;font-weight:600;cursor:pointer;font-family:var(--font)">
                    Guardar
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endif

{{-- Progress (two-tone: capital=blue, interest=green) --}}
<div class="card" style="padding:18px 20px;margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;margin-bottom:10px">
        <span style="font-size:13px;color:var(--text2)">Progreso del préstamo</span>
        <span style="font-size:13px;font-weight:700;font-family:monospace;color:var(--accent)">{{ $pct }}% pagado</span>
    </div>
    <div style="height:10px;background:#f3f4f6;border-radius:5px;overflow:hidden;display:flex">
        <div style="height:100%;width:{{ $pctCapital }}%;background:#2563eb;transition:width .3s" title="Capital cobrado: ${{ number_format($capitalCobrado,2,'.',',') }}"></div>
        <div style="height:100%;width:{{ $pctInteres }}%;background:#16a34a;transition:width .3s" title="Interés cobrado: ${{ number_format($interesCobrado,2,'.',',') }}"></div>
    </div>
    <div style="display:flex;gap:16px;margin-top:9px;font-size:11px;font-family:monospace;flex-wrap:wrap;align-items:center">
        <span style="display:flex;align-items:center;gap:5px">
            <span style="width:9px;height:9px;border-radius:2px;background:#2563eb;flex-shrink:0;display:inline-block"></span>
            <span>Capital: <strong>${{ number_format($capitalCobrado,2,'.',',') }}</strong></span>
        </span>
        <span style="display:flex;align-items:center;gap:5px">
            <span style="width:9px;height:9px;border-radius:2px;background:#16a34a;flex-shrink:0;display:inline-block"></span>
            <span>Interés / mora: <strong>${{ number_format($interesCobrado,2,'.',',') }}</strong></span>
        </span>
        <span style="color:var(--text3);margin-left:auto">Restante: ${{ number_format($totalAdeudadoKpi,2,'.',',') }}</span>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    {{-- Credit details --}}
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Detalles del crédito</div>
        <div style="padding:16px 18px;display:grid;grid-template-columns:1fr 1fr;gap:10px 20px">
            {{-- Total préstamo con desglose acordado y restante --}}
            <div style="grid-column:span 2">
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)">Total préstamo / Deuda acordada</div>
                <div style="font-size:15px;font-weight:700;font-family:monospace;color:#2563eb;margin-top:2px">
                    ${{ number_format($prestamo->monto,2,'.',',') }}
                </div>
                <div style="font-size:11px;color:var(--text3);margin-top:1px;font-family:monospace">
                    ${{ number_format($prestamo->monto_entregado,2,'.',',') }} principal
                    + ${{ number_format($interesAcordado,2,'.',',') }} interés acordado
                </div>
                @if($totalCobrado > 0)
                <div style="margin-top:8px;padding:6px 10px;background:#f8fafc;border:1px solid var(--border);border-radius:6px;display:flex;gap:14px;flex-wrap:wrap">
                    <span style="font-size:11px;font-family:monospace;color:#2563eb;display:flex;align-items:center;gap:4px">
                        <span style="width:7px;height:7px;border-radius:50%;background:#2563eb;display:inline-block"></span>
                        Capital restante: <strong>${{ number_format((float)$prestamo->saldo_actual,2,'.',',') }}</strong>
                    </span>
                    <span style="font-size:11px;font-family:monospace;color:#16a34a;display:flex;align-items:center;gap:4px">
                        <span style="width:7px;height:7px;border-radius:50%;background:#16a34a;display:inline-block"></span>
                        Interés restante: <strong>${{ number_format($interesRestante,2,'.',',') }}</strong>
                    </span>
                </div>
                @endif
            </div>

            @foreach([
                ['Frecuencia',        $prestamo->frecuencia],
                ['Num. pagos',        $prestamo->num_pagos],
                ['Tasa diaria',       $prestamo->tasa_diaria > 0 ? $prestamo->tasa_diaria.'%' : '— (pago fijo)'],
                ['Fecha inicio',      $prestamo->fecha_inicio ? $prestamo->fecha_inicio->format('d/m/Y') : '—'],
                ['Fecha fin estimada',$prestamo->fecha_fin ? $prestamo->fecha_fin->format('d/m/Y') : '—'],
                ['Fecha completado',  $fechaCompletado ? \Carbon\Carbon::parse($fechaCompletado)->format('d/m/Y') : ($prestamo->estatus === 'Finalizado' ? '—' : 'En curso')],
                ['Fecha solicitud',   $prestamo->created_at ? $prestamo->created_at->format('d/m/Y H:i') : '—'],
                ['Promotor',          $prestamo->promotor?->nombre ?? '—'],
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
                <th>Tipo</th>
                <th>Fecha programada</th>
                <th>Fecha de pago</th>
                <th style="text-align:right">Cuota</th>
                <th style="text-align:right">Cobrado</th>
                <th style="text-align:right">Capital</th>
                <th style="text-align:right">Interés / Mora</th>
                <th style="text-align:right">Saldo</th>
                <th>Estatus</th>
                <th>Nota</th>
                @if(in_array($puesto, ['admin','promo']) && in_array($prestamo->estatus, ['Activo','Atrasado']))
                <th></th>
                @endif
            </tr>
        </thead>
        <tbody>
        @foreach($pagos as $p)
        @php
            $tipoPago    = $p->tipo_pago ?? 'plan';
            $esCongelado = $tipoPago === 'congelado';
            $esLiquidado = $tipoPago === 'liquidado';

            $rowBg = match(true) {
                $esCongelado               => 'background:#fff7ed',
                $esLiquidado               => 'background:#f9fafb',
                $p->estatus === 'Pagado'   => 'background:#f0fdf4',
                $p->estatus === 'Parcial'  => 'background:#fffbeb',
                $p->estatus === 'Atrasado' => 'background:#fff5f5',
                default                    => '',
            };

            $statusColors = match(true) {
                $esCongelado               => ['#ffedd5','#c2410c'],
                $esLiquidado               => ['#f3f4f6','#6b7280'],
                $p->estatus === 'Pagado'   => ['#dcfce7','#166534'],
                $p->estatus === 'Parcial'  => ['#fef9c3','#854d0e'],
                $p->estatus === 'Atrasado' => ['#fee2e2','#991b1b'],
                default                    => ['#f3f4f6','var(--text2)'],
            };

            $tipoBadge = match($tipoPago) {
                'extra'     => ['#dbeafe','#1d4ed8','Extra'],
                'agendado'  => ['#ede9fe','#6d28d9','Agendado'],
                'congelado' => ['#ffedd5','#c2410c','Diferido'],
                'liquidado' => ['#f3f4f6','#6b7280','Liquidado'],
                default     => null,
            };

            $estatusLabel = match(true) {
                $esCongelado  => 'Diferido',
                $esLiquidado  => 'Liquidado',
                default       => $p->estatus,
            };

            // Clean nota: hide internal ORIG: marker from display
            $notaDisplay = $p->nota_cobro ?? '—';
            if ($esCongelado) {
                $notaDisplay = explode('|ORIG:', $notaDisplay)[0];
            }
            // Liquidado rows: show "—" for cobrado amount
            if ($esLiquidado) {
                $notaDisplay = $p->nota_cobro ?? '—';
            }
        @endphp
        @php $esDimmed = $esCongelado || $esLiquidado; @endphp
        <tr style="{{ $rowBg }}{{ $esDimmed ? ';opacity:0.75' : '' }}">
            <td style="font-weight:600;font-size:12px;text-align:center{{ $esDimmed ? ';color:var(--text3)' : '' }}">{{ $p->numero_pago }}</td>
            <td>
                @if($tipoBadge)
                <span style="display:inline-flex;padding:2px 8px;border-radius:8px;font-size:10px;font-weight:700;background:{{ $tipoBadge[0] }};color:{{ $tipoBadge[1] }}">{{ $tipoBadge[2] }}</span>
                @else
                <span style="font-size:11px;color:var(--text3)">Plan</span>
                @endif
            </td>
            <td style="font-family:monospace;font-size:12px">{{ \Carbon\Carbon::parse($p->fecha_programada)->format('d/m/Y') }}</td>
            <td style="font-family:monospace;font-size:12px">{{ $p->fecha_pago ? \Carbon\Carbon::parse($p->fecha_pago)->format('d/m/Y') : '—' }}</td>
            <td style="text-align:right;font-family:monospace;font-size:12px{{ $esDimmed ? ';color:var(--text3)' : '' }}">${{ number_format($p->monto_cuota,2,'.',',') }}</td>
            <td style="text-align:right;font-family:monospace;font-size:12px">
                @if($esDimmed)
                <span style="font-size:11px;color:var(--text3)">—</span>
                @else
                {{ $p->monto_cobrado !== null ? '$'.number_format($p->monto_cobrado,2,'.',',') : '—' }}
                @endif
            </td>
            <td style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($p->capital,2,'.',',') }}</td>
            <td style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($p->interes,2,'.',',') }}</td>
            <td style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($p->saldo_restante,2,'.',',') }}</td>
            <td><span style="display:inline-flex;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:600;background:{{ $statusColors[0] }};color:{{ $statusColors[1] }}">{{ $estatusLabel }}</span></td>
            <td style="font-size:12px;color:var(--text2);max-width:160px">{{ $notaDisplay }}</td>
            @if(in_array($puesto, ['admin','promo']) && in_array($prestamo->estatus, ['Activo','Atrasado']))
            <td>
                @if(!$esDimmed && in_array($p->estatus, ['Pendiente','Atrasado']))
                <button
                    onclick="abrirModalCuota({{ $p->id }}, {{ $p->numero_pago }}, {{ $p->monto_cuota }})"
                    style="padding:4px 10px;border-radius:6px;border:1.5px solid #16a34a;background:rgba(22,163,74,.08);color:#16a34a;font-size:11px;font-weight:700;cursor:pointer;font-family:var(--font);white-space:nowrap">
                    Cobrar
                </button>
                @else
                <span style="font-size:11px;color:var(--text3)">—</span>
                @endif
            </td>
            @endif
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     MODALES: Cobro Inmediato / Agendar Cobro / Cambiar Frecuencia
     Requieren <dialog> — soportado en todos los navegadores modernos
═══════════════════════════════════════════════════════════ --}}
@if(in_array($prestamo->estatus, ['Activo','Atrasado']) && in_array($puesto, ['admin','promo']))

{{-- Modal: Cobro Inmediato --}}
<dialog id="modal-cobro-extra" style="border:none;border-radius:14px;padding:0;box-shadow:0 8px 40px rgba(0,0,0,.18);max-width:420px;width:100%">
    <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
            <div style="font-size:15px;font-weight:700">Cobro Inmediato</div>
            <div style="font-size:12px;color:var(--text2);margin-top:2px">Registra un abono extra fuera del plan de pagos</div>
        </div>
        <button onclick="document.getElementById('modal-cobro-extra').close()"
            style="background:none;border:none;font-size:18px;cursor:pointer;color:var(--text3);line-height:1">&times;</button>
    </div>
    <form method="POST" action="{{ route('prestamos.cobroExtra', $prestamo->id) }}" onsubmit="return submitOnce(this)">
        @csrf
        <div style="padding:20px 24px;display:grid;gap:14px">
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Monto a cobrar *</label>
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="font-size:13px;color:var(--text2)">$</span>
                    <input type="number" name="monto" step="0.01" min="0.01" required placeholder="0.00"
                        style="flex:1;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:monospace;outline:none">
                </div>
                @if((float)($prestamo->interes_acumulado ?? 0) > 0)
                <div style="margin-top:6px;font-size:11px;color:#f59e0b;font-weight:600">
                    ⚠ Mora pendiente: ${{ number_format($prestamo->interes_acumulado,2,'.',',') }} — se aplicará primero a mora.
                </div>
                @endif
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Nota (opcional)</label>
                <input type="text" name="nota" maxlength="255" placeholder="Ej. Abono voluntario del cliente"
                    style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;outline:none;box-sizing:border-box">
            </div>
        </div>
        <div style="padding:14px 24px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end">
            <button type="button" onclick="document.getElementById('modal-cobro-extra').close()"
                style="padding:8px 18px;border-radius:8px;border:1px solid var(--border);background:#f9fafb;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                Cancelar
            </button>
            <button type="submit"
                style="padding:8px 18px;border-radius:8px;border:none;background:#2563eb;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                Registrar cobro
            </button>
        </div>
    </form>
</dialog>

{{-- Modal: Agendar Cobro --}}
<dialog id="modal-agendar" style="border:none;border-radius:14px;padding:0;box-shadow:0 8px 40px rgba(0,0,0,.18);max-width:420px;width:100%">
    <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
            <div style="font-size:15px;font-weight:700">Agendar Cobro Futuro</div>
            <div style="font-size:12px;color:var(--text2);margin-top:2px">Programa un cobro acordado fuera de las fechas del plan</div>
        </div>
        <button onclick="document.getElementById('modal-agendar').close()"
            style="background:none;border:none;font-size:18px;cursor:pointer;color:var(--text3);line-height:1">&times;</button>
    </div>
    <form method="POST" action="{{ route('prestamos.agendarCobro', $prestamo->id) }}" onsubmit="return submitOnce(this)">
        @csrf
        <div style="padding:20px 24px;display:grid;gap:14px">
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Monto acordado *</label>
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="font-size:13px;color:var(--text2)">$</span>
                    <input type="number" name="monto" step="0.01" min="0.01" required placeholder="0.00"
                        style="flex:1;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:monospace;outline:none">
                </div>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Fecha de cobro *</label>
                <input type="date" name="fecha" required min="{{ now()->toDateString() }}"
                    style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;outline:none;box-sizing:border-box">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Nota (opcional)</label>
                <input type="text" name="nota" maxlength="255" placeholder="Ej. Acuerdo de pago especial"
                    style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;outline:none;box-sizing:border-box">
            </div>
        </div>
        <div style="padding:14px 24px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end">
            <button type="button" onclick="document.getElementById('modal-agendar').close()"
                style="padding:8px 18px;border-radius:8px;border:1px solid var(--border);background:#f9fafb;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                Cancelar
            </button>
            <button type="submit"
                style="padding:8px 18px;border-radius:8px;border:none;background:#7c3aed;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                Agendar cobro
            </button>
        </div>
    </form>
</dialog>

{{-- Modal: Cambiar Frecuencia (admin only) --}}
@if($puesto === 'admin')
<dialog id="modal-frecuencia" style="border:none;border-radius:14px;padding:0;box-shadow:0 8px 40px rgba(0,0,0,.18);max-width:440px;width:100%">
    <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
            <div style="font-size:15px;font-weight:700">Cambiar Frecuencia de Pagos</div>
            <div style="font-size:12px;color:var(--text2);margin-top:2px">Reprograma todos los pagos pendientes del plan</div>
        </div>
        <button onclick="document.getElementById('modal-frecuencia').close()"
            style="background:none;border:none;font-size:18px;cursor:pointer;color:var(--text3);line-height:1">&times;</button>
    </div>
    <form method="POST" action="{{ route('prestamos.actualizarFrecuencia', $prestamo->id) }}" onsubmit="return submitOnce(this)">
        @csrf
        <div style="padding:20px 24px;display:grid;gap:14px">
            <div style="padding:10px 14px;background:#fef9c3;border:1px solid #fcd34d;border-radius:8px;font-size:12px;color:#854d0e">
                ⚠ Esto reprogramará <strong>todos los pagos del plan pendientes</strong> con la nueva frecuencia a partir de la fecha indicada.
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Nueva frecuencia *</label>
                <select name="frecuencia" required
                    style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;background:#fff">
                    @foreach(['Diario','Semanal','Quincenal','Mensual'] as $f)
                    <option value="{{ $f }}" {{ $prestamo->frecuencia === $f ? 'selected' : '' }}>{{ $f }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Primera fecha de cobro (nueva) *</label>
                <input type="date" name="fecha_nuevo_inicio" required
                    value="{{ now()->toDateString() }}"
                    style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;outline:none;box-sizing:border-box">
                <div style="margin-top:5px;font-size:11px;color:var(--text3)">
                    Frecuencia actual: <strong>{{ $prestamo->frecuencia }}</strong>
                </div>
            </div>
        </div>
        <div style="padding:14px 24px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end">
            <button type="button" onclick="document.getElementById('modal-frecuencia').close()"
                style="padding:8px 18px;border-radius:8px;border:1px solid var(--border);background:#f9fafb;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                Cancelar
            </button>
            <button type="submit" onclick="return confirm('¿Confirmas que deseas reprogramar todos los pagos pendientes con la nueva frecuencia?')"
                style="padding:8px 18px;border-radius:8px;border:none;background:#374151;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                Aplicar cambio
            </button>
        </div>
    </form>
</dialog>
@endif

{{-- Modal: Cobrar cuota específica --}}
<dialog id="modal-pagar-cuota" style="border:none;border-radius:14px;padding:0;box-shadow:0 8px 40px rgba(0,0,0,.18);max-width:400px;width:100%">
    <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
            <div style="font-size:15px;font-weight:700" id="mcq-titulo">Cobrar cuota #—</div>
            <div style="font-size:12px;color:var(--text2);margin-top:2px">Registra el pago de esta cuota específica</div>
        </div>
        <button onclick="document.getElementById('modal-pagar-cuota').close()"
            style="background:none;border:none;font-size:18px;cursor:pointer;color:var(--text3);line-height:1">&times;</button>
    </div>
    <form method="POST" action="{{ route('prestamos.pagarCuota', $prestamo->id) }}" onsubmit="return submitOnce(this)">
        @csrf
        <input type="hidden" name="pago_id" id="mcq-pago-id">
        <div style="padding:20px 24px;display:grid;gap:14px">
            <div style="padding:10px 14px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;font-size:12px;color:#166534;font-weight:600" id="mcq-info">
                Cuota: $—
            </div>
            @if((float)($prestamo->interes_acumulado ?? 0) > 0)
            <div style="padding:8px 12px;background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;font-size:11px;color:#92400e;font-weight:600">
                ⚠ Mora pendiente: ${{ number_format($prestamo->interes_acumulado,2,'.',',') }} — se aplicará primero.
            </div>
            @endif
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Monto a cobrar *</label>
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="font-size:13px;color:var(--text2)">$</span>
                    <input type="number" name="monto" id="mcq-monto" step="0.01" min="0.01" required placeholder="0.00"
                        style="flex:1;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:monospace;outline:none">
                </div>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Nota (opcional)</label>
                <input type="text" name="nota" maxlength="255" placeholder="Ej. Pago en efectivo"
                    style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;outline:none;box-sizing:border-box">
            </div>
        </div>
        <div style="padding:14px 24px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end">
            <button type="button" onclick="document.getElementById('modal-pagar-cuota').close()"
                style="padding:8px 18px;border-radius:8px;border:1px solid var(--border);background:#f9fafb;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                Cancelar
            </button>
            <button type="submit"
                style="padding:8px 18px;border-radius:8px;border:none;background:#16a34a;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                Registrar pago
            </button>
        </div>
    </form>
</dialog>

{{-- Close dialogs on backdrop click; prevent double submit --}}
<script>
['modal-cobro-extra','modal-agendar','modal-frecuencia','modal-pagar-cuota'].forEach(function(id){
    var el = document.getElementById(id);
    if(!el) return;
    el.addEventListener('click', function(e){ if(e.target === el) el.close(); });
});
function submitOnce(form) {
    var btn = form.querySelector('button[type="submit"]');
    if(btn && btn.disabled) return false; // block duplicate
    if(btn) {
        btn.disabled = true;
        btn.textContent = 'Guardando...';
    }
    return true;
}

function abrirModalCuota(pagoId, numeroPago, montoCuota) {
    document.getElementById('mcq-pago-id').value = pagoId;
    document.getElementById('mcq-titulo').textContent = 'Cobrar cuota #' + numeroPago;
    document.getElementById('mcq-info').textContent   = 'Cuota del plan: $' + parseFloat(montoCuota).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('mcq-monto').value        = parseFloat(montoCuota).toFixed(2);
    document.getElementById('modal-pagar-cuota').showModal();
}
</script>

@endif

@endsection
