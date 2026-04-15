@extends('layouts.app')

@section('title', 'Editar préstamo #' . $prestamo->id)

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

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:13px;color:#166534;font-weight:500">{{ session('success') }}</div>
@endif
@if(session('error'))
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:13px;color:#991b1b;font-weight:500">{{ session('error') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

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
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">Cobrador asignado</label>
            <select name="cobrador_id" style="width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;outline:none">
                <option value="">— Sin asignar —</option>
                @foreach($cobradores as $c)
                <option value="{{ $c->id }}" {{ $prestamo->cobrador_id == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                @endforeach
            </select>
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
