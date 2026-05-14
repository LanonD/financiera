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
        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:4px">Estatus</label>
        <div style="display:flex;gap:4px;flex-wrap:wrap">
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
                <th>Cliente</th>
                <th>Contacto</th>
                <th>Estatus</th>
                <th>Préstamos</th>
                <th>Credit Score</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="cBody">
        @forelse($clientes as $c)
        @php
            /* ── Préstamos ─────────────────────────────── */
            $todosP        = $c->prestamos;
            $tieneP        = $todosP->whereIn('estatus', ['Activo','Atrasado','Pendiente'])->isNotEmpty();
            $prestamosActivos = $todosP->whereIn('estatus', ['Activo','Atrasado']);
            $countPrestamos   = $prestamosActivos->count();
            $totalSaldo       = $prestamosActivos->sum('saldo_actual');

            /* ── Status derivado ────────────────────────── */
            if ($todosP->where('estatus', 'Atrasado')->isNotEmpty()) {
                $clientStatus = 'Atrasado';
                $statusStyle  = 'background:#fee2e2;color:#dc2626';
            } elseif ($todosP->where('estatus', 'Activo')->isNotEmpty()) {
                $clientStatus = 'Activo';
                $statusStyle  = 'background:#dcfce7;color:#16a34a';
            } elseif ($todosP->where('estatus', 'Pendiente')->isNotEmpty()) {
                $clientStatus = 'Pendiente';
                $statusStyle  = 'background:#fef9c3;color:#ca8a04';
            } else {
                $clientStatus = 'Sin préstamo';
                $statusStyle  = 'background:#f3f4f6;color:#9ca3af';
            }

            /* ── Credit Score ───────────────────────────── */
            $allPagos    = $todosP->flatMap(fn($p) => $p->pagos);
            $totalPagos  = $allPagos->count();
            $pagados     = $allPagos->where('estatus', 'Pagado')->count();
            $atrasadosP  = $allPagos->where('estatus', 'Atrasado')->count();

            if ($totalPagos === 0) {
                $score = 700;
            } else {
                $ratio = $pagados / $totalPagos;
                $score = (int)(520 + ($ratio * 320)) - ($atrasadosP * 8);
                $score = max(500, min(850, $score));
            }
            // Ajuste extra por préstamo activo con mora
            if ($clientStatus === 'Atrasado') $score = min($score, 650);

            if ($score >= 750)      { $scoreColor = '#16a34a'; $barColor = '#22c55e'; }
            elseif ($score >= 650)  { $scoreColor = '#ca8a04'; $barColor = '#f59e0b'; }
            else                    { $scoreColor = '#dc2626'; $barColor = '#ef4444'; }
            $scorePct = round(($score - 500) / 350 * 100);

            /* ── Search string ─────────────────────────── */
            $pnorm = strtolower($c->nombre . ' ' . ($c->celular ?? '') . ' ' . ($c->curp ?? '') . ' ' . ($c->promotor?->nombre ?? ''));
        @endphp
        <tr data-busqueda="{{ $pnorm }}"
            data-prestamo="{{ $tieneP ? 'con' : 'sin' }}"
            data-promotor="{{ $c->promotor?->nombre ?? '' }}">

            {{-- Customer --}}
            <td>
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="width:32px;height:32px;border-radius:50%;background:var(--accent);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;letter-spacing:-.5px">
                        {{ strtoupper(substr($c->nombre, 0, 2)) }}
                    </span>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--text)">{{ $c->nombre }}</div>
                        @if($c->direccion)
                        <div style="font-size:11px;color:var(--text3)">{{ Str::limit($c->direccion, 28) }}</div>
                        @endif
                    </div>
                </div>
            </td>

            {{-- Contacto --}}
            <td>
                <div style="display:flex;flex-direction:column;gap:3px">
                    @if($c->email)
                    <div style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--text2)">
                        <svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="4" width="12" height="9" rx="1.5"/><path d="M2 5l6 5 6-5" stroke-linecap="round"/></svg>
                        {{ $c->email }}
                    </div>
                    @endif
                    @if($c->celular)
                    <div style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--text2)">
                        <svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="1" width="8" height="14" rx="1.5"/><path d="M7 12h2" stroke-linecap="round"/></svg>
                        {{ $c->celular }}
                    </div>
                    @endif
                    @if(!$c->email && !$c->celular)
                    <span style="font-size:12px;color:var(--text3)">—</span>
                    @endif
                </div>
            </td>

            {{-- Estatus --}}
            <td>
                <span style="{{ $statusStyle }};display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600">
                    @if($clientStatus !== 'Sin préstamo')
                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block"></span>
                    @endif
                    {{ $clientStatus }}
                </span>
            </td>

            {{-- Préstamos --}}
            <td>
                @if($countPrestamos > 0)
                <div style="font-size:13px;font-weight:600;color:var(--text)">
                    {{ $countPrestamos }} {{ $countPrestamos === 1 ? 'préstamo' : 'préstamos' }}
                </div>
                <div style="font-size:11px;font-family:monospace;color:var(--text2);margin-top:2px">
                    ${{ number_format($totalSaldo, 0, '.', ',') }}
                </div>
                @else
                <span style="font-size:12px;color:var(--text3)">0 préstamos</span>
                @endif
            </td>

            {{-- Credit Score --}}
            <td style="min-width:130px">
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:13px;font-weight:700;color:{{ $scoreColor }};font-family:monospace;min-width:30px">
                        {{ $score }}
                    </span>
                    <div style="flex:1;height:6px;background:#f3f4f6;border-radius:999px;overflow:hidden;min-width:60px">
                        <div style="height:100%;width:{{ $scorePct }}%;background:{{ $barColor }};border-radius:999px;transition:width .3s"></div>
                    </div>
                </div>
            </td>

            {{-- Acciones --}}
            <td>
                <div style="display:flex;gap:6px">
                    <a class="btn btn-sm" style="background:#f3f4f6;color:var(--text)" href="{{ route('clientes.show', $c->id) }}">Ver</a>
                    @if($puesto === 'admin')
                    <a class="btn btn-sm" style="background:#f3f4f6;color:var(--text)" href="{{ route('clientes.edit', $c->id) }}">Editar</a>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text3)">No hay clientes registrados</td></tr>
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
        p.style.color      = 'var(--text2)';
        p.style.borderColor = 'var(--border)';
    });
    el.style.background  = 'var(--accent)';
    el.style.color       = '#fff';
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
        el.style.background  = el.dataset.cl === 'todos' ? 'var(--accent)' : '#f9fafb';
        el.style.color       = el.dataset.cl === 'todos' ? '#fff' : 'var(--text2)';
        el.style.borderColor = el.dataset.cl === 'todos' ? 'var(--accent)' : 'var(--border)';
    });
    filtrarClientes();
}

window.addEventListener('load', filtrarClientes);
</script>
@endpush

@endsection
