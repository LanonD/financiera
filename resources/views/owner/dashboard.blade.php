@extends('layouts.app')

@section('title', 'Panel de administración')

@push('styles')
<style>
/* ── Layout ───────────────────────────────────────── */
.ow-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.ow-kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px}
.ow-kpi{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:18px 22px}
.ow-kpi-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:6px}
.ow-kpi-value{font-size:28px;font-weight:700;letter-spacing:-0.03em;color:var(--text)}
.ow-kpi-sub{font-size:11px;color:var(--text2);margin-top:3px}

/* ── Search ──────────────────────────────────────── */
.ow-search-wrap{position:relative;margin-bottom:20px}
.ow-search-wrap svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--text3)}
.ow-search{width:100%;padding:9px 13px 9px 38px;background:var(--card);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:var(--font);color:var(--text);outline:none;transition:border-color .15s}
.ow-search:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.ow-search::placeholder{color:var(--text3)}

/* ── Cards grid ───────────────────────────────────── */
.adm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}
.adm-card{background:var(--card);border:1px solid var(--border);border-radius:18px;overflow:hidden;transition:box-shadow .18s,transform .18s;position:relative}
.adm-card:hover{box-shadow:0 8px 28px rgba(0,0,0,.10);transform:translateY(-2px)}
.adm-card.adm-inactive{opacity:.65}

/* Card header band + avatar */
.adm-band{height:56px;width:100%}
.adm-top{padding:0 20px 16px;margin-top:-28px;position:relative}
.adm-avatar{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#fff;border:3px solid var(--card);box-shadow:0 4px 12px rgba(0,0,0,.15)}
.adm-name{font-size:16px;font-weight:700;color:var(--text);margin-top:10px;line-height:1.2}
.adm-username{font-size:11px;color:var(--text3);font-family:monospace;margin-top:3px;display:flex;align-items:center;gap:6px;flex-wrap:wrap}

/* Budget strip */
.adm-budget{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;border-top:1px solid var(--border);border-bottom:1px solid var(--border);background:#fafbfc}
.adm-budget-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text3)}
.adm-budget-val{font-size:16px;font-weight:800;font-family:monospace;color:var(--text)}

/* Stats row */
.adm-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border)}
.adm-stat{background:var(--card);padding:14px 8px;text-align:center}
.adm-stat-val{font-size:24px;font-weight:800;font-family:monospace;line-height:1}
.adm-stat-lbl{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text3);margin-top:4px}

/* Footer */
.adm-footer{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid var(--border)}
.adm-actions{display:flex;gap:5px}
.ow-no-results{padding:48px 24px;text-align:center;color:var(--text3);font-size:13px}

