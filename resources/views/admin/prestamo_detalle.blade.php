@extends('layouts.app')

@section('title', 'Préstamo #' . $prestamo->id)

@section('content')

@php
// Exclude congelado/diferido pagos from counts and totals (they have monto_cobrado=0)
$pagados    = $pagos->where('estatus', 'Pagado')->filter(fn($p) => !in_array($p->tipo_pago ?? 'plan', ['congelado','liquidado']));
$pendientes = $pagos->whereIn('estatus', ['Pendiente','Atrasado']);
$parciales  = $pagos->where('estatus', 'Parcial');

// Calcular saldo real corriendo por cada pago (para la columna Saldo de la tabla)
// Ordena primero por pagos ya cobrados (fecha_pago ASC), luego pendientes (numero_pago ASC)
$pagosOrdenados = $pagos->sortBy(function($p) {
    if ($p->fecha_pago) return $p->fecha_pago->toDateString() . sprintf('%05d', $p->numero_pago);
    return '9999-99-99' . sprintf('%05d', $p->numero_pago);
});
$saldoCorr = (float)$prestamo->monto; // Deuda total inicial = monto a retornar
$saldoDisplay = [];
foreach ($pagosOrdenados as $pag) {
    $cobrado = (float)($pag->monto_cobrado ?? 0);
    $tipoPag = $pag->tipo_pago ?? 'plan';
    if ($cobrado > 0 && !in_array($tipoPag, ['congelado'])) {
        $saldoCorr = max(0, $saldoCorr - $cobrado);
    }
    $saldoDisplay[$pag->id] = $saldoCorr;
}

$cobrosEfectivos = $pagos->whereIn('estatus', ['Pagado','Parcial'])
    ->filter(fn($p) => !in_array($p->tipo_pago ?? 'plan', ['congelado','liquidado']));
$totalCobrado    = $cobrosEfectivos->sum('monto_cobrado');

// Mora interest accumulated (updated in controller on each page load)
$interesPendiente = (float)($prestamo->interes_acumulado ?? 0);

// ── Distribución REAL de cada cobro (interés-primero) ──────────────────────────
// El pool de interés acordado = monto_retornar - monto_entregado.
// Cada cobro efectivo va primero a reducir ese pool; lo que sobra es capital.
// Esto es SOLO para mostrar en tabla y KPIs — no altera pagos.capital/interes guardados.
$interesAcordadoTotal = max(0, (float)$prestamo->monto - (float)$prestamo->monto_entregado);
$interesPoolRestante  = $interesAcordadoTotal; // se va vaciando conforme se cobra

$capitalDisplay = []; // [pago_id => capital real cobrado en ese pago]
$interesDisplay = []; // [pago_id => interés real cobrado en ese pago]

foreach ($pagosOrdenados as $pag) {
    $cobrado = (float)($pag->monto_cobrado ?? 0);
    $tipoPag = $pag->tipo_pago ?? 'plan';
    if ($cobrado > 0 && !in_array($tipoPag, ['congelado', 'liquidado'])) {
        $intPago = min($cobrado, max(0.0, $interesPoolRestante));
        $capPago = round($cobrado - $intPago, 2);
        $interesPoolRestante = max(0.0, round($interesPoolRestante - $intPago, 2));
    } else {
        $intPago = 0.0;
        $capPago = 0.0;
    }
    $capitalDisplay[$pag->id] = round($capPago, 2);
    $interesDisplay[$pag->id] = round($intPago, 2);
}

// KPIs calculados desde la distribución real (no desde valores del plan)
$capitalCobrado      = round(array_sum($capitalDisplay), 2);
$interesCobrado      = round(array_sum($interesDisplay), 2);
$interesRestante     = max(0, round($interesAcordadoTotal - $interesCobrado, 2));

// KPI de adeudo total: calculado desde pagos reales para ser siempre coherente con saldo_actual
$totalAdeudadoKpi = max(0, (float)$prestamo->monto_entregado - $capitalCobrado) + $interesRestante + $interesPendiente;

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
    'Activo'        => 'badge-green',
    'Atrasado'      => 'badge-red',
    'Finalizado'    => 'badge-gray',
    'Retirado'      => 'badge-gray',
    'Refinanciado'  => 'badge-gray',
    default         => 'badge-yellow',
};

$estatusColor = match($prestamo->estatus) {
    'Activo'        => ['#dcfce7','#166534'],
    'Atrasado'      => ['#fee2e2','#991b1b'],
    'Finalizado'    => ['#f1f5f9','#475569'],
    'Retirado'      => ['#f1f5f9','#64748b'],
    'Refinanciado'  => ['#e0f2fe','#0369a1'],
    'Pendiente'     => ['#fef9c3','#854d0e'],
    default         => ['#f1f5f9','#64748b'],
};
[$estatusBg, $estatusTx] = $estatusColor;

$puesto         = auth()->user()->puesto;
$rolesActuales  = auth()->user()->getAllRoles();
$isPromoDetalle = in_array('promo', $rolesActuales);
$empDetalle     = auth()->user()->empleado;
$esMiPrestamo   = $empDetalle && $prestamo->promotor_id == $empDetalle->id;
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
        @if($prestamo->estatus === 'Pendiente')
        <button type="button" class="btn btn-sm" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5"
            onclick="document.getElementById('modalCancelarPrestamo').style.display='flex'">
            Cancelar préstamo
        </button>
        @endif
        @endif
        {{-- Promotor: acciones sobre sus propios préstamos Pendiente --}}
        @if($isPromoDetalle && $puesto !== 'admin' && $esMiPrestamo && $prestamo->estatus === 'Pendiente')
        <a href="{{ route('desembolsos.index') }}" class="btn btn-sm btn-primary">
            <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="margin-right:4px"><path d="M8 2v12M4 6l4-4 4 4"/></svg>
            Confirmar desembolso
        </a>
        <button type="button" class="btn btn-sm" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5"
            onclick="document.getElementById('modalCancelarPrestamo').style.display='flex'">
            Cancelar préstamo
        </button>
        @endif
    </div>
</div>

@push('styles')
<style>
.rd-grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:14px}
/* Ocultar columnas secundarias de la tabla de pagos en mobile */
@media(max-width:768px){
    .rd-grid-3{grid-template-columns:1fr 1fr!important}
    .pd-col-capital,.pd-col-interes,.pd-col-saldo,.pd-col-nota{display:none}
}
@media(max-width:480px){
    .rd-grid-3{grid-template-columns:1fr!important}
    .pd-col-puntualidad{display:none}
}
</style>
@endpush

@php
    $interesAcordado = round((float)$prestamo->monto - (float)$prestamo->monto_entregado, 2);
    // Próxima cuota: monto_cuota del siguiente pago pendiente (puede ser diferente a prestamo->cuota por carry-forward)
    $proximoPago  = $pagos->whereIn('estatus', ['Pendiente','Atrasado'])->sortBy('numero_pago')->first();
    $proximaCuota = $proximoPago ? (float)$proximoPago->monto_cuota : 0;
    $proximaFecha = $proximoPago?->fecha_programada?->format('d/m/Y') ?? null;
    // Balance restante calculado desde los registros de pago reales, igual que los sub-labels.
    // Evita que saldo_actual desincronizado muestre un número diferente a lo que desglosa abajo.
    $principalRestante = max(0, (float)$prestamo->monto_entregado - $capitalCobrado);
    $balanceRestante   = $principalRestante + $interesRestante + $interesPendiente;
@endphp

