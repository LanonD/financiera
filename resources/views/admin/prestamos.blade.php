@extends('layouts.app')

@section('title', $puesto === 'promo' ? 'Mis préstamos' : 'Todos los préstamos')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
        <h2 style="font-size:20px;font-weight:700;margin-bottom:4px">{{ $puesto === 'promo' ? 'Mis préstamos' : 'Todos los préstamos' }}</h2>
        <p style="color:var(--text2);font-size:13px">{{ $puesto === 'promo' ? 'Cartera personal asignada' : 'Gestión completa de créditos' }}</p>
    </div>
    @if(in_array($puesto, ['admin','promo']))
    <a href="{{ route('prestamos.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:13px;height:13px"><path d="M7 2v10M2 7h10"/></svg>
        Nuevo préstamo
    </a>
    @endif
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@elseif(session('error'))
<div class="alert alert-error" style="margin-bottom:16px">{{ session('error') }}</div>
@endif

{{-- Server-side filters --}}
@php
$hayFiltros = !empty($filtros['frecuencia']) || $filtros['monto_min'] > 0 || $filtros['monto_max'] > 0
           || !empty($filtros['desde']) || !empty($filtros['hasta']);
$frecuencias = ['Diario','Semanal','Quincenal','Mensual'];
@endphp

<form method="GET" action="{{ route('prestamos.index') }}" id="frmFiltros">
<div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 18px;margin-bottom:12px;display:flex;flex-direction:column;gap:12px">

    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span style="font-size:12px;font-weight:600;color:var(--text3);min-width:90px">Frecuencia</span>
        <div style="display:flex;gap:4px">
            <span style="padding:5px 12px;border-radius:20px;border:1px solid {{ empty($filtros['frecuencia']) ? 'var(--accent)' : 'var(--border)' }};background:{{ empty($filtros['frecuencia']) ? 'var(--accent)' : '#f9fafb' }};color:{{ empty($filtros['frecuencia']) ? '#fff' : 'var(--text2)' }};font-size:12px;font-weight:500;cursor:pointer"
                  onclick="setFrecuencia('')">Todas</span>
            @foreach($frecuencias as $fr)
            <span style="padding:5px 12px;border-radius:20px;border:1px solid {{ $filtros['frecuencia'] === $fr ? 'var(--accent)' : 'var(--border)' }};background:{{ $filtros['frecuencia'] === $fr ? 'var(--accent)' : '#f9fafb' }};color:{{ $filtros['frecuencia'] === $fr ? '#fff' : 'var(--text2)' }};font-size:12px;font-weight:500;cursor:pointer"
                  onclick="setFrecuencia('{{ $fr }}')">{{ $fr }}</span>
            @endforeach
        </div>
        <input type="hidden" name="frecuencia" id="hFrecuencia" value="{{ $filtros['frecuencia'] }}">
    </div>

    <div style="display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap">
        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Monto prestado</label>
            <div style="display:flex;gap:6px;align-items:center">
                <input style="padding:6px 10px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);outline:none;width:110px"
                       type="number" name="monto_min" min="0" step="100"
                       placeholder="Desde $" value="{{ $filtros['monto_min'] > 0 ? $filtros['monto_min'] : '' }}">
                <span style="color:var(--text3);font-size:13px">–</span>
                <input style="padding:6px 10px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);outline:none;width:110px"
                       type="number" name="monto_max" min="0" step="100"
                       placeholder="Hasta $" value="{{ $filtros['monto_max'] > 0 ? $filtros['monto_max'] : '' }}">
            </div>
        </div>

        <div style="width:1px;height:32px;background:var(--border);align-self:flex-end"></div>

        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Fecha a cobrar</label>
            <div style="display:flex;gap:6px;align-items:center">
                <input style="padding:6px 10px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);outline:none;width:140px"
                       type="date" name="desde" value="{{ $filtros['desde'] }}">
                <span style="color:var(--text3);font-size:13px">–</span>
                <input style="padding:6px 10px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);outline:none;width:140px"
                       type="date" name="hasta" value="{{ $filtros['hasta'] }}">
            </div>
        </div>

        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
            @if($hayFiltros)
            <a href="{{ route('prestamos.index') }}" class="btn btn-sm" style="background:#f3f4f6;color:var(--text)">Limpiar</a>
            @endif
        </div>
    </div>