/* ── Modals ───────────────────────────────────────── */
.ow-modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(6px);z-index:1000;align-items:center;justify-content:center}
.ow-modal-overlay.open{display:flex}
.ow-modal{background:#fff;border-radius:18px;width:440px;max-width:calc(100vw - 24px);box-shadow:0 20px 60px rgba(0,0,0,.18);overflow:hidden;max-height:90vh;overflow-y:auto}
.ow-modal-header{padding:22px 28px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.ow-modal-title{font-size:17px;font-weight:700}
.ow-modal-close{background:#f1f5f9;border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:18px;color:var(--text3);display:flex;align-items:center;justify-content:center}
.ow-modal-body{padding:24px 28px;display:grid;gap:16px}
.ow-field label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);margin-bottom:5px}
.ow-field input{width:100%;padding:10px 13px;background:#f9fafb;border:1.5px solid var(--border);border-radius:8px;font-size:14px;font-family:var(--font);outline:none;transition:border-color .15s,box-shadow .15s}
.ow-field input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(59,130,246,.1);background:#fff}
.ow-field input.error{border-color:#ef4444}
.ow-modal-footer{padding:16px 28px;background:#f8fafc;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
.ow-notas-modal{width:520px}
.ow-notas-feed{max-height:340px;overflow-y:auto;display:flex;flex-direction:column;gap:10px;padding:20px 28px}
.ow-nota-item{background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:12px 14px}
.ow-nota-text{font-size:13px;color:var(--text);line-height:1.55;white-space:pre-wrap;word-break:break-word}
.ow-nota-meta{display:flex;align-items:center;justify-content:space-between;margin-top:8px}
.ow-nota-date{font-size:11px;color:var(--text3)}
.ow-nota-del{background:none;border:none;cursor:pointer;color:#d1d5db;padding:2px 4px;border-radius:4px;font-size:12px;transition:color .15s}
.ow-nota-del:hover{color:#ef4444}
.ow-notas-empty{padding:32px 28px;text-align:center;color:var(--text3);font-size:13px}
.ow-notas-form{padding:0 28px 20px;display:grid;gap:10px}
.ow-notas-textarea{width:100%;padding:10px 13px;background:#f9fafb;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:var(--font);color:var(--text);resize:vertical;min-height:80px;outline:none;transition:border-color .15s}
.ow-notas-textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(59,130,246,.1);background:#fff}
.ow-nota-badge{display:inline-flex;align-items:center;justify-content:center;min-width:16px;height:16px;padding:0 4px;border-radius:999px;font-size:10px;font-weight:700;background:#3b82f6;color:#fff;margin-left:4px;line-height:1}

/* ── Responsive ──────────────────────────────────── */
@media(max-width:768px){
    .ow-header{flex-direction:column;align-items:flex-start}
    .ow-header .btn{width:100%;justify-content:center}
    .ow-kpi-grid{grid-template-columns:1fr 1fr}
    .adm-grid{grid-template-columns:1fr}
    .ow-modal{border-radius:14px!important}
    .ow-modal-body{padding:18px!important}
    .ow-modal-header{padding:18px!important}
    .ow-modal-footer{padding:14px 18px!important;flex-direction:column}
    .ow-modal-footer .btn{width:100%;justify-content:center}
}
@media(max-width:480px){.ow-kpi-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="ow-header">
    <div>
        <h2 style="font-size:22px;font-weight:700;letter-spacing:-.02em;margin-bottom:3px">Administradores</h2>
        <p style="font-size:13px;color:var(--text2)">Panel de gestión de PrestaCRM</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('modalCrear').classList.add('open')">
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

{{-- Admin cards --}}
@if($admins->isEmpty())
<div class="card" style="text-align:center;padding:60px 24px;color:var(--text3)">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.35"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7"/></svg>
    <p style="font-size:15px;font-weight:600;color:var(--text2);margin-bottom:6px">Sin administradores registrados</p>
    <p style="font-size:13px">Crea el primer administrador con el botón de arriba.</p>
</div>
@else
<div class="adm-grid" id="admGrid">
@foreach($admins as $admin)
@php
    $initial   = strtoupper(substr($admin->nombre ?: $admin->alias ?: $admin->usuario, 0, 1));
    $palette   = ['#3b82f6','#6366f1','#8b5cf6','#ec4899','#10b981','#f59e0b','#ef4444','#0ea5e9','#14b8a6'];
    $color     = $palette[crc32($admin->usuario) % count($palette)];
    $fechaAlta = $admin->created_at?->format('d/m/Y') ?? '—';
    $displayName = $admin->alias ?: ($admin->nombre ?: $admin->usuario);
    $subName     = ($admin->alias || $admin->nombre) ? $admin->usuario : null;
    $totalPrestamos = $admin->stats['prestamos'];
    $totalClientes  = $admin->stats['clientes'];
    $totalEmpleados = $admin->stats['empleados'];
@endphp
<div class="adm-card {{ !$admin->activo ? 'adm-inactive' : '' }}"
     data-search="{{ strtolower($admin->usuario . ' ' . ($admin->nombre ?? '') . ' ' . ($admin->alias ?? '') . ' ' . ($admin->celular ?? '')) }}">

    {{-- Colored band --}}
    <div class="adm-band" style="background:linear-gradient(135deg,{{ $color }}dd,{{ $color }}88)"></div>

    {{-- Avatar + name --}}
    <div class="adm-top">
        <div class="adm-avatar" style="background:{{ $color }}">{{ $initial }}</div>
        <div class="adm-name">{{ $displayName }}</div>
        <div class="adm-username">
            <span style="font-family:monospace">@{{ $admin->usuario }}</span>
            @if($admin->celular)
            <span style="opacity:.4">·</span>
            <a href="https://wa.me/52{{ preg_replace('/\D/','',$admin->celular) }}" target="_blank"
               style="color:#16a34a;text-decoration:none;font-family:sans-serif;font-size:11px">{{ $admin->celular }}</a>
            @endif
        </div>
        <div style="font-size:11px;color:var(--text3);margin-top:4px">
            Desde {{ $fechaAlta }}
        </div>
    </div>

    {{-- Budget --}}
    <div class="adm-budget">
        <div>
            <div class="adm-budget-lbl">Presupuesto</div>
            <div class="adm-budget-val">${{ number_format($admin->presupuesto, 0, '.', ',') }}</div>
        </div>
        @if($admin->activo)
        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:999px;background:#dcfce7;color:#15803d;font-size:11px;font-weight:700">
            <span style="width:6px;height:6px;border-radius:50%;background:#16a34a;display:inline-block"></span> Activo
        </span>
        @else
        <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:999px;background:#fee2e2;color:#dc2626;font-size:11px;font-weight:700">
            <span style="width:6px;height:6px;border-radius:50%;background:#dc2626;display:inline-block"></span> Inactivo
        </span>
        @endif
    </div>

    {{-- Stats --}}
    <div class="adm-stats">
        <div class="adm-stat">
            <div class="adm-stat-val" style="color:#3b82f6">{{ $totalClientes }}</div>
            <div class="adm-stat-lbl">Clientes</div>
        </div>
        <div class="adm-stat">
            <div class="adm-stat-val" style="color:#8b5cf6">{{ $totalPrestamos }}</div>
            <div class="adm-stat-lbl">Préstamos</div>
        </div>
        <div class="adm-stat">
            <div class="adm-stat-val" style="color:#f59e0b">{{ $totalEmpleados }}</div>
            <div class="adm-stat-lbl">Empleados</div>
        </div>
    </div>

    {{-- Footer actions --}}
    <div class="adm-footer">
        <div style="font-size:11px;color:var(--text3)">
            {{ ($admin->notas->count()) ? $admin->notas->count() . ' nota' . ($admin->notas->count() > 1 ? 's' : '') : 'Sin notas' }}
        </div>
        <div class="adm-actions">
            {{-- Notas --}}
            <button type="button" class="btn btn-sm" style="background:#faf5ff;color:#7c3aed" title="Notas"
                onclick="abrirNotas({{ $admin->id }}, '{{ addslashes($displayName) }}')">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><path d="M13 2H3a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h4l2 2 2-2h2a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z"/><path d="M5 6h6M5 9h4"/></svg>
            </button>
            {{-- Editar --}}
            <button type="button" class="btn btn-sm" style="background:#f1f5f9;color:var(--text2)" title="Editar"
                onclick="abrirEditarAdmin({{ $admin->id }}, '{{ addslashes($admin->usuario) }}', '{{ addslashes($admin->nombre ?? '') }}', '{{ addslashes($admin->alias ?? '') }}', '{{ addslashes($admin->celular ?? '') }}', '{{ $admin->presupuesto }}')">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><path d="M11 2l3 3-8 8H3v-3l8-8z"/></svg>
            </button>
            {{-- Reset password --}}
            <button type="button" class="btn btn-sm" style="background:#eff6ff;color:#2563eb" title="Cambiar contraseña"
                onclick="abrirResetPassword({{ $admin->id }}, '{{ addslashes($admin->usuario) }}')">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><rect x="3" y="7" width="10" height="8" rx="1.5"/><path d="M5 7V5a3 3 0 0 1 6 0v2"/><circle cx="8" cy="11" r="1" fill="currentColor" stroke="none"/></svg>
            </button>
            {{-- Toggle --}}
            <form method="POST" action="{{ route('owner.admins.toggle', $admin->id) }}" style="margin:0">
                @csrf
                <button type="submit" class="btn btn-sm" title="{{ $admin->activo ? 'Desactivar' : 'Activar' }}"
                    style="{{ $admin->activo ? 'background:#fee2e2;color:#dc2626' : 'background:#dcfce7;color:#16a34a' }}"
                    onclick="return confirm('¿{{ $admin->activo ? 'Desactivar' : 'Activar' }} a {{ addslashes($admin->usuario) }}?')">
                    {{ $admin->activo ? '⏸' : '▶' }}
                </button>
            </form>
            {{-- Eliminar --}}
            <form method="POST" action="{{ route('owner.admins.destroy', $admin->id) }}" style="margin:0">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm" title="Eliminar"
                    style="background:#f3f4f6;color:#ef4444"
                    onclick="return confirm('¿Eliminar permanentemente a {{ addslashes($admin->usuario) }}? No se puede deshacer.')">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><polyline points="3 4 13 4"/><path d="M5 4V3h6v1M6 7v5M10 7v5"/><rect x="3" y="4" width="10" height="10" rx="1.5"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>
@endforeach
</div>

{{-- Empty search --}}
<div class="ow-no-results" id="owNoResults" style="display:none">
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;display:block;opacity:.3"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
    Sin resultados para tu búsqueda.
</div>
@endif

{{-- ── Contenedores de notas (ocultos, usados por el modal) ── --}}
@foreach($admins as $admin)
<div id="notas-data-{{ $admin->id }}" style="display:none" data-admin="{{ $admin->id }}">
    @forelse($admin->notas as $nota)
    <div class="ow-nota-tpl"
         data-id="{{ $nota->id }}"
         data-fecha="{{ $nota->created_at->format('d/m/Y H:i') }}"
         data-texto="{{ addslashes($nota->contenido) }}"
         data-del="{{ route('owner.admins.notas.destroy', [$admin->id, $nota->id]) }}">
    </div>
    @empty
    <div class="ow-nota-empty"></div>
    @endforelse
</div>
@endforeach

{{-- ── Contenedores de detalle de admin (ocultos, usados por el modal) ── --}}
@foreach($admins as $admin)
<script type="application/json" id="detalle-admin-{{ $admin->id }}">
{!! json_encode([
    'nombre' => $admin->alias ?: ($admin->nombre ?: $admin->celular),

    'empleados' => collect($admin->detalle['empleados'] ?? [])->map(fn($e) => [
        'nombre' => $e->nombre ?? 'Sin nombre',
        'celular' => $e->celular ?? '—',
    ])->values(),

    'clientes' => collect($admin->detalle['clientes'] ?? [])->map(fn($c) => [
        'nombre' => $c->nombre ?? 'Sin nombre',
        'celular' => $c->celular ?? '—',
    ])->values(),

    'prestamos' => collect($admin->detalle['prestamos'] ?? [])->map(fn($p) => [
        'monto' => '$' . number_format($p->monto ?? 0, 2),
        'estatus' => $p->estatus ?? '—',
    ])->values(),

], JSON_UNESCAPED_UNICODE) !!}
</script>
@endforeach

{{-- ── Modal: Notas de administrador ─────────────────────── --}}
<div class="ow-modal-overlay" id="modalNotas">
    <div class="ow-modal ow-notas-modal">
        <div class="ow-modal-header">
            <div>
                <div class="ow-modal-title">Notas</div>
                <div style="font-size:12px;color:var(--text3);margin-top:2px">Administrador: <strong id="notasAdminLabel">—</strong></div>
            </div>
            <button class="ow-modal-close" onclick="cerrarNotas()">&times;</button>
        </div>

        {{-- Feed de notas --}}
        <div class="ow-notas-feed" id="notasFeed"></div>

        {{-- Divisor --}}
        <div style="height:1px;background:var(--border);margin:0 28px"></div>

        {{-- Formulario nueva nota --}}
        <form method="POST" id="notasForm" action="" onsubmit="return submitOnce(this)">
            @csrf
            <div class="ow-notas-form">
                <textarea name="contenido" class="ow-notas-textarea"
                    placeholder="Escribe una nota…" required maxlength="2000"></textarea>
                <button type="submit" class="btn btn-primary" style="justify-self:end">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:13px;height:13px"><path d="M2 8l4 4 8-8"/></svg>
                    Guardar nota
                </button>
            </div>
        </form>
    </div>
</div>

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
                    <label>Alias <span style="font-weight:400;color:var(--text3)">(apodo interno)</span></label>
                    <input type="text" name="alias" id="edit_alias_admin" placeholder="ej. El Gordo, Zona Norte…" autocomplete="off" maxlength="80">
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
                    <label>Alias <span style="font-weight:400;color:var(--text3)">(apodo interno)</span></label>
                    <input type="text" name="alias" value="{{ old('alias') }}"
                           placeholder="ej. El Gordo, Zona Norte…" autocomplete="off" maxlength="80">
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

<div class="ow-modal-overlay" id="modalDetalleAdmin">
    <div class="ow-modal" style="width:720px">
        <div class="ow-modal-header">
            <div>
                <div class="ow-modal-title">Detalle del administrador</div>
                <div style="font-size:12px;color:var(--text3);margin-top:2px">
                    Admin: <strong id="detalleAdminNombre">—</strong>
                </div>
            </div>
            <button class="ow-modal-close" onclick="cerrarDetalleAdmin()">&times;</button>
        </div>

        <div class="ow-modal-body" id="detalleAdminBody"></div>
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
        var cards = document.querySelectorAll('.adm-card');
        var visible = 0;
        cards.forEach(function (card) {
            var haystack = card.dataset.search || '';
            var match = !q || haystack.indexOf(q) !== -1;
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        var noRes = document.getElementById('owNoResults');
        if (noRes) noRes.style.display = visible === 0 ? '' : 'none';
    });
})();

// ── Modal Notas ──────────────────────────────────────────────
const NOTAS_BASE = '{{ url("owner/admins") }}/';

function abrirNotas(adminId, nombre) {
    document.getElementById('notasAdminLabel').textContent = nombre;
    document.getElementById('notasForm').action = NOTAS_BASE + adminId + '/notas';

    const feed = document.getElementById('notasFeed');
    feed.innerHTML = '';

    const container = document.getElementById('notas-data-' + adminId);
    const tpls      = container ? container.querySelectorAll('.ow-nota-tpl') : [];
    const isEmpty   = container ? container.querySelector('.ow-nota-empty') : true;

    if (tpls.length === 0) {
        feed.innerHTML = '<div class="ow-notas-empty"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;display:block;opacity:.3"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Sin notas aún.</div>';
    } else {
        tpls.forEach(function(tpl) {
            var texto  = tpl.dataset.texto.replace(/\\n/g, '\n');
            var fecha  = tpl.dataset.fecha;
            var delUrl = tpl.dataset.del;
            var notaId = tpl.dataset.id;

            var item = document.createElement('div');
            item.className = 'ow-nota-item';
            item.innerHTML =
                '<p class="ow-nota-text">' + escHtml(texto) + '</p>' +
                '<div class="ow-nota-meta">' +
                    '<span class="ow-nota-date">' + fecha + '</span>' +
                    '<form method="POST" action="' + delUrl + '" style="margin:0" onsubmit="return confirm(\'¿Eliminar esta nota?\')">' +
                        '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                        '<input type="hidden" name="_method" value="DELETE">' +
                        '<button type="submit" class="ow-nota-del" title="Eliminar">' +
                            '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><polyline points="3 4 13 4"/><path d="M5 4V3h6v1M6 7v5M10 7v5"/><rect x="3" y="4" width="10" height="10" rx="1.5"/></svg>' +
                        '</button>' +
                    '</form>' +
                '</div>';
            feed.appendChild(item);
        });
    }

    // Clear textarea
    document.querySelector('#notasForm textarea').value = '';
    document.getElementById('modalNotas').classList.add('open');
    setTimeout(function(){ document.querySelector('#notasForm textarea').focus(); }, 80);
}

function cerrarNotas() {
    document.getElementById('modalNotas').classList.remove('open');
}

document.getElementById('modalNotas').addEventListener('click', function(e) {
    if (e.target === this) cerrarNotas();
});

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Re-abrir notas si se acaba de guardar/eliminar una
@php $openNotasAdmin = session('open_notas_admin'); @endphp
@if($openNotasAdmin)
(function(){
    var btn = document.querySelector('[onclick^="abrirNotas({{ $openNotasAdmin }},"]');
    if (btn) btn.click();
})();
@endif

// ── Modal Crear ─────────────────────────────────────────────
@if($errors->any() && !session('reset_admin_id'))
document.getElementById('modalCrear').classList.add('open');
@endif

document.getElementById('modalCrear').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
});

// ── Modal Editar Admin ───────────────────────────────────
const EDIT_BASE = '{{ url("owner/admins") }}/';

function abrirEditarAdmin(adminId, usuario, nombre, alias, celular, presupuesto) {
    document.getElementById('editAdminLabel').textContent      = usuario;
    document.getElementById('edit_nombre_admin').value        = nombre || '';
    document.getElementById('edit_alias_admin').value         = alias || '';
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

function abrirDetalleAdmin(adminId) {
    const script = document.getElementById('detalle-admin-' + adminId);
    if (!script) return;

    const data = JSON.parse(script.textContent);

    document.getElementById('detalleAdminNombre').textContent = data.nombre;

    const body = document.getElementById('detalleAdminBody');

    body.innerHTML = `
        ${renderSeccion('Empleados', data.empleados, ['nombre', 'celular'])}
        ${renderSeccion('Clientes', data.clientes, ['nombre', 'celular'])}
        ${renderSeccion('Préstamos', data.prestamos, ['monto', 'estatus'])}
    `;

    document.getElementById('modalDetalleAdmin').classList.add('open');
}

function cerrarDetalleAdmin() {
    document.getElementById('modalDetalleAdmin').classList.remove('open');
}

function renderSeccion(titulo, items, campos) {
    if (!items || items.length === 0) {
        return `
            <div style="border:1px solid var(--border);border-radius:12px;padding:14px">
                <strong>${titulo}</strong>
                <p style="font-size:13px;color:var(--text3);margin-top:8px">Sin registros.</p>
            </div>
        `;
    }

    let rows = items.map(item => `
        <tr>
            ${campos.map(c => `<td style="padding:8px;border-bottom:1px solid var(--border);font-size:13px">${escHtml(String(item[c] ?? '—'))}</td>`).join('')}
        </tr>
    `).join('');

    return `
        <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden">
            <div style="padding:12px 14px;background:#f9fafb;font-weight:700">${titulo}</div>
            <table style="width:100%;border-collapse:collapse">
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

document.getElementById('modalDetalleAdmin').addEventListener('click', function(e) {
    if (e.target === this) cerrarDetalleAdmin();
});
</script>
@endpush

@endsection