{{-- KPI cards — scroll horizontal en móvil --}}
<div style="overflow-x:auto;margin-bottom:16px;-webkit-overflow-scrolling:touch;scrollbar-width:none">
<div style="display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:12px;min-width:640px">

    {{-- 1. Estatus --}}
    <div class="card" style="padding:16px 18px">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:8px">Estatus</div>
        <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:13px;font-weight:700;background:{{ $estatusBg }};color:{{ $estatusTx }}">
            <span style="width:7px;height:7px;border-radius:50%;background:{{ $estatusTx }};display:inline-block"></span>
            {{ $prestamo->estatus }}
        </span>
        @if($prestamo->estatus === 'Pendiente')
        @php $diasRestantes = max(0, 5 - (int)$prestamo->created_at->diffInDays(now())); @endphp
        <div style="margin-top:7px;font-size:11px;color:{{ $diasRestantes <= 1 ? '#dc2626' : '#ca8a04' }};font-weight:600">
            ⏳ Auto-retira en {{ $diasRestantes }} día(s)
        </div>
        @elseif($prestamo->estatus === 'Finalizado')
        <div style="margin-top:7px;font-size:11px;color:#16a34a;font-weight:600">✓ Liquidado el {{ $fechaCompletado ? \Carbon\Carbon::parse($fechaCompletado)->format('d/m/Y') : '—' }}</div>
        @endif
    </div>

    {{-- 2. Balance restante — se recalcula en cada visita --}}
    <div class="card" style="padding:16px 18px;border-left:3px solid #dc2626">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:6px">Balance restante</div>
        <div style="font-size:22px;font-weight:800;font-family:monospace;color:#dc2626;line-height:1">${{ number_format($balanceRestante, 2, '.', ',') }}</div>
        <div style="font-size:10px;color:var(--text3);margin-top:5px;display:flex;flex-direction:column;gap:2px;font-family:monospace">
            <span>Principal: ${{ number_format((float)$prestamo->monto_entregado - $capitalCobrado, 2, '.', ',') }}</span>
            <span>Interés: ${{ number_format($interesRestante, 2, '.', ',') }}</span>
            @if($interesPendiente > 0)
            <span style="color:#c2410c">Mora: ${{ number_format($interesPendiente, 2, '.', ',') }}</span>
            @endif
        </div>
    </div>

    {{-- 3. Próxima cuota (monto real del siguiente pago, no la cuota base) --}}
    <div class="card" style="padding:16px 18px">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:6px">Próxima cuota</div>
        @if($proximaCuota > 0)
        <div style="font-size:22px;font-weight:800;font-family:monospace;color:#f59e0b;line-height:1">${{ number_format($proximaCuota, 2, '.', ',') }}</div>
        @if($proximaFecha)
        <div style="font-size:11px;color:var(--text3);margin-top:5px">Vence: {{ $proximaFecha }}</div>
        @endif
        @else
        <div style="font-size:16px;font-weight:600;color:var(--text3)">Sin cuotas pend.</div>
        @endif
    </div>

    {{-- 4. Total cobrado --}}
    <div class="card" style="padding:16px 18px">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:6px">Total cobrado</div>
        <div style="font-size:22px;font-weight:800;font-family:monospace;color:#16a34a;line-height:1">${{ number_format($totalCobrado, 2, '.', ',') }}</div>
        @if($ultimaFechaPago)
        <div style="font-size:11px;color:var(--text3);margin-top:5px">Último: {{ \Carbon\Carbon::parse($ultimaFechaPago)->format('d/m/Y') }}</div>
        @else
        <div style="font-size:11px;color:var(--text3);margin-top:5px">{{ $pagados->count() }} pagos realizados</div>
        @endif
    </div>

</div>{{-- fin grid --}}
</div>{{-- fin scroll wrapper --}}

@push('styles')
<style>
@media(max-width:768px){
    .rd-grid-3,.rd-grid-4{ grid-template-columns:repeat(2,1fr)!important; }
    .rd-grid-2{ grid-template-columns:1fr!important; }
    .rd-grid-2-inner{ grid-template-columns:1fr!important; }
}
@media(max-width:480px){
    .rd-grid-3,.rd-grid-4,.rd-grid-2{ grid-template-columns:1fr!important; }
}
</style>
@endpush

{{-- Actions panel: cobro extra, agendar, pago diferido, cambiar frecuencia --}}
@if(in_array($prestamo->estatus, ['Activo','Atrasado']) && in_array($puesto, ['admin','promo']))
<div class="card" style="padding:0;overflow:hidden;margin-bottom:16px">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Acciones del préstamo</div>
    <div style="padding:16px 18px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-start">

        {{-- Cobro Inmediato --}}
        <div style="display:flex;flex-direction:column;gap:4px;min-width:140px">
            <button onclick="document.getElementById('modal-cobro-extra').showModal()"
                style="padding:8px 16px;border-radius:8px;border:1.5px solid #2563eb;background:rgba(37,99,235,.07);color:#2563eb;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font);white-space:nowrap">
                + Cobro inmediato
            </button>
            <span style="font-size:11px;color:var(--text3);padding:0 2px">Registra un abono extra fuera del plan de pagos. Se aplica primero a mora, luego a capital.</span>
        </div>

        {{-- Agendar Cobro --}}
        <div style="display:flex;flex-direction:column;gap:4px;min-width:140px">
            <button onclick="document.getElementById('modal-agendar').showModal()"
                style="padding:8px 16px;border-radius:8px;border:1.5px solid #7c3aed;background:rgba(124,58,237,.07);color:#7c3aed;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font);white-space:nowrap">
                📅 Agendar cobro
            </button>
            <span style="font-size:11px;color:var(--text3);padding:0 2px">Programa un cobro acordado para una fecha futura específica.</span>
        </div>

        {{-- Pago Diferido (Payment Hold) --}}
        <div style="display:flex;flex-direction:column;gap:4px;min-width:140px">
            <form method="POST" action="{{ route('prestamos.paymentHold', $prestamo->id) }}" style="margin:0"
                onsubmit="return confirm('{{ ($prestamo->payment_hold ?? false) ? '¿Cancelar el pago diferido y restaurar el plan?' : '¿Establecer pago diferido? El siguiente cobro se combinará con el siguiente y se pagará doble.' }}')">
                @csrf
                @if($prestamo->payment_hold ?? false)
                <button type="submit"
                    style="padding:8px 16px;border-radius:8px;border:1.5px solid #fb923c;background:rgba(251,146,60,.12);color:#c2410c;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font);white-space:nowrap">
                    ↩ Cancelar pago diferido
                </button>
                @else
                <button type="submit"
                    style="padding:8px 16px;border-radius:8px;border:1.5px solid #f59e0b;background:rgba(245,158,11,.07);color:#92400e;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font);white-space:nowrap">
                    ⏸ Establecer pago diferido
                </button>
                @endif
            </form>
            @if($prestamo->payment_hold ?? false)
            <span style="font-size:11px;color:#c2410c;padding:0 2px">Pago diferido activo. Cancela para restaurar el plan original.</span>
            @else
            <span style="font-size:11px;color:var(--text3);padding:0 2px">Salta el próximo cobro; se combina con el siguiente (cuota doble).</span>
            @endif
        </div>

        {{-- Cambiar Frecuencia (admin only) --}}
        @if($puesto === 'admin')
        <div style="display:flex;flex-direction:column;gap:4px;min-width:140px">
            <button onclick="document.getElementById('modal-frecuencia').showModal()"
                style="padding:8px 16px;border-radius:8px;border:1.5px solid #d1d5db;background:#f9fafb;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font);white-space:nowrap">
                ⚙ Cambiar frecuencia
            </button>
            <span style="font-size:11px;color:var(--text3);padding:0 2px">Reprograma todos los pagos pendientes con una nueva frecuencia y fecha de inicio.</span>
        </div>
        @endif

        {{-- Refinanciar (admin only) --}}
        @if($puesto === 'admin')
        <div style="display:flex;flex-direction:column;gap:4px;min-width:140px">
            <button onclick="abrirModalRefinanciar()"
                style="padding:8px 16px;border-radius:8px;border:1.5px solid #0891b2;background:rgba(8,145,178,.07);color:#0e7490;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font);white-space:nowrap">
                ↺ Refinanciar
            </button>
            <span style="font-size:11px;color:var(--text3);padding:0 2px">Consolida la deuda actual en un nuevo préstamo con nuevos términos.</span>
        </div>
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
        <div class="rd-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px 20px;margin-bottom:12px">
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

