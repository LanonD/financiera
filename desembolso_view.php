<?php session_start();

// TEMPORAL para diseño — quitar cuando BD esté lista
$_SESSION['usuario'] = $_SESSION['usuario'] ?? 'Carlos Vega';
$_SESSION['id']      = $_SESSION['id']      ?? '5';
$_SESSION['puesto']  = $_SESSION['puesto']  ?? 'desembolso';

// Protección — solo rol desembolso/admin
// if (!isset($_SESSION['puesto']) || !in_array($_SESSION['puesto'], ['desembolso','admin'])) {
//     header("Location: login.php"); exit();
// }

// ── Datos mock — reemplazar con query real ──
// SELECT p.*, c.* FROM prestamos p JOIN clientes c ON p.cliente_id = c.id WHERE p.estatus = 'Pendiente'
$prestamos_pendientes = [
    [
        'id'         => 1042,
        'nombre'     => 'Laura Méndez',
        'celular'    => '55 1234 5678',
        'direccion'  => 'Av. Reforma 120, CDMX',
        'monto'      => 45000,
        'promotor'   => 'Juan Reyes',
        'fecha'      => '2024-06-01',
        'estatus_doc'=> 'pendiente', // pendiente | entregado
    ],
    [
        'id'         => 1043,
        'nombre'     => 'Roberto Cruz',
        'celular'    => '55 9988 7700',
        'direccion'  => 'Insurgentes 88, CDMX',
        'monto'      => 20000,
        'promotor'   => 'María López',
        'fecha'      => '2024-06-01',
        'estatus_doc'=> 'pendiente',
    ],
];

