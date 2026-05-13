@extends('layouts.app')

@section('title', $puesto === 'promo' ? 'Mis clientes' : 'Todos los clientes')

@section('content')

@php
    $totalClientes        = $clientes->count();
    $clientesConPrestamo  = $clientes->filter(fn($c) => $c->prestamos->isNotEmpty())->count();
    $carteraTotal         = $clientes->sum(fn($c) => $c->prestamos->sum('saldo_actual'));
@endphp

{{-- Page header --}}
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold" style="color:#111827;letter-spacing:-0.02em">
            {{ $puesto === 'promo' ? 'Mis clientes' : 'Todos los clientes' }}
        </h1>
        <p class="text-sm mt-0.5" style="color:#6b7280">Gestión de clientes y cartera activa</p>
    </div>
    <a href="{{ route('clientes.create') }}" class="btn btn-primary">
        <svg width="13" height="13" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <path d="M7 2v10M2 7h10"/>
        </svg>
        Nuevo cliente
    </a>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@elseif(session('error'))
<div class="alert alert-error">{{ session('error') }}</div>
@endif

{{-- Stat cards --}}
<div class="grid gap-4 mb-5" style="grid-template-columns:repeat(3,1fr)">
    <x-stat-card label="Total clientes" value="{{ $totalClientes }}" color="blue">
        <x-slot name="icon">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <circle cx="5.5" cy="5" r="2.5"/><path d="M1 14c0-2.761 2.015-5 4.5-5"/>
                <circle cx="11" cy="5" r="2.5"/><path d="M15 14c0-2.761-2.015-5-4.5-5"/>
            </svg>
        </x-slot>
    </x-stat-card>

    <x-stat-card label="Clientes activos" value="{{ $clientesConPrestamo }}" sub="con préstamo activo" color="green">
        <x-slot name="icon">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <path d="M2 8l4 4 8-8"/>
            </svg>
        </x-slot>
    </x-stat-card>

    <x-stat-card label="Cartera total" value="${{ number_format($carteraTotal, 0, '.', ',') }}" sub="saldo activo" color="amber">
        <x-slot name="icon">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                <path d="M2 4h12v8H2zM2 7h12"/><circle cx="5" cy="10" r="1"/>
            </svg>
        </x-slot>
    </x-stat-card>
</div>