<div class="rd-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    {{-- Credit details --}}
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Detalles del crédito</div>
        <div class="rd-grid-2-inner" style="padding:16px 18px;display:grid;grid-template-columns:1fr 1fr;gap:10px 20px">
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
                @php $principalPagado = max(0, round((float)$prestamo->monto_entregado - (float)$prestamo->saldo_actual + $interesAcordado - $interesRestante, 2)); @endphp
                <div style="margin-top:8px;padding:8px 12px;background:#f8fafc;border:1px solid var(--border);border-radius:8px;display:flex;gap:14px;flex-wrap:wrap">
                    {{-- Interés acordado sobre la deuda --}}
                    <span style="font-size:11px;font-family:monospace;color:#8b5cf6;display:flex;align-items:center;gap:4px">
                        <span style="width:7px;height:7px;border-radius:50%;background:#8b5cf6;display:inline-block"></span>
                        Interés s/deuda: <strong>${{ number_format($interesAcordado,2,'.',',') }}</strong>
                    </span>
                    {{-- Principal pagado --}}
                    <span style="font-size:11px;font-family:monospace;color:#2563eb;display:flex;align-items:center;gap:4px">
                        <span style="width:7px;height:7px;border-radius:50%;background:#2563eb;display:inline-block"></span>
                        Principal pagado: <strong>${{ number_format($capitalCobrado,2,'.',',') }}</strong>
                    </span>
                    {{-- Interés restante --}}
                    <span style="font-size:11px;font-family:monospace;color:#16a34a;display:flex;align-items:center;gap:4px">
                        <span style="width:7px;height:7px;border-radius:50%;background:#16a34a;display:inline-block"></span>
                        Interés restante: <strong>${{ number_format($interesRestante,2,'.',',') }}</strong>
                    </span>
                </div>
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
                <th class="pd-col-capital" style="text-align:right">Capital</th>
                <th class="pd-col-interes" style="text-align:right">Interés / Mora</th>
                <th class="pd-col-saldo" style="text-align:right" title="Deuda restante después de cada cobro">Saldo restante</th>
                <th>Estatus</th>
                <th class="pd-col-nota">Nota</th>
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
            @php
                // Distribución real: usar valores calculados para cobros; plan para pendientes
                $capShow = in_array($p->estatus, ['Pagado','Parcial'])
                    ? ($capitalDisplay[$p->id] ?? 0)
                    : $p->capital;
                $intShow = in_array($p->estatus, ['Pagado','Parcial'])
                    ? ($interesDisplay[$p->id] ?? 0)
                    : $p->interes;
            @endphp
            <td class="pd-col-capital" style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($capShow,2,'.',',') }}</td>
            <td class="pd-col-interes" style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($intShow,2,'.',',') }}</td>
            <td class="pd-col-saldo" style="text-align:right;font-family:monospace;font-size:12px">${{ number_format($saldoDisplay[$p->id] ?? $p->saldo_restante, 2, '.', ',') }}</td>
            <td><span style="display:inline-flex;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:600;background:{{ $statusColors[0] }};color:{{ $statusColors[1] }}">{{ $estatusLabel }}</span></td>
            <td class="pd-col-nota" style="font-size:12px;color:var(--text2);max-width:160px">{{ $notaDisplay }}</td>
            @if(in_array($puesto, ['admin','promo']) && in_array($prestamo->estatus, ['Activo','Atrasado']))
            <td>
                @if(!$esDimmed && in_array($p->estatus, ['Pendiente','Atrasado']))
                <div style="display:flex;gap:4px;flex-wrap:nowrap">
                    <button
                        onclick="abrirModalCuota({{ $p->id }}, {{ $p->numero_pago }}, {{ $p->monto_cuota }}, false)"
                        style="padding:4px 9px;border-radius:6px;border:1.5px solid #16a34a;background:rgba(22,163,74,.08);color:#16a34a;font-size:11px;font-weight:700;cursor:pointer;font-family:var(--font);white-space:nowrap">
                        Completo
                    </button>
                    <button
                        onclick="abrirModalCuota({{ $p->id }}, {{ $p->numero_pago }}, {{ $p->monto_cuota }}, true)"
                        style="padding:4px 9px;border-radius:6px;border:1.5px solid #f59e0b;background:rgba(245,158,11,.08);color:#b45309;font-size:11px;font-weight:700;cursor:pointer;font-family:var(--font);white-space:nowrap">
                        Parcial
                    </button>
                </div>
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

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- Modal: Refinanciar préstamo (admin only)                             --}}
{{-- Lógica: deuda actual traspasada sin nuevo interés +                  --}}
{{--          nuevo efectivo × (1 + rendimiento%) = total a retornar      --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
@if($puesto === 'admin' && in_array($prestamo->estatus, ['Activo','Atrasado']))
@php
    // Separar capital pendiente e interés pendiente del préstamo actual
    // usando la misma distribución interés-primero que ya calculó la vista arriba
    $refi_principal_pend = max(0, round((float)$prestamo->monto_entregado - $capitalCobrado, 2));
    $refi_interes_pend   = max(0, $interesRestante);                      // interés acordado no cobrado
    $refi_mora           = round((float)$prestamo->interes_acumulado, 2); // mora
    $refi_interes_total  = round($refi_interes_pend + $refi_mora, 2);
    $refi_deuda          = round($refi_principal_pend + $refi_interes_total, 2);
@endphp
{{-- Estilos del modal de refinanciamiento (responsive + fix cierre) --}}
@push('styles')
<style>
/* Fix cierre: display:flex SOLO cuando el dialog está abierto */
#modal-refinanciar { border:none;border-radius:16px;padding:0;box-shadow:0 12px 48px rgba(0,0,0,.22);max-width:590px;width:calc(100% - 24px);max-height:94svh;flex-direction:column;overflow:hidden }
#modal-refinanciar[open] { display:flex }
/* Backdrop */
#modal-refinanciar::backdrop { background:rgba(0,0,0,.45) }
/* Scroll area */
.refi-scroll { overflow-y:auto;flex:1;display:flex;flex-direction:column }
.refi-body   { padding:18px 22px;display:flex;flex-direction:column;gap:16px }
/* Grids responsive */
.refi-g3 { display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px }
.refi-g2 { display:grid;grid-template-columns:1fr 1fr;gap:12px }
.refi-desglose-cols { display:grid;grid-template-columns:1fr 1fr }
/* Tabla: ocultar Capital/Interés/Saldo en móvil pequeño */
@media(max-width:480px){
    #modal-refinanciar { width:100%;max-width:100%;border-radius:16px 16px 0 0;max-height:92svh;position:fixed;bottom:0;margin:0;left:0 }
    .refi-g3 { grid-template-columns:1fr 1fr }
    .refi-g2 { grid-template-columns:1fr }
    .refi-desglose-cols { grid-template-columns:1fr }
    .refi-desglose-cols > div:first-child { border-right:none!important;border-bottom:1px solid var(--border) }
    .refi-body { padding:14px 16px;gap:14px }
    .refi-col-cap,.refi-col-int,.refi-col-saldo { display:none }
}
@media(min-width:481px) and (max-width:600px){
    #modal-refinanciar { width:calc(100% - 16px) }
    .refi-g3 { grid-template-columns:1fr 1fr }
    .refi-g2 { grid-template-columns:1fr 1fr }
}
</style>
@endpush

<dialog id="modal-refinanciar">

    {{-- Header --}}
    <div style="padding:15px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
        <div>
            <div style="font-size:15px;font-weight:700;color:#0e7490">↺ Refinanciar préstamo #{{ $prestamo->id }}</div>
            <div style="font-size:12px;color:var(--text2);margin-top:1px">{{ $prestamo->cliente?->nombre }}</div>
        </div>
        <button type="button" id="refi-close-btn"
            style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:50%;font-size:20px;cursor:pointer;color:var(--text3);display:flex;align-items:center;justify-content:center;flex-shrink:0;line-height:1">&times;</button>
    </div>

    <form method="POST" action="{{ route('prestamos.refinanciar', $prestamo->id) }}"
          id="form-refinanciar" enctype="multipart/form-data"
          onsubmit="return confirmarRefinanciar()"
          class="refi-scroll">
        @csrf
        <input type="hidden" name="fecha_inicio" value="{{ now()->toDateString() }}">

        <div class="refi-body">

            {{-- 1. Deuda actual --}}
            <div style="background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:10px;padding:13px 16px">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#0369a1;margin-bottom:9px">Deuda actual (se traslada sin nuevo cargo)</div>
                <div class="refi-g3">
                    <div>
                        <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;margin-bottom:2px">Capital pendiente</div>
                        <div style="font-size:17px;font-weight:800;font-family:monospace;color:#0369a1">${{ number_format($refi_principal_pend,2,'.',',') }}</div>
                    </div>
                    <div>
                        <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;margin-bottom:2px">Interés@if($refi_mora>0)+mora@endif</div>
                        <div style="font-size:17px;font-weight:800;font-family:monospace;color:#8b5cf6">${{ number_format($refi_interes_total,2,'.',',') }}</div>
                    </div>
                    <div>
                        <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;margin-bottom:2px">Total deuda</div>
                        <div style="font-size:17px;font-weight:800;font-family:monospace;color:#0e7490">${{ number_format($refi_deuda,2,'.',',') }}</div>
                    </div>
                </div>
            </div>

            {{-- 2. Inputs --}}
            <div class="refi-g2">
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:block;margin-bottom:5px">Nuevo efectivo ($)</label>
                    <input type="number" name="nuevo_efectivo" id="refi-nuevo-efectivo"
                        step="0.01" min="0" value="0" required oninput="recalcRefi()"
                        style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:15px;font-family:monospace;outline:none;box-sizing:border-box">
                    <div style="font-size:10px;color:var(--text3);margin-top:3px">Dinero adicional a entregar (puede ser $0)</div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:block;margin-bottom:5px">% Rendimiento (sobre efectivo)</label>
                    <input type="number" name="rentabilidad" id="refi-rentabilidad"
                        step="0.1" min="0" value="30" required oninput="recalcRefi()"
                        style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:15px;font-family:monospace;outline:none;box-sizing:border-box">
                    <div style="font-size:10px;color:var(--text3);margin-top:3px">Solo aplica al nuevo dinero entregado</div>
                </div>
            </div>

            {{-- 3. Desglose --}}
            <div style="border:1.5px solid var(--border);border-radius:10px;overflow:hidden">
                <div style="padding:8px 14px;background:#f8fafc;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3)">Nuevo préstamo — desglose</div>
                <div class="refi-desglose-cols">
                    <div style="padding:12px 16px;border-right:1px solid var(--border)">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#0369a1;margin-bottom:5px">Capital total</div>
                        <div style="display:flex;align-items:center;gap:5px;font-family:monospace;font-size:12px;margin-bottom:3px;flex-wrap:wrap">
                            <span style="color:#64748b">${{ number_format($refi_principal_pend,2,'.',',') }}</span>
                            <span style="color:#94a3b8">+</span>
                            <span style="color:#64748b" id="refi-ef-disp2">$0.00</span>
                            <span style="color:#94a3b8">=</span>
                        </div>
                        <div style="font-size:20px;font-weight:800;font-family:monospace;color:#0369a1" id="refi-nuevo-principal">$0.00</div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px">capital viejo + nuevo efectivo</div>
                    </div>
                    <div style="padding:12px 16px">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#8b5cf6;margin-bottom:5px">Interés total</div>
                        <div style="display:flex;align-items:center;gap:5px;font-family:monospace;font-size:12px;margin-bottom:3px;flex-wrap:wrap">
                            <span style="color:#64748b">${{ number_format($refi_interes_total,2,'.',',') }}</span>
                            <span style="color:#94a3b8">+</span>
                            <span style="color:#64748b" id="refi-rend-disp">$0.00</span>
                            <span style="color:#94a3b8">=</span>
                        </div>
                        <div style="font-size:20px;font-weight:800;font-family:monospace;color:#8b5cf6" id="refi-nuevo-interes">$0.00</div>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px">interés viejo + rendimiento</div>
                    </div>
                </div>
                <div style="padding:10px 16px;background:#f0fdf4;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
                    <span style="font-size:11px;font-weight:700;color:#166534;text-transform:uppercase;letter-spacing:.06em">Total a retornar</span>
                    <span style="font-size:20px;font-weight:800;font-family:monospace;color:#15803d" id="refi-total">$0.00</span>
                </div>
            </div>

            {{-- 4. Plan --}}
            <div class="refi-g3">
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:block;margin-bottom:5px">Total de pagos</label>
                    <input type="number" name="num_pagos" id="refi-num-pagos"
                        step="1" min="1" value="{{ $prestamo->num_pagos }}" required oninput="recalcRefi()"
                        style="width:100%;padding:9px 10px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:monospace;outline:none;box-sizing:border-box">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:block;margin-bottom:5px">Frecuencia</label>
                    <select name="frecuencia" id="refi-frecuencia" required onchange="autoFechaRefi();recalcRefi()"
                        style="width:100%;padding:9px 10px;border:1px solid var(--border);border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;background:#fff;font-family:var(--font)">
                        @foreach(['Diario','Semanal','Quincenal','Mensual'] as $f)
                        <option value="{{ $f }}" {{ $prestamo->frecuencia === $f ? 'selected' : '' }}>{{ $f }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:block;margin-bottom:5px">Siguiente cobro</label>
                    <input type="date" name="fecha_primer_cobro" id="refi-fecha-primer" required
                        value="{{ now()->addDay()->toDateString() }}" oninput="recalcRefi()"
                        style="width:100%;padding:9px 10px;border:1px solid var(--border);border-radius:8px;font-size:12px;outline:none;box-sizing:border-box;font-family:var(--font)">
                </div>
            </div>

            {{-- 5. Cuota --}}
            <div style="display:flex;align-items:center;justify-content:space-between;background:#f0fdf4;border:1.5px solid #86efac;border-radius:8px;padding:10px 16px">
                <span style="font-size:12px;font-weight:700;color:#166534;text-transform:uppercase;letter-spacing:.06em">Cuota por pago</span>
                <span style="font-size:22px;font-weight:800;font-family:monospace;color:#15803d" id="refi-cuota-est">—</span>
            </div>

            {{-- 6. Tabla --}}
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:6px">Plan de pagos</div>
                <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;max-height:180px;overflow-y:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:12px">
                        <thead style="background:#f8fafc;position:sticky;top:0;z-index:1">
                            <tr>
                                <th style="padding:7px 8px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text3)">#</th>
                                <th style="padding:7px 8px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text3)">Fecha</th>
                                <th style="padding:7px 8px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text3)">Cuota</th>
                                <th class="refi-col-cap" style="padding:7px 8px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;color:#16a34a">Capital</th>
                                <th class="refi-col-int" style="padding:7px 8px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;color:#8b5cf6">Interés</th>
                                <th class="refi-col-saldo" style="padding:7px 8px;text-align:right;font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text3)">Saldo</th>
                            </tr>
                        </thead>
                        <tbody id="refi-tabla-body">
                            <tr><td colspan="6" style="padding:18px;text-align:center;color:var(--text3);font-size:12px">Ingresa los datos para generar el plan</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 7. Pagaré --}}
            <div style="border-top:1px solid var(--border);padding-top:14px">
                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:block;margin-bottom:6px">
                    Pagaré firmado <span style="color:#ef4444">*</span>
                    <span style="font-size:10px;font-weight:400;text-transform:none;color:#16a34a"> — INE y domicilio ya registrados</span>
                </label>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <label style="flex:1;min-width:130px;display:flex;align-items:center;gap:8px;padding:9px 14px;background:#f9fafb;border:1.5px dashed var(--border);border-radius:8px;cursor:pointer;font-size:13px;color:var(--text2)" onmouseover="this.style.borderColor='#0891b2'" onmouseout="this.style.borderColor='var(--border)'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Subir archivo
                        <input type="file" name="doc_pagare" id="refi-pagare-file" required accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="refiPagareSeleccionado(this)">
                    </label>
                    <label style="flex:1;min-width:130px;display:flex;align-items:center;gap:8px;padding:9px 14px;background:#f9fafb;border:1.5px dashed var(--border);border-radius:8px;cursor:pointer;font-size:13px;color:var(--text2)" onmouseover="this.style.borderColor='#0891b2'" onmouseout="this.style.borderColor='var(--border)'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        Tomar foto
                        <input type="file" accept="image/*" capture="environment" style="display:none" onchange="refiPagareSeleccionado(this)">
                    </label>
                </div>
                <div id="refi-pagare-txt" style="display:none;margin-top:6px;font-size:11px;color:#166534;padding:5px 10px;background:rgba(22,163,74,.07);border-radius:6px"></div>
                <div style="font-size:10px;color:var(--text3);margin-top:4px">JPG, PNG o PDF — máx. 10 MB</div>
            </div>

        </div>{{-- /refi-body --}}

        <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0;background:var(--card)">
            <button type="button" id="refi-cancel-btn"
                style="padding:9px 18px;border-radius:8px;border:1px solid var(--border);background:#f9fafb;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                Cancelar
            </button>
            <button type="submit"
                style="padding:9px 18px;border-radius:8px;border:none;background:#0891b2;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                ↺ Confirmar
            </button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
// Valores del préstamo actual (capital e interés separados)
const REFI_CAP_VIEJO  = {{ $refi_principal_pend }};
const REFI_INT_VIEJO  = {{ $refi_interes_total }};
const REFI_DIAS       = { Diario:1, Semanal:7, Quincenal:14, Mensual:30 };

// Botones de cierre (por ID, no onclick inline — más robusto)
document.addEventListener('DOMContentLoaded', function() {
    const dlg = document.getElementById('modal-refinanciar');
    document.getElementById('refi-close-btn')?.addEventListener('click',  () => dlg.close());
    document.getElementById('refi-cancel-btn')?.addEventListener('click', () => dlg.close());
    // Cerrar al hacer clic en el backdrop
    dlg?.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
    @if(session('abrir_refinanciar'))
    // Redirigido desde "crear préstamo" con préstamo activo → abrir refinanciamiento directamente
    if (dlg) abrirModalRefinanciar();
    @endif
});

function abrirModalRefinanciar() {
    recalcRefi();
    document.getElementById('modal-refinanciar').showModal();
}

function autoFechaRefi() {
    const freq = document.getElementById('refi-frecuencia')?.value || 'Diario';
    const dias = REFI_DIAS[freq] || 1;
    const d    = new Date('{{ now()->toDateString() }}T12:00:00');
    if (freq === 'Mensual') d.setMonth(d.getMonth() + 1);
    else d.setDate(d.getDate() + dias);
    const inp = document.getElementById('refi-fecha-primer');
    if (inp) { inp.value = d.toISOString().split('T')[0]; recalcRefi(); }
}

function refiPagareSeleccionado(input) {
    const realInput = document.getElementById('refi-pagare-file');
    if (input !== realInput && input.files[0]) {
        try { const dt = new DataTransfer(); dt.items.add(input.files[0]); realInput.files = dt.files; } catch(e){}
    }
    const file = (realInput.files && realInput.files[0]) || input.files[0];
    const txt  = document.getElementById('refi-pagare-txt');
    if (file && txt) {
        txt.textContent = '✓ ' + (file.name.length > 45 ? '…'+file.name.slice(-42) : file.name)
            + ' · ' + (file.size/1048576).toFixed(1) + ' MB';
        txt.style.display = '';
    }
}

function recalcRefi() {
    const fmt  = n => '$' + Math.abs(n).toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});
    const fmtD = s => { if(!s) return '—'; const [y,m,d]=s.split('-'); return d+'/'+m+'/'+y; };
    const set  = (id,v) => { const el=document.getElementById(id); if(el) el.textContent=v; };

    const efectivo = Math.round((parseFloat(document.getElementById('refi-nuevo-efectivo')?.value)||0)*100)/100;
    const rentPct  = parseFloat(document.getElementById('refi-rentabilidad')?.value)||0;

    // Rendimiento SOLO sobre el nuevo efectivo
    const rendimiento   = Math.round(efectivo * rentPct / 100 * 100) / 100;

    // Nuevo préstamo: capital = cap_viejo + efectivo | interés = int_viejo + rendimiento
    const nuevoPrincipal = Math.round((REFI_CAP_VIEJO + efectivo) * 100) / 100;
    const nuevoInteres   = Math.round((REFI_INT_VIEJO + rendimiento) * 100) / 100;
    const total          = Math.round((nuevoPrincipal + nuevoInteres) * 100) / 100;

    const numPagos   = parseInt(document.getElementById('refi-num-pagos')?.value)||1;
    const freq       = document.getElementById('refi-frecuencia')?.value||'Diario';
    const fechaPrimer= document.getElementById('refi-fecha-primer')?.value;
    const dias       = REFI_DIAS[freq]||1;

    // Actualizar display
    set('refi-ef-disp2',      fmt(efectivo));
    set('refi-nuevo-principal', fmt(nuevoPrincipal));
    set('refi-rend-disp',     fmt(rendimiento));
    set('refi-nuevo-interes', fmt(nuevoInteres));
    set('refi-total',         fmt(total));

    const cuotaBase  = numPagos > 1 ? Math.round(total/numPagos/5)*5 : total;
    const ultimoPago = numPagos > 1 ? Math.round((total-cuotaBase*(numPagos-1))*100)/100 : total;
    set('refi-cuota-est', fmt(cuotaBase));

    // Tabla: interés-primero sobre el interés total (nuevoInteres)
    const tbody = document.getElementById('refi-tabla-body');
    if (!tbody || !fechaPrimer || total <= 0) return;

    let interesRest = nuevoInteres;
    let saldo       = nuevoPrincipal;
    let rows        = '';

    for (let i = 1; i <= numPagos; i++) {
        let d = new Date(fechaPrimer + 'T12:00:00');
        if (freq === 'Mensual') d.setMonth(d.getMonth()+(i-1));
        else d.setDate(d.getDate()+dias*(i-1));

        const cuota  = (i===numPagos) ? Math.round(ultimoPago*100)/100 : cuotaBase;
        const int_   = Math.min(cuota, Math.max(0, interesRest));
        const cap_   = Math.round((cuota-int_)*100)/100;
        interesRest  = Math.max(0, Math.round((interesRest-int_)*100)/100);
        saldo        = Math.max(0, Math.round((saldo-cap_)*100)/100);

        rows += `<tr style="border-bottom:1px solid #f3f4f6">
            <td style="padding:6px 8px;font-family:monospace;color:var(--text3);font-size:11px">${i}</td>
            <td style="padding:6px 8px;font-size:11px">${fmtD(d.toISOString().split('T')[0])}</td>
            <td style="padding:6px 8px;text-align:right;font-family:monospace;font-weight:600;font-size:12px">${fmt(cuota)}</td>
            <td class="refi-col-cap" style="padding:6px 8px;text-align:right;font-family:monospace;color:#16a34a;font-size:11px">${fmt(cap_)}</td>
            <td class="refi-col-int" style="padding:6px 8px;text-align:right;font-family:monospace;color:#8b5cf6;font-size:11px">${fmt(int_)}</td>
            <td class="refi-col-saldo" style="padding:6px 8px;text-align:right;font-family:monospace;color:var(--text3);font-size:11px">${fmt(saldo)}</td>
        </tr>`;
    }
    tbody.innerHTML = rows;
}