</div>
</form>

{{-- JS filters --}}
@php
$listaPromotores = $prestamos->pluck('promotor.nombre')->filter()->unique()->sort()->values();
$listaCobradoresP = $prestamos->pluck('cobrador.nombre')->filter()->unique()->sort()->values();
@endphp

<div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:12px 18px;margin-bottom:16px;display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
    <div>
        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:4px">Buscar</label>
        <input style="padding:7px 10px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);outline:none;min-width:200px"
               type="text" id="globalSearch" placeholder="Nombre, ID, promotor…" oninput="filterTable()">
    </div>
    <div style="width:1px;height:32px;background:var(--border);align-self:flex-end"></div>
    <div>
        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:4px">Estatus</label>
        <div style="display:flex;gap:4px;flex-wrap:wrap">
            @foreach(['Activo' => 'badge-green', 'Pendiente' => 'badge-yellow', 'Atrasado' => 'badge-red', 'Finalizado' => 'badge-gray', 'Retirado' => 'badge-gray'] as $s => $bc)
            <span style="padding:4px 10px;border-radius:20px;border:1px solid var(--border);background:#f9fafb;color:var(--text2);font-size:12px;font-weight:500;cursor:pointer"
                  data-status="{{ $s }}" onclick="togglePill(this)">{{ $s }}</span>
            @endforeach
        </div>
    </div>
    @if($puesto === 'admin' && $listaPromotores->isNotEmpty())
    <div style="width:1px;height:32px;background:var(--border);align-self:flex-end"></div>
    <div>
        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:4px">Promotor</label>
        <select style="padding:6px 10px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);outline:none;min-width:140px"
                id="jsPromotor" onchange="filterTable()">
            <option value="">Todos</option>
            @foreach($listaPromotores as $pn)
            <option>{{ $pn }}</option>
            @endforeach
        </select>
    </div>
    @endif
    @if($listaCobradoresP->isNotEmpty())
    <div style="width:1px;height:32px;background:var(--border);align-self:flex-end"></div>
    <div>
        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:4px">Cobrador</label>
        <select style="padding:6px 10px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:var(--font);outline:none;min-width:140px"
                id="jsCobrador" onchange="filterTable()">
            <option value="">Todos</option>
            @foreach($listaCobradoresP as $cn)
            <option>{{ $cn }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div style="align-self:flex-end">
        <button style="padding:7px 14px;background:#f3f4f6;border:1px solid var(--border);border-radius:6px;font-size:12px;font-family:var(--font);cursor:pointer;color:var(--text2)" onclick="resetFilters()">Limpiar</button>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
            <span style="font-size:13px;font-weight:600">Préstamos</span>
            <span id="tableCount" style="background:#f3f4f6;color:var(--text2);padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;margin-left:8px">{{ $prestamos->count() }} registros</span>
        </div>
        @if($hayFiltros)
        <span style="font-size:12px;color:#f59e0b;font-weight:500">Filtros activos — <a href="{{ route('prestamos.index') }}" style="color:#f59e0b">ver todos</a></span>
        @endif
    </div>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Cliente</th><th>Monto</th><th>Cuota</th>
                <th>Frecuencia</th><th>Próximo cobro</th><th>Saldo pendiente</th>
                <th>Estatus</th><th>Acción</th>
            </tr>
        </thead>
        <tbody id="tableBody">
        @forelse($prestamos as $row)
        @php
            $badgeClass = match($row->estatus) {
                'Activo'     => 'badge-green',
                'Atrasado'   => 'badge-red',
                'Finalizado' => 'badge-gray',
                'Retirado'   => 'badge-gray',
                default      => 'badge-yellow',
            };
            $nombre       = $row->cliente?->nombre ?? '—';
            $saldoTotal   = $row->saldo_actual + ($row->interes_acumulado ?? 0);
            $proximoCobro = $row->proximo_pago;
            $hoy          = date('Y-m-d');
            $cobrovencido = $proximoCobro && $proximoCobro < $hoy;
            $promotorNom  = $row->promotor?->nombre ?? '';
            $cobradorNom  = $row->cobrador?->nombre ?? '';
            $busqueda     = strtolower($nombre . ' ' . $row->id . ' ' . $promotorNom . ' ' . $cobradorNom);
        @endphp
        <tr data-status="{{ $row->estatus }}"
            data-promotor="{{ $promotorNom }}"
            data-cobrador="{{ $cobradorNom }}"
            data-busqueda="{{ $busqueda }}">
            <td style="font-size:12px;color:var(--text2);font-weight:600">#{{ $row->id }}</td>
            <td>
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">{{ strtoupper(substr($nombre,0,2)) }}</span>
                    {{ $nombre }}
                </div>
            </td>
            <td style="font-family:monospace;font-size:13px;font-weight:600">${{ number_format($row->monto, 0, '.', ',') }}</td>
            <td style="font-family:monospace;font-size:13px">${{ number_format($row->cuota, 0, '.', ',') }}</td>
            <td style="font-size:12px;color:var(--text2)">{{ $row->frecuencia }}</td>
            <td style="font-family:monospace;font-size:12px;{{ $cobrovencido ? 'color:#ef4444;font-weight:600' : '' }}">
                {{ $proximoCobro ? \Carbon\Carbon::parse($proximoCobro)->format('d/m/Y') : '—' }}
                @if($cobrovencido) <span style="font-size:10px;background:#fee2e2;color:#dc2626;padding:1px 5px;border-radius:4px">Vencido</span> @endif
            </td>
            <td style="font-family:monospace;font-size:13px">${{ number_format($saldoTotal, 0, '.', ',') }}</td>
            <td><span class="badge {{ $badgeClass }}">{{ $row->estatus }}</span></td>
            <td><a class="btn btn-sm" style="background:#f3f4f6;color:var(--text)" href="{{ route('prestamos.show', $row->id) }}">Ver</a></td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text3)">No hay préstamos con los filtros seleccionados</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

