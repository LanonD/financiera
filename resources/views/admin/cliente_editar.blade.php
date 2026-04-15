@extends('layouts.app')

@section('title', 'Editar cliente')

@section('content')

<a href="{{ route('clientes.show', $cliente->id) }}" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);margin-bottom:16px;text-decoration:none">
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11L5 7l4-4"/></svg>
    Volver al detalle
</a>

<div style="margin-bottom:20px">
    <h2 style="font-size:20px;font-weight:700;margin-bottom:4px">Editar cliente</h2>
    <p style="color:var(--text2);font-size:13px">{{ $cliente->nombre }}</p>
</div>

<div class="card" style="max-width:700px;padding:0;overflow:hidden">
<form method="POST" action="{{ route('clientes.update', $cliente->id) }}" onsubmit="this.querySelector('[type=submit]').disabled=true">
    @csrf
    @method('PUT')
    <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:16px">

        @php
        $fields = [
            ['nombre',    'Nombre completo',   'text',  true,  '1/-1'],
            ['celular',   'Celular',            'tel',   false, ''],
            ['email',     'Correo electrónico', 'email', false, ''],
            ['curp',      'CURP',               'text',  false, ''],
            ['direccion', 'Dirección',          'text',  false, '1/-1'],
        ];
        @endphp

        @foreach($fields as [$name, $label, $type, $required, $span])
        <div @if($span) style="grid-column:{{ $span }}" @endif>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:block;margin-bottom:5px">{{ $label }}</label>
            <input type="{{ $type }}" name="{{ $name }}"
                   value="{{ old($name, $cliente->$name) }}"
                   @if($required) required @endif
                   style="width:100%;padding:9px 11px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-family:var(--font);font-size:13px;outline:none">
        </div>
        @endforeach

        <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:block;margin-bottom:5px">Ocupación</label>
            <select name="ocupacion" style="width:100%;padding:9px 11px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-family:var(--font);font-size:13px;outline:none">
                @foreach(['Empleado','Negocio propio','Independiente','Otro'] as $op)
                <option {{ ($cliente->ocupacion === $op) ? 'selected' : '' }}>{{ $op }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:block;margin-bottom:5px">Promotor</label>
            <select name="promotor_id" style="width:100%;padding:9px 11px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-family:var(--font);font-size:13px;outline:none">
                <option value="">— Sin promotor —</option>
                @foreach($promotores as $p)
                <option value="{{ $p->id }}" {{ $cliente->promotor_id == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                @endforeach
            </select>
        </div>

    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--border);background:#f9fafb;display:flex;gap:8px;justify-content:flex-end">
        <a href="{{ route('clientes.show', $cliente->id) }}" class="btn" style="background:#f3f4f6;color:var(--text)">Cancelar</a>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
    </div>
</form>
</div>

@endsection