function confirmarRefinanciar() {
    const efectivo      = parseFloat(document.getElementById('refi-nuevo-efectivo')?.value)||0;
    const rentPct       = parseFloat(document.getElementById('refi-rentabilidad')?.value)||0;
    const rendimiento   = Math.round(efectivo*rentPct/100*100)/100;
    const nuevoPrincipal= Math.round((REFI_CAP_VIEJO+efectivo)*100)/100;
    const nuevoInteres  = Math.round((REFI_INT_VIEJO+rendimiento)*100)/100;
    const total         = nuevoPrincipal + nuevoInteres;
    const numPagos      = parseInt(document.getElementById('refi-num-pagos')?.value)||1;
    const cuota         = numPagos>1 ? Math.round(total/numPagos/5)*5 : total;
    const fmt           = n=>'$'+n.toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});
    return confirm(
        '¿Confirmar refinanciamiento?\n\n'+
        '  Préstamo #{{ $prestamo->id }} → REFINANCIADO\n\n'+
        '  Capital nuevo:  '+fmt(nuevoPrincipal)+
            ' ('+fmt(REFI_CAP_VIEJO)+(efectivo>0?' + '+fmt(efectivo):'')+')'+'\n'+
        '  Interés nuevo:  '+fmt(nuevoInteres)+
            ' ('+fmt(REFI_INT_VIEJO)+(rendimiento>0?' + '+fmt(rendimiento)+' ('+rentPct+'%)':'')+')'+'\n'+
        '  Total:          '+fmt(total)+'\n'+
        '  Cuota aprox.:   '+fmt(cuota)+' × '+numPagos+'\n\n'+
        'Esta acción no se puede deshacer.'
    );
}
</script>
@endpush
@endif

