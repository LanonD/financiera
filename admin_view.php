<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <title>PrestaCRM — Vista general</title>
</head>
<body>

<!-- ========================
     SIDEBAR
========================= -->
<?php session_start(); ?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark"><svg viewBox="0 0 14 14" fill="white"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg></div>
        <span class="logo-text">PrestaCRM</span>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Principal</span>
        <a class="nav-item active" href="admin_view.php"><svg viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1.5"/><rect x="9" y="1" width="6" height="6" rx="1.5"/><rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/></svg>Vista general</a>
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

<!-- ========================
     MAIN
========================= -->
<div class="main-wrapper">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <h1>Vista general</h1>
            <div class="breadcrumb">Préstamos · Todos los registros</div>
        </div>
        <div class="topbar-right">
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="7"/><path d="M16.5 16.5L22 22"/>
                </svg>
                <input type="text" id="globalSearch" placeholder="Buscar por nombre o ID…" oninput="filterTable()">
            </div>
            <button class="btn-icon" title="Notificaciones" onclick="alert('Sin notificaciones nuevas')">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 2a5 5 0 00-5 5v2l-1 2h12l-1-2V7a5 5 0 00-5-5zM6.5 13a1.5 1.5 0 003 0"/></svg>
            </button>
            <button class="btn-icon" title="Exportar" onclick="alert('Exportando datos…')">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2M8 2v7M5 6l3 3 3-3"/></svg>
            </button>
        </div>
    </header>

    <!-- Content -->
    <main class="content">

        <!-- Page header -->
        <div class="content-header">
            <div>
                <h2>Cartera de préstamos</h2>
                <p>Gestión y seguimiento de todos los créditos activos</p>
            </div>
            <button class="btn-primary" onclick="openModal()">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M7 2v10M2 7h10"/>
                </svg>
                Nuevo préstamo
            </button>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="4" width="12" height="9" rx="1.5"/><path d="M5 4V3a1 1 0 011-1h4a1 1 0 011 1v1"/><circle cx="8" cy="8.5" r="1.5"/></svg>
                </div>
                <div class="kpi-label">Cartera total</div>
                <div class="kpi-value">$1.4M</div>
                <span class="kpi-trend up">↑ 5.3% vs mes anterior</span>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l3 3 7-7"/></svg>
                </div>
                <div class="kpi-label">Activos</div>
                <div class="kpi-value">142</div>
                <span class="kpi-trend up">↑ 12 nuevos este mes</span>
            </div>
            <div class="kpi-card yellow">
                <div class="kpi-icon">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="6"/><path d="M8 5v3M8 11v.5"/></svg>
                </div>
                <div class="kpi-label">Pendientes</div>
                <div class="kpi-value">31</div>
                <span class="kpi-trend flat">Sin cambio</span>
            </div>
            <div class="kpi-card red">
                <div class="kpi-icon">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2L2 14h12L8 2z"/><path d="M8 7v3M8 12v.5"/></svg>
                </div>
                <div class="kpi-label">Atrasados</div>
                <div class="kpi-value">18</div>
                <span class="kpi-trend down">↑ 3 vs semana pasada</span>
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
                    <span class="status-pill pill-pendiente" data-status="Pendiente" onclick="togglePill(this)">
                        <span class="dot"></span> Pendiente
                    </span>
                    <span class="status-pill pill-atrasado" data-status="Atrasado" onclick="togglePill(this)">
                        <span class="dot"></span> Atrasado
                    </span>
                </div>
            </div>

            <div class="filter-actions">
                <button class="btn-secondary" onclick="resetFilters()">Limpiar</button>
                <button class="btn-primary" onclick="filterTable()">
                    <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="6" cy="6" r="4.5"/><path d="M10.5 10.5L13 13"/></svg>
                    Buscar
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-card">
            <div class="table-header">
                <div>
                    <div class="table-title">Todos los préstamos</div>
                    <div class="table-count" id="tableCount">Mostrando 8 registros</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="sortable" onclick="sortTable(0)">ID ↕</th>
                        <th>Nombre</th>
                        <th class="sortable" onclick="sortTable(2)">Monto ↕</th>
                        <th>Plazo</th>
                        <th>Pago</th>
                        <th>Esquema</th>
                        <th>Interés</th>
                        <th class="sortable" onclick="sortTable(7)">Saldo actual ↕</th>
                        <th>Estatus</th>
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
                        <td class="td-amount">$38,200</td>
                        <td><span class="badge badge-activo"><span class="dot"></span>Activo</span></td>
                        <td><div class="td-actions">
                            <button class="action-btn edit" onclick="openModal('#1042','Laura Méndez','45000','24','Mensual','12','38200','Activo')">Editar</button>
                            <button class="action-btn" onclick="alert('Perfil de Laura Méndez')">Ver</button>
                        </div></td>
                    </tr>
                    <tr data-status="Pendiente">
                        <td class="td-id">#1038</td>
                        <td class="td-name"><span class="initials" style="background:#fef9c3;color:#854d0e">CR</span>Carlos Rivas</td>
                        <td class="td-amount">$80,000</td>
                        <td class="td-numeric">36 meses</td>
                        <td class="td-numeric">$2,800</td>
                        <td class="td-numeric">Quincenal</td>
                        <td class="td-numeric">10%</td>
                        <td class="td-amount">$71,500</td>
                        <td><span class="badge badge-pendiente"><span class="dot"></span>Pendiente</span></td>
                        <td><div class="td-actions">
                            <button class="action-btn edit" onclick="openModal('#1038','Carlos Rivas','80000','36','Quincenal','10','71500','Pendiente')">Editar</button>
                            <button class="action-btn">Ver</button>
                        </div></td>
                    </tr>
                    <tr data-status="Atrasado">
                        <td class="td-id">#1029</td>
                        <td class="td-name"><span class="initials" style="background:#fee2e2;color:#991b1b">AT</span>Ana Torres</td>
                        <td class="td-amount">$22,000</td>
                        <td class="td-numeric">12 meses</td>
                        <td class="td-numeric">$2,050</td>
                        <td class="td-numeric">Mensual</td>
                        <td class="td-numeric">15%</td>
                        <td class="td-amount">$5,100</td>
                        <td><span class="badge badge-atrasado"><span class="dot"></span>Atrasado</span></td>
                        <td><div class="td-actions">
                            <button class="action-btn edit" onclick="openModal('#1029','Ana Torres','22000','12','Mensual','15','5100','Atrasado')">Editar</button>
                            <button class="action-btn">Ver</button>
                        </div></td>
                    </tr>
                    <tr data-status="Activo">
                        <td class="td-id">#1021</td>
                        <td class="td-name"><span class="initials">MG</span>Miguel García</td>
                        <td class="td-amount">$60,000</td>
                        <td class="td-numeric">30 meses</td>
                        <td class="td-numeric">$2,500</td>
                        <td class="td-numeric">Quincenal</td>
                        <td class="td-numeric">11%</td>
                        <td class="td-amount">$48,300</td>
                        <td><span class="badge badge-activo"><span class="dot"></span>Activo</span></td>
                        <td><div class="td-actions">
                            <button class="action-btn edit">Editar</button>
                            <button class="action-btn">Ver</button>
                        </div></td>
                    </tr>
                    <tr data-status="Activo">
                        <td class="td-id">#1018</td>
                        <td class="td-name"><span class="initials">SR</span>Sofía Ramírez</td>
                        <td class="td-amount">$35,000</td>
                        <td class="td-numeric">18 meses</td>
                        <td class="td-numeric">$2,200</td>
                        <td class="td-numeric">Mensual</td>
                        <td class="td-numeric">13%</td>
                        <td class="td-amount">$21,000</td>
                        <td><span class="badge badge-activo"><span class="dot"></span>Activo</span></td>
                        <td><div class="td-actions">
                            <button class="action-btn edit">Editar</button>
                            <button class="action-btn">Ver</button>
                        </div></td>
                    </tr>
                    <tr data-status="Pendiente">
                        <td class="td-id">#1011</td>
                        <td class="td-name"><span class="initials" style="background:#fef9c3;color:#854d0e">JL</span>Jorge López</td>
                        <td class="td-amount">$120,000</td>
                        <td class="td-numeric">48 meses</td>
                        <td class="td-numeric">$3,100</td>
                        <td class="td-numeric">Mensual</td>
                        <td class="td-numeric">9%</td>
                        <td class="td-amount">$115,200</td>
                        <td><span class="badge badge-pendiente"><span class="dot"></span>Pendiente</span></td>
                        <td><div class="td-actions">
                            <button class="action-btn edit">Editar</button>
                            <button class="action-btn">Ver</button>
                        </div></td>
                    </tr>
                    <tr data-status="Atrasado">
                        <td class="td-id">#1007</td>
                        <td class="td-name"><span class="initials" style="background:#fee2e2;color:#991b1b">PH</span>Patricia Herrera</td>
                        <td class="td-amount">$18,000</td>
                        <td class="td-numeric">12 meses</td>
                        <td class="td-numeric">$1,650</td>
                        <td class="td-numeric">Quincenal</td>
                        <td class="td-numeric">16%</td>
                        <td class="td-amount">$9,800</td>
                        <td><span class="badge badge-atrasado"><span class="dot"></span>Atrasado</span></td>
                        <td><div class="td-actions">
                            <button class="action-btn edit">Editar</button>
                            <button class="action-btn">Ver</button>
                        </div></td>
                    </tr>
                    <tr data-status="Activo">
                        <td class="td-id">#1002</td>
                        <td class="td-name"><span class="initials">RC</span>Roberto Cruz</td>
                        <td class="td-amount">$95,000</td>
                        <td class="td-numeric">36 meses</td>
                        <td class="td-numeric">$3,400</td>
                        <td class="td-numeric">Mensual</td>
                        <td class="td-numeric">10.5%</td>
                        <td class="td-amount">$62,000</td>
                        <td><span class="badge badge-activo"><span class="dot"></span>Activo</span></td>
                        <td><div class="td-actions">
                            <button class="action-btn edit">Editar</button>
                            <button class="action-btn">Ver</button>
                        </div></td>
                    </tr>
                </tbody>
            </table>

            <div class="table-footer">
                <span class="pagination-info">Página 1 de 12 · 191 registros totales</span>
                <div class="pagination-controls">
                    <button class="page-btn">‹</button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <button class="page-btn">…</button>
                    <button class="page-btn">12</button>
                    <button class="page-btn">›</button>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- ========================
     MODAL
