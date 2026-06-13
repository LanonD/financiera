@extends('layouts.app')

@section('title', $puesto === 'promo' ? 'Mis clientes' : 'Todos los clientes')

@push('styles')
<style>
.cl-filters{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;margin-bottom:16px;box-shadow:var(--shadow-sm)}
.cl-filter-row{display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end}
.cl-field label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px}
.cl-input{padding:8px 11px;background:#f9fafb;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:var(--font);color:var(--text);outline:none;transition:border-color .15s,box-shadow .15s;width:100%}
.cl-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(16,185,129,.12);background:#fff}
.cl-search{position:relative}
.cl-search svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--text3);pointer-events:none}
.cl-search .cl-input{padding-left:33px}
.cl-divider{width:1px;align-self:stretch;background:var(--border)}
.cl-pills{display:flex;gap:5px;flex-wrap:wrap}
.clpill{cursor:pointer;user-select:none}
.clpill input{position:absolute;opacity:0;width:0;height:0}
.clpill span{display:inline-flex;align-items:center;padding:6px 13px;border-radius:999px;border:1px solid var(--border);background:#f9fafb;color:var(--text2);font-size:12px;font-weight:600;transition:all .15s}
.clpill input:checked + span{background:var(--accent);border-color:var(--accent);color:#fff;box-shadow:0 4px 10px -4px rgba(16,185,129,.5)}
.clpill:hover span{border-color:rgba(16,185,129,.4)}
@media(max-width:640px){
    .cl-filter-row{flex-direction:column;align-items:stretch;gap:12px}
    .cl-divider{display:none}
    .cl-col-contacto,.cl-col-score{display:none}
}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
        <h2 style="font-size:20px;font-weight:700;margin-bottom:4px">{{ $puesto === 'promo' ? 'Mis clientes' : 'Todos los clientes' }}</h2>
        <p style="color:var(--text2);font-size:13px">Gestión de clientes del sistema</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('clientes.create') }}" class="btn" style="background:#f3f4f6;color:var(--text)">Solo cliente</a>
        <a href="{{ route('clientes.create_with_prestamo') }}" class="btn btn-primary">Cliente + préstamo</a>
    </div>
</div>

@php
    $hayFiltros = $filtros['q'] !== '' || $filtros['prestamo'] !== 'todos' || $filtros['promotor'] !== '';
@endphp

