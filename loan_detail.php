<?php
session_start();

// ── Por ahora datos de ejemplo — el backend los reemplazará con query real ──
// El ID llegará por GET: prestamo_detalle.php?id=1042
$id = $_GET['id'] ?? '1042';

// Datos mock — reemplazar con: $stmt = $conn->prepare("SELECT ... FROM prestamos WHERE id = ?");
$prestamo = [
    'id'            => $id,
    'nombre'        => 'Laura Méndez',
    'monto'         => 45000,
    'plazo'         => 24,
    'pago'          => 2100,
    'esquema'       => 'Mensual',
    'interes'       => 12,
    'saldo_actual'  => 38200,
    'fecha_inicio'  => '2024-02-01',
    'fecha_fin'     => '2026-02-01',
    'estatus'       => 'Activo',
    'promotor'      => 'Juan Reyes',
    'cobrador'      => 'Pedro Morales',
    // Cliente
    'celular'       => '55 1234 5678',
    'direccion'     => 'Av. Reforma 120, CDMX',
    'curp'          => 'MELJ900101MDFRN01',
    'ocupacion'     => 'Empleado',
    // Contactos
    'contacto1_nombre'   => 'Roberto Méndez',
    'contacto1_telefono' => '55 9988 7766',
    'contacto2_nombre'   => 'Ana Flores',
    'contacto2_telefono' => '55 5544 3322',
    // Tabla de pagos mock
    'pagos' => [
        ['num'=>1,'fecha'=>'2024-03-01','cuota'=>2100,'interes'=>450,'capital'=>1650,'saldo'=>36550,'estatus'=>'Pagado'],
        ['num'=>2,'fecha'=>'2024-04-01','cuota'=>2100,'interes'=>433,'capital'=>1667,'saldo'=>34883,'estatus'=>'Pagado'],
        ['num'=>3,'fecha'=>'2024-05-01','cuota'=>2100,'interes'=>415,'capital'=>1685,'saldo'=>33198,'estatus'=>'Pagado'],
        ['num'=>4,'fecha'=>'2024-06-01','cuota'=>2100,'interes'=>398,'capital'=>1702,'saldo'=>31496,'estatus'=>'Pendiente'],
        ['num'=>5,'fecha'=>'2024-07-01','cuota'=>2100,'interes'=>380,'capital'=>1720,'saldo'=>29776,'estatus'=>'Pendiente'],
    ],
];

// Helpers
function money($v) { return '$' . number_format($v, 2, '.', ','); }

$pct_pagado = round((($prestamo['monto'] - $prestamo['saldo_actual']) / $prestamo['monto']) * 100);

