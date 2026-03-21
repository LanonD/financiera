<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <title>PrestaCRM — Empleados</title>
</head>
<body>

<!-- Sidebar -->
<?php session_start(); ?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark"><svg viewBox="0 0 14 14" fill="white"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg></div>
        <span class="logo-text">PrestaCRM</span>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Principal</span>
        <a class="nav-item" href="admin_view.php"><svg viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1.5"/><rect x="9" y="1" width="6" height="6" rx="1.5"/><rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/></svg>Vista general</a>
        <a class="nav-item active" href="admin_view2.php"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6"/></svg>Empleados</a>
        <a class="nav-item" href="admin_view3.php"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/></svg>Búsqueda avanzada</a>
        <a class="nav-item" href="calculadora.php"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 8h6M8 5v6"/></svg>Calculadora</a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['usuario'] ?? 'U', 0, 2)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') ?></div>
            <div class="user-role"><?= htmlspecialchars($_SESSION['puesto'] ?? '') ?></div>
        </div>
    </div>
</aside>

<!-- Main -->
<div class="main-wrapper">
    <header class="topbar">
        <div class="topbar-left">
            <h1>Empleados</h1>
            <div class="breadcrumb">Administración · Gestión de personal</div>
        </div>
        <div class="topbar-right">
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M16.5 16.5L22 22"/></svg>
                <input type="text" id="globalSearch" placeholder="Buscar por nombre o ID…" oninput="filterTables()">
            </div>
            <button class="btn-icon">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 2a5 5 0 00-5 5v2l-1 2h12l-1-2V7a5 5 0 00-5-5zM6.5 13a1.5 1.5 0 003 0"/></svg>
            </button>
        </div>
    </header>

    <main class="content">
        <div class="content-header">
            <div>
                <h2>Gestión de empleados</h2>
                <p>Promotores y cobradores registrados en el sistema</p>
            </div>
            <button class="btn-primary" onclick="openModal()">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M2 7h10"/></svg>
                Dar de alta
            </button>
        </div>

        <!-- KPI row -->
        <div class="kpi-grid" style="grid-template-columns: repeat(4,1fr); margin-bottom:20px;">
            <div class="kpi-card">
                <div class="kpi-label">Total empleados</div>
                <div class="kpi-value">24</div>
                <span class="kpi-trend flat">Activos en nómina</span>
            </div>
            <div class="kpi-card green">
                <div class="kpi-label">Promotores</div>
                <div class="kpi-value">14</div>
                <span class="kpi-trend up">↑ 2 este mes</span>
            </div>
            <div class="kpi-card" style="--kpi-accent:#8b5cf6">
                <div class="kpi-label">Cobradores</div>
                <div class="kpi-value">10</div>
                <span class="kpi-trend flat">Sin cambio</span>
            </div>
            <div class="kpi-card yellow">
                <div class="kpi-label">Cap. disponible</div>
                <div class="kpi-value">$340K</div>
                <span class="kpi-trend flat">Entre todos</span>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-panel">
            <div class="filter-group">
                <label>Empleado ID</label>
                <input class="filter-input" type="text" id="filterId" placeholder="ej. E-012" oninput="filterTables()">
            </div>
            <div class="filter-divider"></div>
            <div class="filter-group">
                <label>Tipo</label>
                <div class="status-group">
                    <span class="status-pill pill-promotor" data-type="promotor" onclick="togglePill(this)">
                        <span class="dot" style="background:#8b5cf6"></span> Promotor
                    </span>
                    <span class="status-pill pill-cobrador" data-type="cobrador" onclick="togglePill(this)">
                        <span class="dot" style="background:#0891b2"></span> Cobrador
                    </span>
                </div>
            </div>
            <div class="filter-actions">
                <button class="btn-secondary" onclick="resetFilters()">Limpiar</button>
            </div>
        </div>

        <!-- Promotores table -->
        <div class="table-section" id="section-promotores">
            <div class="table-section-header">
                <div>
                    <div class="table-section-title">Promotores</div>
                    <div class="table-section-sub" id="count-promotores">Cargando…</div>
                </div>
            </div>
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Celular</th>
                            <th>Dirección</th>
                            <th>Rango</th>
                            <th>Clientes activos</th>
                            <th>Capacidad máx.</th>
                            <th>Monto ocupado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-promotores">
                        <tr data-type="promotor">
                            <td class="td-id">P-001</td>
                            <td class="td-name"><span class="initials">JR</span>Juan Reyes</td>
                            <td class="td-numeric">55 1234 5678</td>
                            <td class="td-numeric" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Av. Reforma 120, CDMX</td>
                            <td><span class="rank-badge rank-oro">Oro</span></td>
                            <td class="td-numeric">18</td>
                            <td>
                                <div class="capacity-text">$80,000</div>
                                <div class="capacity-bar"><div class="capacity-fill warn" style="width:72%"></div></div>
                            </td>
                            <td class="td-amount">$57,600</td>
                            <td><div class="td-actions">
                                <button class="action-btn edit">Editar</button>
                                <button class="action-btn">Ver</button>
                            </div></td>
                        </tr>
                        <tr data-type="promotor">
                            <td class="td-id">P-002</td>
                            <td class="td-name"><span class="initials">ML</span>María López</td>
                            <td class="td-numeric">55 8765 4321</td>
                            <td class="td-numeric" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Insurgentes 45, CDMX</td>
                            <td><span class="rank-badge rank-platino">Platino</span></td>
                            <td class="td-numeric">26</td>
                            <td>
                                <div class="capacity-text">$150,000</div>
                                <div class="capacity-bar"><div class="capacity-fill" style="width:44%"></div></div>
                            </td>
                            <td class="td-amount">$66,000</td>
                            <td><div class="td-actions">
                                <button class="action-btn edit">Editar</button>
                                <button class="action-btn">Ver</button>
                            </div></td>
                        </tr>
                        <tr data-type="promotor">
                            <td class="td-id">P-003</td>
                            <td class="td-name"><span class="initials">AV</span>Andrés Vega</td>
                            <td class="td-numeric">55 3344 5566</td>
                            <td class="td-numeric" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Tlalpan 88, CDMX</td>
                            <td><span class="rank-badge rank-plata">Plata</span></td>
                            <td class="td-numeric">9</td>
                            <td>
                                <div class="capacity-text">$40,000</div>
                                <div class="capacity-bar"><div class="capacity-fill full" style="width:95%"></div></div>
                            </td>
                            <td class="td-amount">$38,000</td>
                            <td><div class="td-actions">
                                <button class="action-btn edit">Editar</button>
                                <button class="action-btn">Ver</button>
                            </div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cobradores table -->
        <div class="table-section" id="section-cobradores">
            <div class="table-section-header">
                <div>
                    <div class="table-section-title">Cobradores</div>
                    <div class="table-section-sub" id="count-cobradores">Cargando…</div>
                </div>
            </div>
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Celular</th>
                            <th>Dirección</th>
                            <th>Rango</th>
                            <th>Clientes activos</th>
                            <th>Monto máximo</th>
                            <th>Monto ocupado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-cobradores">
                        <tr data-type="cobrador">
                            <td class="td-id">C-001</td>
                            <td class="td-name"><span class="initials" style="background:#e0f2fe;color:#0369a1">PM</span>Pedro Morales</td>
                            <td class="td-numeric">55 9988 7766</td>
                            <td class="td-numeric" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Narvarte 12, CDMX</td>
                            <td><span class="rank-badge rank-diamante">Diamante</span></td>
                            <td class="td-numeric">32</td>
                            <td class="td-amount">$200,000</td>
                            <td>
                                <div class="capacity-text">$142,000</div>
                                <div class="capacity-bar"><div class="capacity-fill warn" style="width:71%"></div></div>
                            </td>
                            <td><div class="td-actions">
                                <button class="action-btn edit">Editar</button>
                                <button class="action-btn">Ver</button>
                            </div></td>
                        </tr>
                        <tr data-type="cobrador">
                            <td class="td-id">C-002</td>
                            <td class="td-name"><span class="initials" style="background:#e0f2fe;color:#0369a1">SR</span>Sandra Ríos</td>
                            <td class="td-numeric">55 1122 3344</td>
                            <td class="td-numeric" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Polanco 77, CDMX</td>
                            <td><span class="rank-badge rank-oro">Oro</span></td>
                            <td class="td-numeric">21</td>
                            <td class="td-amount">$100,000</td>
                            <td>
                                <div class="capacity-text">$38,500</div>
                                <div class="capacity-bar"><div class="capacity-fill" style="width:38%"></div></div>
                            </td>
                            <td><div class="td-actions">
                                <button class="action-btn edit">Editar</button>
                                <button class="action-btn">Ver</button>
                            </div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- ========================
     MODAL — Registrar Empleado