<form method="GET" action="{{ route('clientes.index') }}" id="frmClientes" class="cl-filters">
    <div class="cl-filter-row">
        <div class="cl-search cl-field" style="flex:1;min-width:200px">
            <label>Buscar</label>
            <div style="position:relative">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/></svg>
                <input class="cl-input" type="text" name="q" value="{{ $filtros['q'] }}" placeholder="Nombre, celular, CURP o promotor…" autocomplete="off">
            </div>
        </div>
        <div class="cl-divider"></div>
        <div class="cl-field">
            <label>Préstamos</label>
            <div class="cl-pills">
                @foreach(['todos' => 'Todos', 'con' => 'Con préstamo', 'sin' => 'Sin préstamo'] as $val => $lbl)
                <label class="clpill">
                    <input type="radio" name="prestamo" value="{{ $val }}" {{ $filtros['prestamo'] === $val ? 'checked' : '' }} onchange="document.getElementById('frmClientes').submit()">
                    <span>{{ $lbl }}</span>
                </label>
                @endforeach
            </div>
        </div>
        @if($puesto === 'admin' && $promotores->isNotEmpty())
        <div class="cl-divider"></div>
        <div class="cl-field">
            <label>Promotor</label>
            <select class="cl-input" name="promotor" style="min-width:150px" onchange="document.getElementById('frmClientes').submit()">
                <option value="">Todos</option>
                @foreach($promotores as $p)
                <option value="{{ $p->nombre }}" {{ $filtros['promotor'] === $p->nombre ? 'selected' : '' }}>{{ $p->nombre }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
            @if($hayFiltros)
            <a href="{{ route('clientes.index') }}" class="btn btn-sm" style="background:#f3f4f6;color:var(--text)">Limpiar</a>
            @endif
        </div>
    </div>
</form>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
            <span style="font-size:13px;font-weight:600">Clientes</span>
            <span style="background:#f3f4f6;color:var(--text2);padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;margin-left:8px">{{ $clientes->total() }} {{ $clientes->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
    </div>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th class="cl-col-contacto">Contacto</th>
                <th>Estatus</th>
                <th>Préstamos</th>
                <th class="cl-col-score">Credit Score</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($clientes as $c)
        @php
            $todosP           = $c->prestamos;
            $prestamosActivos = $todosP->whereIn('estatus', ['Activo','Atrasado']);
            $countPrestamos   = $prestamosActivos->count();
            $totalSaldo       = $prestamosActivos->sum('saldo_actual');

            if ($todosP->where('estatus', 'Atrasado')->isNotEmpty()) {
                $clientStatus = 'Atrasado'; $statusStyle = 'background:#fee2e2;color:#dc2626';
            } elseif ($todosP->where('estatus', 'Activo')->isNotEmpty()) {
                $clientStatus = 'Activo'; $statusStyle = 'background:#dcfce7;color:#16a34a';
            } elseif ($todosP->where('estatus', 'Pendiente')->isNotEmpty()) {
                $clientStatus = 'Pendiente'; $statusStyle = 'background:#fef9c3;color:#ca8a04';
            } else {
                $clientStatus = 'Sin préstamo'; $statusStyle = 'background:#f3f4f6;color:#9ca3af';
            }

            $allPagos   = $todosP->flatMap(fn($p) => $p->pagos);
            $totalPagos = $allPagos->count();
            $pagados    = $allPagos->where('estatus', 'Pagado')->count();
            $atrasadosP = $allPagos->where('estatus', 'Atrasado')->count();
            if ($totalPagos === 0) {
                $score = 700;
            } else {
                $ratio = $pagados / $totalPagos;
                $score = (int)(520 + ($ratio * 320)) - ($atrasadosP * 8);
                $score = max(500, min(850, $score));
            }
            if ($clientStatus === 'Atrasado') $score = min($score, 650);
            if ($score >= 750)     { $scoreColor = '#16a34a'; $barColor = '#22c55e'; }
            elseif ($score >= 650) { $scoreColor = '#ca8a04'; $barColor = '#f59e0b'; }
            else                   { $scoreColor = '#dc2626'; $barColor = '#ef4444'; }
            $scorePct = round(($score - 500) / 350 * 100);
        @endphp
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="width:32px;height:32px;border-radius:50%;background:var(--accent);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;letter-spacing:-.5px">{{ strtoupper(substr($c->nombre, 0, 2)) }}</span>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--text)">{{ $c->nombre }}</div>
                        @if($c->direccion)<div style="font-size:11px;color:var(--text3)">{{ Str::limit($c->direccion, 28) }}</div>@endif
                    </div>
                </div>
            </td>
            <td class="cl-col-contacto">
                <div style="display:flex;flex-direction:column;gap:3px">
                    @if($c->email)<div style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--text2)"><svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="4" width="12" height="9" rx="1.5"/><path d="M2 5l6 5 6-5" stroke-linecap="round"/></svg>{{ $c->email }}</div>@endif
                    @if($c->celular)<div style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--text2)"><svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="1" width="8" height="14" rx="1.5"/><path d="M7 12h2" stroke-linecap="round"/></svg>{{ $c->celular }}</div>@endif
                    @if(!$c->email && !$c->celular)<span style="font-size:12px;color:var(--text3)">—</span>@endif
                </div>
            </td>
            <td>
                <span style="{{ $statusStyle }};display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600">
                    @if($clientStatus !== 'Sin préstamo')<span style="width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block"></span>@endif
                    {{ $clientStatus }}
                </span>
            </td>
            <td>
                @if($countPrestamos > 0)
                <div style="font-size:13px;font-weight:600;color:var(--text)">{{ $countPrestamos }} {{ $countPrestamos === 1 ? 'préstamo' : 'préstamos' }}</div>
                <div style="font-size:11px;font-family:var(--font-mono);color:var(--text2);margin-top:2px">${{ number_format($totalSaldo, 0, '.', ',') }}</div>
                @else
                <span style="font-size:12px;color:var(--text3)">0 préstamos</span>
                @endif
            </td>
            <td class="cl-col-score" style="min-width:130px">
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:13px;font-weight:700;color:{{ $scoreColor }};font-family:var(--font-mono);min-width:30px">{{ $score }}</span>
                    <div style="flex:1;height:6px;background:#f3f4f6;border-radius:999px;overflow:hidden;min-width:60px">
                        <div style="height:100%;width:{{ $scorePct }}%;background:{{ $barColor }};border-radius:999px"></div>
                    </div>
                </div>
            </td>
            <td>
                <div style="display:flex;gap:6px">
                    <a class="btn btn-sm" style="background:#f3f4f6;color:var(--text)" href="{{ route('clientes.show', $c->id) }}">Ver</a>
                    @if($puesto === 'admin')<a class="btn btn-sm" style="background:#f3f4f6;color:var(--text)" href="{{ route('clientes.edit', $c->id) }}">Editar</a>@endif
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:48px 24px;color:var(--text3)">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.3"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7"/></svg>
            <p style="font-size:14px;font-weight:600;color:var(--text2);margin-bottom:4px">{{ $hayFiltros ? 'Sin resultados para tu búsqueda' : 'No hay clientes registrados' }}</p>
            <p style="font-size:12px">{{ $hayFiltros ? 'Prueba con otros filtros.' : 'Crea el primero con el botón «Cliente + préstamo».' }}</p>
        </td></tr>
        @endforelse
        </tbody>
    </table>
    </div>

    @include('partials.pagination', ['paginator' => $clientes])
</div>

@endsection