function money($v) { return '$' . number_format($v, 2, '.', ','); }
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
    <title>PrestaCRM — Desembolso</title>
    <style>

        /* ── Tarjetas de préstamo pendiente ── */
        .prestamos-grid {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .prestamo-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: border-color 0.15s;
        }

        .prestamo-card.entregado {
            border-color: #bbf7d0;
            opacity: 0.7;
        }

        .prestamo-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .prestamo-card-cliente {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .prestamo-card-body {
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Info section */
        .info-section h4 {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        .info-rows { display: flex; flex-direction: column; gap: 8px; }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 13px;
        }

        .info-row-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            width: 80px;
            flex-shrink: 0;
            padding-top: 1px;
        }

        .info-row-value {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
            font-family: var(--font-mono);
        }

        .monto-display {
            font-size: 28px;
            font-weight: 600;
            font-family: var(--font-mono);
            color: var(--text-primary);
            letter-spacing: -0.03em;
            margin-bottom: 4px;
        }

        .monto-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        /* Documentos checklist */
        .docs-section h4 {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        .doc-checklist {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .doc-check-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: var(--bg-hover);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            transition: border-color 0.15s;
        }

        .doc-check-item.has-file {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .doc-check-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .doc-check-icon {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            background: var(--bg-input);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .doc-check-icon.done { background: #dcfce7; }
        .doc-check-icon svg { width: 14px; height: 14px; fill: none; stroke: var(--text-muted); stroke-width: 1.5; stroke-linecap: round; }
        .doc-check-icon.done svg { stroke: #16a34a; }

        .doc-check-name { font-size: 12px; font-weight: 500; color: var(--text-primary); }
        .doc-check-status { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
        .doc-check-status.ok { color: #16a34a; }

        .doc-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            background: transparent;
            border: 1px solid var(--border-input);
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 11px;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }

        .doc-upload-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }
        .doc-upload-btn svg { width: 12px; height: 12px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; }
        .doc-upload-btn.view-btn:hover { border-color: #16a34a; color: #16a34a; background: #f0fdf4; }

        /* Hidden file inputs */
        .file-input-hidden { display: none; }

        /* Divider */
        .card-divider {
            grid-column: 1 / -1;
            height: 1px;
            background: var(--border);
            margin: 4px 0;
        }

        /* Firma de entrega */
        .entrega-section {
            grid-column: 1 / -1;
            border-top: 1px solid var(--border);
            padding-top: 16px;
            margin-top: 4px;
        }

        .entrega-section h4 {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        .entrega-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            margin-bottom: 14px;
        }

        .entrega-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .entrega-field label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
        }

        .entrega-field input,
        .entrega-field select {
            padding: 8px 11px;
            background: var(--bg-input);
            border: 1px solid var(--border-input);
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 13px;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .entrega-field input:focus,
        .entrega-field select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        .entrega-field input::placeholder { color: var(--text-muted); }

        /* Nota */
        .nota-field { display: flex; flex-direction: column; gap: 5px; }
        .nota-field label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); }
        .nota-field textarea {
            padding: 8px 11px;
            background: var(--bg-input);
            border: 1px solid var(--border-input);
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 13px;
            color: var(--text-primary);
            outline: none;
            resize: none;
            height: 60px;
            transition: border-color 0.15s;
        }
        .nota-field textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .nota-field textarea::placeholder { color: var(--text-muted); }

        /* Submit row */
        .submit-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 14px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .submit-warning {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .submit-warning svg { width: 14px; height: 14px; fill: none; stroke: #ca8a04; stroke-width: 1.5; flex-shrink: 0; }

        .btn-confirmar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
        }

        .btn-confirmar:hover  { background: #15803d; }
        .btn-confirmar:active { transform: scale(0.98); }
        .btn-confirmar:disabled { background: var(--text-muted); cursor: not-allowed; transform: none; opacity: 0.6; }
        .btn-confirmar svg { width: 14px; height: 14px; fill: none; stroke: white; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

        /* Entregado badge */
        .badge-entregado {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 12px; border-radius: 20px;
            background: #dcfce7; color: #166534;
            font-size: 12px; font-weight: 600;
        }
        .badge-entregado svg { width: 13px; height: 13px; fill: none; stroke: #16a34a; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

        .badge-pendiente-desembolso {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 12px; border-radius: 20px;
            background: #fef9c3; color: #854d0e;
            font-size: 12px; font-weight: 600;
        }

        /* Empty state */
        .empty-state {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 60px 24px;
            text-align: center;
        }
        .empty-state h3 { font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; }
        .empty-state p  { font-size: 13px; color: var(--text-secondary); }

        /* Foto vivienda preview */
        .foto-preview {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            display: none;
            margin-top: 6px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrapper { margin-left: 0; }
            .topbar { padding: 0 14px; }
            .content { padding: 16px 14px; }
            .prestamo-card-body { grid-template-columns: 1fr; }
            .entrega-grid { grid-template-columns: 1fr 1fr; }
            .submit-row { flex-direction: column; align-items: stretch; }
            .btn-confirmar { justify-content: center; }
        }

        @media (max-width: 480px) {
            .entrega-grid { grid-template-columns: 1fr; }
            .prestamo-card-header { flex-direction: column; align-items: flex-start; }
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
        <span class="nav-section-label">Mi panel</span>
        <a class="nav-item active" href="desembolso_view.php">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 8h12M9 4l4 4-4 4"/></svg>
            Desembolsos
        </a>
        <a class="nav-item" href="#">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2,12 6,7 10,9 14,4"/></svg>
            Mi historial
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
            <h1>Desembolsos pendientes</h1>
            <div class="breadcrumb">Panel de desembolso · <?= count($prestamos_pendientes) ?> préstamos por entregar</div>
        </div>
        <div class="topbar-right">
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M16.5 16.5L22 22"/></svg>
                <input type="text" id="globalSearch" placeholder="Buscar cliente…" oninput="filterCards()">
            </div>
        </div>
    </header>

    <main class="content">

        <div class="content-header">
            <div>
                <h2>Entrega de efectivo y documentos</h2>
                <p>Registra la entrega del dinero y recopila los documentos físicos del cliente</p>
            </div>
        </div>

        <!-- KPI row -->
        <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
            <div class="kpi-card yellow">
                <div class="kpi-label">Por entregar</div>
                <div class="kpi-value" id="kpi-pendientes"><?= count($prestamos_pendientes) ?></div>
                <span class="kpi-trend flat">Hoy</span>
            </div>
            <div class="kpi-card green">
                <div class="kpi-label">Entregados hoy</div>
                <div class="kpi-value" id="kpi-entregados">0</div>
                <span class="kpi-trend up" id="kpi-monto-entregado">$0</span>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Total a entregar</div>
                <div class="kpi-value"><?= money(array_sum(array_column($prestamos_pendientes, 'monto'))) ?></div>
                <span class="kpi-trend flat">En efectivo</span>
            </div>
        </div>

        <!-- Lista de préstamos -->
        <div class="prestamos-grid" id="prestamosGrid">

        <?php foreach ($prestamos_pendientes as $p): ?>
        <div class="prestamo-card" id="card-<?= $p['id'] ?>" data-nombre="<?= strtolower($p['nombre']) ?>">

            <!-- Header -->
            <div class="prestamo-card-header">
                <div class="prestamo-card-cliente">
                    <div class="initials"><?= strtoupper(substr($p['nombre'], 0, 2)) ?></div>
                    <div>
                        <div style="font-size:15px;font-weight:600;color:var(--text-primary)"><?= htmlspecialchars($p['nombre']) ?></div>
                        <div style="font-size:12px;color:var(--text-secondary);font-family:var(--font-mono)"><?= htmlspecialchars($p['celular']) ?></div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:12px;color:var(--text-muted);font-family:var(--font-mono)">Préstamo #<?= $p['id'] ?></span>
                    <span class="badge-pendiente-desembolso" id="badge-<?= $p['id'] ?>">Pendiente entrega</span>
                </div>
            </div>

            <!-- Body -->
            <div class="prestamo-card-body">

                <!-- Columna izquierda: info cliente + monto -->
                <div class="info-section">
                    <h4>Datos del desembolso</h4>
                    <div style="margin-bottom:16px">
                        <div class="monto-label">Monto a entregar en efectivo</div>
                        <div class="monto-display"><?= money($p['monto']) ?></div>
                    </div>
                    <div class="info-rows">
                        <div class="info-row">
                            <span class="info-row-label">Dirección</span>
                            <span class="info-row-value" style="font-family:var(--font);font-size:12px"><?= htmlspecialchars($p['direccion']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-row-label">Promotor</span>
                            <span class="info-row-value"><?= htmlspecialchars($p['promotor']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-row-label">Fecha</span>
                            <span class="info-row-value"><?= $p['fecha'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: documentos -->
                <div class="docs-section">
                    <h4>Documentos a recopilar</h4>
                    <div class="doc-checklist">

                        <!-- Pagaré firmado -->
                        <div class="doc-check-item" id="doc-pagare-<?= $p['id'] ?>">
                            <div class="doc-check-left">
                                <div class="doc-check-icon" id="icon-pagare-<?= $p['id'] ?>">
                                    <svg viewBox="0 0 16 16"><path d="M4 2h5l3 3v9H4V2z"/><path d="M9 2v3h3"/><path d="M6 8h4"/></svg>
                                </div>
                                <div>
                                    <div class="doc-check-name">Pagaré firmado</div>
                                    <div class="doc-check-status" id="status-pagare-<?= $p['id'] ?>">Sin subir</div>
                                </div>
                            </div>
                            <label class="doc-upload-btn" for="file-pagare-<?= $p['id'] ?>">
                                <svg viewBox="0 0 14 14"><path d="M7 2v7M4 6l3 3 3-3"/><path d="M2 11h10"/></svg>
                                Subir
                            </label>
                            <input type="file" class="file-input-hidden" id="file-pagare-<?= $p['id'] ?>"
                                accept=".pdf,.jpg,.jpeg,.png"
                                onchange="onFileUpload(this, 'pagare', <?= $p['id'] ?>)">
                        </div>

                        <!-- INE -->
                        <div class="doc-check-item" id="doc-ine-<?= $p['id'] ?>">
                            <div class="doc-check-left">
                                <div class="doc-check-icon" id="icon-ine-<?= $p['id'] ?>">
                                    <svg viewBox="0 0 16 16"><rect x="2" y="4" width="12" height="9" rx="1.5"/><path d="M5 8h3M5 11h5"/></svg>
                                </div>
                                <div>
                                    <div class="doc-check-name">Foto de INE</div>
                                    <div class="doc-check-status" id="status-ine-<?= $p['id'] ?>">Sin subir</div>
                                </div>
                            </div>
                            <label class="doc-upload-btn" for="file-ine-<?= $p['id'] ?>">
                                <svg viewBox="0 0 14 14"><path d="M7 2v7M4 6l3 3 3-3"/><path d="M2 11h10"/></svg>
                                Subir
                            </label>
                            <input type="file" class="file-input-hidden" id="file-ine-<?= $p['id'] ?>"
                                accept=".jpg,.jpeg,.png,.pdf"
                                onchange="onFileUpload(this, 'ine', <?= $p['id'] ?>)">
                        </div>

                        <!-- Comprobante de domicilio -->
                        <div class="doc-check-item" id="doc-comprobante-<?= $p['id'] ?>">
                            <div class="doc-check-left">
                                <div class="doc-check-icon" id="icon-comprobante-<?= $p['id'] ?>">
                                    <svg viewBox="0 0 16 16"><path d="M2 13V7l6-5 6 5v6H2z"/><path d="M6 13V9h4v4"/></svg>
                                </div>
                                <div>
                                    <div class="doc-check-name">Comprobante de domicilio</div>
                                    <div class="doc-check-status" id="status-comprobante-<?= $p['id'] ?>">Sin subir</div>
                                </div>
                            </div>
                            <label class="doc-upload-btn" for="file-comprobante-<?= $p['id'] ?>">
                                <svg viewBox="0 0 14 14"><path d="M7 2v7M4 6l3 3 3-3"/><path d="M2 11h10"/></svg>
                                Subir
                            </label>
                            <input type="file" class="file-input-hidden" id="file-comprobante-<?= $p['id'] ?>"
                                accept=".jpg,.jpeg,.png,.pdf"
                                onchange="onFileUpload(this, 'comprobante', <?= $p['id'] ?>)">
                        </div>

                        <!-- Foto vivienda (opcional) -->
                        <div class="doc-check-item" id="doc-vivienda-<?= $p['id'] ?>">
                            <div class="doc-check-left">
                                <div class="doc-check-icon" id="icon-vivienda-<?= $p['id'] ?>">
                                    <svg viewBox="0 0 16 16"><rect x="2" y="6" width="12" height="8" rx="1"/><path d="M5 14v-4h6v4M1 7l7-5 7 5"/></svg>
                                </div>
                                <div>
                                    <div class="doc-check-name">Foto de vivienda <span style="font-size:10px;color:var(--text-muted);font-weight:400">(opcional)</span></div>
                                    <div class="doc-check-status" id="status-vivienda-<?= $p['id'] ?>">Sin subir</div>
                                </div>
                            </div>
                            <label class="doc-upload-btn" for="file-vivienda-<?= $p['id'] ?>">
                                <svg viewBox="0 0 14 14"><path d="M1 10l3-3 2 2 4-5 3 4H1z"/><circle cx="10.5" cy="3.5" r="1.5"/></svg>
                                Foto
                            </label>
                            <input type="file" class="file-input-hidden" id="file-vivienda-<?= $p['id'] ?>"
                                accept=".jpg,.jpeg,.png"
                                capture="environment"
                                onchange="onFileUpload(this, 'vivienda', <?= $p['id'] ?>)">
                        </div>
                        <!-- Preview foto vivienda -->
                        <img class="foto-preview" id="preview-vivienda-<?= $p['id'] ?>" alt="Foto vivienda">

                    </div>
                </div>

                <!-- Sección de entrega -->
                <div class="entrega-section">
                    <h4>Confirmar entrega de efectivo</h4>

                    <div class="entrega-grid">
                        <div class="entrega-field">
                            <label>Monto entregado</label>
                            <input type="number"
                                id="monto-entregado-<?= $p['id'] ?>"
                                value="<?= $p['monto'] ?>"
                                placeholder="<?= $p['monto'] ?>"
                                min="1" step="0.01">
                        </div>
                        <div class="entrega-field">
                            <label>Forma de entrega</label>
                            <select id="forma-<?= $p['id'] ?>">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>
                        <div class="entrega-field">
                            <label>Hora de entrega</label>
                            <input type="time" id="hora-<?= $p['id'] ?>" value="<?= date('H:i') ?>">
                        </div>
                    </div>

                    <div class="nota-field">
                        <label>Nota <span style="font-weight:400;text-transform:none;letter-spacing:0">(opcional)</span></label>
                        <textarea id="nota-<?= $p['id'] ?>"
                            placeholder="Observaciones de la visita, condiciones del domicilio, actitud del cliente…"></textarea>
                    </div>

                    <div class="submit-row">
                        <div class="submit-warning">
                            <svg viewBox="0 0 16 16"><circle cx="8" cy="8" r="6"/><path d="M8 5v3M8 10v.5"/></svg>
                            Asegúrate de tener el pagaré firmado antes de confirmar
                        </div>
                        <button class="btn-confirmar"
                            id="btn-confirmar-<?= $p['id'] ?>"
                            onclick="confirmarEntrega(<?= $p['id'] ?>, <?= $p['monto'] ?>)">
                            <svg viewBox="0 0 14 14"><path d="M2 7l3 3 7-7"/></svg>
                            Confirmar entrega
                        </button>
                    </div>
                </div>

            </div><!-- end card-body -->
        </div>
        <?php endforeach; ?>

        <?php if (empty($prestamos_pendientes)): ?>
        <div class="empty-state">
            <h3>Sin desembolsos pendientes</h3>
            <p>No hay préstamos asignados para entregar hoy.</p>
        </div>
        <?php endif; ?>

        </div><!-- end prestamos-grid -->

    </main>
</div>

<script>
/* ── Estado de documentos por préstamo ── */
const docState = {};

function initDoc(id) {
    if (!docState[id]) {
        docState[id] = { pagare: false, ine: false, comprobante: false, vivienda: false };
    }
}

/* ── Manejar subida de archivo ── */
function onFileUpload(input, tipo, id) {
    initDoc(id);
    const file = input.files[0];
    if (!file) return;

    docState[id][tipo] = true;

    // Actualizar UI del item
    const item   = document.getElementById(`doc-${tipo}-${id}`);
    const icon   = document.getElementById(`icon-${tipo}-${id}`);
    const status = document.getElementById(`status-${tipo}-${id}`);

    item.classList.add('has-file');
    icon.classList.add('done');
    icon.innerHTML = '<svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg>';
    status.textContent = file.name.length > 20 ? file.name.substring(0, 20) + '…' : file.name;
    status.classList.add('ok');

    // Si es foto de vivienda, mostrar preview
    if (tipo === 'vivienda') {
        const preview = document.getElementById(`preview-vivienda-${id}`);
        const reader  = new FileReader();
        reader.onload = e => {
            preview.src   = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    // Cambiar botón a "Ver"
    const label = input.previousElementSibling;
    if (label && label.classList.contains('doc-upload-btn')) {
        label.innerHTML = `<svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="7" cy="7" r="5"/><path d="M4.5 7c0 0 1 2 2.5 2s2.5-2 2.5-2-1-2-2.5-2-2.5 2-2.5 2z"/><circle cx="7" cy="7" r="1"/></svg> Ver`;
        label.classList.add('view-btn');
    }
}

/* ── Confirmar entrega ── */
let entregados   = 0;
let montoTotal   = 0;

function confirmarEntrega(id, montoOriginal) {
    const monto = parseFloat(document.getElementById(`monto-entregado-${id}`).value);
    const forma = document.getElementById(`forma-${id}`).value;
    const hora  = document.getElementById(`hora-${id}`).value;
    const nota  = document.getElementById(`nota-${id}`).value;

    initDoc(id);

    // Validar pagaré obligatorio
    if (!docState[id].pagare) {
        alert('⚠️ El pagaré firmado es obligatorio antes de confirmar la entrega.');
        document.getElementById(`file-pagare-${id}`).click();
        return;
    }

    if (!monto || monto <= 0) {
        alert('Ingresa el monto entregado.');
        document.getElementById(`monto-entregado-${id}`).focus();
        return;
    }

    // Marcar como entregado
    const card  = document.getElementById(`card-${id}`);
    const badge = document.getElementById(`badge-${id}`);
    const btn   = document.getElementById(`btn-confirmar-${id}`);

    card.classList.add('entregado');
    badge.className  = 'badge-entregado';
    badge.innerHTML  = '<svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg> Entregado';
    btn.disabled     = true;
    btn.textContent  = 'Entrega confirmada';

    // Actualizar KPIs
    entregados++;
    montoTotal += monto;
    document.getElementById('kpi-entregados').textContent     = entregados;
    document.getElementById('kpi-pendientes').textContent     = <?= count($prestamos_pendientes) ?> - entregados;
    document.getElementById('kpi-monto-entregado').textContent = '$' + montoTotal.toLocaleString();

    // TODO backend: enviar a PHP
    // fetch('php/registrar_desembolso.php', {
    //     method: 'POST',
    //     headers: {'Content-Type': 'application/json'},
    //     body: JSON.stringify({ prestamo_id: id, monto, forma, hora, nota, docs: docState[id] })
    // })

    console.log('Desembolso registrado:', { id, monto, forma, hora, nota, docs: docState[id] });
}

/* ── Filtro de búsqueda ── */
function filterCards() {
    const q = document.getElementById('globalSearch').value.trim().toLowerCase();
    document.querySelectorAll('.prestamo-card').forEach(card => {
        card.style.display = (!q || card.dataset.nombre.includes(q)) ? '' : 'none';
    });
}
</script>

</body>
</html>