========================= -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Nuevo préstamo</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="modal-field">
                <label>Nombre del cliente</label>
                <input type="text" id="mNombre" placeholder="Nombre completo">
            </div>
            <div class="modal-field">
                <label>Préstamo ID</label>
                <input type="text" id="mId" placeholder="ej. #1043" readonly>
            </div>
            <div class="modal-field">
                <label>Monto</label>
                <input type="number" id="mMonto" placeholder="$0.00">
            </div>
            <div class="modal-field">
                <label>Plazo (meses)</label>
                <input type="number" id="mPlazo" placeholder="ej. 24">
            </div>
            <div class="modal-field">
                <label>Esquema de pago</label>
                <select id="mEsquema">
                    <option value="">Seleccionar…</option>
                    <option value="Mensual">Mensual</option>
                    <option value="Quincenal">Quincenal</option>
                    <option value="Semanal">Semanal</option>
                </select>
            </div>
            <div class="modal-field">
                <label>Tasa de interés (%)</label>
                <input type="number" id="mInteres" placeholder="ej. 12">
            </div>
            <div class="modal-field">
                <label>Saldo actual</label>
                <input type="number" id="mSaldo" placeholder="$0.00">
            </div>
            <div class="modal-field">
                <label>Estatus</label>
                <select id="mEstatus">
                    <option value="Activo">Activo</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Atrasado">Atrasado</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal()">Cancelar</button>
            <button class="btn-primary" onclick="saveModal()">Guardar préstamo</button>
        </div>
    </div>