{{-- Filters + Table --}}
<div class="bg-white rounded-xl" style="border:1px solid rgba(0,0,0,0.07);box-shadow:0 1px 3px rgba(0,0,0,0.04);overflow:hidden">

    {{-- Filter bar --}}
    <div class="flex flex-wrap items-center gap-3 px-5 py-3.5" style="border-bottom:1px solid rgba(0,0,0,0.07)">

        {{-- Search --}}
        <div class="relative flex-1" style="min-width:200px;max-width:320px">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2" width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round">
                <circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/>
            </svg>
            <input type="text" id="cSearch" placeholder="Buscar clientes…"
                   style="width:100%;height:36px;padding:0 10px 0 30px;background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;outline:none;color:#111827"
                   oninput="filtrarClientes()"
                   onfocus="this.style.borderColor='#22c55e';this.style.boxShadow='0 0 0 3px rgba(34,197,94,0.1)'"
                   onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow=''">
        </div>

        <div class="w-px h-7" style="background:rgba(0,0,0,0.08)"></div>

        {{-- Loan filter pills --}}
        <div class="flex items-center gap-1.5">
            <span class="text-xs font-semibold mr-1" style="color:#9ca3af">Préstamo:</span>
            <button data-cl="todos"   onclick="setPillCl(this)" class="pill-btn active-pill text-xs font-semibold px-3 py-1 rounded-full border transition-all">Todos</button>
            <button data-cl="con"     onclick="setPillCl(this)" class="pill-btn text-xs font-semibold px-3 py-1 rounded-full border transition-all">Con préstamo</button>
            <button data-cl="sin"     onclick="setPillCl(this)" class="pill-btn text-xs font-semibold px-3 py-1 rounded-full border transition-all">Sin préstamo</button>
        </div>

        @if($puesto === 'admin' && $promotores->isNotEmpty())
        <div class="w-px h-7" style="background:rgba(0,0,0,0.08)"></div>
        <select id="cPromotor" onchange="filtrarClientes()"
                style="height:36px;padding:0 10px;background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;outline:none;color:#111827;min-width:140px">
            <option value="">Todos los promotores</option>
            @foreach($promotores as $prom)
            <option value="{{ $prom->nombre }}">{{ $prom->nombre }}</option>
            @endforeach
        </select>
        @endif

        <button onclick="resetFiltrosClientes()"
                style="height:36px;padding:0 12px;background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;font-family:'Inter',sans-serif;cursor:pointer;color:#6b7280;transition:all .15s"
                onmouseover="this.style.borderColor='#d1d5db'" onmouseout="this.style.borderColor='#e5e7eb'">
            Limpiar
        </button>

        <div class="ml-auto flex items-center gap-2">
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background:#f3f4f6;color:#6b7280" id="cCount">
                {{ $clientes->count() }} registros
            </span>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Contacto</th>
                    <th>Dirección / CURP</th>
                    <th>Préstamo</th>
                    <th>Promotor</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="cBody">
            @forelse($clientes as $c)
            @php
                $tieneP = $c->prestamos->isNotEmpty();
                $pnorm  = strtolower($c->nombre . ' ' . ($c->celular ?? '') . ' ' . ($c->curp ?? '') . ' ' . ($c->promotor?->nombre ?? ''));
            @endphp
            <tr data-busqueda="{{ $pnorm }}"
                data-prestamo="{{ $tieneP ? 'con' : 'sin' }}"
                data-promotor="{{ $c->promotor?->nombre ?? '' }}">

                {{-- Cliente --}}
                <td>
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                              style="background:#22c55e">
                            {{ strtoupper(substr($c->nombre, 0, 2)) }}
                        </span>
                        <div>
                            <p class="text-sm font-semibold" style="color:#111827">{{ $c->nombre }}</p>
                            @if($c->direccion)
                            <p class="text-xs" style="color:#9ca3af">{{ Str::limit($c->direccion, 30) }}</p>
                            @endif
                        </div>
                    </div>
                </td>

                {{-- Contacto --}}
                <td>
                    <div class="text-xs" style="line-height:1.6">
                        @if($c->email)
                        <div class="flex items-center gap-1.5" style="color:#6b7280">
                            <svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="4" width="12" height="9" rx="1.5"/><path d="M2 5l6 5 6-5" stroke-linecap="round"/></svg>
                            {{ $c->email }}
                        </div>
                        @endif
                        @if($c->celular)
                        <div class="flex items-center gap-1.5" style="color:#6b7280">
                            <svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="1" width="8" height="14" rx="1.5"/><path d="M7 12h2" stroke-linecap="round"/></svg>
                            {{ $c->celular }}
                        </div>
                        @endif
                        @if(!$c->email && !$c->celular)
                        <span style="color:#9ca3af">—</span>
                        @endif
                    </div>
                </td>

                {{-- Dirección / CURP --}}
                <td>
                    <div class="text-xs" style="line-height:1.6">
                        @if($c->curp)
                        <div class="font-mono" style="color:#6b7280">{{ $c->curp }}</div>
                        @endif
                        @if($c->direccion)
                        <div style="color:#9ca3af">{{ Str::limit($c->direccion, 28) }}</div>
                        @endif
                        @if(!$c->curp && !$c->direccion)
                        <span style="color:#9ca3af">—</span>
                        @endif
                    </div>
                </td>

                {{-- Préstamo status --}}
                <td>
                    @if($tieneP)
                    <div>
                        <span style="display:inline-flex;align-items:center;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;background:#dcfce7;color:#16a34a">
                            Activo
                        </span>
                        <p class="text-xs font-mono mt-1" style="color:#6b7280">
                            ${{ number_format($c->prestamos->sum('saldo_actual'), 0, '.', ',') }}
                        </p>
                    </div>
                    @else
                    <span style="display:inline-flex;align-items:center;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;background:#f3f4f6;color:#6b7280">
                        Sin préstamo
                    </span>
                    @endif
                </td>

                {{-- Promotor --}}
                <td class="text-sm" style="color:#6b7280">{{ $c->promotor?->nombre ?? '—' }}</td>

                {{-- Acciones --}}
                <td>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('clientes.show', $c->id) }}" class="btn btn-sm" style="background:#f3f4f6;color:#111827">Ver</a>
                        @if($puesto === 'admin')
                        <a href="{{ route('clientes.edit', $c->id) }}" class="btn btn-sm" style="background:#f3f4f6;color:#111827">Editar</a>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-16 text-sm" style="color:#9ca3af">
                    No hay clientes registrados
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('styles')
<style>
.pill-btn { cursor:pointer; background:#f9fafb; color:#6b7280; border-color:#e5e7eb; font-family:'Inter',sans-serif; }
.pill-btn:hover { background:#f3f4f6; color:#374151; }
.active-pill { background:#22c55e !important; color:#fff !important; border-color:#22c55e !important; }
</style>
@endpush

@push('scripts')
<script>
let clFiltroP = 'todos';

function setPillCl(el) {
    clFiltroP = el.dataset.cl;
    document.querySelectorAll('.pill-btn').forEach(p => p.classList.remove('active-pill'));
    el.classList.add('active-pill');
    filtrarClientes();
}

function filtrarClientes() {
    const q        = (document.getElementById('cSearch')?.value  || '').trim().toLowerCase();
    const promotor = (document.getElementById('cPromotor')?.value || '').toLowerCase();
    let v = 0;
    document.querySelectorAll('#cBody tr[data-busqueda]').forEach(r => {
        const matchQ = !q        || r.dataset.busqueda.includes(q);
        const matchP = clFiltroP === 'todos' || r.dataset.prestamo === clFiltroP;
        const matchR = !promotor || r.dataset.promotor.toLowerCase() === promotor;
        const show   = matchQ && matchP && matchR;
        r.style.display = show ? '' : 'none';
        if (show) v++;
    });
    document.getElementById('cCount').textContent = v + ' registros';
}

function resetFiltrosClientes() {
    const s = document.getElementById('cSearch');
    const p = document.getElementById('cPromotor');
    if (s) s.value = '';
    if (p) p.value = '';
    clFiltroP = 'todos';
    document.querySelectorAll('.pill-btn').forEach(el => el.classList.remove('active-pill'));
    const todos = document.querySelector('[data-cl="todos"]');
    if (todos) todos.classList.add('active-pill');
    filtrarClientes();
}

window.addEventListener('load', filtrarClientes);
</script>
@endpush

@endsection
