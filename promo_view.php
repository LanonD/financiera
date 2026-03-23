<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <title>PrestaCRM — Promotor</title>
</head>
<body>

<!-- Sidebar --><aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark"><svg viewBox="0 0 14 14" fill="white"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg></div>
        <span class="logo-text">PrestaCRM</span>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Principal</span>
        <a class="nav-item" href="admin_view.php"><svg viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1.5"/><rect x="9" y="1" width="6" height="6" rx="1.5"/><rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/></svg>Vista general</a>
        <a class="nav-item" href="admin_view2.php"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6"/></svg>Empleados</a>
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
            <h1>Mis préstamos</h1>
            <div class="breadcrumb">Panel de promotor · Cartera personal</div>
        </div>
        <div class="topbar-right">
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M16.5 16.5L22 22"/></svg>
                <input type="text" id="globalSearch" placeholder="Buscar por ID o nombre…" oninput="filterTable()">
            </div>
            <button class="btn-primary" onclick="openModal()">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M2 7h10"/></svg>
                Dar de alta
            </button>
        </div>
    </header>

    <main class="content">
        <div class="content-header">
            <div>
                <h2>Cartera de préstamos</h2>
                <p>Clientes registrados bajo tu cuenta</p>
            </div>
        </div>

        <!-- Capacity bar -->
        <div class="cap-bar-wrap">
            <div class="cap-ring">
                <svg viewBox="0 0 80 80">
                    <circle class="cap-ring-bg" cx="40" cy="40" r="30"/>
                    <circle class="cap-ring-fill warn" cx="40" cy="40" r="30" id="ringFill" style="stroke-dashoffset:97"/>
                </svg>
                <div class="cap-ring-label">
                    <span class="cap-ring-pct" id="ringPct">48%</span>
                    <span class="cap-ring-sub">ocupado</span>
                </div>
            </div>
            <div class="cap-stats">
                <div class="cap-stat">
                    <div class="cap-stat-label">Capacidad máxima</div>
                    <div class="cap-stat-value">$80,000</div>
                </div>
                <div class="cap-stat">
                    <div class="cap-stat-label">Monto ocupado</div>
                    <div class="cap-stat-value ocupado">$38,400</div>
                </div>
                <div class="cap-stat" style="border-right:none">
                    <div class="cap-stat-label">Monto libre</div>
                    <div class="cap-stat-value libre">$41,600</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-panel">
            <div class="filter-group">
                <label>Préstamo ID</label>
                <input class="filter-input" type="text" id="filterId" placeholder="ej. 1042" oninput="filterTable()">
            </div>
            <div class="filter-divider"></div>
            <div class="filter-group">
                <label>Estatus</label>
                <div class="status-group">
                    <span class="status-pill pill-activo" data-status="Activo" onclick="togglePill(this)">
                        <span class="dot"></span> Activo
                    </span>
                    <span class="status-pill pill-finalizado" data-status="Finalizado"
                        style="background:#f0fdf4;color:#166534;border-color:#bbf7d0"
                        onclick="togglePill(this)">
                        <span class="dot" style="background:#16a34a"></span> Finalizado
                    </span>
                </div>
            </div>
            <div class="filter-actions">
                <button class="btn-secondary" onclick="resetFilters()">Limpiar</button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-card">
            <div class="table-header">
                <div>
                    <div class="table-title">Préstamos activos</div>
                    <div class="table-count" id="tableCount">Cargando…</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Monto</th>
                        <th>Plazo</th>
                        <th>Pago</th>
                        <th>Esquema</th>
                        <th>Interés</th>
                        <th>Fecha contrato</th>
                        <th>Fecha inicio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr data-status="Activo">
                        <td class="td-id">#1042</td>
                        <td class="td-name"><span class="initials">LM</span>Laura Méndez</td>
                        <td class="td-amount">$45,000</td>
                        <td class="td-numeric">24 meses</td>
                        <td class="td-numeric">$2,100</td>
                        <td class="td-numeric">Mensual</td>
                        <td class="td-numeric">12%</td>
                        <td class="fecha-tag">12 Ene 2024</td>
                        <td class="fecha-tag">01 Feb 2024</td>
                        <td><span class="badge badge-activo"><span class="dot"></span>Activo</span></td>
                        <td><div class="td-actions">
                            <button class="action-btn edit">Editar</button>
                            <button class="action-btn">Ver</button>
                        </div></td>
                    </tr>
                    <tr data-status="Activo">
                        <td class="td-id">#1021</td>
                        <td class="td-name"><span class="initials">MG</span>Miguel García</td>
                        <td class="td-amount">$18,000</td>
                        <td class="td-numeric">12 meses</td>
                        <td class="td-numeric">$1,680</td>
                        <td class="td-numeric">Quincenal</td>
                        <td class="td-numeric">11%</td>
                        <td class="fecha-tag">03 Mar 2024</td>
                        <td class="fecha-tag">15 Mar 2024</td>
                        <td><span class="badge badge-activo"><span class="dot"></span>Activo</span></td>
                        <td><div class="td-actions">
                            <button class="action-btn edit">Editar</button>
                            <button class="action-btn">Ver</button>
                        </div></td>
                    </tr>
                    <tr data-status="Activo">
                        <td class="td-id">#1008</td>
                        <td class="td-name"><span class="initials">PH</span>Patricia Herrera</td>
                        <td class="td-amount">$22,000</td>
                        <td class="td-numeric">18 meses</td>
                        <td class="td-numeric">$1,400</td>
                        <td class="td-numeric">Mensual</td>
                        <td class="td-numeric">13%</td>
                        <td class="fecha-tag">20 Nov 2023</td>
                        <td class="fecha-tag">01 Dic 2023</td>
                        <td><span class="badge badge-activo"><span class="dot"></span>Activo</span></td>
                        <td><div class="td-actions">
                            <button class="action-btn edit">Editar</button>
                            <button class="action-btn">Ver</button>
                        </div></td>
                    </tr>
                    <tr data-status="Finalizado">
                        <td class="td-id">#0993</td>
                        <td class="td-name"><span class="initials" style="background:#f0fdf4;color:#166534">RC</span>Roberto Cruz</td>
                        <td class="td-amount">$30,000</td>
                        <td class="td-numeric">12 meses</td>
                        <td class="td-numeric">$2,750</td>
                        <td class="td-numeric">Mensual</td>
                        <td class="td-numeric">10%</td>
                        <td class="fecha-tag">05 Ene 2023</td>
                        <td class="fecha-tag">01 Feb 2023</td>
                        <td><span class="badge" style="background:#f0fdf4;color:#166534"><span class="dot" style="background:#16a34a"></span>Finalizado</span></td>
                        <td><div class="td-actions">
                            <button class="action-btn">Ver</button>
                        </div></td>
                    </tr>
                </tbody>
            </table>
            <div class="table-footer">
                <span class="pagination-info">Página 1 · <span id="totalCount">4</span> clientes en cartera</span>
                <div class="pagination-controls">
                    <button class="page-btn active">1</button>
                    <button class="page-btn">›</button>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- MODAL — Registrar cliente -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
    <div class="modal" style="width:640px">
        <div class="modal-header">
            <h3>Registrar cliente</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body-scroll">
            <form method="POST" action="php/registro_clientes.php" enctype="multipart/form-data">
                <div class="form-grid">

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
                    <div class="modal-field">
                        <label>CURP</label>
                        <input type="text" name="curp" placeholder="CURP de 18 caracteres" required>
                    </div>
                    <div class="modal-field">
                        <label>Ocupación</label>
                        <select name="ocupacion" required>
                            <option value="">Seleccionar…</option>
                            <option value="Empleado">Empleado</option>
                            <option value="Negocio propio">Negocio propio</option>
                        </select>
                    </div>

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
                        <input type="text" name="contacto_direccion2" required>
                    </div>

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

                    <div class="section-divider full"><span>Ubicación exacta</span></div>
                    <div class="modal-field full">
                        <label>Selecciona en el mapa</label>
                        <div id="map"></div>
                        <input type="hidden" name="latitud" id="latitud">
                        <input type="hidden" name="longitud" id="longitud">
                    </div>

                </div>

                <div class="modal-footer" style="margin:16px -22px -20px;padding:14px 22px;border-top:1px solid var(--border);background:var(--bg-hover);display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Registrar cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/* ---- Filter ---- */
