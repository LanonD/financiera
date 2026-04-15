@extends('layouts.app')

@section('title', 'Gestión de Empleados')

@section('content')

@push('styles')
<style>
    /* Premium Palette & Variables */
    :root {
        --glass: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.4);
        --shadow-sm: 0 2px 4px rgba(0,0,0,0.02);
        --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.05);
        --promo-color: #6366f1;
        --collector-color: #10b981;
        --desembolso-color: #f59e0b;
        --admin-color: #ec4899;
    }

    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
    }
    .content-header h2 {
        font-size: 24px;
        font-weight: 700;
        letter-spacing: -0.03em;
        color: var(--text);
    }
    .content-header p {
        color: var(--text2);
        font-size: 14px;
        margin-top: 4px;
    }

    /* Filter Panel Overhaul */
    .filter-panel {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 24px;
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
        margin-bottom: 32px;
        box-shadow: var(--shadow-sm);
        align-items: flex-end;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .filter-group label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text3);
    }
    .filter-input {
        background: #f9fafb;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 13px;
        outline: none;
        transition: all 0.2s;
        width: 100%;
        color: var(--text);
    }
    .filter-input:focus {
        border-color: var(--accent);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Pills for Type Filter */
    .status-group {
        display: flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 12px;
        gap: 4px;
    }
    .status-pill {
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        color: var(--text2);
        white-space: nowrap;
    }
    .status-pill:hover { background: rgba(255,255,255,0.5); color: var(--text); }
    .status-pill.active { background: #fff; color: var(--accent); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

    /* Grid Layout for Sections */
    .emp-sections-grid { display: grid; grid-template-columns: 1fr; gap: 32px; }

    /* Section Cards */
    .emp-card {
        background: var(--card); border: 1px solid var(--border); border-radius: 18px;
        overflow: hidden; box-shadow: var(--shadow-sm); transition: transform 0.2s, box-shadow 0.2s;
    }
    .emp-card:hover { box-shadow: var(--shadow-md); }
    .emp-card-header {
        padding: 20px 24px; border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
        background: linear-gradient(to right, #ffffff, #fcfcfd);
    }
    .emp-card-title { display: flex; align-items: center; gap: 12px; }
    .type-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
    .type-icon svg { width: 16px; height: 16px; stroke-width: 2.5; }

    .type-promo { background: rgba(99, 102, 241, 0.1); color: var(--promo-color); }
    .type-collector { background: rgba(16, 185, 129, 0.1); color: var(--collector-color); }
    .type-desembolso { background: rgba(245, 158, 11, 0.1); color: var(--desembolso-color); }

    /* Table Styling */
    .table-premium { width: 100%; border-collapse: separate; border-spacing: 0; }
    .table-premium th {
        background: #fcfcfd; padding: 14px 24px; font-size: 11px; font-weight: 600;
        text-transform: uppercase; color: var(--text3); letter-spacing: 0.05em; border-bottom: 1px solid var(--border);
    }
    .table-premium td { padding: 16px 24px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .table-premium tr:last-child td { border-bottom: none; }
    .table-premium tr:hover td { background: #fdfdfe; }

    /* User Profile Cell */
    .user-profile { display: flex; align-items: center; gap: 12px; }
    .user-avatar-circle {
        width: 36px; height: 36px; border-radius: 50%; background: var(--bg);
        display: flex; align-items: center; justify-content: center;
        font-weight: 600; font-size: 13px; color: var(--text2);
        border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    /* Badges */
    .rango-badge {
        padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 700;
        text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px;
    }
    .rango-Oro { background: #fef9c3; color: #854d0e; }
    .rango-Plata { background: #f1f5f9; color: #475569; }
    .rango-Bronce { background: #ffedd5; color: #9a3412; }
    .rango-Diamante { background: #e0e7ff; color: #3730a3; }
    .status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

    .role-tag {
        font-size: 10px; font-weight: 600; text-transform: uppercase;
        padding: 2px 6px; border-radius: 4px; background: #f1f5f9; color: var(--text3);
        margin-right: 4px;
    }
    .role-admin { background: #fce7f3; color: #9d174d; }
    .role-promo { background: #e0e7ff; color: #3730a3; }
    .role-collector { background: #d1fae5; color: #065f46; }
    .role-desembolso { background: #fef3c7; color: #92400e; }

    /* Modals */
    .modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(8px); z-index: 2000; align-items: center; justify-content: center;
        animation: fadeIn 0.2s ease-out;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
        background: #fff; border-radius: 20px; width: 500px; max-width: 95vw;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;
        transform: translateY(20px); transition: all 0.3s;
    }
    .modal-overlay.open .modal-box { transform: translateY(0); }
    .modal-header-p { padding: 24px 32px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .modal-header-p h3 { font-size: 18px; font-weight: 700; }
    .modal-close-p {
        background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        font-size: 18px; color: var(--text3);
    }
    .modal-body-p { padding: 32px; }
    .modal-footer-p {
        padding: 20px 32px; background: #f8fafc; border-top: 1px solid var(--border);
        display: flex; justify-content: flex-end; gap: 12px;
    }

    /* Checkbox Styles */
    .role-checkbox-group { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 4px; }
    .role-checkbox { cursor: pointer; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-weight: 500; transition: all 0.2s; }
    .role-checkbox input { accent-color: var(--accent); }
    .role-checkbox:hover { background: #f8fafc; border-color: var(--accent); }
    .role-checkbox.checked { background: #eff6ff; border-color: var(--accent); color: var(--accent); }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    /* Responsive */
    @media (max-width: 768px) {
        .filter-panel { flex-direction: column; align-items: stretch; }
        .table-premium th:nth-child(4), .table-premium td:nth-child(4) { display: none; }
    }
</style>
@endpush

<div class="content-header">
    <div>
        <h2>Empleados</h2>
        <p>Gestiona los roles y capacidades de tu equipo operativo.</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalEmpleado')">
        <svg viewBox="0 0 20 20" fill="currentColor" style="width:18px;height:18px"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
        Nuevo Empleado
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@elseif(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

<div class="filter-panel">
    <div class="filter-group" style="flex:1">
        <label>Búsqueda inteligente</label>
        <input class="filter-input" type="text" id="eSearch" placeholder="Nombre, usuario, celular..." oninput="filtrarEmpleados()">
    </div>
    <div class="filter-group">
        <label>Tipo de Cuenta</label>
        <div class="status-group">
            <span class="status-pill active" data-ep="todos" onclick="setPillEmp(this)">Todos</span>
            <span class="status-pill" data-ep="promo" onclick="setPillEmp(this)">Promotores</span>
            <span class="status-pill" data-ep="collector" onclick="setPillEmp(this)">Cobradores</span>
            <span class="status-pill" data-ep="desembolso" onclick="setPillEmp(this)">Desembolso</span>
        </div>
    </div>
    <div class="filter-group">
        <label>Rango o Nivel</label>
        <select class="filter-input" id="eRango" onchange="filtrarEmpleados()" style="width:140px">
            <option value="">Todos</option>
            <option>Bronce</option><option>Plata</option><option>Oro</option><option>Platino</option><option>Diamante</option>
        </select>
    </div>
    <button class="btn btn-sm" onclick="resetFiltrosEmp()" style="margin-bottom:6px">Limpiar</button>
</div>

@php
    $secciones = [
        ['Promotores', 'promo', $promotores, 'type-promo'],
        ['Cobradores', 'collector', $cobradores, 'type-collector'],
        ['Desembolso', 'desembolso', $desembolso, 'type-desembolso'],
    ];
@endphp

<div class="emp-sections-grid">
    @foreach($secciones as [$titulo, $tipo, $lista, $class])
    <div class="emp-card emp-section" data-seccion="{{ $tipo }}">
        <div class="emp-card-header">
            <div class="emp-card-title">
                <div class="type-icon {{ $class }}">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor"><path d="M12 11c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2zM10 3a7 7 0 100 14 7 7 0 000-14z" stroke-width="2" stroke-linecap="round"/></svg>
                </div>
                <div>
                    <span style="font-size: 15px; font-weight: 700;">{{ $titulo }}</span>
                    <span style="font-size: 12px; color: var(--text3); margin-left: 8px;">{{ $lista->count() }} registrados</span>
                </div>
            </div>
        </div>
        
        <div class="table-wrap">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th style="width: 300px">Empleado</th>
                        <th>WhatsApp / Cel</th>
                        <th>Rango</th>
                        <th>Capacidad</th>
                        <th style="text-align: right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-{{ $tipo }}">
                    @forelse($lista as $e)
                    @php $roles = $e->roles ?? ($e->puesto ? [$e->puesto] : []); @endphp
                    <tr data-busqueda="{{ strtolower($e->nombre . ' ' . ($e->usuario?->usuario ?? '') . ' ' . ($e->celular ?? '')) }}"
                        data-puesto="{{ $tipo }}"
                        data-rango="{{ $e->rango ?? '' }}">
                        <td>
                            <div class="user-profile">
                                <div class="user-avatar-circle">{{ strtoupper(substr($e->nombre, 0, 1)) }}</div>
                                <div>
                                    <div style="font-weight: 600; font-size: 14px;">{{ $e->nombre }}</div>
                                    <div style="font-size: 10px; margin-top: 2px;">
                                        @foreach($roles as $r)
                                            <span class="role-tag role-{{ $r }}">{{ $r }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 13px; font-family: var(--font-mono); color: var(--text2);">{{ $e->celular ?: '—' }}</div>
                        </td>
                        <td>
                            <span class="rango-badge rango-{{ $e->rango ?? 'Bronce' }}"><span class="status-dot"></span>{{ $e->rango ?? 'Bronce' }}</span>
                        </td>
                        <td><div style="font-weight: 600; color: var(--text2);">${{ number_format($e->capacidad_maxima ?? 0, 0) }}</div></td>
                        <td style="text-align: right">
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <a href="{{ route('empleados.show', $e->id) }}" class="btn btn-sm" title="Detalles">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                                </a>
                                <button class="btn btn-sm" onclick='openEditModal({!! json_encode([
                                    "id" => $e->id, "nombre" => $e->nombre, "usuario" => $e->usuario?->usuario ?? "", "celular" => $e->celular, "email" => $e->email, "rango" => $e->rango, "capacidad" => $e->capacidad_maxima, "roles" => $roles
                                ]) !!})' title="Editar">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                                </button>
                                <button class="btn btn-sm" style="color: #ef4444;" onclick="confirmDelete({{ $e->id }}, '{{ addslashes($e->nombre) }}')" title="Desactivar">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text3);">No hay {{ strtolower($titulo) }} registrados aún.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>

{{-- MODALS --}}

{{-- Nuevo Empleado --}}
<div class="modal-overlay" id="modalEmpleado">
    <div class="modal-box" style="width: 580px">
        <div class="modal-header-p">
            <h3>Nuevo Colaborador</h3>
            <button class="modal-close-p" onclick="closeModal('modalEmpleado')">×</button>
        </div>
        <form method="POST" action="{{ route('empleados.store') }}">
            @csrf
            <div class="modal-body-p">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="filter-group">
                        <label>Usuario (Login)</label>
                        <input type="text" name="usuario" required class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" required class="filter-input">
                    </div>
                    <div class="filter-group" style="grid-column: span 2">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre" required class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label>WhatsApp / Celular</label>
                        <input type="tel" name="celular" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label>Email</label>
                        <input type="email" name="email" class="filter-input">
                    </div>
                    <div class="filter-group" style="grid-column: span 2">
                        <label>Roles / Funciones (Selecciona uno o varios)</label>
                        <div class="role-checkbox-group">
                            <label class="role-checkbox"><input type="checkbox" name="roles[]" value="admin"> Administrador</label>
                            <label class="role-checkbox"><input type="checkbox" name="roles[]" value="promo" checked> Promotor</label>
                            <label class="role-checkbox"><input type="checkbox" name="roles[]" value="collector"> Cobrador</label>
                            <label class="role-checkbox"><input type="checkbox" name="roles[]" value="desembolso"> Desembolso</label>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Rango</label>
                        <select name="rango" class="filter-input">
                            <option>Bronce</option><option>Plata</option><option>Oro</option><option>Platino</option><option>Diamante</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Capacidad Máxima ($)</label>
                        <input type="number" name="capacidad" value="0" class="filter-input">
                    </div>
                </div>
            </div>
            <div class="modal-footer-p">
                <button type="button" class="btn" onclick="closeModal('modalEmpleado')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Cuenta</button>
            </div>
        </form>
    </div>
</div>

{{-- Editar Empleado --}}
<div class="modal-overlay" id="modalEdit">
    <div class="modal-box" style="width: 580px">
        <div class="modal-header-p">
            <h3>Editar Colaborador</h3>
            <button class="modal-close-p" onclick="closeModal('modalEdit')">×</button>
        </div>
        <form id="formEdit" method="POST" action="">
            @csrf @method('PUT')
            <div class="modal-body-p">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="filter-group" style="grid-column: span 2">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre" id="edit_nombre" required class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label>WhatsApp / Celular</label>
                        <input type="tel" name="celular" id="edit_celular" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email" class="filter-input">
                    </div>
                    <div class="filter-group" style="grid-column: span 2">
                        <label>Roles / Funciones (Selecciona uno o varios)</label>
                        <div class="role-checkbox-group" id="edit_role_checks">
                            <label class="role-checkbox"><input type="checkbox" name="roles[]" value="admin"> Administrador</label>
                            <label class="role-checkbox"><input type="checkbox" name="roles[]" value="promo"> Promotor</label>
                            <label class="role-checkbox"><input type="checkbox" name="roles[]" value="collector"> Cobrador</label>
                            <label class="role-checkbox"><input type="checkbox" name="roles[]" value="desembolso"> Desembolso</label>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Rango</label>
                        <select name="rango" id="edit_rango" class="filter-input">
                            <option>Bronce</option><option>Plata</option><option>Oro</option><option>Platino</option><option>Diamante</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Capacidad Máxima ($)</label>
                        <input type="number" name="capacidad" id="edit_capacidad" class="filter-input">
                    </div>
                </div>
            </div>
            <div class="modal-footer-p">
                <button type="button" class="btn" onclick="closeModal('modalEdit')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<form id="formDelete" method="POST" action="" style="display:none">@csrf @method('DELETE')</form>

@push('scripts')
<script>
    function openModal(id) { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }

    let currentType = 'todos';
    function setPillEmp(el) {
        currentType = el.dataset.ep;
        document.querySelectorAll('.status-pill').forEach(p => p.classList.remove('active'));
        el.classList.add('active');
        filtrarEmpleados();
    }

    function filtrarEmpleados() {
        const query = document.getElementById('eSearch').value.toLowerCase();
        const rango = document.getElementById('eRango').value.toLowerCase();
        document.querySelectorAll('.emp-section').forEach(section => {
            const sectionType = section.dataset.seccion;
            if (currentType !== 'todos' && currentType !== sectionType) { section.style.display = 'none'; return; }
            section.style.display = 'block';
            section.querySelectorAll('tbody tr').forEach(row => {
                if(row.querySelector('td[colspan]')) return;
                const textMatch = row.dataset.busqueda.includes(query);
                const rangoMatch = !rango || row.dataset.rango.toLowerCase() === rango;
                row.style.display = (textMatch && rangoMatch) ? '' : 'none';
            });
        });
    }

    function openEditModal(e) {
        document.getElementById('edit_nombre').value = e.nombre;
        document.getElementById('edit_celular').value = e.celular;
        document.getElementById('edit_email').value = e.email;
        document.getElementById('edit_rango').value = e.rango;
        document.getElementById('edit_capacidad').value = e.capacidad;
        
        // Reset and check roles
        const checkGroup = document.getElementById('edit_role_checks');
        checkGroup.querySelectorAll('input').forEach(chk => {
            chk.checked = e.roles.includes(chk.value);
            chk.closest('.role-checkbox').classList.toggle('checked', chk.checked);
        });

        document.getElementById('formEdit').action = `{{ url('empleados') }}/${e.id}`;
        openModal('modalEdit');
    }

    // Role checkbox visual feedback
    document.querySelectorAll('.role-checkbox input').forEach(chk => {
        chk.addEventListener('change', function() {
            this.closest('.role-checkbox').classList.toggle('checked', this.checked);
        });
    });

    function confirmDelete(id, nombre) {
        if(confirm(`¿Estás seguro de que deseas desactivar a ${nombre}? El usuario perderá acceso al sistema.`)) {
            const f = document.getElementById('formDelete'); f.action = `{{ url('empleados') }}/${id}`; f.submit();
        }
    }

    function resetFiltrosEmp() {
        document.getElementById('eSearch').value = ''; document.getElementById('eRango').value = '';
        currentType = 'todos'; document.querySelectorAll('.status-pill').forEach(p => p.classList.remove('active'));
        document.querySelector('[data-ep="todos"]').classList.add('active'); filtrarEmpleados();
    }
</script>
@endpush
@endsection
