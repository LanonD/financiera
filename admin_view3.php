<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <title>PrestaCRM — Búsqueda</title>
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
        <a class="nav-item" href="admin_view2.php"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6"/></svg>Empleados</a>
        <a class="nav-item active" href="admin_view3.php"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/></svg>Búsqueda avanzada</a>
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
            <h1>Búsqueda avanzada</h1>
            <div class="breadcrumb">Administración · Consulta de registros</div>
        </div>
    </header>

    <main class="content">
        <div class="content-header" style="margin-bottom:20px;">
            <div>
                <h2>Búsqueda personalizada</h2>
                <p>Consulta clientes y empleados por cualquier campo</p>
            </div>
        </div>

        <div class="search-layout">

            <!-- Left: search inputs -->
            <div class="search-panel">
                <div class="search-panel-header">
                    <div class="search-panel-title">Parámetros de búsqueda</div>
                </div>
                <div class="search-panel-body">

                    <span class="search-section-label">Clientes</span>

                    <div class="search-field">
                        <label>Préstamo ID</label>
                        <input type="text" id="id_prestamo" placeholder="ej. #1042" oninput="runSearch()">
                    </div>
                    <div class="search-field">
                        <label>Nombre</label>
                        <input type="text" id="c_name" placeholder="Nombre completo" oninput="runSearch()">
                    </div>
                    <div class="search-field">
                        <label>Celular</label>
                        <input type="text" id="c_phone" placeholder="55 0000 0000" oninput="runSearch()">
                    </div>
                    <div class="search-field">
                        <label>Cliente ID</label>
                        <input type="text" id="c_id" placeholder="ej. CLI-004" oninput="runSearch()">
                    </div>
                    <div class="search-field">
                        <label>Dirección</label>
                        <input type="text" id="c_address" placeholder="Calle, colonia…" oninput="runSearch()">
                    </div>

                    <span class="search-section-label">Empleados</span>

                    <div class="search-field">
                        <label>Nombre</label>
                        <input type="text" id="e_name" placeholder="Nombre completo" oninput="runSearch()">
                    </div>
                    <div class="search-field">
                        <label>Celular</label>
                        <input type="text" id="e_phone" placeholder="55 0000 0000" oninput="runSearch()">
                    </div>
                    <div class="search-field">
                        <label>Empleado ID</label>
                        <input type="text" id="e_id" placeholder="ej. P-001" oninput="runSearch()">
                    </div>
                    <div class="search-field">
                        <label>Dirección</label>
                        <input type="text" id="e_address" placeholder="Calle, colonia…" oninput="runSearch()">
                    </div>

                </div>
                <div class="search-panel-footer">
                    <button class="btn-primary" style="flex:1" onclick="runSearch()">
                        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="6" cy="6" r="4.5"/><path d="M10.5 10.5L13 13"/></svg>
                        Buscar
                    </button>
                    <button class="btn-secondary" onclick="clearSearch()">Limpiar</button>
                </div>
            </div>

            <!-- Right: results -->
            <div class="results-area" id="resultsArea">

                <!-- Empty state -->
                <div class="result-empty" id="emptyState">
                    <div class="result-empty-icon">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="9" cy="9" r="6.5"/><path d="M14 14L18 18"/></svg>
                    </div>
                    <h3>Sin resultados aún</h3>
                    <p>Ingresa uno o más criterios en el panel izquierdo para comenzar la búsqueda.</p>
                </div>

                <!-- Cliente result card -->
                <div class="result-card" id="clienteCard">
                    <div class="result-card-header">
                        <div class="result-card-type">
                            <span class="result-type-badge type-cliente">Cliente</span>
                            <span class="result-card-name" id="rc-nombre">—</span>
                        </div>
                        <div class="result-card-actions">
                            <button class="action-btn">Contrato</button>
                            <button class="action-btn">Historial</button>
                            <button class="action-btn edit">Editar</button>
                        </div>
                    </div>
                    <div class="result-card-body">
                        <div class="result-grid">
                            <div class="result-field">
                                <div class="result-field-label">Préstamo ID</div>
                                <div class="result-field-value mono accent" id="rc-prestamo">—</div>
                            </div>
                            <div class="result-field">
                                <div class="result-field-label">Cliente ID</div>
                                <div class="result-field-value mono" id="rc-cliente-id">—</div>
                            </div>
                            <div class="result-field">
                                <div class="result-field-label">Estatus</div>
                                <div class="result-field-value" id="rc-estatus">—</div>
                            </div>
                            <div class="result-field">
                                <div class="result-field-label">Atraso</div>
                                <div class="result-field-value down" id="rc-atraso">—</div>
                            </div>
                            <div class="result-field">
                                <div class="result-field-label">Balance total</div>
                                <div class="result-field-value mono" id="rc-balance">—</div>
                            </div>
                            <div class="result-field">
                                <div class="result-field-label">Tasa anual</div>
                                <div class="result-field-value mono" id="rc-tasa">—</div>
                            </div>
                            <div class="result-field">
                                <div class="result-field-label">Principal</div>
                                <div class="result-field-value mono" id="rc-principal">—</div>
                            </div>
                            <div class="result-field">
                                <div class="result-field-label">Interés diario</div>
                                <div class="result-field-value mono" id="rc-interes">—</div>
                            </div>
                            <div class="result-field">
                                <div class="result-field-label">Total pagado</div>
                                <div class="result-field-value mono up" id="rc-pagado">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empleado result card -->
                <div class="result-card" id="empleadoCard">
                    <div class="result-card-header">
                        <div class="result-card-type">
                            <span class="result-type-badge type-empleado">Empleado</span>
                            <span class="result-card-name" id="re-nombre">—</span>
                        </div>
                        <div class="result-card-actions">
                            <button class="action-btn">Contrato</button>
                            <button class="action-btn edit">Editar</button>
                        </div>
                    </div>
                    <div class="result-card-body">
                        <div class="result-grid">
                            <div class="result-field">
                                <div class="result-field-label">Empleado ID</div>
                                <div class="result-field-value mono accent" id="re-id">—</div>
                            </div>
                            <div class="result-field">
                                <div class="result-field-label">Rango</div>
                                <div class="result-field-value" id="re-rango">—</div>
                            </div>
                            <div class="result-field">
                                <div class="result-field-label">Fecha contratación</div>
                                <div class="result-field-value mono" id="re-fecha">—</div>
                            </div>
                            <div class="result-field">
                                <div class="result-field-label">Clientes activos</div>
                                <div class="result-field-value" id="re-clientes">—</div>
                            </div>
                            <div class="result-field">
                                <div class="result-field-label">Tipo</div>
                                <div class="result-field-value" id="re-tipo">—</div>
                            </div>
                            <div class="result-field">
                                <div class="result-field-label">Monto ocupado</div>
                                <div class="result-field-value mono" id="re-monto">—</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- end results-area -->
        </div>

    </main>
