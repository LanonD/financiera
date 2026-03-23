<?php
session_start();

// TEMPORAL para diseño — descomentar cuando BD esté lista
$_SESSION['usuario'] = $_SESSION['usuario'] ?? 'Laura Méndez';
$_SESSION['id']      = $_SESSION['id']      ?? '18';
$_SESSION['puesto']  = $_SESSION['puesto']  ?? 'cliente';

// Protección — solo clientes
if (!isset($_SESSION['puesto']) || $_SESSION['puesto'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

// ── Datos mock — el backend reemplaza esto con query real ──
// SELECT * FROM prestamos WHERE cliente_id = $_SESSION['id']
$cliente = [
    'nombre'        => $_SESSION['usuario'] ?? 'Cliente',
    'id'            => $_SESSION['id'] ?? '—',
];

$prestamo = [
    'id'            => '#1042',
    'monto'         => 45000,
    'saldo_actual'  => 38200,
    'cuota'         => 2100,
    'esquema'       => 'Mensual',
    'tasa'          => 12,
    'plazo'         => 24,
    'pagos_hechos'  => 3,
    'fecha_inicio'  => '01 Feb 2024',
    'proximo_pago'  => '01 Jul 2024',
    'estatus'       => 'Activo',
    'promotor'      => 'Juan Reyes',
    'cobrador'      => 'Pedro Morales',
];

$documentos = [
    ['nombre' => 'Contrato de préstamo',   'archivo' => 'contrato.pdf'],
    ['nombre' => 'Pagaré firmado',         'archivo' => 'pagare.pdf'],
    ['nombre' => 'Tabla de amortización',  'archivo' => 'amortizacion.pdf'],
];

$pagos = [
    ['num'=>1,'fecha'=>'01 Mar 2024','monto'=>2100,'estatus'=>'Pagado'],
    ['num'=>2,'fecha'=>'01 Abr 2024','monto'=>2100,'estatus'=>'Pagado'],
    ['num'=>3,'fecha'=>'01 May 2024','monto'=>2100,'estatus'=>'Pagado'],
    ['num'=>4,'fecha'=>'01 Jun 2024','monto'=>2100,'estatus'=>'Pendiente'],
    ['num'=>5,'fecha'=>'01 Jul 2024','monto'=>2100,'estatus'=>'Pendiente'],
];

function money($v) { return '$' . number_format($v, 2, '.', ','); }
$pct = round((($prestamo['monto'] - $prestamo['saldo_actual']) / $prestamo['monto']) * 100);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <title>PrestaCRM — Mi préstamo</title>
    <style>
        :root {
            --bg-page:    #f0f2f5;
            --bg-card:    #ffffff;
            --bg-dark:    #0f1623;
            --bg-hover:   #f7f8fa;
            --bg-input:   #f4f5f7;
            --text-primary:   #111827;
            --text-secondary: #6b7280;
            --text-muted:     #9ca3af;
            --border:         rgba(0,0,0,0.07);
            --border-input:   rgba(0,0,0,0.12);
            --accent:         #3b82f6;
            --accent-hover:   #2563eb;
            --accent-light:   #eff6ff;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --font:      'DM Sans', Arial, sans-serif;
            --font-mono: 'DM Mono', monospace;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        a { text-decoration: none; color: inherit; }

        body {
            font-family: var(--font);
            background: var(--bg-page);
            color: var(--text-primary);
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.5;
        }

        /* TOPBAR */
        .topbar {
            background: var(--bg-dark);
            padding: 0 24px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-logo { display: flex; align-items: center; gap: 10px; }

        .logo-mark {
            width: 28px; height: 28px;
            background: var(--accent);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }

        .logo-mark svg { width: 14px; height: 14px; fill: white; display: block; }
        .logo-text { font-size: 15px; font-weight: 600; color: #fff; letter-spacing: -0.01em; }

        .topbar-user { display: flex; align-items: center; gap: 10px; }

        .user-greeting { font-size: 13px; color: rgba(155,168,188,0.8); }
        .user-greeting strong { color: #fff; font-weight: 500; }

        .user-avatar {
            width: 32px; height: 32px;
            background: var(--accent); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 600; color: white;
        }

        .btn-logout {
            padding: 6px 12px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 12px;
            color: rgba(155,168,188,0.8);
            cursor: pointer;
            transition: all 0.15s;
        }

        .btn-logout:hover { background: rgba(255,255,255,0.12); color: #fff; }

        /* CONTENT */
        .content { max-width: 900px; margin: 0 auto; padding: 28px 20px; }

        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 22px; font-weight: 600; color: var(--text-primary); letter-spacing: -0.02em; margin-bottom: 4px; }
        .page-header p  { font-size: 13px; color: var(--text-secondary); }

        /* CARDS */
        .card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 16px; }

        .card-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }

        .card-header-left { display: flex; align-items: center; gap: 8px; }
        .card-header h2  { font-size: 14px; font-weight: 600; color: var(--text-primary); }
        .card-header svg { width: 16px; height: 16px; color: var(--accent); fill: none; stroke: currentColor; stroke-width: 1.5; }
        .card-body { padding: 20px; }

        /* LOAN HERO */
        .loan-hero { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }

        .loan-main {
            background: var(--bg-dark);
            border-radius: var(--radius-md);
            padding: 20px;
            display: flex; flex-direction: column; gap: 4px;
        }

        .loan-main-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: rgba(155,168,188,0.6); }
        .loan-main-value { font-size: 32px; font-weight: 600; font-family: var(--font-mono); color: #fff; letter-spacing: -0.03em; line-height: 1; margin: 4px 0; }
        .loan-main-sub   { font-size: 12px; color: rgba(155,168,188,0.6); }

        .loan-id-badge {
            display: inline-flex; align-items: center;
            padding: 3px 8px;
            background: rgba(59,130,246,0.2);
            border-radius: 12px;
            font-size: 11px; font-weight: 600;
            color: #60a5fa; font-family: var(--font-mono);
            margin-top: 6px; width: fit-content;
        }

        .loan-stats { display: flex; flex-direction: column; gap: 10px; }

        .loan-stat {
            background: var(--bg-hover);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            display: flex; align-items: center; justify-content: space-between;
        }

        .loan-stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); }
        .loan-stat-value { font-size: 14px; font-weight: 600; font-family: var(--font-mono); color: var(--text-primary); }
        .loan-stat-value.accent { color: var(--accent); }
        .loan-stat-value.green  { color: #16a34a; }

        /* PROGRESS */
        .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .progress-label  { font-size: 12px; font-weight: 500; color: var(--text-secondary); }
        .progress-pct    { font-size: 13px; font-weight: 600; font-family: var(--font-mono); color: var(--accent); }

        .progress-bar { height: 8px; background: var(--bg-input); border-radius: 4px; overflow: hidden; margin-bottom: 6px; }
        .progress-fill { height: 100%; border-radius: 4px; background: var(--accent); }

        .progress-meta { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); font-family: var(--font-mono); }

        /* PRÓXIMO PAGO */
        .next-payment {
            background: #eff6ff; border: 1px solid #bfdbfe;
            border-radius: var(--radius-md); padding: 14px 16px;
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 16px;
        }

        .next-payment-left { display: flex; flex-direction: column; gap: 2px; }
        .next-payment-label  { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #3b82f6; }
        .next-payment-date   { font-size: 14px; font-weight: 500; color: #1e40af; }
        .next-payment-amount { font-size: 20px; font-weight: 600; font-family: var(--font-mono); color: #1e40af; letter-spacing: -0.02em; }

        /* BADGE */
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge .dot { width: 5px; height: 5px; border-radius: 50%; }
        .badge-activo { background: #dcfce7; color: #166534; }
        .badge-activo .dot { background: #16a34a; }

        /* INFO GRID */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }

        .info-field { padding: 12px 0; border-bottom: 1px solid var(--border); display: flex; flex-direction: column; gap: 3px; }
        .info-field:nth-child(odd)  { padding-right: 20px; border-right: 1px solid var(--border); }
        .info-field:nth-child(even) { padding-left: 20px; }
        .info-field:nth-last-child(-n+2) { border-bottom: none; }

        .info-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-muted); }
        .info-value { font-size: 13px; font-weight: 500; color: var(--text-primary); font-family: var(--font-mono); }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: var(--bg-hover); }
        th { padding: 9px 14px; text-align: left; font-size: 10px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid var(--border); }
        td { padding: 11px 14px; font-size: 13px; color: var(--text-primary); border-bottom: 1px solid var(--border); font-family: var(--font-mono); }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: var(--bg-hover); }

        .pago-badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; font-family: var(--font); }

        /* DOCUMENTOS */
        .doc-list { display: flex; flex-direction: column; gap: 10px; }

        .doc-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 14px;
            background: var(--bg-hover);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            transition: border-color 0.15s;
        }

        .doc-item:hover { border-color: var(--accent); }
        .doc-left { display: flex; align-items: center; gap: 12px; }

        .doc-icon {
            width: 36px; height: 36px;
            background: var(--accent-light);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }

        .doc-icon svg { width: 16px; height: 16px; fill: none; stroke: var(--accent); stroke-width: 1.5; stroke-linecap: round; }
        .doc-name { font-size: 13px; font-weight: 500; color: var(--text-primary); }
        .doc-type { font-size: 11px; color: var(--text-muted); margin-top: 1px; }

        .btn-download {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 12px;
            background: var(--accent); color: white;
            border: none; border-radius: var(--radius-sm);
            font-family: var(--font); font-size: 12px; font-weight: 500;
            cursor: pointer; transition: background 0.15s;
        }

        .btn-download:hover { background: var(--accent-hover); }
        .btn-download svg { width: 12px; height: 12px; fill: none; stroke: white; stroke-width: 2; stroke-linecap: round; }

        /* SOLICITUD BANNER */
        .solicitud-banner {
            background: var(--bg-dark);
            border-radius: var(--radius-lg);
            padding: 24px;
            display: flex; align-items: center; justify-content: space-between; gap: 20px;
            margin-bottom: 16px;
        }

        .solicitud-text h3 { font-size: 16px; font-weight: 600; color: #fff; margin-bottom: 4px; }
        .solicitud-text p  { font-size: 13px; color: rgba(155,168,188,0.7); }

        .btn-solicitar {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 20px;
            background: var(--accent); color: white;
            border: none; border-radius: var(--radius-sm);
            font-family: var(--font); font-size: 13px; font-weight: 600;
            cursor: pointer; transition: background 0.15s, transform 0.1s; white-space: nowrap;
        }

        .btn-solicitar:hover  { background: var(--accent-hover); }
        .btn-solicitar:active { transform: scale(0.98); }
        .btn-solicitar svg { width: 14px; height: 14px; fill: none; stroke: white; stroke-width: 2; stroke-linecap: round; }

        /* MODAL */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.45); z-index: 200;
            align-items: center; justify-content: center;
            backdrop-filter: blur(2px);
        }
        .modal-overlay.open { display: flex; }

        /* RESPONSIVE */
        @media (max-width: 640px) {
            .content { padding: 16px 14px; }
            .loan-hero { grid-template-columns: 1fr; }
            .info-grid { grid-template-columns: 1fr; }
            .info-field:nth-child(odd)  { padding-right: 0; border-right: none; }
            .info-field:nth-child(even) { padding-left: 0; }
            .info-field:nth-last-child(-n+2) { border-bottom: 1px solid var(--border); }
            .info-field:last-child { border-bottom: none; }
            .solicitud-banner { flex-direction: column; align-items: flex-start; }
            .btn-solicitar { width: 100%; justify-content: center; }
            .topbar { padding: 0 14px; }
            .user-greeting { display: none; }
            th, td { padding: 9px 10px; font-size: 12px; }
            .doc-item { flex-direction: column; align-items: flex-start; gap: 10px; }
            .btn-download { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-logo">
        <div class="logo-mark">
            <svg viewBox="0 0 14 14"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg>
        </div>
        <span class="logo-text">PrestaCRM</span>
    </div>
    <div class="topbar-user">
        <div class="user-greeting">
            Hola, <strong><?= htmlspecialchars($cliente['nombre']) ?></strong>
        </div>
        <div class="user-avatar"><?= strtoupper(substr($cliente['nombre'], 0, 2)) ?></div>
        <a href="php/logout.php" class="btn-logout">Cerrar sesión</a>
    </div>
</header>

<main class="content">

    <div class="page-header">
        <h1>Mi préstamo</h1>
        <p>Consulta el estado de tu crédito y tus documentos</p>
    </div>

    <!-- Banner solicitar -->
    <div class="solicitud-banner">
        <div class="solicitud-text">
            <h3>¿Necesitas un nuevo préstamo?</h3>
            <p>Contáctanos y un promotor te atenderá a la brevedad.</p>
        </div>
        <button class="btn-solicitar" id="btnSolicitar">
            <svg viewBox="0 0 14 14"><path d="M7 2v10M2 7h10"/></svg>
            Solicitar préstamo
        </button>
    </div>

    <!-- Préstamo activo -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <svg viewBox="0 0 16 16"><rect x="2" y="3" width="12" height="10" rx="1.5"/><path d="M5 7h6M5 10h4"/></svg>
                <h2>Préstamo activo</h2>
            </div>
            <span class="badge badge-activo"><span class="dot"></span><?= $prestamo['estatus'] ?></span>
        </div>
        <div class="card-body">
            <div class="loan-hero">
                <div class="loan-main">
                    <span class="loan-main-label">Saldo pendiente</span>
                    <span class="loan-main-value"><?= money($prestamo['saldo_actual']) ?></span>
                    <span class="loan-main-sub">de <?= money($prestamo['monto']) ?> originales</span>
                    <span class="loan-id-badge"><?= $prestamo['id'] ?></span>
                </div>
                <div class="loan-stats">
                    <div class="loan-stat">
                        <span class="loan-stat-label">Cuota</span>
                        <span class="loan-stat-value accent"><?= money($prestamo['cuota']) ?></span>
                    </div>
                    <div class="loan-stat">
                        <span class="loan-stat-label">Esquema</span>
                        <span class="loan-stat-value"><?= $prestamo['esquema'] ?></span>
                    </div>
                    <div class="loan-stat">
                        <span class="loan-stat-label">Interés diario</span>
                        <span class="loan-stat-value"><?= $prestamo['tasa'] ?>%</span>
                    </div>
                    <div class="loan-stat">
                        <span class="loan-stat-label">Pagos realizados</span>
                        <span class="loan-stat-value green"><?= $prestamo['pagos_hechos'] ?> / <?= $prestamo['plazo'] ?></span>
                    </div>
                </div>
            </div>

            <div class="progress-header">
                <span class="progress-label">Progreso del préstamo</span>
                <span class="progress-pct"><?= $pct ?>% pagado</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width:<?= $pct ?>%"></div>
            </div>
            <div class="progress-meta">
                <span>Pagado: <?= money($prestamo['monto'] - $prestamo['saldo_actual']) ?></span>
                <span>Restante: <?= money($prestamo['saldo_actual']) ?></span>
            </div>

            <div class="next-payment">
                <div class="next-payment-left">
                    <span class="next-payment-label">Próximo pago</span>
                    <span class="next-payment-date"><?= $prestamo['proximo_pago'] ?></span>
                </div>
                <span class="next-payment-amount"><?= money($prestamo['cuota']) ?></span>
            </div>
        </div>
    </div>

    <!-- Detalles -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <svg viewBox="0 0 16 16"><circle cx="8" cy="8" r="6"/><path d="M8 7v4M8 5.5v.5"/></svg>
                <h2>Detalles del crédito</h2>
            </div>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-field">
                    <span class="info-label">Fecha de inicio</span>
                    <span class="info-value"><?= $prestamo['fecha_inicio'] ?></span>
                </div>
                <div class="info-field">
                    <span class="info-label">Plazo total</span>
                    <span class="info-value"><?= $prestamo['plazo'] ?> meses</span>
                </div>
                <div class="info-field">
                    <span class="info-label">Monto original</span>
                    <span class="info-value"><?= money($prestamo['monto']) ?></span>
                </div>
                <div class="info-field">
                    <span class="info-label">Tasa diaria</span>
                    <span class="info-value"><?= $prestamo['tasa'] ?>%</span>
                </div>
                <div class="info-field">
                    <span class="info-label">Promotor</span>
                    <span class="info-value"><?= htmlspecialchars($prestamo['promotor']) ?></span>
                </div>
                <div class="info-field">
                    <span class="info-label">Cobrador</span>
                    <span class="info-value"><?= htmlspecialchars($prestamo['cobrador']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <svg viewBox="0 0 16 16"><path d="M2 4h12M2 8h8M2 12h5"/></svg>
                <h2>Historial de pagos</h2>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagos as $p): ?>
                <tr>
                    <td><?= $p['num'] ?></td>
                    <td><?= $p['fecha'] ?></td>
                    <td><?= money($p['monto']) ?></td>
                    <td>
                        <?php if ($p['estatus'] === 'Pagado'): ?>
                            <span class="pago-badge" style="background:#dcfce7;color:#166534">✓ Pagado</span>
                        <?php else: ?>
                            <span class="pago-badge" style="background:#fef9c3;color:#854d0e">Pendiente</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Documentos -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <svg viewBox="0 0 16 16"><path d="M4 2h5l3 3v9H4V2z"/><path d="M9 2v3h3"/></svg>
                <h2>Mis documentos</h2>
            </div>
        </div>
        <div class="card-body">
            <div class="doc-list">
                <?php foreach ($documentos as $doc): ?>
                <div class="doc-item">
                    <div class="doc-left">
                        <div class="doc-icon">
                            <svg viewBox="0 0 16 16"><path d="M4 2h5l3 3v9H4V2z"/><path d="M9 2v3h3"/><path d="M6 8h4M6 11h4"/></svg>
                        </div>
                        <div>
                            <div class="doc-name"><?= htmlspecialchars($doc['nombre']) ?></div>
                            <div class="doc-type">PDF · Documento oficial</div>
                        </div>
                    </div>
                    <a href="uploads/clientes/<?= urlencode($_SESSION['curp'] ?? '') ?>/<?= $doc['archivo'] ?>"
                       class="btn-download" target="_blank">
                        <svg viewBox="0 0 14 14"><path d="M7 2v7M4 6l3 3 3-3"/><path d="M2 11h10"/></svg>
                        Descargar
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</main>

<!-- Modal solicitar -->
<div class="modal-overlay" id="modalSolicitud">
    <div style="background:var(--bg-card);border-radius:var(--radius-lg);width:440px;max-width:95vw;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.15)">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
            <h3 style="font-size:15px;font-weight:600">Solicitar nuevo préstamo</h3>
            <button id="btnCerrarModal" style="width:26px;height:26px;border:none;background:var(--bg-hover);border-radius:6px;cursor:pointer;font-size:16px;color:var(--text-muted)">×</button>
        </div>
        <div style="padding:20px">
            <p style="font-size:13px;color:var(--text-secondary);margin-bottom:16px;line-height:1.6">
                Para solicitar un nuevo préstamo, comunícate con tu promotor asignado:
            </p>
            <div style="background:var(--bg-hover);border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:16px">
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:4px">Tu promotor</div>
                <div style="font-size:15px;font-weight:600;color:var(--text-primary)"><?= htmlspecialchars($prestamo['promotor']) ?></div>
            </div>
            <p style="font-size:12px;color:var(--text-muted)">Un asesor revisará tu historial y te contactará para evaluar tu solicitud.</p>
        </div>
        <div style="padding:14px 20px;border-top:1px solid var(--border);background:var(--bg-hover);display:flex;justify-content:flex-end">
            <button id="btnCerrarModal2" style="padding:8px 16px;background:var(--accent);color:white;border:none;border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;font-weight:500;cursor:pointer">
                Entendido
            </button>
        </div>
    </div>
</div>

<script>
const modal = document.getElementById('modalSolicitud');
document.getElementById('btnSolicitar').onclick    = () => modal.classList.add('open');
document.getElementById('btnCerrarModal').onclick  = () => modal.classList.remove('open');
document.getElementById('btnCerrarModal2').onclick = () => modal.classList.remove('open');
modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('open'); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') modal.classList.remove('open'); });
</script>

</body>
</html>