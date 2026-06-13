@extends('layouts.app')

@section('title', $puesto === 'promo' ? 'Mis préstamos' : 'Todos los préstamos')

@push('styles')
<style>
.pr-filters{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;margin-bottom:16px;box-shadow:var(--shadow-sm)}
.pr-filter-row{display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end}
.pr-field label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px}
.pr-input{padding:8px 11px;background:#f9fafb;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:var(--font);color:var(--text);outline:none;transition:border-color .15s,box-shadow .15s}
.pr-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(16,185,129,.12);background:#fff}
.pr-search{position:relative}
.pr-search svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--text3);pointer-events:none}
.pr-search .pr-input{padding-left:33px;width:100%}
.pr-divider{width:1px;align-self:stretch;background:var(--border);margin:0 2px}
/* Pills (checkbox/radio server-side) */
.pr-pills{display:flex;gap:5px;flex-wrap:wrap}
.fpill{cursor:pointer;user-select:none}
.fpill input{position:absolute;opacity:0;width:0;height:0}
.fpill span{display:inline-flex;align-items:center;padding:6px 13px;border-radius:999px;border:1px solid var(--border);background:#f9fafb;color:var(--text2);font-size:12px;font-weight:600;transition:all .15s}
.fpill input:checked + span{background:var(--accent);border-color:var(--accent);color:#fff;box-shadow:0 4px 10px -4px rgba(16,185,129,.5)}
.fpill:hover span{border-color:rgba(16,185,129,.4)}
.pr-actions{display:flex;gap:8px;align-items:center}
.pr-active-note{font-size:12px;color:#b45309;font-weight:500}
@media(max-width:768px){
    .pr-filter-row{flex-direction:column;align-items:stretch;gap:12px}
    .pr-divider{display:none}
    .pr-field,.pr-search{width:100%}
    .pr-input{width:100%}
    .pr-actions{flex-direction:column}
    .pr-actions .btn{width:100%;justify-content:center}
}
</style>
@endpush

@section('content')

@php
    $frecuencias = ['Diario','Semanal','Quincenal','Mensual'];
    $estatuses   = ['Activo','Pendiente','Atrasado','Finalizado','Refinanciado','Retirado'];
    $hayFiltros  = !empty($filtros['frecuencia']) || $filtros['monto_min'] > 0 || $filtros['monto_max'] > 0
                || !empty($filtros['desde']) || !empty($filtros['hasta']) || $filtros['q'] !== ''
                || !empty($filtros['estatus']) || $filtros['promotor'] !== '' || $filtros['cobrador'] !== '';
@endphp

<div class="prestamos-page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
        <h2 style="font-size:20px;font-weight:700;margin-bottom:4px">{{ $puesto === 'promo' ? 'Mis préstamos' : 'Todos los préstamos' }}</h2>
        <p style="color:var(--text2);font-size:13px">{{ $puesto === 'promo' ? 'Cartera personal asignada' : 'Gestión completa de créditos' }}</p>
    </div>
    @if(in_array($puesto, ['admin','promo']))
    <a href="{{ route('prestamos.create') }}" class="btn btn-primary">Nuevo préstamo</a>
    @endif
</div>

<form method="GET" action="{{ route('prestamos.index') }}" id="frmFiltros" class="pr-filters">
    {{-- Fila 1: búsqueda + estatus --}}
    <div class="pr-filter-row" style="margin-bottom:14px">
        <div class="pr-search" style="flex:1;min-width:200px">
            <label>Buscar</label>
            <div style="position:relative">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/></svg>
                <input class="pr-input" type="text" name="q" value="{{ $filtros['q'] }}" placeholder="Nombre del cliente, #ID, promotor o cobrador…" autocomplete="off">
            </div>
        </div>
        <div class="pr-field">
            <label>Estatus</label>
            <div class="pr-pills">
                @foreach($estatuses as $est)
                <label class="fpill">
                    <input type="checkbox" name="estatus[]" value="{{ $est }}" {{ in_array($est, $filtros['estatus']) ? 'checked' : '' }} onchange="document.getElementById('frmFiltros').submit()">
                    <span>{{ $est }}</span>
                </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Fila 2: frecuencia, monto, fechas, promotor/cobrador --}}
    <div class="pr-filter-row">
        <div class="pr-field">
            <label>Frecuencia</label>
            <select class="pr-input" name="frecuencia" onchange="document.getElementById('frmFiltros').submit()">
                <option value="">Todas</option>
                @foreach($frecuencias as $fr)
                <option value="{{ $fr }}" {{ $filtros['frecuencia'] === $fr ? 'selected' : '' }}>{{ $fr }}</option>
                @endforeach
            </select>
        </div>
        <div class="pr-divider"></div>
        <div class="pr-field">
            <label>Monto prestado</label>
            <div style="display:flex;gap:6px;align-items:center">
                <input class="pr-input" style="width:110px" type="number" name="monto_min" min="0" step="100" placeholder="Desde $" value="{{ $filtros['monto_min'] > 0 ? $filtros['monto_min'] : '' }}">
                <span style="color:var(--text3)">–</span>
                <input class="pr-input" style="width:110px" type="number" name="monto_max" min="0" step="100" placeholder="Hasta $" value="{{ $filtros['monto_max'] > 0 ? $filtros['monto_max'] : '' }}">
            </div>
        </div>
        <div class="pr-divider"></div>
        <div class="pr-field">
            <label>Fecha a cobrar</label>
            <div style="display:flex;gap:6px;align-items:center">
                <input class="pr-input" style="width:150px" type="date" name="desde" value="{{ $filtros['desde'] }}">
                <span style="color:var(--text3)">–</span>
                <input class="pr-input" style="width:150px" type="date" name="hasta" value="{{ $filtros['hasta'] }}">
            </div>
        </div>
        @if($puesto === 'admin' && $listaPromotores->isNotEmpty())
        <div class="pr-divider"></div>
        <div class="pr-field">
            <label>Promotor</label>
            <select class="pr-input" name="promotor" style="min-width:140px" onchange="document.getElementById('frmFiltros').submit()">
                <option value="">Todos</option>
                @foreach($listaPromotores as $pn)
                <option value="{{ $pn }}" {{ $filtros['promotor'] === $pn ? 'selected' : '' }}>{{ $pn }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if($listaCobradores->isNotEmpty())
        <div class="pr-divider"></div>
        <div class="pr-field">
            <label>Cobrador</label>
            <select class="pr-input" name="cobrador" style="min-width:140px" onchange="document.getElementById('frmFiltros').submit()">
                <option value="">Todos</option>
                @foreach($listaCobradores as $cn)
                <option value="{{ $cn }}" {{ $filtros['cobrador'] === $cn ? 'selected' : '' }}>{{ $cn }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="pr-actions">
            <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
            @if($hayFiltros)
            <a href="{{ route('prestamos.index') }}" class="btn btn-sm" style="background:#f3f4f6;color:var(--text)">Limpiar</a>
            @endif
        </div>
    </div>
</form>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
        <div>
            <span style="font-size:13px;font-weight:600">Préstamos</span>
            <span style="background:#f3f4f6;color:var(--text2);padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;margin-left:8px">{{ $prestamos->total() }} {{ $prestamos->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
        @if($hayFiltros)
        <span class="pr-active-note">Filtros activos — <a href="{{ route('prestamos.index') }}" style="color:#b45309">ver todos</a></span>
        @endif
    </div>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Cliente</th><th>Monto</th><th>Cuota</th><th>Última cuota</th>
                <th>Frecuencia</th><th>Saldo pendiente</th><th>Estatus</th><th>Acción</th>
            </tr>
        </thead>
        <tbody>
        @forelse($prestamos as $row)
        @php
            $nombre      = $row->cliente?->nombre ?? '—';
            $saldoTotal  = $row->saldo_actual + ($row->interes_acumulado ?? 0);
            $ultimaCuota = $row->fecha_fin;
            $badgeStyle = match($row->estatus) {
                'Activo'        => 'background:#dcfce7;color:#16a34a',
                'Atrasado'      => 'background:#fee2e2;color:#dc2626',
                'Pendiente'     => 'background:#fef9c3;color:#ca8a04',
                'Finalizado'    => 'background:#f3f4f6;color:#6b7280',
                'Cancelado'     => 'background:#f3f4f6;color:#9ca3af',
                'Retirado'      => 'background:#f3f4f6;color:#9ca3af',
                'Refinanciado'  => 'background:#e0f2fe;color:#0369a1',
                default         => 'background:#f3f4f6;color:#6b7280',
            };
            $dotColor = match($row->estatus) {
                'Activo' => '#16a34a', 'Atrasado' => '#dc2626', 'Pendiente' => '#ca8a04', default => null,
            };
        @endphp
        <tr>
            <td>
                <div style="font-size:13px;font-weight:600;color:var(--text)">{{ $nombre }}</div>
                <div style="font-size:11px;color:var(--text3)">#{{ $row->id }}</div>
            </td>
            <td style="font-family:var(--font-mono);font-size:13px;font-weight:600">${{ number_format($row->monto, 0, '.', ',') }}</td>
            <td style="font-family:var(--font-mono);font-size:13px">${{ number_format($row->cuota, 0, '.', ',') }}</td>
            <td style="font-family:var(--font-mono);font-size:12px;color:var(--text2)">{{ $ultimaCuota ? \Carbon\Carbon::parse($ultimaCuota)->format('d/m/Y') : '—' }}</td>
            <td style="font-size:12px;color:var(--text2)">{{ $row->frecuencia }}</td>
            <td style="font-family:var(--font-mono);font-size:13px">
                ${{ number_format($saldoTotal, 0, '.', ',') }}
                @if($row->interes_acumulado > 0)
                    <div style="font-size:10px;color:#f59e0b;font-weight:600">+${{ number_format($row->interes_acumulado,0,'.',',') }} mora</div>
                @endif
            </td>
            <td>
                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;{{ $badgeStyle }}">
                    @if($dotColor)<span style="width:6px;height:6px;border-radius:50%;background:{{ $dotColor }};display:inline-block"></span>@endif
                    {{ $row->estatus }}
                </span>
            </td>
            <td><a class="btn btn-sm" style="background:#f3f4f6;color:var(--text)" href="{{ route('prestamos.show', $row->id) }}">Ver</a></td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:48px 24px;color:var(--text3)">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.3"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 10h10M7 14h6"/></svg>
            <p style="font-size:14px;font-weight:600;color:var(--text2);margin-bottom:4px">No hay préstamos con estos filtros</p>
            <p style="font-size:12px">Prueba ajustando o limpiando los filtros.</p>
        </td></tr>
        @endforelse
        </tbody>
    </table>
    </div>

    @include('partials.pagination', ['paginator' => $prestamos])
</div>

@endsection