{{-- Modal: Cobrar cuota específica --}}
<dialog id="modal-pagar-cuota" style="border:none;border-radius:16px;padding:0;box-shadow:0 12px 48px rgba(0,0,0,.2);max-width:420px;width:100%">
    <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
            <div style="font-size:15px;font-weight:700" id="mcq-titulo">Cobrar cuota #—</div>
            <div style="font-size:12px;color:var(--text2);margin-top:2px" id="mcq-subtitulo">Pago completo</div>
        </div>
        <button onclick="document.getElementById('modal-pagar-cuota').close()"
            style="background:#f1f5f9;border:none;width:28px;height:28px;border-radius:50%;font-size:17px;cursor:pointer;color:var(--text3);display:flex;align-items:center;justify-content:center">&times;</button>
    </div>

    <form method="POST" action="{{ route('prestamos.pagarCuota', $prestamo->id) }}" onsubmit="return submitOnce(this)">
        @csrf
        <input type="hidden" name="pago_id"      id="mcq-pago-id">
        <input type="hidden" name="carry_forward" id="mcq-carry"  value="0">

        <div style="padding:20px 24px;display:grid;gap:14px">

            {{-- Cuota info card --}}
            <div style="padding:12px 16px;background:#f0fdf4;border:1px solid #86efac;border-radius:10px;display:flex;align-items:center;justify-content:space-between">
                <div>
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#166534">Cuota del plan</div>
                    <div style="font-size:22px;font-weight:800;font-family:monospace;color:#15803d;line-height:1.2" id="mcq-cuota-display">$—</div>
                </div>
                {{-- Mode toggle --}}
                <div style="display:flex;border:1.5px solid var(--border);border-radius:8px;overflow:hidden;font-size:12px;font-weight:700">
                    <button type="button" id="mcq-btn-completo"
                        onclick="setModo(false)"
                        style="padding:6px 14px;border:none;background:#16a34a;color:#fff;cursor:pointer;font-family:var(--font);font-size:12px;font-weight:700">
                        Completo
                    </button>
                    <button type="button" id="mcq-btn-parcial"
                        onclick="setModo(true)"
                        style="padding:6px 14px;border:none;background:#f9fafb;color:var(--text2);cursor:pointer;font-family:var(--font);font-size:12px;font-weight:700">
                        Parcial
                    </button>
                </div>
            </div>

            @if((float)($prestamo->interes_acumulado ?? 0) > 0)
            <div style="padding:8px 12px;background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;font-size:11px;color:#92400e;font-weight:600">
                ⚠ Mora pendiente: ${{ number_format($prestamo->interes_acumulado,2,'.',',') }} — se aplicará primero al monto ingresado.
            </div>
            @endif

            {{-- Monto --}}
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Monto a cobrar *</label>
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="font-size:14px;color:var(--text2);font-weight:600">$</span>
                    <input type="number" name="monto" id="mcq-monto" step="0.01" min="0.01" required placeholder="0.00"
                        oninput="calcDiferencia()"
                        style="flex:1;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:16px;font-family:monospace;outline:none;transition:border-color .15s">
                </div>
            </div>

            {{-- Diferencia (solo modo parcial) --}}
            <div id="mcq-diferencia-box" style="display:none;padding:12px 14px;background:#fff7ed;border:1.5px solid #fed7aa;border-radius:10px">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#c2410c;margin-bottom:6px">Saldo que pasa al siguiente pago</div>
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <div style="font-size:20px;font-weight:800;font-family:monospace;color:#ea580c" id="mcq-diferencia-val">$0.00</div>
                    <div style="font-size:11px;color:#9a3412;text-align:right" id="mcq-diferencia-desc">— cuota #—</div>
                </div>
                <div style="font-size:11px;color:#9a3412;margin-top:5px">Se sumará automáticamente al próximo pago pendiente.</div>
            </div>

            {{-- Nota --}}
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">Nota (opcional)</label>
                <input type="text" name="nota" maxlength="255" placeholder="Ej. Pago en efectivo"
                    style="width:100%;padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;outline:none;box-sizing:border-box">
            </div>
        </div>

        <div style="padding:14px 24px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end;background:#f9fafb;border-radius:0 0 16px 16px">
            <button type="button" onclick="document.getElementById('modal-pagar-cuota').close()"
                style="padding:8px 18px;border-radius:8px;border:1px solid var(--border);background:#fff;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                Cancelar
            </button>
            <button type="submit" id="mcq-btn-submit"
                style="padding:8px 20px;border-radius:8px;border:none;background:#16a34a;color:#fff;font-size:13px;font-weight:700;cursor:pointer;font-family:var(--font)">
                Registrar pago
            </button>
        </div>
    </form>