</div>

<script>
function hasInput() {
    const ids = ['id_prestamo','c_name','c_phone','c_id','c_address','e_name','e_phone','e_id','e_address'];
    return ids.some(id => document.getElementById(id).value.trim() !== '');
}

function runSearch() {
    const hasAny = hasInput();
    document.getElementById('emptyState').style.display = hasAny ? 'none' : 'block';

    const clienteInputs = ['id_prestamo','c_name','c_phone','c_id','c_address'].some(id => document.getElementById(id).value.trim());
    const empleadoInputs = ['e_name','e_phone','e_id','e_address'].some(id => document.getElementById(id).value.trim());

    // Simulate results (in real app these come from server)
    if (clienteInputs) {
        document.getElementById('clienteCard').classList.add('visible');
        document.getElementById('rc-nombre').textContent   = 'Laura Méndez';
        document.getElementById('rc-prestamo').textContent = '#1042';
        document.getElementById('rc-cliente-id').textContent = 'CLI-018';
        document.getElementById('rc-estatus').textContent  = 'Activo';
        document.getElementById('rc-atraso').textContent   = '0 días';
        document.getElementById('rc-balance').textContent  = '$38,200';
        document.getElementById('rc-tasa').textContent     = '12%';
        document.getElementById('rc-principal').textContent= '$45,000';
        document.getElementById('rc-interes').textContent  = '$14.79';
        document.getElementById('rc-pagado').textContent   = '$6,800';
    } else {
        document.getElementById('clienteCard').classList.remove('visible');
    }

    if (empleadoInputs) {
        document.getElementById('empleadoCard').classList.add('visible');
        document.getElementById('re-nombre').textContent  = 'Juan Reyes';
        document.getElementById('re-id').textContent      = 'P-001';
        document.getElementById('re-rango').textContent   = 'Oro';
        document.getElementById('re-fecha').textContent   = '2022-03-15';
        document.getElementById('re-clientes').textContent= '18';
        document.getElementById('re-tipo').textContent    = 'Promotor';
        document.getElementById('re-monto').textContent   = '$57,600';
    } else {
        document.getElementById('empleadoCard').classList.remove('visible');
    }
}

function clearSearch() {
    ['id_prestamo','c_name','c_phone','c_id','c_address','e_name','e_phone','e_id','e_address']
        .forEach(id => document.getElementById(id).value = '');
    document.getElementById('emptyState').style.display = 'block';
    document.getElementById('clienteCard').classList.remove('visible');
    document.getElementById('empleadoCard').classList.remove('visible');
}
</script>
</body>
</html>
