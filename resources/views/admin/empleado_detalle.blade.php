@extends('layouts.app')

@section('title', 'Detalle de Empleado')

@section('content')

<div class="content-header">
    <div style="display:flex;align-items:center;gap:1rem">
        <a href="{{ route('empleados.index') }}" class="btn" style="padding:6px 10px">← Volver</a>
        <div>
            <h2>{{ $empleado->nombre }}</h2>
            <p>{{ ucfirst($empleado->puesto) }}{{ $empleado->celular ? ' · ' . $empleado->celular : '' }}</p>
        </div>
    </div>
</div>

<div class="kpi-grid" style="margin-bottom:20px">
    <div class="kpi">
        <div class="kpi-label">Préstamos activos asignados</div>
        <div class="kpi-value">{{ $prestamosActivos->count() }}</div>
        <div class="kpi-sub">En curso</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Pendientes / Por tratar</div>
        <div class="kpi-value">{{ $pendientes->count() }}</div>
        <div class="kpi-sub">Requieren atención</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Historial (recientes)</div>
        <div class="kpi-value">{{ $historial->count() }}</div>
        <div class="kpi-sub">Acciones completadas</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr;gap:20px">

    <!-- Active loans -->
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border)">
            <span style="font-size:13px;font-weight:600">Préstamos Activos</span>
        </div>
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Cliente</th><th>Monto</th>
                    <th>Cuota</th><th>Saldo Actual</th><th>Estatus</th><th></th>
                </tr>
            </thead>
            <tbody>
            @if($prestamosActivos->isEmpty())
            <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted)">No hay préstamos activos asignados.</td></tr>
            @else
            @foreach($prestamosActivos as $row)
            @php
                $badge = match($row->estatus) {
                    'Activo'     => 'badge-green',
                    'Pendiente'  => 'badge-yellow',
                    'Atrasado'   => 'badge-red',
                    'Finalizado' => 'badge-blue',
                    default      => 'badge-gray'
                };
            @endphp
            <tr>
                <td style="font-size:12px;color:var(--text-muted)">#{{ $row->id }}</td>
                <td>{{ $row->cliente->nombre ?? '—' }}</td>
                <td style="font-family:var(--font-mono)">${{ number_format($row->monto ?? 0, 2) }}</td>
                <td style="font-family:var(--font-mono)">${{ number_format($row->cuota ?? 0, 2) }}</td>
                <td style="font-family:var(--font-mono)">${{ number_format($row->saldo_actual ?? 0, 2) }}</td>
                <td><span class="badge {{ $badge }}"><span class="dot"></span>{{ $row->estatus }}</span></td>
                <td><a class="btn btn-sm" href="{{ route('prestamos.show', $row->id) }}">Ver préstamo</a></td>
            </tr>
            @endforeach
            @endif
            </tbody>
        </table>
        </div>
    </div>

    @if($empleado->puesto === 'promo')
    <!-- Pending disbursements for promotor -->
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border)">
            <span style="font-size:13px;font-weight:600">Desembolsos Pendientes</span>
        </div>
        <div class="table-wrap">
        <table>
            <thead>
                <tr><th>ID</th><th>Cliente</th><th>Monto</th><th>Fecha creado</th><th></th></tr>
            </thead>
            <tbody>
            @if($pendientes->isEmpty())
            <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted)">No hay desembolsos pendientes.</td></tr>
            @else
            @foreach($pendientes as $row)
            <tr>
                <td style="font-size:12px;color:var(--text-muted)">#{{ $row->id }}</td>
                <td>{{ $row->cliente->nombre ?? '—' }}</td>
                <td style="font-family:var(--font-mono)">${{ number_format($row->monto ?? 0, 2) }}</td>
                <td>{{ $row->created_at?->format('d/m/Y') ?? '—' }}</td>
                <td><a class="btn btn-sm" href="{{ route('prestamos.show', $row->id) }}">Ver préstamo</a></td>
            </tr>
            @endforeach
            @endif
            </tbody>
        </table>
        </div>
    </div>
    @endif

    @if($empleado->puesto === 'collector')
    <!-- Payment history for collector -->
    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border)">
            <span style="font-size:13px;font-weight:600">Últimos Pagos Cobrados (Historial)</span>
        </div>
        <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Préstamo</th><th>Cliente</th><th>Monto cobrado</th><th>Tipo</th><th>Fecha cobro</th></tr>
            </thead>
            <tbody>
            @if($historial->isEmpty())
            <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted)">No hay historial reciente de cobros.</td></tr>
            @else
            @foreach($historial as $row)
            <tr>
                <td>
                    <a href="{{ route('prestamos.show', $row->prestamo_id) }}"
                       style="color:var(--accent);text-decoration:none;font-size:12px">#{{ $row->prestamo_id }}</a>
                </td>
                <td>{{ $row->prestamo?->cliente?->nombre ?? '—' }}</td>
                <td style="font-family:var(--font-mono)">${{ number_format($row->monto_cobrado ?? 0, 2) }}</td>
                <td><span class="badge badge-green">{{ ucfirst($row->tipo_cobro ?? '—') }}</span></td>
                <td>{{ $row->fecha_pago ? \Carbon\Carbon::parse($row->fecha_pago)->format('d/m/Y H:i') : '—' }}</td>
            </tr>
            @endforeach
            @endif
            </tbody>
        </table>
        </div>
    </div>
    @endif

</div>

@endsection