</dialog>

@php
$pagosPendientesJs = $pagos
    ->whereIn('estatus', ['Pendiente','Atrasado'])
    ->sortBy('numero_pago')
    ->values()
    ->map(fn($p) => ['id' => $p->id, 'num' => $p->numero_pago, 'cuota' => (float)$p->monto_cuota])
    ->values()
    ->toArray();
@endphp

{{-- Close dialogs on backdrop click; prevent double submit --}}
<script>
const PAGOS_PENDIENTES = {!! json_encode($pagosPendientesJs) !!};

['modal-cobro-extra','modal-agendar','modal-frecuencia','modal-pagar-cuota'].forEach(function(id){
    var el = document.getElementById(id);
    if(!el) return;
    el.addEventListener('click', function(e){ if(e.target === el) el.close(); });
});

function submitOnce(form) {
    var btn = form.querySelector('button[type="submit"]');
    if(btn && btn.disabled) return false;
    if(btn) { btn.disabled = true; btn.textContent = 'Guardando...'; }
    return true;
}

let _mcqCuota   = 0;
let _mcqPagoId  = 0;
let _mcqNumPago = 0;
let _mcqParcial = false;

function abrirModalCuota(pagoId, numeroPago, montoCuota, parcial) {
    _mcqPagoId  = pagoId;
    _mcqNumPago = numeroPago;
    _mcqCuota   = parseFloat(montoCuota);

    document.getElementById('mcq-pago-id').value = pagoId;
    document.getElementById('mcq-titulo').textContent = 'Cobrar cuota #' + numeroPago;
    document.getElementById('mcq-cuota-display').textContent = '$' + _mcqCuota.toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});

    // Reset submit button
    const btn = document.getElementById('mcq-btn-submit');
    btn.disabled = false;
    btn.textContent = 'Registrar pago';

    setModo(!!parcial);
    document.getElementById('modal-pagar-cuota').showModal();
    setTimeout(() => document.getElementById('mcq-monto').select(), 60);
}

function setModo(parcial) {
    _mcqParcial = parcial;
    document.getElementById('mcq-carry').value = parcial ? '1' : '0';

    const btnC = document.getElementById('mcq-btn-completo');
    const btnP = document.getElementById('mcq-btn-parcial');
    btnC.style.background = parcial ? '#f9fafb' : '#16a34a';
    btnC.style.color       = parcial ? 'var(--text2)' : '#fff';
    btnP.style.background  = parcial ? '#f59e0b' : '#f9fafb';
    btnP.style.color        = parcial ? '#fff' : 'var(--text2)';

    document.getElementById('mcq-subtitulo').textContent = parcial ? 'Pago parcial — diferencia al siguiente cobro' : 'Pago completo';

    if (parcial) {
        document.getElementById('mcq-monto').value = '';
        document.getElementById('mcq-monto').removeAttribute('max');
    } else {
        document.getElementById('mcq-monto').value = _mcqCuota.toFixed(2);
        document.getElementById('mcq-monto').removeAttribute('max');
    }
    calcDiferencia();
}

