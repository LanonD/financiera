@extends('layouts.app')

@section('title', 'Búsqueda avanzada')

@push('styles')
<style>
@media(max-width:640px){
    form[action*="busqueda"] .btn{width:100%;}
    form[action*="busqueda"] > div{width:100%;}
}
</style>
@endpush

@section('content')

<div style="margin-bottom:20px">
    <h2 style="font-size:20px;font-weight:700;margin-bottom:4px">Búsqueda avanzada</h2>
    <p style="color:var(--text2);font-size:13px">Busca clientes y préstamos por nombre o teléfono</p>
</div>

<div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 18px;margin-bottom:20px;box-shadow:var(--shadow-sm)">
    <form method="GET" action="{{ route('busqueda.index') }}" style="display:flex;gap:10px;align-items:flex-end;width:100%;flex-wrap:wrap">
        <div style="flex:1">
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">Buscar por nombre o teléfono</label>
            <div style="position:relative">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                     style="position:absolute;left:11px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--text3);pointer-events:none">
                    <circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/>
                </svg>
                <input style="width:100%;padding:9px 12px 9px 34px;background:#f9fafb;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:var(--font);outline:none;transition:border-color .15s,box-shadow .15s"
                       onfocus="this.style.borderColor='var(--accent)';this.style.boxShadow='0 0 0 3px rgba(16,185,129,.12)'"
                       onblur="this.style.borderColor='var(--border)';this.style.boxShadow='none'"
                       name="q" value="{{ $q }}" placeholder="Ej: Laura Méndez ó 55 1234…" autofocus autocomplete="off">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>
</div>

@if($q)
<div class="card" style="margin-bottom:16px;padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <span style="font-size:13px;font-weight:600">Clientes encontrados</span>
        <span style="background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600">{{ $clientes->count() }}</span>
    </div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Nombre</th><th>Celular</th><th>Dirección</th><th>CURP</th><th>Promotor</th></tr></thead>
        <tbody>
        @if($clientes->isEmpty())
        <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text3)">Sin resultados</td></tr>
        @else
        @foreach($clientes as $c)
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">{{ strtoupper(substr($c->nombre,0,2)) }}</span>
                    <a href="{{ route('clientes.show', $c->id) }}" style="color:var(--accent);text-decoration:none;font-weight:500">{{ $c->nombre }}</a>
                </div>
            </td>
            <td style="font-family:monospace;font-size:12px">{{ $c->celular ?? '—' }}</td>
            <td>{{ $c->direccion ?? '—' }}</td>
            <td style="font-family:monospace;font-size:11px">{{ $c->curp ?? '—' }}</td>
            <td>{{ $c->promotor?->nombre ?? '—' }}</td>
        </tr>
        @endforeach
        @endif
        </tbody>
    </table>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <span style="font-size:13px;font-weight:600">Préstamos relacionados</span>
        <span style="background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600">{{ $prestamos->count() }}</span>
    </div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>ID</th><th>Cliente</th><th>Monto</th><th>Saldo</th><th>Estatus</th><th></th></tr></thead>
        <tbody>
        @if($prestamos->isEmpty())
        <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text3)">Sin resultados</td></tr>
        @else
        @foreach($prestamos as $p)
        @php
            $badgeClass = match($p->estatus) {
                'Activo'     => 'badge-green',
                'Atrasado'   => 'badge-red',
                'Finalizado' => 'badge-gray',
                default      => 'badge-yellow',
            };
        @endphp
        <tr>
            <td style="font-size:12px;color:var(--text2);font-weight:600">#{{ $p->id }}</td>
            <td>{{ $p->cliente?->nombre ?? '—' }}</td>
            <td style="font-family:monospace;font-size:13px;font-weight:600">${{ number_format($p->monto, 2, '.', ',') }}</td>
            <td style="font-family:monospace;font-size:13px">${{ number_format($p->saldo_actual, 2, '.', ',') }}</td>
            <td><span class="badge {{ $badgeClass }}">{{ $p->estatus }}</span></td>
            <td><a class="btn btn-sm" style="background:#f3f4f6;color:var(--text)" href="{{ route('prestamos.show', $p->id) }}">Ver</a></td>
        </tr>
        @endforeach
        @endif
        </tbody>
    </table>
    </div>
</div>
@else
<div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:60px 24px;text-align:center;color:var(--text3);box-shadow:var(--shadow-sm)">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 14px;display:block;opacity:.3"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
    <div style="font-size:15px;font-weight:600;color:var(--text2);margin-bottom:6px">Ingresa un término de búsqueda</div>
    <div style="font-size:13px;max-width:38ch;margin:0 auto">Puedes buscar por nombre de cliente, número de teléfono o CURP. Los resultados incluirán sus préstamos relacionados.</div>
</div>
@endif

@endsection
