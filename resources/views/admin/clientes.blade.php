@extends('layouts.app')

@section('title', $puesto === 'promo' ? 'Mis clientes' : 'Todos los clientes')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
        <h2 style="font-size:20px;font-weight:700;margin-bottom:4px">{{ $puesto === 'promo' ? 'Mis clientes' : 'Todos los clientes' }}</h2>
        <p style="color:var(--text2);font-size:13px">Gestión de clientes del sistema</p>
    </div>
    <a href="{{ route('clientes.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:13px;height:13px"><path d="M7 2v10M2 7h10"/></svg>
        Nuevo cliente
    </a>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@elseif(session('error'))
<div class="alert alert-error" style="margin-bottom:16px">{{ session('error') }}</div>
@endif

{{-- Filtros JS --}}
<div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 18px;margin-bottom:16px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
    <div>
        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:4px">Buscar</label>
        <input style="padding:7px 10px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);outline:none;min-width:220px"
               type="text" id="cSearch" placeholder="Nombre, celular, CURP…" oninput="filtrarClientes()">
    </div>

    <div style="width:1px;height:32px;background:var(--border);align-self:flex-end"></div>

    <div>
        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:4px">Préstamo</label>
        <div style="display:flex;gap:4px">
            <span style="padding:5px 12px;border-radius:20px;border:1px solid var(--accent);background:var(--accent);color:#fff;font-size:12px;font-weight:500;cursor:pointer" data-cl="todos" onclick="setPillCl(this)">Todos</span>
            <span style="padding:5px 12px;border-radius:20px;border:1px solid var(--border);background:#f9fafb;color:var(--text2);font-size:12px;font-weight:500;cursor:pointer" data-cl="con" onclick="setPillCl(this)">Con préstamo</span>
            <span style="padding:5px 12px;border-radius:20px;border:1px solid var(--border);background:#f9fafb;color:var(--text2);font-size:12px;font-weight:500;cursor:pointer" data-cl="sin" onclick="setPillCl(this)">Sin préstamo</span>
        </div>
    </div>

    @if($puesto === 'admin' && $promotores->isNotEmpty())
    <div style="width:1px;height:32px;background:var(--border);align-self:flex-end"></div>
    <div>
        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:4px">Promotor</label>
        <select style="padding:7px 10px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);outline:none;min-width:150px"
                id="cPromotor" onchange="filtrarClientes()">
            <option value="">Todos</option>
            @foreach($promotores as $p)
            <option value="{{ $p->nombre }}">{{ $p->nombre }}</option>
            @endforeach
        </select>
    </div>
    @endif

    <div style="align-self:flex-end">
        <button style="padding:7px 14px;background:#f3f4f6;border:1px solid var(--border);border-radius:6px;font-size:12px;font-family:var(--font);cursor:pointer;color:var(--text2)" onclick="resetFiltrosClientes()">Limpiar</button>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
            <span style="font-size:13px;font-weight:600">Clientes</span>
            <span style="background:#f3f4f6;color:var(--text2);padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;margin-left:8px" id="cCount">{{ $clientes->count() }} registros</span>
        </div>
    </div>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Nombre</th><th>Celular</th><th>Correo</th>
                <th>Dirección</th><th>CURP</th><th>Préstamo</th>
                <th>Promotor</th><th></th>
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
            <td>
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">{{ strtoupper(substr($c->nombre,0,2)) }}</span>
                    {{ $c->nombre }}
                </div>
            </td>
            <td style="font-family:monospace;font-size:12px">{{ $c->celular ?? '—' }}</td>
            <td style="font-size:12px;color:var(--text2)">{{ $c->email ?? '—' }}</td>
            <td>{{ $c->direccion ?? '—' }}</td>
            <td style="font-family:monospace;font-size:11px">{{ $c->curp ?? '—' }}</td>
            <td>
                @if($tieneP)
                <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:#dcfce7;color:#166534">Con préstamo</span>
                @else
                <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:#f1f5f9;color:#64748b">Sin préstamo</span>
                @endif
            </td>
            <td>{{ $c->promotor?->nombre ?? '—' }}</td>
            <td style="display:flex;gap:6px">
                <a class="btn btn-sm" style="background:#f3f4f6;color:var(--text)" href="{{ route('clientes.show', $c->id) }}">Ver</a>
                @if($puesto === 'admin')
                <a class="btn btn-sm" style="background:#f3f4f6;color:var(--text)" href="{{ route('clientes.edit', $c->id) }}">Editar</a>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text3)">No hay clientes registrados</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

@push('scripts')
<script>
let clFiltroP = 'todos';

function setPillCl(el) {
    clFiltroP = el.dataset.cl;
    document.querySelectorAll('[data-cl]').forEach(p => {
        p.style.background = '';
        p.style.color = 'var(--text2)';
        p.style.borderColor = 'var(--border)';
    });
    el.style.background = 'var(--accent)';
    el.style.color = '#fff';
    el.style.borderColor = 'var(--accent)';
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
    document.querySelectorAll('[data-cl]').forEach(el => {
        el.style.background = '';
        el.style.color = 'var(--text2)';
        el.style.borderColor = 'var(--border)';
    });
    const first = document.querySelector('[data-cl="todos"]');
    if (first) { first.style.background = 'var(--accent)'; first.style.color = '#fff'; first.style.borderColor = 'var(--accent)'; }
    filtrarClientes();
}

window.addEventListener('load', filtrarClientes);
</script>
@endpush

@endsection