$badge_colors = [
    'Activo'    => 'badge-activo',
    'Pendiente' => 'badge-pendiente',
    'Atrasado'  => 'badge-atrasado',
    'Finalizado'=> 'badge-finalizado',
];
$pago_badge = [
    'Pagado'    => ['bg'=>'#dcfce7','color'=>'#166534'],
    'Pendiente' => ['bg'=>'#fef9c3','color'=>'#854d0e'],
    'Atrasado'  => ['bg'=>'#fee2e2','color'=>'#991b1b'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <title>PrestaCRM — Préstamo #<?= htmlspecialchars($id) ?></title>
    <style>
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .detail-card-header {
            padding: 13px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-card-header h3 {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .detail-card-header svg {
            width: 15px;
            height: 15px;
            color: var(--accent);
            fill: none;
            stroke: currentColor;
            stroke-width: 1.5;
        }

        .detail-card-body {
            padding: 16px 18px;
        }

        .field-row {
            display: grid;
            grid-template-columns: 160px 1fr;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
            align-items: center;
        }

        .field-row:last-child { border-bottom: none; }

        .field-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
        }

        .field-value {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .field-value.mono {
            font-family: var(--font-mono);
            font-size: 12px;
        }

        .field-value.large {
            font-size: 20px;
            font-weight: 600;
            font-family: var(--font-mono);
            letter-spacing: -0.02em;
            color: var(--accent);
        }

        /* Progress bar */
        .progress-wrap {
            margin: 16px 18px;
            padding: 14px 16px;
            background: var(--bg-hover);
            border-radius: var(--radius-sm);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .progress-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
        }

        .progress-pct {
            font-size: 12px;
            font-weight: 600;
            font-family: var(--font-mono);
            color: var(--accent);
        }

        .progress-bar {
            height: 8px;
            background: var(--bg-input);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: var(--accent);
            transition: width 0.6s ease;
        }

        .progress-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 6px;
        }

        .progress-meta span {
            font-size: 11px;
            color: var(--text-muted);
            font-family: var(--font-mono);
        }

        /* Stats row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }

        /* Badge estatus pago */
        .pago-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }

        /* Back button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.15s;
            margin-bottom: 20px;
        }

        .back-btn:hover { color: var(--text-primary); }

        .back-btn svg {
            width: 14px;
            height: 14px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
        }

        .badge-finalizado { background: #f0fdf4; color: #166534; }

        /* Amort table inside card */
        .amort-table td { font-family: var(--font-mono); font-size: 12px; }
        .amort-table thead tr { background: var(--bg-hover); }

        @media (max-width: 900px) {
            .detail-grid { grid-template-columns: 1fr; }
            .stats-row   { grid-template-columns: repeat(2, 1fr); }
        }

        @media print {
            .sidebar, .topbar, .back-btn, .td-actions { display: none !important; }
            .main-wrapper { margin-left: 0 !important; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark">
            <svg viewBox="0 0 14 14" fill="white"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg>
        </div>
        <span class="logo-text">PrestaCRM</span>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Principal</span>
        <a class="nav-item active" href="admin_view.php">
            <svg viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1.5"/><rect x="9" y="1" width="6" height="6" rx="1.5"/><rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/></svg>
            Vista general
        </a>
        <a class="nav-item" href="admin_view2.php">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6"/></svg>
            Empleados
        </a>
        <a class="nav-item" href="admin_view3.php">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/></svg>
            Búsqueda avanzada
        </a>
        <span class="nav-section-label">Herramientas</span>
        <a class="nav-item" href="calculadora.php">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 8h6M8 5v6"/></svg>
            Calculadora
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['usuario'] ?? 'U', 0, 2)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') ?></div>
            <div class="user-role"><?= htmlspecialchars($_SESSION['puesto'] ?? '') ?></div>
        </div>
    </div>
</aside>

<!-- MAIN -->
<div class="main-wrapper">

    <header class="topbar">
        <div class="topbar-left">
            <h1>Préstamo #<?= htmlspecialchars($id) ?></h1>
            <div class="breadcrumb">Vista general · Detalle de préstamo</div>
        </div>
        <div class="topbar-right">
            <button class="btn-secondary" onclick="window.print()">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M4 6V2h8v4M4 12H2V7h12v5h-2M4 10h8v4H4v-4z"/></svg>
                Imprimir
            </button>
            <button class="btn-primary" onclick="alert('Editar préstamo #<?= $id ?>')">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M9.5 1.5l3 3L4 13H1v-3L9.5 1.5z"/></svg>
                Editar préstamo
            </button>
        </div>
    </header>

    <main class="content">

        <!-- Back -->
        <a href="admin_view.php" class="back-btn">
            <svg viewBox="0 0 14 14"><path d="M9 2L4 7l5 5"/></svg>
            Volver a Vista general
        </a>

        <!-- KPI Cards -->
        <div class="stats-row">
            <div class="kpi-card">
                <div class="kpi-label">Monto original</div>
                <div class="kpi-value"><?= money($prestamo['monto']) ?></div>
                <span class="kpi-trend flat"><?= $prestamo['plazo'] ?> meses · <?= $prestamo['esquema'] ?></span>
            </div>
            <div class="kpi-card green">
                <div class="kpi-label">Saldo actual</div>
                <div class="kpi-value"><?= money($prestamo['saldo_actual']) ?></div>
                <span class="kpi-trend up"><?= $pct_pagado ?>% pagado</span>
            </div>
            <div class="kpi-card yellow">
                <div class="kpi-label">Cuota</div>
                <div class="kpi-value"><?= money($prestamo['pago']) ?></div>
                <span class="kpi-trend flat"><?= $prestamo['esquema'] ?> · <?= $prestamo['interes'] ?>% interés</span>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Estatus</div>
                <div class="kpi-value" style="font-size:18px">
                    <span class="badge <?= $badge_colors[$prestamo['estatus']] ?? '' ?>">
                        <span class="dot"></span>
                        <?= $prestamo['estatus'] ?>
                    </span>
                </div>
                <span class="kpi-trend flat">Inicio: <?= $prestamo['fecha_inicio'] ?></span>
            </div>
        </div>

        <!-- Progress bar -->
        <div class="detail-card" style="margin-bottom:20px">
            <div class="progress-wrap" style="margin:0;border-radius:0">
                <div class="progress-header">
                    <span class="progress-label">Progreso de pago</span>
                    <span class="progress-pct"><?= $pct_pagado ?>% completado</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $pct_pagado ?>%"></div>
                </div>
                <div class="progress-meta">
                    <span>Pagado: <?= money($prestamo['monto'] - $prestamo['saldo_actual']) ?></span>
                    <span>Restante: <?= money($prestamo['saldo_actual']) ?></span>
                    <span>Total: <?= money($prestamo['monto']) ?></span>
                </div>
            </div>
        </div>

        <!-- Detail grid: préstamo info + cliente info -->
        <div class="detail-grid">

            <!-- Datos del préstamo -->
            <div class="detail-card">
                <div class="detail-card-header">
                    <svg viewBox="0 0 16 16"><rect x="2" y="3" width="12" height="10" rx="1.5"/><path d="M5 7h6M5 10h4"/></svg>
                    <h3>Datos del préstamo</h3>
                </div>
                <div class="detail-card-body">
                    <div class="field-row">
                        <span class="field-label">Préstamo ID</span>
                        <span class="field-value mono">#<?= htmlspecialchars($prestamo['id']) ?></span>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Monto</span>
                        <span class="field-value mono"><?= money($prestamo['monto']) ?></span>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Plazo</span>
                        <span class="field-value mono"><?= $prestamo['plazo'] ?> meses</span>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Cuota</span>
                        <span class="field-value mono"><?= money($prestamo['pago']) ?></span>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Esquema</span>
                        <span class="field-value"><?= $prestamo['esquema'] ?></span>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Interés diario</span>
                        <span class="field-value mono"><?= $prestamo['interes'] ?>%</span>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Fecha inicio</span>
                        <span class="field-value mono"><?= $prestamo['fecha_inicio'] ?></span>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Fecha fin</span>
                        <span class="field-value mono"><?= $prestamo['fecha_fin'] ?></span>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Promotor</span>
                        <span class="field-value"><?= htmlspecialchars($prestamo['promotor']) ?></span>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Cobrador</span>
                        <span class="field-value"><?= htmlspecialchars($prestamo['cobrador']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Datos del cliente -->
            <div class="detail-card">
                <div class="detail-card-header">
                    <svg viewBox="0 0 16 16"><circle cx="8" cy="5.5" r="2.5"/><path d="M2.5 14c0-3.038 2.462-5.5 5.5-5.5s5.5 2.462 5.5 5.5"/></svg>
                    <h3>Datos del cliente</h3>
                </div>
                <div class="detail-card-body">
                    <div class="field-row">
                        <span class="field-label">Nombre</span>
                        <span class="field-value"><?= htmlspecialchars($prestamo['nombre']) ?></span>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Celular</span>
                        <span class="field-value mono"><?= htmlspecialchars($prestamo['celular']) ?></span>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Dirección</span>
                        <span class="field-value"><?= htmlspecialchars($prestamo['direccion']) ?></span>
                    </div>
                    <div class="field-row">
                        <span class="field-label">CURP</span>
                        <span class="field-value mono" style="font-size:11px"><?= htmlspecialchars($prestamo['curp']) ?></span>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Ocupación</span>
                        <span class="field-value"><?= htmlspecialchars($prestamo['ocupacion']) ?></span>
                    </div>

                    <div style="padding:10px 0 4px">
                        <span class="field-label">Contactos de emergencia</span>
                    </div>

                    <div class="field-row">
                        <span class="field-label">Contacto 1</span>
                        <div>
                            <div class="field-value"><?= htmlspecialchars($prestamo['contacto1_nombre']) ?></div>
                            <div class="field-value mono" style="font-size:11px;color:var(--text-secondary)"><?= htmlspecialchars($prestamo['contacto1_telefono']) ?></div>
                        </div>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Contacto 2</span>
                        <div>
                            <div class="field-value"><?= htmlspecialchars($prestamo['contacto2_nombre']) ?></div>
                            <div class="field-value mono" style="font-size:11px;color:var(--text-secondary)"><?= htmlspecialchars($prestamo['contacto2_telefono']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- end detail-grid -->

        <!-- Tabla de pagos -->
        <div class="table-card">
            <div class="table-header">
                <div>
                    <div class="table-title">Historial de pagos</div>
                    <div class="table-count">Plan de amortización · <?= $prestamo['plazo'] ?> cuotas de <?= money($prestamo['pago']) ?></div>
                </div>
            </div>
            <table class="amort-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha programada</th>
                        <th>Cuota</th>
                        <th>Interés</th>
                        <th>Capital</th>
                        <th>Saldo restante</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestamo['pagos'] as $pago): ?>
                    <tr>
                        <td class="td-id"><?= $pago['num'] ?></td>
                        <td class="td-numeric"><?= $pago['fecha'] ?></td>
                        <td class="td-amount"><?= money($pago['cuota']) ?></td>
                        <td style="color:#dc2626;font-family:var(--font-mono);font-size:12px"><?= money($pago['interes']) ?></td>
                        <td style="color:#16a34a;font-family:var(--font-mono);font-size:12px"><?= money($pago['capital']) ?></td>
                        <td class="td-amount"><?= money($pago['saldo']) ?></td>
                        <td>
                            <?php
                            $b = $pago_badge[$pago['estatus']] ?? ['bg'=>'#f1f5f9','color'=>'#475569'];
                            ?>
                            <span class="pago-badge" style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>">
                                <?= $pago['estatus'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

</body>
</html>