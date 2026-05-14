@extends('layouts.app')

@section('title', 'Vista general')

@section('content')

<div class="kpi-grid">
    <div class="kpi">
        <div class="kpi-label">Préstamos activos</div>
        <div class="kpi-value">{{ $kpis['prestamos_activos'] }}</div>
        <div class="kpi-sub">de {{ $kpis['total_prestamos'] }} en total</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">En mora</div>
        <div class="kpi-value" style="color:#dc2626">{{ $kpis['prestamos_mora'] }}</div>
        <div class="kpi-sub">préstamos atrasados</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Clientes</div>
        <div class="kpi-value">{{ $kpis['total_clientes'] }}</div>
        <div class="kpi-sub">clientes activos</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Empleados</div>
        <div class="kpi-value">{{ $kpis['total_empleados'] }}</div>
        <div class="kpi-sub">activos</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Cartera total</div>
        <div class="kpi-value" style="font-size:18px">${{ number_format($kpis['cartera_total'], 0) }}</div>
        <div class="kpi-sub">saldo por cobrar</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Préstamos recientes</span>
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
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->cliente->nombre ?? '—' }}</td>
                    <td>{{ $p->promotor->nombre ?? '—' }}</td>
                    <td>${{ number_format($p->monto, 0) }}</td>
                    <td>${{ number_format($p->saldo_actual, 0) }}</td>
                    <td>
                        @php
                            $badge = match($p->estatus) {
                                'Activo'    => 'badge-green',
                                'Atrasado'  => 'badge-red',
                                'Pendiente' => 'badge-yellow',
                                'Finalizado'=> 'badge-blue',
                                default     => 'badge-gray',
                            };
                        @endphp
                        <span class="badge {{ $badge }}">{{ $p->estatus }}</span>
                    </td>
                    <td>{{ $p->fecha_inicio->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;color:#9ca3af;padding:24px">Sin préstamos registrados aún</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