function calcDiferencia() {
    const monto    = parseFloat(document.getElementById('mcq-monto').value) || 0;
    const diffBox  = document.getElementById('mcq-diferencia-box');
    const diffVal  = document.getElementById('mcq-diferencia-val');
    const diffDesc = document.getElementById('mcq-diferencia-desc');

    if (!_mcqParcial) { diffBox.style.display = 'none'; return; }

    const diferencia = Math.max(0, Math.round((_mcqCuota - monto) * 100) / 100);
    diffBox.style.display = '';
    diffVal.textContent = '$' + diferencia.toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});

    // Find next pending pago after current
    const idx  = PAGOS_PENDIENTES.findIndex(p => p.id === _mcqPagoId);
    const next = PAGOS_PENDIENTES[idx + 1] || null;
    diffDesc.textContent = next ? '→ cuota #' + next.num : '→ no hay siguiente pago';

    // Color feedback
    diffBox.style.borderColor = diferencia > 0 ? '#fed7aa' : '#86efac';
    diffBox.style.background  = diferencia > 0 ? '#fff7ed' : '#f0fdf4';
    document.getElementById('mcq-diferencia-val').style.color = diferencia > 0 ? '#ea580c' : '#16a34a';
    document.getElementById('mcq-diferencia-desc').style.color= diferencia > 0 ? '#9a3412' : '#166534';
}
</script>

@endif

{{-- ── Archivos adjuntos ───────────────────────────────────────────── --}}
<div class="card" style="padding:0;overflow:hidden;margin-top:20px">
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:8px">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:var(--accent)"><path d="M13.5 9.5v3a1.5 1.5 0 0 1-1.5 1.5H4A1.5 1.5 0 0 1 2.5 12.5v-3"/><polyline points="5 6 8 3 11 6"/><line x1="8" y1="3" x2="8" y2="10"/></svg>
            <span style="font-size:13px;font-weight:600">Archivos adjuntos</span>
            <span style="font-size:11px;color:var(--text3);margin-left:2px">{{ $archivos->count() }} archivo(s)</span>
        </div>
        <button type="button" onclick="document.getElementById('panel-subir-archivo').classList.toggle('hidden-panel')"
            style="padding:5px 14px;border-radius:8px;border:1.5px solid var(--accent);background:rgba(59,130,246,.07);color:var(--accent);font-size:12px;font-weight:600;cursor:pointer;font-family:var(--font)">
            + Subir archivo
        </button>
    </div>

    {{-- Panel de subida --}}
    <div id="panel-subir-archivo" class="hidden-panel" style="border-bottom:1px solid var(--border);padding:16px 18px;background:#f8fafc">
        <form method="POST" action="{{ route('prestamos.archivos.subir', $prestamo->id) }}" enctype="multipart/form-data"
              onsubmit="return submitOnceArchivo(this)" id="form-subir-archivo">
            @csrf
            <div style="display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap">
                <div style="flex:1;min-width:220px">
                    <label style="font-size:11px;font-weight:600;color:var(--text2);display:block;margin-bottom:5px">
                        Selecciona un archivo (PDF, JPG, JPEG, PNG — máx. 10 MB)
                    </label>
                    <input type="file" name="archivo" id="input-archivo" accept=".pdf,.jpg,.jpeg,.png"
                           required onchange="previewArchivo(this)"
                           style="width:100%;padding:7px 10px;border:1.5px dashed var(--border);border-radius:8px;font-size:13px;background:#fff;cursor:pointer;box-sizing:border-box">
                </div>
                <button type="submit" id="btn-subir-archivo"
                    style="padding:8px 18px;border-radius:8px;border:none;background:#2563eb;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font);white-space:nowrap">
                    Subir
                </button>
                <button type="button" onclick="document.getElementById('panel-subir-archivo').classList.add('hidden-panel')"
                    style="padding:8px 14px;border-radius:8px;border:1px solid var(--border);background:#f9fafb;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                    Cancelar
                </button>
            </div>
            <div id="archivo-preview" style="display:none;margin-top:10px"></div>
        </form>
        @error('archivo')
        <div style="margin-top:8px;padding:8px 12px;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;font-size:12px;color:#dc2626">{{ $message }}</div>
        @enderror
    </div>

    {{-- Lista de archivos --}}
    @if($archivos->isEmpty())
    <div style="padding:28px;text-align:center;color:var(--text3);font-size:13px">
        No hay archivos adjuntos. Sube PDFs o imágenes relacionadas con este préstamo.
    </div>
    @else
    <div style="padding:14px 18px;display:flex;flex-direction:column;gap:10px">
        @foreach($archivos as $arch)
        <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:#f8fafc;border:1px solid var(--border);border-radius:10px;flex-wrap:wrap">

            {{-- Icono según tipo --}}
            <div style="width:38px;height:38px;border-radius:8px;background:{{ $arch->esImagen() ? '#dbeafe' : '#fee2e2' }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                @if($arch->esImagen())
                <svg viewBox="0 0 16 16" fill="none" stroke="#2563eb" stroke-width="1.5" stroke-linecap="round" style="width:18px;height:18px"><rect x="1.5" y="1.5" width="13" height="13" rx="1.5"/><path d="M1.5 10.5l3-3 2.5 2.5L10 7l4.5 4.5"/><circle cx="5" cy="5" r="1"/></svg>
                @else
                <svg viewBox="0 0 16 16" fill="none" stroke="#dc2626" stroke-width="1.5" stroke-linecap="round" style="width:18px;height:18px"><path d="M9.5 1.5H4A1.5 1.5 0 0 0 2.5 3v10A1.5 1.5 0 0 0 4 14.5h8A1.5 1.5 0 0 0 13.5 13V5.5L9.5 1.5z"/><polyline points="9.5 1.5 9.5 5.5 13.5 5.5"/></svg>
                @endif
            </div>

            {{-- Info --}}
            <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $arch->nombre_original }}</div>
                <div style="font-size:11px;color:var(--text3);margin-top:2px">
                    {{ strtoupper($arch->tipo_archivo) }} · {{ $arch->tamanoFormateado() }}
                    @if($arch->subidoPor)
                    · subido por <strong>{{ $arch->subidoPor->usuario ?? '—' }}</strong>
                    @endif
                    · {{ $arch->created_at->format('d/m/Y H:i') }}
                </div>
            </div>

            {{-- Acciones --}}
            <div style="display:flex;gap:6px;flex-shrink:0">
                @if($arch->esImagen())
                <button type="button"
                    onclick="verImagen('{{ asset($arch->ruta) }}', '{{ $arch->nombre_original }}')"
                    style="padding:5px 12px;border-radius:7px;border:1px solid #d1d5db;background:#fff;color:var(--text2);font-size:11px;font-weight:600;cursor:pointer;font-family:var(--font)">
                    Ver
                </button>
                @else
                <a href="{{ asset($arch->ruta) }}" target="_blank"
                    style="padding:5px 12px;border-radius:7px;border:1px solid #d1d5db;background:#fff;color:var(--text2);font-size:11px;font-weight:600;cursor:pointer;font-family:var(--font);text-decoration:none;display:inline-flex;align-items:center">
                    Abrir
                </a>
                @endif
                <a href="{{ asset($arch->ruta) }}" download="{{ $arch->nombre_original }}"
                    style="padding:5px 12px;border-radius:7px;border:1px solid #d1d5db;background:#fff;color:var(--text2);font-size:11px;font-weight:600;cursor:pointer;font-family:var(--font);text-decoration:none;display:inline-flex;align-items:center">
                    Descargar
                </a>
                @if(in_array($puesto, ['admin','promo']))
                <form method="POST" action="{{ route('prestamos.archivos.eliminar', [$prestamo->id, $arch->id]) }}" style="margin:0"
                      onsubmit="return confirm('¿Eliminar este archivo permanentemente?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        style="padding:5px 12px;border-radius:7px;border:1px solid #fca5a5;background:#fff;color:#dc2626;font-size:11px;font-weight:600;cursor:pointer;font-family:var(--font)">
                        Eliminar
                    </button>
                </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Modal visor de imagen --}}
<div id="modal-visor-imagen" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.78);z-index:600;align-items:center;justify-content:center;padding:20px"
     onclick="if(event.target===this)cerrarVisor()">
    <div style="max-width:90vw;max-height:90vh;position:relative;display:flex;flex-direction:column;align-items:center;gap:10px">
        <div style="display:flex;align-items:center;justify-content:space-between;width:100%;padding:0 4px">
            <span id="visor-nombre" style="font-size:13px;color:#e2e8f0;font-weight:600"></span>
            <button onclick="cerrarVisor()" style="background:rgba(255,255,255,.15);border:none;color:#fff;font-size:20px;cursor:pointer;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center">&times;</button>
        </div>
        <img id="visor-img" src="" alt="" style="max-width:100%;max-height:80vh;border-radius:10px;object-fit:contain;box-shadow:0 8px 40px rgba(0,0,0,.5)">
    </div>
