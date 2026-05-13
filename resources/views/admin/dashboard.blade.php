@extends('layouts.app')
@section('title', 'Panel general')

@section('content')

{{-- Page header --}}
<div class="mb-6">
    <h1 class="text-xl font-bold" style="color:#111827;letter-spacing:-0.02em">Panel general</h1>
    <p class="text-sm mt-0.5" style="color:#6b7280">Bienvenido de vuelta. Aquí tienes un resumen de tu cartera.</p>
</div>

{{-- KPI Grid --}}
<div class="grid gap-4 mb-6" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">

    <x-stat-card
        label="Préstamos activos"
        value="{{ $kpis['prestamos_activos'] }}"
        sub="{{ $kpis['total_prestamos'] }} préstamos en total"
        color="green">
        <x-slot name="icon">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <rect x="2" y="3" width="12" height="10" rx="1.5"/><path d="M5 7h6M5 10h4"/>
            </svg>
        </x-slot>
    </x-stat-card>

    <x-stat-card
        label="En mora"
        value="{{ $kpis['prestamos_mora'] }}"
        sub="préstamos atrasados"
        color="red">
        <x-slot name="icon">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <circle cx="8" cy="8" r="6"/><path d="M8 5v3.5l2 1.5"/>
            </svg>
        </x-slot>
    </x-stat-card>

    <x-stat-card
        label="Cartera total"
        value="${{ number_format($kpis['cartera_total'], 0, '.', ',') }}"
        sub="saldo pendiente por cobrar"
        color="blue">
        <x-slot name="icon">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <path d="M2 4h12v8H2zM2 7h12"/><circle cx="5" cy="10" r="1"/>
            </svg>
        </x-slot>
    </x-stat-card>

    <x-stat-card
        label="Clientes activos"
        value="{{ $kpis['total_clientes'] }}"
        sub="{{ $kpis['total_empleados'] }} empleados activos"
        color="amber">
        <x-slot name="icon">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <circle cx="5.5" cy="5" r="2.5"/><path d="M1 14c0-2.761 2.015-5 4.5-5"/>
                <circle cx="11" cy="5" r="2.5"/><path d="M15 14c0-2.761-2.015-5-4.5-5"/>
            </svg>
        </x-slot>
    </x-stat-card>

</div>

{{-- Recent loans table --}}
<div class="bg-white rounded-xl" style="border:1px solid rgba(0,0,0,0.07);box-shadow:0 1px 3px rgba(0,0,0,0.04);overflow:hidden">

    <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid rgba(0,0,0,0.07)">
        <div>
            <span class="text-sm font-semibold" style="color:#111827">Préstamos recientes</span>
            <span class="ml-2 text-xs font-semibold px-2 py-0.5 rounded-full" style="background:#f3f4f6;color:#6b7280">
                {{ $prestamos->count() }} registros
            </span>
        </div>
        <a href="{{ route('prestamos.index') }}" class="btn btn-primary btn-sm">Ver todos</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Promotor</th>
                    <th>Monto</th>
                    <th>Saldo</th>
                    <th>Estatus</th>
                    <th>Fecha inicio</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prestamos as $p)
                <tr>
                    <td class="text-xs font-mono font-semibold" style="color:#9ca3af">#{{ $p->id }}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0"
                                  style="background:#22c55e">
                                {{ strtoupper(substr($p->cliente->nombre ?? 'N', 0, 2)) }}
                            </span>
                            <span class="text-sm font-medium" style="color:#111827">{{ $p->cliente->nombre ?? '—' }}</span>
                        </div>
                    </td>
                    <td class="text-sm" style="color:#6b7280">{{ $p->promotor->nombre ?? '—' }}</td>
                    <td class="font-mono font-semibold text-sm">${{ number_format($p->monto, 0, '.', ',') }}</td>
                    <td class="font-mono text-sm" style="color:#6b7280">${{ number_format($p->saldo_actual, 0, '.', ',') }}</td>
                    <td><x-status-badge :status="$p->estatus" /></td>
                    <td class="text-xs font-mono" style="color:#9ca3af">
                        {{ $p->fecha_inicio ? $p->fecha_inicio->format('d/m/Y') : '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-sm" style="color:#9ca3af">
                        Sin préstamos registrados aún
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
