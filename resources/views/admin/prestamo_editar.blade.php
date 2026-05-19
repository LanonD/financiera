@extends('layouts.app')

@section('title', 'Editar préstamo #' . $prestamo->id)

@push('styles')
<style>
@media(max-width:768px){
    .pe-grid-3{grid-template-columns:1fr!important;}
    .pe-grid-2{grid-template-columns:1fr!important;}
    /* page header wrap */
    div[style*="justify-content:space-between"][style*="flex-wrap:wrap"]{flex-direction:column!important;}
}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="{{ route('prestamos.show', $prestamo->id) }}" class="btn btn-sm" style="background:#f3f4f6;color:var(--text)">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M8 2L4 6l4 4"/></svg>
            Volver
        </a>
        <div>
            <h2 style="font-size:20px;font-weight:700;margin-bottom:2px">Editar préstamo #{{ $prestamo->id }}</h2>
            <p style="color:var(--text2);font-size:13px">{{ $prestamo->cliente?->nombre ?? '—' }}</p>
        </div>
    </div>
</div>

{{-- Alerts are shown by layouts/app.blade.php — no need to repeat them here --}}

@php
    $interesAcordado  = round((float)$prestamo->monto - (float)$prestamo->monto_entregado, 2);
    $esPendiente      = $prestamo->estatus === 'Pendiente';
    $esFinalizado     = in_array($prestamo->estatus, ['Finalizado', 'Retirado']);
    $esActivo         = !$esPendiente && !$esFinalizado; // Activo o Atrasado
    $inputStyle       = 'width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:14px;font-family:monospace;outline:none;box-sizing:border-box';
    $inputReadStyle   = 'width:100%;padding:9px 12px;background:#f1f5f9;border:1px solid var(--border);border-radius:6px;font-size:14px;font-family:monospace;color:var(--text2);cursor:not-allowed;box-sizing:border-box';
    $lblStyle         = 'display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px';

    // Valores calculados de lo que queda pendiente
    $pagosEfectivos   = $prestamo->pagos->whereIn('estatus', ['Pagado','Parcial']);
    $interesCobrado   = collect($pagosEfectivos)->sum('interes');
    $interesRestante  = max(0, round($interesAcordado - $interesCobrado, 2));