</div>

<script>
    /* ---- Filter & Search ---- */
    let activeFilters = new Set(['Activo', 'Pendiente', 'Atrasado']);

    function togglePill(el) {
        const status = el.dataset.status;
        if (activeFilters.has(status)) {
            activeFilters.delete(status);
            el.classList.add('inactive');
        } else {
            activeFilters.add(status);
            el.classList.remove('inactive');
        }
        filterTable();
    }

    function filterTable() {
        const idVal   = document.getElementById('filterId').value.trim().toLowerCase();
        const search  = document.getElementById('globalSearch').value.trim().toLowerCase();
        const rows    = document.querySelectorAll('#tableBody tr');
        let visible   = 0;

        rows.forEach(row => {
            const rowStatus = row.dataset.status;
            const id        = row.cells[0].textContent.toLowerCase();
            const name      = row.cells[1].textContent.toLowerCase();
            const matchFilter = activeFilters.size === 0 || activeFilters.has(rowStatus);
            const matchId     = !idVal   || id.includes(idVal);
            const matchSearch = !search  || id.includes(search) || name.includes(search);

            if (matchFilter && matchId && matchSearch) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('tableCount').textContent =
            `Mostrando ${visible} registro${visible !== 1 ? 's' : ''}`;
    }

    function resetFilters() {
        document.getElementById('filterId').value = '';
        document.getElementById('globalSearch').value = '';
        activeFilters = new Set(['Activo', 'Pendiente', 'Atrasado']);
        document.querySelectorAll('.status-pill').forEach(p => p.classList.remove('inactive'));
        filterTable();
    }

    /* ---- Sort ---- */
    let sortDir = {};
    function sortTable(col) {
        const tbody = document.getElementById('tableBody');
        const rows  = Array.from(tbody.querySelectorAll('tr'));
        sortDir[col] = !sortDir[col];

        rows.sort((a, b) => {
            let av = a.cells[col].textContent.replace(/[$,%]/g,'').trim();
            let bv = b.cells[col].textContent.replace(/[$,%]/g,'').trim();
            const an = parseFloat(av), bn = parseFloat(bv);
            if (!isNaN(an) && !isNaN(bn)) return sortDir[col] ? an - bn : bn - an;
            return sortDir[col] ? av.localeCompare(bv) : bv.localeCompare(av);
        });
        rows.forEach(r => tbody.appendChild(r));
    }

    /* ---- Modal ---- */
    function openModal(id='', name='', monto='', plazo='', esquema='', interes='', saldo='', estatus='Activo') {
        document.getElementById('modalTitle').textContent = id ? `Editar préstamo ${id}` : 'Nuevo préstamo';
        document.getElementById('mId').value       = id;
        document.getElementById('mNombre').value   = name;
        document.getElementById('mMonto').value    = monto;
        document.getElementById('mPlazo').value    = plazo;
        document.getElementById('mEsquema').value  = esquema;
        document.getElementById('mInteres').value  = interes;
        document.getElementById('mSaldo').value    = saldo;
        document.getElementById('mEstatus').value  = estatus;
        document.getElementById('modalOverlay').classList.add('open');
    }

    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('open');
    }

    function closeModalOutside(e) {
        if (e.target === document.getElementById('modalOverlay')) closeModal();
    }

    function saveModal() {
        const name = document.getElementById('mNombre').value;
        if (!name) { alert('Por favor ingresa el nombre del cliente.'); return; }
        alert(`Préstamo de "${name}" guardado correctamente.`);
        closeModal();
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
</body>
</html>