========================= -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
    <div class="modal" style="width:640px">
        <div class="modal-header">
            <h3>Registrar empleado</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>

        <div class="modal-body-scroll">
            <form method="POST" action="php/registro_usuario.php" enctype="multipart/form-data">
                <div class="form-grid">

                    <!-- Datos personales -->
                    <div class="section-divider full"><span>Datos personales</span></div>

                    <div class="modal-field">
                        <label>Nombre completo</label>
                        <input type="text" name="nombre" placeholder="Nombre y apellidos" required>
                    </div>
                    <div class="modal-field">
                        <label>No. Celular</label>
                        <input type="text" name="celular" placeholder="55 1234 5678" required>
                    </div>
                    <div class="modal-field">
                        <label>No. Fijo</label>
                        <input type="text" name="fijo" placeholder="55 0000 0000">
                    </div>
                    <div class="modal-field">
                        <label>Dirección</label>
                        <input type="text" name="direccion" placeholder="Calle, número, colonia" required>
                    </div>

                    <!-- Puesto y acceso -->
                    <div class="section-divider full"><span>Puesto y acceso</span></div>

                    <div class="modal-field">
                        <label>Tipo de empleado</label>
                        <select name="puesto" required>
                            <option value="">Seleccionar…</option>
                            <option value="Promotor">Promotor</option>
                            <option value="Cobrador">Cobrador</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label>Rango</label>
                        <select name="rango" required>
                            <option value="">Seleccionar…</option>
                            <option value="Bronce">Bronce</option>
                            <option value="Plata">Plata</option>
                            <option value="Oro">Oro</option>
                            <option value="Platino">Platino</option>
                            <option value="Diamante">Diamante</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label>Usuario</label>
                        <input type="text" name="usuario" placeholder="Nombre de usuario" required>
                    </div>
                    <div class="modal-field">
                        <label>Contraseña</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <div class="modal-field">
                        <label>Capacidad máxima</label>
                        <input type="number" name="capacidad" placeholder="$0" required>
                    </div>

                    <!-- Contactos de emergencia -->
                    <div class="section-divider full"><span>Contacto de emergencia 1</span></div>

                    <div class="modal-field">
                        <label>Nombre</label>
                        <input type="text" name="contacto_nombre" placeholder="Nombre completo" required>
                    </div>
                    <div class="modal-field">
                        <label>Teléfono</label>
                        <input type="text" name="contacto_telefono" placeholder="55 0000 0000" required>
                    </div>
                    <div class="modal-field full">
                        <label>Dirección</label>
                        <input type="text" name="contacto_direccion" placeholder="Calle, número, colonia" required>
                    </div>

                    <div class="section-divider full"><span>Contacto de emergencia 2</span></div>

                    <div class="modal-field">
                        <label>Nombre</label>
                        <input type="text" name="contacto_nombre2" placeholder="Nombre completo" required>
                    </div>
                    <div class="modal-field">
                        <label>Teléfono</label>
                        <input type="text" name="contacto_telefono2" placeholder="55 0000 0000" required>
                    </div>
                    <div class="modal-field full">
                        <label>Dirección</label>
                        <input type="text" name="contacto_direccion2" placeholder="Calle, número, colonia" required>
                    </div>

                    <!-- Documentos -->
                    <div class="section-divider full"><span>Documentos</span></div>

                    <div class="file-upload-field">
                        <label>INE</label>
                        <label class="file-upload-wrap">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2M8 2v7M5 6l3 3 3-3"/></svg>
                            <span>Subir archivo…</span>
                            <input type="file" name="ine" required>
                        </label>
                    </div>
                    <div class="file-upload-field">
                        <label>Pagaré</label>
                        <label class="file-upload-wrap">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2M8 2v7M5 6l3 3 3-3"/></svg>
                            <span>Subir archivo…</span>
                            <input type="file" name="pagare" required>
                        </label>
                    </div>
                    <div class="file-upload-field">
                        <label>Contrato</label>
                        <label class="file-upload-wrap">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2M8 2v7M5 6l3 3 3-3"/></svg>
                            <span>Subir archivo…</span>
                            <input type="file" name="contrato" required>
                        </label>
                    </div>
                    <div class="file-upload-field">
                        <label>Comprobante de domicilio</label>
                        <label class="file-upload-wrap">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2M8 2v7M5 6l3 3 3-3"/></svg>
                            <span>Subir archivo…</span>
                            <input type="file" name="comprobante" required>
                        </label>
                    </div>

                    <!-- Ubicación -->
                    <div class="section-divider full"><span>Ubicación exacta</span></div>
                    <div class="modal-field full">
                        <label>Selecciona en el mapa</label>
                        <div id="map"></div>
                        <input type="hidden" name="latitud" id="latitud">
                        <input type="hidden" name="longitud" id="longitud">
                    </div>

                </div><!-- end form-grid -->

                <div class="modal-footer" style="margin:16px -22px -20px;padding:14px 22px;border-top:1px solid var(--border);background:var(--bg-hover);display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Registrar empleado</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/* ---- Filter ---- */