@endphp

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- CAMPOS FINANCIEROS — comportamiento por estatus               --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="card" style="padding:0;overflow:hidden;margin-bottom:20px">

    {{-- Header con badge de modo --}}
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <div style="font-size:13px;font-weight:600">Campos financieros</div>
        @if($esPendiente)
        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;background:#fef9c3;color:#854d0e;font-size:11px;font-weight:700">
            <span style="width:6px;height:6px;border-radius:50%;background:#f59e0b;display:inline-block"></span>
            Pendiente — edición completa
        </span>
        @elseif($esFinalizado)
        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;background:#f1f5f9;color:#475569;font-size:11px;font-weight:700">
            <span style="width:6px;height:6px;border-radius:50%;background:#94a3b8;display:inline-block"></span>
            {{ $prestamo->estatus }} — solo lectura
        </span>
        @else
        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;background:#dcfce7;color:#166534;font-size:11px;font-weight:700">
            <span style="width:6px;height:6px;border-radius:50%;background:#16a34a;display:inline-block"></span>
            {{ $prestamo->estatus }} — edición de saldos pendientes
        </span>
        @endif
    </div>

    {{-- ── FINALIZADO: estado de cuenta, nada editable ──────────────────── --}}
    @if($esFinalizado)
    <div style="padding:20px">
        <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:16px 20px;margin-bottom:16px">
            <div style="font-size:12px;font-weight:700;color:var(--text2);margin-bottom:12px;text-transform:uppercase;letter-spacing:.06em">Estado de cuenta — {{ $prestamo->estatus }}</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
                @foreach([
                    ['Principal entregado', '$'.number_format($prestamo->monto_entregado,2,'.',',')],
                    ['Interés acordado',    '$'.number_format($interesAcordado,2,'.',',')],
                    ['Total acordado',      '$'.number_format($prestamo->monto,2,'.',',')],
                    ['Total cobrado',       '$'.number_format($pagosEfectivos->sum('monto_cobrado'),2,'.',',')],
                    ['Mora cobrada',        '$'.number_format(0,2,'.',',')],
                    ['Saldo final',         '$'.number_format($prestamo->saldo_actual,2,'.',',')],
                ] as [$l,$v])
                <div>
                    <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text3)">{{ $l }}</div>
                    <div style="font-size:15px;font-weight:700;font-family:monospace;color:var(--text);margin-top:3px">{{ $v }}</div>
                </div>
                @endforeach
            </div>
        </div>
        <p style="font-size:12px;color:var(--text3)">
            El préstamo está {{ strtolower($prestamo->estatus) }}. El historial de pagos queda como estado de cuenta permanente y no puede modificarse.
        </p>
    </div>

    {{-- ── PENDIENTE: edición completa ─────────────────────────────────── --}}
    @elseif($esPendiente)
    <form method="POST" action="{{ route('prestamos.campos', $prestamo->id) }}" style="padding:20px">
        @csrf

        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:10px">Montos</div>
        <div class="pe-grid-3" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px">
            <div>
                <label style="{{ $lblStyle }}">Principal entregado ($)</label>
                <input type="number" name="monto_entregado" step="0.01" min="0"
                    value="{{ number_format((float)$prestamo->monto_entregado, 2, '.', '') }}"
                    style="{{ $inputStyle }}">
                <p style="font-size:11px;color:var(--text3);margin-top:4px">Dinero real entregado al cliente.</p>
            </div>
            <div>
                <label style="{{ $lblStyle }}">Total acordado ($)</label>
                <input type="number" name="monto" id="peMontoTotal" step="0.01" min="0"
                    value="{{ number_format((float)$prestamo->monto, 2, '.', '') }}"
                    style="{{ $inputStyle }}" oninput="calcInteresAcordado()">
                <p style="font-size:11px;color:var(--text3);margin-top:4px">
                    Interés acordado: <strong id="peInteresLabel">${{ number_format($interesAcordado, 2, '.', ',') }}</strong>
                </p>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#d97706;margin-bottom:5px">Mora acumulada ($)</label>
                <input type="number" name="interes_acumulado" step="0.01" min="0"
                    value="{{ number_format((float)$prestamo->interes_acumulado, 2, '.', '') }}"
                    style="{{ $inputStyle }};background:#fffbeb;border-color:#fcd34d;color:#92400e">
            </div>
        </div>

        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:10px;padding-top:14px;border-top:1px solid var(--border)">Plan de pagos</div>
        <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#92400e">
            ⚠ Las fechas de todos los pagos programados se <strong>recalcularán automáticamente</strong> al guardar.
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:16px;margin-bottom:20px">
            <div>
                <label style="{{ $lblStyle }}">Fecha inicio</label>
                <input type="date" name="fecha_inicio"
                    value="{{ $prestamo->fecha_inicio ? $prestamo->fecha_inicio->format('Y-m-d') : '' }}"
                    style="{{ $inputStyle }};font-family:var(--font)">
            </div>
            <div>
                <label style="{{ $lblStyle }}">Fecha primer cobro</label>
                <input type="date" name="fecha_primer_cobro"
                    value="{{ $prestamo->pagos()->orderBy('numero_pago')->first()?->fecha_programada?->format('Y-m-d') ?? '' }}"
                    style="{{ $inputStyle }};font-family:var(--font)">
            </div>
            <div>
                <label style="{{ $lblStyle }}">Frecuencia</label>
                <select name="frecuencia" style="{{ $inputStyle }};font-family:var(--font);cursor:pointer">
                    @foreach(['Mensual','Quincenal','Semanal','Diario'] as $f)
                    <option value="{{ $f }}" {{ $prestamo->frecuencia === $f ? 'selected' : '' }}>{{ $f }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lblStyle }}">Núm. pagos</label>
                <input type="number" name="num_pagos" step="1" min="1"
                    value="{{ $prestamo->num_pagos }}" style="{{ $inputStyle }}">
            </div>
        </div>

        <button type="submit" class="btn btn-primary"
            onclick="return confirm('¿Confirmar los cambios?')">
            Guardar y recalcular fechas
        </button>
    </form>

    {{-- ── ACTIVO / ATRASADO: solo saldos pendientes ────────────────────── --}}
    @else
    <form method="POST" action="{{ route('prestamos.campos', $prestamo->id) }}" style="padding:20px">
        @csrf

        {{-- Contexto (read-only) --}}
        <div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 16px;margin-bottom:18px;display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3)">Principal entregado</div>
                <div style="font-size:15px;font-weight:700;font-family:monospace;color:var(--text2);margin-top:3px">${{ number_format($prestamo->monto_entregado,2,'.',',') }}</div>
                <div style="font-size:10px;color:var(--text3);margin-top:1px">Histórico — no editable</div>
            </div>
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3)">Total acordado</div>
                <div style="font-size:15px;font-weight:700;font-family:monospace;color:var(--text2);margin-top:3px">${{ number_format($prestamo->monto,2,'.',',') }}</div>
                <div style="font-size:10px;color:var(--text3);margin-top:1px">Histórico — no editable</div>
            </div>
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3)">Interés acordado</div>
                <div style="font-size:15px;font-weight:700;font-family:monospace;color:var(--text2);margin-top:3px">${{ number_format($interesAcordado,2,'.',',') }}</div>
                <div style="font-size:10px;color:var(--text3);margin-top:1px">Histórico — no editable</div>
            </div>
        </div>

        {{-- Saldos pendientes editables --}}
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:12px">Saldos pendientes actuales</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px">

            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#2563eb;margin-bottom:5px">Saldo pendiente ($)</label>
                <input type="number" name="saldo_actual" step="0.01" min="0"
                    value="{{ number_format((float)$prestamo->saldo_actual, 2, '.', '') }}"
                    style="{{ $inputStyle }};background:#eff6ff;border-color:#bfdbfe;color:#1e40af">
                <p style="font-size:11px;color:#3b82f6;margin-top:4px">Deuda total que aún le queda al cliente.</p>
            </div>

            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#16a34a;margin-bottom:5px">Interés pendiente</label>
                <div style="{{ $inputReadStyle }};background:#f0fdf4;border-color:#86efac;color:#166534">
                    ${{ number_format($interesRestante, 2, '.', ',') }}
                </div>
                <p style="font-size:11px;color:#16a34a;margin-top:4px">Calculado: interés acordado menos cobrado. Solo lectura.</p>
            </div>

            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#d97706;margin-bottom:5px">Mora pendiente ($)</label>
                <input type="number" name="interes_acumulado" step="0.01" min="0"
                    value="{{ number_format((float)$prestamo->interes_acumulado, 2, '.', '') }}"
                    style="{{ $inputStyle }};background:#fffbeb;border-color:#fcd34d;color:#92400e">
                <p style="font-size:11px;color:#92400e;margin-top:4px">Interés por mora que aún no se ha cobrado.</p>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"
            onclick="return confirm('¿Ajustar los saldos pendientes de la cuenta?')">
            Guardar saldos pendientes
        </button>
    </form>
    @endif
