@extends('layouts.app')

@section('title', 'Panel de administración')

@push('styles')
<style>
/* ── Owner layout ───────────────────────────── */
.ow-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px}
.ow-kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:28px}
.ow-kpi{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:18px 22px}
.ow-kpi-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:6px}
.ow-kpi-value{font-size:28px;font-weight:700;letter-spacing:-0.03em;color:var(--text)}
.ow-kpi-sub{font-size:11px;color:var(--text2);margin-top:3px}

/* Admin cards */
.ow-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
.ow-card{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:box-shadow .2s}
.ow-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.08)}
.ow-card-top{padding:18px 20px;display:flex;align-items:center;gap:14px}
.ow-avatar{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff;flex-shrink:0;background:var(--accent)}
.ow-card-name{font-size:15px;font-weight:700;color:var(--text);line-height:1.2}
.ow-card-meta{font-size:11px;color:var(--text3);margin-top:2px}
.ow-card-body{padding:0 20px 16px;display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.ow-stat{background:#f9fafb;border-radius:8px;padding:10px;text-align:center}
.ow-stat-val{font-size:17px;font-weight:700;color:var(--text)}
.ow-stat-lbl{font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text3);margin-top:1px}
.ow-card-footer{padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:8px;justify-content:flex-end;background:#fafafa}

/* Modal crear admin */
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
    .ow-grid{grid-template-columns:1fr;}
}
@media(max-width:480px){
    .ow-kpi-grid{grid-template-columns:1fr;}
    .ow-card-body{grid-template-columns:repeat(3,1fr);}
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

{{-- Admin cards --}}
@if($admins->isEmpty())
<div class="card" style="text-align:center;padding:60px 24px;color:var(--text3)">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.35"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7"/></svg>
    <p style="font-size:15px;font-weight:600;color:var(--text2);margin-bottom:6px">Sin administradores registrados</p>
    <p style="font-size:13px">Crea el primer administrador con el botón de arriba.</p>
</div>
@else
<div class="ow-grid">
    @foreach($admins as $admin)
    @php
        $initial  = strtoupper(substr($admin->usuario, 0, 1));
        $colors   = ['#3b82f6','#6366f1','#8b5cf6','#ec4899','#10b981','#f59e0b','#ef4444','#0ea5e9'];
        $color    = $colors[crc32($admin->usuario) % count($colors)];
        $fechaAlta = $admin->created_at?->format('d/m/Y') ?? '—';
    @endphp
    <div class="ow-card" style="{{ !$admin->activo ? 'opacity:.65' : '' }}">
        <div class="ow-card-top">
            <div class="ow-avatar" style="background:{{ $color }}">{{ $initial }}</div>
            <div style="flex:1;min-width:0">
                <div class="ow-card-name">{{ $admin->nombre ?: $admin->usuario }}</div>
                @if($admin->nombre)
                <div style="font-size:11px;color:var(--text3);margin-top:1px">@{{ $admin->usuario }}</div>
                @endif
                <div class="ow-card-meta">
                    Alta: {{ $fechaAlta }}
                    &nbsp;·&nbsp;
                    @if($admin->activo)
                    <span style="color:#16a34a;font-weight:600">● Activo</span>
                    @else
                    <span style="color:#dc2626;font-weight:600">● Inactivo</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Contacto y presupuesto --}}
        <div style="padding:0 20px 12px;display:flex;gap:12px;flex-wrap:wrap">
            <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2)">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px;flex-shrink:0"><path d="M2 2l2 2-1 3 3-1 2 2 1-4-4-4z"/></svg>
                {{ $admin->celular ?: '—' }}
            </div>
            <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2)">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px;flex-shrink:0"><rect x="1" y="3" width="12" height="9" rx="1.5"/><path d="M1 6h12M5 9h4"/></svg>
                Presupuesto: <strong style="color:var(--text)">${{ number_format($admin->presupuesto, 0, '.', ',') }}</strong>
            </div>
        </div>

        {{-- Stats del sistema --}}
        <div class="ow-card-body">
            <div class="ow-stat">
                <div class="ow-stat-val">{{ $admin->stats['empleados'] }}</div>
                <div class="ow-stat-lbl">Empleados</div>
            </div>
            <div class="ow-stat">
                <div class="ow-stat-val">{{ $admin->stats['clientes'] }}</div>
                <div class="ow-stat-lbl">Clientes</div>
            </div>
            <div class="ow-stat">
                <div class="ow-stat-val">{{ $admin->stats['prestamos'] }}</div>
                <div class="ow-stat-lbl">Préstamos</div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="ow-card-footer">
            {{-- Editar info --}}
            <button type="button" class="btn btn-sm"
                style="background:#f3f4f6;color:var(--text)"
                onclick="abrirEditarAdmin({{ $admin->id }}, '{{ addslashes($admin->usuario) }}', '{{ addslashes($admin->nombre ?? '') }}', '{{ addslashes($admin->celular ?? '') }}', '{{ $admin->presupuesto }}')">
                <svg viewBox="0 0 16 16" fill="currentColor" style="width:12px;height:12px"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                Editar
            </button>

            {{-- Reset password --}}
            <button type="button" class="btn btn-sm"
                style="background:#eff6ff;color:#2563eb"
                onclick="abrirResetPassword({{ $admin->id }}, '{{ addslashes($admin->usuario) }}')">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><rect x="3" y="7" width="10" height="8" rx="1.5"/><path d="M5 7V5a3 3 0 0 1 6 0v2"/><circle cx="8" cy="11" r="1" fill="currentColor" stroke="none"/></svg>
                Contraseña
            </button>

            {{-- Toggle activo/inactivo --}}
            <form method="POST" action="{{ route('owner.admins.toggle', $admin->id) }}" style="margin:0">
                @csrf
                <button type="submit" class="btn btn-sm"
                    style="{{ $admin->activo ? 'background:#fee2e2;color:#dc2626' : 'background:#dcfce7;color:#16a34a' }}"
                    onclick="return confirm('¿{{ $admin->activo ? 'Desactivar' : 'Activar' }} al usuario {{ addslashes($admin->usuario) }}?')">
                    {{ $admin->activo ? '⏸ Desactivar' : '▶ Activar' }}
                </button>
            </form>

            {{-- Eliminar --}}
            <form method="POST" action="{{ route('owner.admins.destroy', $admin->id) }}" style="margin:0">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm"
                    style="background:#f3f4f6;color:#ef4444"
                    onclick="return confirm('¿Eliminar permanentemente al usuario {{ addslashes($admin->usuario) }}? Esta acción no se puede deshacer.')">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><polyline points="3 4 13 4"/><path d="M5 4V3h6v1M6 7v5M10 7v5"/><rect x="3" y="4" width="10" height="10" rx="1.5"/></svg>
                    Eliminar
                </button>
            </form>
        </div>
    </div>
    @endforeach
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