let showPromotor = true, showCobrador = true;

function togglePill(el) {
    const type = el.dataset.type;
    if (type === 'promotor') {
        showPromotor = !showPromotor;
        el.classList.toggle('inactive', !showPromotor);
        document.getElementById('section-promotores').style.display = showPromotor ? '' : 'none';
    } else {
        showCobrador = !showCobrador;
        el.classList.toggle('inactive', !showCobrador);
        document.getElementById('section-cobradores').style.display = showCobrador ? '' : 'none';
    }
}

function filterTables() {
    const q = document.getElementById('globalSearch').value.trim().toLowerCase();
    const id = document.getElementById('filterId').value.trim().toLowerCase();
    ['tbody-promotores', 'tbody-cobradores'].forEach(tbId => {
        let visible = 0;
        document.querySelectorAll(`#${tbId} tr`).forEach(row => {
            const text = row.textContent.toLowerCase();
            const show = (!q || text.includes(q)) && (!id || row.cells[0]?.textContent.toLowerCase().includes(id));
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const countEl = document.getElementById(tbId === 'tbody-promotores' ? 'count-promotores' : 'count-cobradores');
        countEl.textContent = `${visible} registro${visible !== 1 ? 's' : ''}`;
    });
}

function resetFilters() {
    document.getElementById('globalSearch').value = '';
    document.getElementById('filterId').value = '';
    showPromotor = true; showCobrador = true;
    document.querySelectorAll('.status-pill').forEach(p => p.classList.remove('inactive'));
    document.getElementById('section-promotores').style.display = '';
    document.getElementById('section-cobradores').style.display = '';
    filterTables();
}

/* ---- Modal ---- */
function openModal() { document.getElementById('modalOverlay').classList.add('open'); }
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
function closeModalOutside(e) { if (e.target === document.getElementById('modalOverlay')) closeModal(); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

/* ---- Map ---- */
let map, marker;
function initMap() {
    map = new google.maps.Map(document.getElementById('map'), { zoom: 14, center: { lat: 19.4326, lng: -99.1332 } });
    map.addListener('click', function(e) {
        document.getElementById('latitud').value = e.latLng.lat();
        document.getElementById('longitud').value = e.latLng.lng();
        if (marker) marker.setMap(null);
        marker = new google.maps.Marker({ position: e.latLng, map });
    });
}

window.addEventListener('load', () => { filterTables(); if (typeof google !== 'undefined') initMap(); });
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfb3MRYco1aN4yaJyXmK8jperHTMJl07E&callback=initMap" async defer></script>
</body>
</html>