</div>

@push('scripts')
<script>
const PE_PRINCIPAL = {{ (float)$prestamo->monto_entregado }};
function calcInteresAcordado() {
    const monto   = parseFloat(document.getElementById('peMontoTotal')?.value) || 0;
    const interes = Math.max(0, monto - PE_PRINCIPAL);
    const el = document.getElementById('peInteresLabel');
    if (el) el.textContent = '$' + interes.toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});
}
</script>
@endpush

<div class="pe-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

{{-- Estatus y cobrador --}}
<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Estado del préstamo</div>
    <form method="POST" action="{{ route('prestamos.update', $prestamo->id) }}" style="padding:20px">
        @csrf @method('PUT')
        <div style="margin-bottom:16px">
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">Estatus</label>
            <select name="estatus" style="width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;outline:none">
                @foreach(['Pendiente','Activo','Atrasado','Finalizado','Retirado'] as $est)
                <option value="{{ $est }}" {{ $prestamo->estatus === $est ? 'selected' : '' }}>{{ $est }}</option>
                @endforeach
            </select>
        </div>
        <div style="margin-bottom:16px">
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">Promotor</label>
            <select name="promotor_id" style="width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;outline:none">
                <option value="">— Sin asignar —</option>
                @foreach($promotores as $p)
                <option value="{{ $p->id }}" {{ $prestamo->promotor_id == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div style="margin-bottom:16px">
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">Cobrador asignado</label>
            <select name="cobrador_id" style="width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;outline:none">
                <option value="">— Sin asignar —</option>
                @foreach($cobradores as $c)
                <option value="{{ $c->id }}" {{ $prestamo->cobrador_id == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div style="margin-bottom:16px">
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">Desembolsador</label>
            <select name="desembolso_id" style="width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;outline:none">
                <option value="">— Sin asignar —</option>
                @foreach($desembolsadores as $d)
                <option value="{{ $d->id }}" {{ $prestamo->desembolso_id == $d->id ? 'selected' : '' }}>{{ $d->nombre }}</option>
                @endforeach
            </select>
            <p style="font-size:11px;color:var(--text3);margin-top:4px">Empleado que realizó el desembolso.</p>
        </div>
        <div style="margin-bottom:16px">
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">Interés diario por mora ($)</label>
            <input type="number" name="interes_diario" step="0.01" min="0"
                value="{{ number_format((float)$prestamo->interes_diario, 2, '.', '') }}"
                style="width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;outline:none"
                placeholder="0.00">
            <p style="font-size:11px;color:var(--text3);margin-top:4px">Monto que se suma cada día cuando la mora está activa. Dejar en 0 para no cobrar mora.</p>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">Guardar cambios</button>
    </form>
</div>

{{-- Summary --}}
<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Resumen del préstamo</div>
    <div style="padding:16px 18px;display:grid;gap:12px">
        @foreach([
            ['Cliente',     $prestamo->cliente?->nombre ?? '—'],
            ['Promotor',    $prestamo->promotor?->nombre ?? '—'],
            ['Monto',       '$'.number_format($prestamo->monto, 2, '.', ',')],
            ['Saldo actual','$'.number_format($prestamo->saldo_actual, 2, '.', ',')],
            ['Cuota',       '$'.number_format($prestamo->cuota, 2, '.', ',')],
            ['Frecuencia',  $prestamo->frecuencia],
            ['Núm. pagos',  $prestamo->num_pagos],
            ['Fecha inicio',$prestamo->fecha_inicio ? \Carbon\Carbon::parse($prestamo->fecha_inicio)->format('d/m/Y') : '—'],
        ] as [$l, $v])
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;border-bottom:1px solid #f3f4f6;padding-bottom:8px">
            <span style="color:var(--text2)">{{ $l }}</span>
            <span style="font-family:monospace;font-weight:500">{{ $v }}</span>
        </div>
        @endforeach
    </div>
</div>

</div>

@endsection