@push('scripts')
<script>
let activeFilters = new Set(['Activo','Pendiente','Atrasado','Retirado','Finalizado']);

function setFrecuencia(val) {
    document.getElementById('hFrecuencia').value = val;
    document.getElementById('frmFiltros').submit();
}

function togglePill(el) {
    const s = el.dataset.status;
    if (activeFilters.has(s)) {
        activeFilters.delete(s);
        el.style.background = '#f3f4f6';
        el.style.color = 'var(--text3)';
        el.style.opacity = '.5';
    } else {
        activeFilters.add(s);
        el.style.background = '#f9fafb';
        el.style.color = 'var(--text2)';
        el.style.opacity = '1';
    }
    filterTable();
}

function filterTable() {
    const q        = document.getElementById('globalSearch').value.trim().toLowerCase();
    const promotor = (document.getElementById('jsPromotor')?.value || '').toLowerCase();
    const cobrador = (document.getElementById('jsCobrador')?.value || '').toLowerCase();
    let v = 0;
    document.querySelectorAll('#tableBody tr[data-status]').forEach(r => {
        const matchStatus   = activeFilters.has(r.dataset.status);
        const matchQ        = !q        || (r.dataset.busqueda || '').includes(q);
        const matchPromotor = !promotor || (r.dataset.promotor || '').toLowerCase() === promotor;
        const matchCobrador = !cobrador || (r.dataset.cobrador || '').toLowerCase() === cobrador;
        const show = matchStatus && matchQ && matchPromotor && matchCobrador;
        r.style.display = show ? '' : 'none';
        if (show) v++;
    });
    document.getElementById('tableCount').textContent = v + ' registros';
}

function resetFilters() {
    document.getElementById('globalSearch').value = '';
    const jp = document.getElementById('jsPromotor');
    const jc = document.getElementById('jsCobrador');
    if (jp) jp.value = '';
    if (jc) jc.value = '';
    activeFilters = new Set(['Activo','Pendiente','Atrasado','Retirado','Finalizado']);
    document.querySelectorAll('[data-status]').forEach(p => {
        p.style.background = '#f9fafb';
        p.style.color = 'var(--text2)';
        p.style.opacity = '1';
    });
    filterTable();
}

window.addEventListener('load', filterTable);
</script>
@endpush

@endsection