let activeFilters = new Set(['Activo', 'Finalizado']);

function togglePill(el) {
    const s = el.dataset.status;
    if (activeFilters.has(s)) { activeFilters.delete(s); el.classList.add('inactive'); }
    else { activeFilters.add(s); el.classList.remove('inactive'); }
    filterTable();
}

function filterTable() {
    const id = document.getElementById('filterId').value.trim().toLowerCase();
    const q  = document.getElementById('globalSearch').value.trim().toLowerCase();
    let visible = 0;
    document.querySelectorAll('#tableBody tr').forEach(row => {
        const show = activeFilters.has(row.dataset.status)
            && (!id || row.cells[0]?.textContent.toLowerCase().includes(id))
            && (!q  || row.textContent.toLowerCase().includes(q));
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('tableCount').textContent = `${visible} cliente${visible !== 1 ? 's' : ''} visibles`;
    document.getElementById('totalCount').textContent = visible;
}

function resetFilters() {
    document.getElementById('filterId').value = '';
    document.getElementById('globalSearch').value = '';
    activeFilters = new Set(['Activo', 'Finalizado']);
    document.querySelectorAll('.status-pill').forEach(p => p.classList.remove('inactive'));
    filterTable();
}

/* ---- Modal ---- */
function openModal()  { document.getElementById('modalOverlay').classList.add('open'); }
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

window.addEventListener('load', filterTable);
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfb3MRYco1aN4yaJyXmK8jperHTMJl07E&callback=initMap" async defer></script>
</body>
</html>