</div>

@push('styles')
<style>
.hidden-panel { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
function verImagen(src, nombre) {
    document.getElementById('visor-img').src = src;
    document.getElementById('visor-nombre').textContent = nombre;
    document.getElementById('modal-visor-imagen').style.display = 'flex';
}
function cerrarVisor() {
    document.getElementById('modal-visor-imagen').style.display = 'none';
    document.getElementById('visor-img').src = '';
}
function previewArchivo(input) {
    const prev = document.getElementById('archivo-preview');
    if (!input.files.length) { prev.style.display = 'none'; return; }
    const file = input.files[0];
    const isImg = file.type.startsWith('image/');
    if (isImg) {
        const reader = new FileReader();
        reader.onload = e => {
            prev.innerHTML = '<img src="' + e.target.result + '" style="max-height:160px;border-radius:8px;border:1px solid var(--border)">';
            prev.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        prev.innerHTML = '<div style="padding:8px 12px;background:#fff;border:1px solid var(--border);border-radius:8px;font-size:12px;color:var(--text2)">📄 ' + file.name + ' (' + (file.size > 1048576 ? (file.size/1048576).toFixed(1)+' MB' : Math.round(file.size/1024)+' KB') + ')</div>';
        prev.style.display = 'block';
    }
}
function submitOnceArchivo(form) {
    const btn = document.getElementById('btn-subir-archivo');
    if (btn.disabled) return false;
    btn.disabled = true;
    btn.textContent = 'Subiendo...';
    return true;
}
</script>
@endpush

{{-- ── Actividad / línea de tiempo ────────────────────────────────── --}}
<div class="card" style="padding:0;overflow:hidden;margin-top:20px">
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:14px;height:14px;color:var(--accent)"><circle cx="8" cy="8" r="6"/><path d="M8 5v3l2 2"/></svg>
        <span style="font-size:13px;font-weight:600">Actividad</span>
        <span style="font-size:11px;color:var(--text3);margin-left:4px">{{ $actividad->count() }} eventos</span>
    </div>

    @if($actividad->isEmpty())
    <div style="padding:32px;text-align:center;color:var(--text3);font-size:13px">Sin actividad registrada aún.</div>
    @else
    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:0">
        @foreach($actividad as $i => $ev)
        @php
            $estilos = \App\Models\PrestamoActividad::$estilos;
            $est     = $estilos[$ev->tipo] ?? ['color'=>'#6b7280','icon'=>'·','label'=>$ev->tipo];
            $esUltimo= $i === $actividad->count() - 1;
        @endphp
        <div style="display:flex;gap:14px;{{ !$esUltimo ? 'padding-bottom:18px' : '' }}">
            {{-- Línea + icono --}}
            <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0">
                <div style="width:30px;height:30px;border-radius:50%;background:{{ $est['color'] }}18;border:2px solid {{ $est['color'] }};display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;line-height:1">
                    {{ $est['icon'] }}
                </div>
                @if(!$esUltimo)
                <div style="width:2px;flex:1;background:var(--border);margin-top:4px;min-height:16px"></div>
                @endif
            </div>
            {{-- Contenido --}}
            <div style="flex:1;min-width:0;padding-top:4px">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:{{ $est['color'] }};margin-bottom:3px">
                    {{ $est['label'] }}
                </div>
                <div style="font-size:13px;color:var(--text);line-height:1.5">{{ $ev->descripcion }}</div>
                <div style="font-size:11px;color:var(--text3);margin-top:4px;display:flex;gap:10px;flex-wrap:wrap">
                    <span>{{ $ev->created_at->format('d/m/Y H:i') }}</span>
                    @if($ev->user)
                    <span style="color:var(--text2)">· {{ $ev->user->nombre ?? $ev->user->usuario }}</span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- ── Modal: Cancelar préstamo ────────────────────────────── --}}
@if($prestamo->estatus === 'Pendiente')
<div id="modalCancelarPrestamo"
     style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);z-index:500;align-items:center;justify-content:center;padding:20px"
     onclick="if(event.target===this)this.style.display='none'">

    <div style="background:#fff;border-radius:16px;width:100%;max-width:460px;box-shadow:0 24px 64px rgba(0,0,0,.22);overflow:hidden">

        {{-- Cabecera roja --}}
        <div style="background:linear-gradient(135deg,#dc2626,#b91c1c);padding:24px 28px;display:flex;align-items:flex-start;gap:14px">
            <div style="width:44px;height:44px;background:rgba(255,255,255,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" style="width:22px;height:22px">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div>
                <div style="font-size:17px;font-weight:800;color:#fff;letter-spacing:-.02em">Acción irreversible</div>
                <div style="font-size:13px;color:rgba(255,255,255,.8);margin-top:3px">Cancelar préstamo #{{ $prestamo->id }}</div>
            </div>
        </div>

        {{-- Cuerpo --}}
        <div style="padding:24px 28px">

            {{-- Cliente destacado --}}
            <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;margin-bottom:18px">
                <div style="width:36px;height:36px;border-radius:50%;background:#dc2626;color:#fff;font-size:14px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    {{ strtoupper(substr($prestamo->cliente?->nombre ?? 'C', 0, 1)) }}
                </div>
                <div>
                    <div style="font-size:13px;font-weight:700;color:#991b1b">{{ $prestamo->cliente?->nombre ?? '—' }}</div>
                    <div style="font-size:11px;color:#b91c1c">Préstamo de ${{ number_format($prestamo->monto_entregado, 0, '.', ',') }} · {{ $prestamo->num_pagos }} pagos {{ strtolower($prestamo->frecuencia) }}s</div>
                </div>
            </div>

            {{-- Consecuencias --}}
            <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:10px">Al cancelar este préstamo:</div>
            <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:18px">
                <div style="display:flex;gap:10px;align-items:flex-start">
                    <span style="min-width:18px;height:18px;border-radius:50%;background:#fee2e2;color:#dc2626;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px">!</span>
                    <p style="margin:0;font-size:13px;color:#374151;line-height:1.5">El préstamo quedará como <strong>Retirado</strong> y no podrá reactivarse.</p>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start">
                    <span style="min-width:18px;height:18px;border-radius:50%;background:#fee2e2;color:#dc2626;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px">!</span>
                    <p style="margin:0;font-size:13px;color:#374151;line-height:1.5">El historial del cliente registrará un préstamo cancelado, lo que <strong>puede afectar su acceso a futuros créditos</strong>.</p>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start">
                    <span style="min-width:18px;height:18px;border-radius:50%;background:#fee2e2;color:#dc2626;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px">!</span>
                    <p style="margin:0;font-size:13px;color:#374151;line-height:1.5">Si el cliente necesita el dinero, tendrá que <strong>solicitarse un nuevo préstamo</strong> desde cero.</p>
                </div>
            </div>

            {{-- Confirmación de texto --}}
            <div style="background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:10px 14px;font-size:12px;color:#713f12;margin-bottom:20px">
                <strong>¿Estás seguro?</strong> Esta acción quedará registrada en el historial de actividad del préstamo.
            </div>

            {{-- Botones --}}
            <div style="display:flex;gap:10px;flex-direction:column">
                <button type="button"
                    style="width:100%;padding:11px;background:#f3f4f6;border:none;border-radius:8px;font-size:14px;font-weight:600;color:var(--text);cursor:pointer;transition:background .15s"
                    onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'"
                    onclick="document.getElementById('modalCancelarPrestamo').style.display='none'">
                    Volver — no cancelar
                </button>
                <form method="POST" action="{{ route('prestamos.cancelar', $prestamo->id) }}" style="margin:0">
                    @csrf
                    <button type="submit"
                        style="width:100%;padding:11px;background:#dc2626;border:none;border-radius:8px;font-size:13px;font-weight:700;color:#fff;cursor:pointer;transition:opacity .15s;letter-spacing:.01em"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                        Sí, cancelar el préstamo definitivamente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection
