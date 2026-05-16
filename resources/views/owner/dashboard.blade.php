@extends('layouts.app')

@section('title', 'Panel de administración')

@push('styles')
<style>
/* ── Owner layout ───────────────────────────── */
.ow-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.ow-kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px}
.ow-kpi{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:18px 22px}
.ow-kpi-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:6px}
.ow-kpi-value{font-size:28px;font-weight:700;letter-spacing:-0.03em;color:var(--text)}
.ow-kpi-sub{font-size:11px;color:var(--text2);margin-top:3px}

/* Search bar */
.ow-search-wrap{position:relative;margin-bottom:16px}
.ow-search-wrap svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--text3)}
.ow-search{width:100%;padding:9px 13px 9px 38px;background:var(--card);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:var(--font);color:var(--text);outline:none;transition:border-color .15s,box-shadow .15s}
.ow-search:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.ow-search::placeholder{color:var(--text3)}

/* List table */
.ow-list-wrap{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.ow-list-header{display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr auto;gap:0;padding:9px 18px;background:#f9fafb;border-bottom:1px solid var(--border)}
.ow-list-hcell{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text3)}
.ow-list-row{display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr auto;gap:0;padding:13px 18px;border-bottom:1px solid var(--border);align-items:center;transition:background .1s}
.ow-list-row:last-child{border-bottom:none}
.ow-list-row:hover{background:#f9fafb}
.ow-list-row.ow-inactive{opacity:.6}
.ow-avatar{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0}
.ow-row-name{font-size:13px;font-weight:600;color:var(--text)}
.ow-row-sub{font-size:11px;color:var(--text3);margin-top:1px}
.ow-row-cell{font-size:13px;color:var(--text2)}
.ow-row-actions{display:flex;gap:6px;justify-content:flex-end}
.ow-no-results{padding:48px 24px;text-align:center;color:var(--text3);font-size:13px}

/* Modal */
.ow-modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(6px);z-index:1000;align-items:center;justify-content:center}
.ow-modal-overlay.open{display:flex}
.ow-modal{background:#fff;border-radius:18px;width:440px;max-width:calc(100vw - 24px);box-shadow:0 20px 60px rgba(0,0,0,.18);overflow:hidden}
.ow-modal-header{padding:22px 28px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.ow-modal-title{font-size:17px;font-weight:700}
.ow-modal-close{background:#f1f5f9;border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:18px;color:var(--text3);display:flex;align-items:center;justify-content:center}
.ow-modal-body{padding:24px 28px;display:grid;gap:16px}
.ow-field label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);margin-bottom:5px}
.ow-field input{width:100%;padding:10px 13px;background:#f9fafb;border:1.5px solid var(--border);border-radius:8px;font-size:14px;font-family:var(--font);outline:none;transition:border-color .15s,box-shadow .15s}
.ow-field input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(59,130,246,.1);background:#fff}
.ow-field input.error{border-color:#ef4444}
.ow-modal-footer{padding:16px 28px;background:#f8fafc;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}

/* Responsive */
@media(max-width:768px){
    .ow-header{flex-direction:column;align-items:flex-start;}
    .ow-header .btn{width:100%;justify-content:center;}
    .ow-kpi-grid{grid-template-columns:1fr 1fr;}
    .ow-list-header{display:none}
    .ow-list-row{grid-template-columns:1fr auto;gap:8px}
    .ow-list-row > .ow-row-cell:not(:first-child):not(:last-child){display:none}
    .ow-modal{border-radius:14px!important;}
    .ow-modal-body{padding:18px 18px!important;}
    .ow-modal-header{padding:18px 18px!important;}
    .ow-modal-footer{padding:14px 18px!important;flex-direction:column;}
    .ow-modal-footer .btn{width:100%;justify-content:center;}
    .ow-row-actions{flex-wrap:wrap;}
}
@media(max-width:480px){
    .ow-kpi-grid{grid-template-columns:1fr;}
}
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="ow-header">
    <div>
        <h2 style="font-size:22px;font-weight:700;letter-spacing:-.02em;margin-bottom:3px">Clientes del sistema</h2>
        <p style="font-size:13px;color:var(--text2)">Administra quién tiene acceso a PrestaCRM</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('modalCrear').classList.add('open')">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:13px;height:13px"><path d="M7 2v10M2 7h10"/></svg>
        Nuevo administrador
    </button>
</div>

{{-- Alerts --}}
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@elseif(session('error'))
<div class="alert alert-error">{{ session('error') }}</div>
@endif

{{-- KPIs --}}
<div class="ow-kpi-grid">
    <div class="ow-kpi">
        <div class="ow-kpi-label">Total administradores</div>
        <div class="ow-kpi-value">{{ $totales['total'] }}</div>
        <div class="ow-kpi-sub">cuentas registradas</div>
    </div>
    <div class="ow-kpi" style="border-color:rgba(22,163,74,.2)">
        <div class="ow-kpi-label">Activos</div>
        <div class="ow-kpi-value" style="color:#16a34a">{{ $totales['activos'] }}</div>
        <div class="ow-kpi-sub">con acceso habilitado</div>
    </div>
    <div class="ow-kpi" style="border-color:rgba(220,38,38,.15)">
        <div class="ow-kpi-label">Inactivos</div>
        <div class="ow-kpi-value" style="color:#dc2626">{{ $totales['inactivos'] }}</div>
        <div class="ow-kpi-sub">acceso suspendido</div>
    </div>
</div>

{{-- Search bar --}}
@if(!$admins->isEmpty())
<div class="ow-search-wrap">
    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:15px;height:15px">
        <circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/>
    </svg>
    <input type="text" class="ow-search" id="owSearch" placeholder="Buscar por nombre, usuario o teléfono…" autocomplete="off">
</div>
@endif

{{-- Admin list --}}
@if($admins->isEmpty())
<div class="card" style="text-align:center;padding:60px 24px;color:var(--text3)">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.35"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7"/></svg>
    <p style="font-size:15px;font-weight:600;color:var(--text2);margin-bottom:6px">Sin administradores registrados</p>
    <p style="font-size:13px">Crea el primer administrador con el botón de arriba.</p>
</div>
@else
<div class="ow-list-wrap">
    {{-- Column headers --}}
    <div class="ow-list-header">
        <div class="ow-list-hcell">Administrador</div>
        <div class="ow-list-hcell">Teléfono</div>
        <div class="ow-list-hcell">Presupuesto</div>
        <div class="ow-list-hcell">Empleados / Clientes</div>
        <div class="ow-list-hcell">Estatus</div>
        <div class="ow-list-hcell"></div>
    </div>

    {{-- Rows --}}
    @foreach($admins as $admin)
    @php
        $initial   = strtoupper(substr($admin->usuario, 0, 1));
        $colors    = ['#3b82f6','#6366f1','#8b5cf6','#ec4899','#10b981','#f59e0b','#ef4444','#0ea5e9'];
        $color     = $colors[crc32($admin->usuario) % count($colors)];
        $fechaAlta = $admin->created_at?->format('d/m/Y') ?? '—';
    @endphp
    <div class="ow-list-row {{ !$admin->activo ? 'ow-inactive' : '' }}"
         data-search="{{ strtolower($admin->usuario . ' ' . ($admin->nombre ?? '') . ' ' . ($admin->celular ?? '')) }}">

        {{-- Nombre / usuario --}}
        <div style="display:flex;align-items:center;gap:12px">
            <div class="ow-avatar" style="background:{{ $color }}">{{ $initial }}</div>
            <div>
                <div class="ow-row-name">{{ $admin->nombre ?: $admin->usuario }}</div>
                @if($admin->nombre)
                <div class="ow-row-sub">@{{ $admin->usuario }} &nbsp;·&nbsp; Alta: {{ $fechaAlta }}</div>
                @else
                <div class="ow-row-sub">Alta: {{ $fechaAlta }}</div>
                @endif
            </div>
        </div>

        {{-- Teléfono --}}
        <div class="ow-row-cell">
            @if($admin->celular)
            <a href="https://wa.me/52{{ preg_replace('/\D/','',$admin->celular) }}"
               target="_blank"
               style="display:inline-flex;align-items:center;gap:5px;color:#16a34a;text-decoration:none;font-size:13px;font-weight:500">
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;flex-shrink:0"><path d="M10 0C4.477 0 0 4.477 0 10c0 1.763.46 3.417 1.264 4.857L0 20l5.285-1.384A9.958 9.958 0 0010 20c5.523 0 10-4.477 10-10S15.523 0 10 0zm0 18.182a8.173 8.173 0 01-4.163-1.136l-.298-.178-3.136.822.837-3.056-.195-.314A8.182 8.182 0 1110 18.182zm4.504-6.13c-.247-.124-1.463-.722-1.69-.804-.227-.083-.392-.124-.557.124-.165.247-.638.804-.782.969-.144.165-.288.185-.535.062-.247-.124-1.043-.384-1.987-1.225-.734-.655-1.23-1.464-1.374-1.71-.144-.247-.015-.381.108-.504.111-.11.247-.288.37-.432.124-.144.165-.247.247-.412.083-.165.041-.309-.02-.432-.062-.124-.557-1.343-.763-1.838-.2-.483-.405-.418-.557-.426l-.474-.008c-.165 0-.432.062-.659.309-.227.247-.866.846-.866 2.063s.887 2.392 1.011 2.557c.124.165 1.746 2.665 4.231 3.737.591.255 1.052.407 1.411.521.593.188 1.132.162 1.559.098.475-.071 1.463-.598 1.669-1.176.206-.577.206-1.072.144-1.176-.062-.103-.227-.165-.474-.289z"/></svg>
                {{ $admin->celular }}
            </a>
            @else
            <span style="color:var(--text3)">—</span>
            @endif
        </div>

        {{-- Presupuesto --}}
        <div class="ow-row-cell" style="font-weight:500;color:var(--text)">
            ${{ number_format($admin->presupuesto, 0, '.', ',') }}
        </div>

        {{-- Empleados / Clientes / Préstamos --}}
        <div class="ow-row-cell" style="display:flex;gap:10px">
            <span title="Empleados" style="display:inline-flex;align-items:center;gap:4px">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:12px;height:12px;color:var(--text3)"><circle cx="7" cy="4" r="2.5"/><path d="M1 13c0-3.314 2.686-5 6-5s6 1.686 6 5"/></svg>
                {{ $admin->stats['empleados'] }}
            </span>
            <span title="Clientes" style="display:inline-flex;align-items:center;gap:4px">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:12px;height:12px;color:var(--text3)"><circle cx="5" cy="4" r="2"/><path d="M1 12c0-2.5 1.8-4 4-4"/><circle cx="10" cy="4" r="2"/><path d="M13 12c0-2.5-1.8-4-4-4"/></svg>
                {{ $admin->stats['clientes'] }}
            </span>
            <span title="Préstamos" style="display:inline-flex;align-items:center;gap:4px">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:12px;height:12px;color:var(--text3)"><rect x="1" y="3" width="12" height="9" rx="1.5"/><path d="M4 7h6M4 10h4"/></svg>
                {{ $admin->stats['prestamos'] }}
            </span>
        </div>

        {{-- Estatus --}}
        <div class="ow-row-cell">
            @if($admin->activo)
            <span class="badge badge-green">● Activo</span>
            @else
            <span class="badge badge-red">● Inactivo</span>
            @endif
        </div>

        {{-- Acciones --}}
        <div class="ow-row-actions">
            {{-- Editar --}}
            <button type="button" class="btn btn-sm"
                style="background:#f1f5f9;color:var(--text2)"
                title="Editar"
                onclick="abrirEditarAdmin({{ $admin->id }}, '{{ addslashes($admin->usuario) }}', '{{ addslashes($admin->nombre ?? '') }}', '{{ addslashes($admin->celular ?? '') }}', '{{ $admin->presupuesto }}')">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><path d="M11 2l3 3-8 8H3v-3l8-8z"/></svg>
                Editar
            </button>

            {{-- Reset password --}}
            <button type="button" class="btn btn-sm"
                style="background:#eff6ff;color:#2563eb"
                title="Cambiar contraseña"
                onclick="abrirResetPassword({{ $admin->id }}, '{{ addslashes($admin->usuario) }}')">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><rect x="3" y="7" width="10" height="8" rx="1.5"/><path d="M5 7V5a3 3 0 0 1 6 0v2"/><circle cx="8" cy="11" r="1" fill="currentColor" stroke="none"/></svg>
            </button>

            {{-- Toggle activo/inactivo --}}
            <form method="POST" action="{{ route('owner.admins.toggle', $admin->id) }}" style="margin:0">
                @csrf
                <button type="submit" class="btn btn-sm"
                    title="{{ $admin->activo ? 'Desactivar' : 'Activar' }}"
                    style="{{ $admin->activo ? 'background:#fee2e2;color:#dc2626' : 'background:#dcfce7;color:#16a34a' }}"
                    onclick="return confirm('¿{{ $admin->activo ? 'Desactivar' : 'Activar' }} al usuario {{ addslashes($admin->usuario) }}?')">
                    {{ $admin->activo ? '⏸' : '▶' }}
                </button>
            </form>

            {{-- Eliminar --}}
            <form method="POST" action="{{ route('owner.admins.destroy', $admin->id) }}" style="margin:0">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm"
                    title="Eliminar"
                    style="background:#f3f4f6;color:#ef4444"
                    onclick="return confirm('¿Eliminar permanentemente al usuario {{ addslashes($admin->usuario) }}? Esta acción no se puede deshacer.')">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><polyline points="3 4 13 4"/><path d="M5 4V3h6v1M6 7v5M10 7v5"/><rect x="3" y="4" width="10" height="10" rx="1.5"/></svg>
                </button>
            </form>
        </div>
    </div>
    @endforeach

    {{-- Empty search result --}}
    <div class="ow-no-results" id="owNoResults" style="display:none">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;display:block;opacity:.3"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        Sin resultados para tu búsqueda.
    </div>
</div>
@endif

{{-- ── Modal: Reset contraseña ─────────────────────────────── --}}
<div class="ow-modal-overlay" id="modalReset">
    <div class="ow-modal">
        <div class="ow-modal-header">
            <div>
                <div class="ow-modal-title">Cambiar contraseña</div>
                <div style="font-size:12px;color:var(--text3);margin-top:2px">Usuario: <strong id="resetUsuarioLabel">—</strong></div>
            </div>
            <button class="ow-modal-close" onclick="cerrarResetPassword()">&times;</button>
        </div>

        <form method="POST" id="resetForm" action="" onsubmit="return submitOnce(this)">
            @csrf
            <div class="ow-modal-body">

                @if($errors->has('password') && session('reset_admin_id'))
                <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:12px;color:#991b1b">
                    {{ $errors->first('password') }}
                </div>
                @endif

                <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:12px;color:#92400e">
                    ⚠️ La nueva contraseña reemplazará la actual de inmediato. El administrador deberá usarla en su próximo inicio de sesión.
                </div>
                <div class="ow-field">
                    <label>Nueva contraseña *</label>
                    <input type="password" name="password" placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
                </div>
                <div class="ow-field">
                    <label>Confirmar nueva contraseña *</label>
                    <input type="password" name="password_confirmation" placeholder="Repite la nueva contraseña" required autocomplete="new-password">
                </div>
            </div>
            <div class="ow-modal-footer">
                <button type="button" class="btn" style="background:#f3f4f6;color:var(--text)"
                    onclick="cerrarResetPassword()">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><rect x="3" y="7" width="10" height="8" rx="1.5"/><path d="M5 7V5a3 3 0 0 1 6 0v2"/></svg>
                    Guardar contraseña
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal: Editar admin (celular + presupuesto) ─────────── --}}
<div class="ow-modal-overlay" id="modalEditar">
    <div class="ow-modal">
        <div class="ow-modal-header">
            <div>
                <div class="ow-modal-title">Editar administrador</div>
                <div style="font-size:12px;color:var(--text3);margin-top:2px">Usuario: <strong id="editAdminLabel">—</strong></div>
            </div>
            <button class="ow-modal-close" onclick="cerrarEditarAdmin()">&times;</button>
        </div>
        <form method="POST" id="formEditar" action="">
            @csrf
            @method('PUT')
            <div class="ow-modal-body">
                <div class="ow-field">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" id="edit_nombre_admin" placeholder="ej. Juan Pérez" autocomplete="off">
                </div>
                <div class="ow-field">
                    <label>Usuario (login)</label>
                    <input type="text" name="usuario" id="edit_usuario_admin" placeholder="nombre de usuario" autocomplete="off">
                </div>
                <div class="ow-field">
                    <label>Teléfono / WhatsApp</label>
                    <input type="tel" name="celular" id="edit_celular" placeholder="ej. 5512345678" autocomplete="off">
                </div>
                <div class="ow-field">
                    <label>Presupuesto asignado ($)</label>
                    <input type="number" name="presupuesto" id="edit_presupuesto" min="0" step="100" placeholder="0">
                </div>
            </div>
            <div class="ow-modal-footer">
                <button type="button" class="btn" style="background:#f3f4f6;color:var(--text)" onclick="cerrarEditarAdmin()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal: Crear nuevo administrador ───────────────────── --}}
<div class="ow-modal-overlay" id="modalCrear">
    <div class="ow-modal">
        <div class="ow-modal-header">
            <div>
                <div class="ow-modal-title">Nuevo administrador</div>
                <div style="font-size:12px;color:var(--text3);margin-top:2px">Crea una cuenta de acceso a PrestaCRM</div>
            </div>
            <button class="ow-modal-close" onclick="document.getElementById('modalCrear').classList.remove('open')">&times;</button>
        </div>

        <form method="POST" action="{{ route('owner.admins.store') }}" onsubmit="return submitOnce(this)">
            @csrf
            <div class="ow-modal-body">

                @if($errors->any())
                <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:12px;color:#991b1b">
                    {{ $errors->first() }}
                </div>
                @endif

                <div class="ow-field">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}"
                           placeholder="ej. Juan Pérez" autocomplete="off">
                </div>
                <div class="ow-field">
                    <label>Nombre de usuario *</label>
                    <input type="text" name="usuario" value="{{ old('usuario') }}"
                           placeholder="ej. empresa_xyz"
                           required autocomplete="off"
                           class="{{ $errors->has('usuario') ? 'error' : '' }}">
                </div>
                <div class="ow-field">
                    <label>Contraseña *</label>
                    <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
                </div>
                <div class="ow-field">
                    <label>Confirmar contraseña *</label>
                    <input type="password" name="password_confirmation" placeholder="Repite la contraseña" required>
                </div>
                <div class="ow-field">
                    <label>Teléfono / WhatsApp</label>
                    <input type="tel" name="celular" placeholder="ej. 5512345678">
                </div>
                <div class="ow-field">
                    <label>Presupuesto asignado ($)</label>
                    <input type="number" name="presupuesto" value="0" min="0" step="100">
                </div>
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;font-size:12px;color:#1d4ed8">
                    ℹ️ El nuevo usuario podrá iniciar sesión con estos datos y tendrá acceso completo como administrador.
                </div>
            </div>
            <div class="ow-modal-footer">
                <button type="button" class="btn" style="background:#f3f4f6;color:var(--text)"
                    onclick="document.getElementById('modalCrear').classList.remove('open')">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">Crear administrador</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// ── Buscador en tiempo real ──────────────────────────────────
(function () {
    var input = document.getElementById('owSearch');
    if (!input) return;
    input.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        var rows = document.querySelectorAll('.ow-list-row');
        var visible = 0;
        rows.forEach(function (row) {
            var haystack = row.dataset.search || '';
            var match = !q || haystack.indexOf(q) !== -1;
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        var noRes = document.getElementById('owNoResults');
        if (noRes) noRes.style.display = visible === 0 ? '' : 'none';
    });
})();

// ── Modal Crear ─────────────────────────────────────────────
@if($errors->any() && !session('reset_admin_id'))
document.getElementById('modalCrear').classList.add('open');
@endif

document.getElementById('modalCrear').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
});

// ── Modal Editar Admin ───────────────────────────────────
const EDIT_BASE = '{{ url("owner/admins") }}/';

function abrirEditarAdmin(adminId, usuario, nombre, celular, presupuesto) {
    document.getElementById('editAdminLabel').textContent      = usuario;
    document.getElementById('edit_nombre_admin').value        = nombre || '';
    document.getElementById('edit_usuario_admin').value       = usuario || '';
    document.getElementById('edit_celular').value             = celular || '';
    document.getElementById('edit_presupuesto').value         = presupuesto || 0;
    document.getElementById('formEditar').action = EDIT_BASE + adminId;
    document.getElementById('modalEditar').classList.add('open');
    document.getElementById('edit_nombre_admin').focus();
}

function cerrarEditarAdmin() {
    document.getElementById('modalEditar').classList.remove('open');
}

document.getElementById('modalEditar').addEventListener('click', function(e) {
    if (e.target === this) cerrarEditarAdmin();
});

// ── Modal Reset Password ─────────────────────────────────────
const RESET_BASE = '{{ url("owner/admins") }}/';

function abrirResetPassword(adminId, usuario) {
    document.getElementById('resetUsuarioLabel').textContent = usuario;
    document.getElementById('resetForm').action = RESET_BASE + adminId + '/reset-password';
    // Limpiar inputs
    document.getElementById('resetForm').querySelectorAll('input[type="password"]').forEach(i => i.value = '');
    document.getElementById('modalReset').classList.add('open');
    document.getElementById('resetForm').querySelector('input[type="password"]').focus();
}

function cerrarResetPassword() {
    document.getElementById('modalReset').classList.remove('open');
}

document.getElementById('modalReset').addEventListener('click', function(e) {
    if (e.target === this) cerrarResetPassword();
});

// Re-abrir modal reset si hay error de validación de password
@if($errors->has('password') && session('reset_admin_id'))
abrirResetPassword({{ session('reset_admin_id') }}, '{{ addslashes(session("reset_usuario", "")) }}');
@endif

// ── Shared ───────────────────────────────────────────────────
function submitOnce(form) {
    var btn = form.querySelector('button[type="submit"]');
    if (btn && btn.disabled) return false;
    if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }
    return true;
}
</script>
@endpush

@endsection
