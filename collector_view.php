
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
    <title>PrestaCRM — Cobrador</title>
    <style>
        /* ── Cobro column ── */
        .cobro-col { width: 120px; text-align: center; }

        .cobro-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* Checkmark — pago completo */
        .check-btn {
            width: 28px; height: 28px;
            border-radius: 50%;
            border: 2px solid var(--border-input);
            background: transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
            padding: 0;
            flex-shrink: 0;
        }
        .check-btn:hover  { border-color: #16a34a; background: #dcfce7; }
        .check-btn.checked { border-color: #16a34a; background: #16a34a; }
        .check-btn svg {
            width: 13px; height: 13px;
            opacity: 0; color: white;
            fill: none; stroke: currentColor;
            stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round;
            transition: opacity 0.1s;
        }
        .check-btn.checked svg { opacity: 1; }

        /* Pago parcial button */
        .parcial-btn {
            width: 28px; height: 28px;
            border-radius: 6px;
            border: 1px solid var(--border-input);
            background: transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
            padding: 0;
            flex-shrink: 0;
            color: var(--text-muted);
        }
        .parcial-btn:hover {
            border-color: #ca8a04;
            background: #fef9c3;
            color: #854d0e;
        }
        .parcial-btn.has-parcial {
            border-color: #ca8a04;
            background: #fef9c3;
            color: #854d0e;
        }
        .parcial-btn svg {
            width: 13px; height: 13px;
            fill: none; stroke: currentColor; stroke-width: 2;
            stroke-linecap: round;
        }

        /* Row state — cobro registrado */
        tr.cobro-completo td { opacity: 0.5; }
        tr.cobro-completo td:first-child { opacity: 1; }

        tr.cobro-parcial { background: #fffbeb; }
        tr.cobro-parcial:hover td { background: #fef9c3; }

        /* Monto cobrado display in row */
        .monto-cobrado-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            font-family: var(--font-mono);
            background: #fef9c3;
            color: #854d0e;
            margin-top: 2px;
        }

        /* ── Modal pago parcial ── */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 200;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }
        .modal-overlay.open { display: flex; animation: fadeIn 0.2s ease; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }

        .modal {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            width: 420px;
            max-width: 95vw;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            animation: slideUp 0.2s ease;
            overflow: hidden;
        }
        @keyframes slideUp {
            from { opacity:0; transform: translateY(12px) scale(0.98); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }

        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h3 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .modal-close {
            width: 26px; height: 26px;
            border: none; background: var(--bg-hover);
            border-radius: 6px; cursor: pointer;
            font-size: 16px; color: var(--text-muted);
            display: flex; align-items: center; justify-content: center;
            transition: all 0.15s;
        }
        .modal-close:hover { background: #fee2e2; color: #dc2626; }

        .modal-body { padding: 20px; }

        .modal-cliente-info {
            background: var(--bg-hover);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            margin-bottom: 18px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .modal-info-item { display: flex; flex-direction: column; gap: 2px; }
        .modal-info-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); }
        .modal-info-value { font-size: 13px; font-weight: 500; color: var(--text-primary); font-family: var(--font-mono); }

        .monto-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 16px;
        }

        .monto-option {
            padding: 10px 14px;
            border: 1.5px solid var(--border-input);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.15s;
            text-align: center;
        }

        .monto-option:hover { border-color: var(--accent); background: var(--accent-light); }
        .monto-option.selected { border-color: var(--accent); background: var(--accent-light); }

        .monto-option-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); }
        .monto-option-value { font-size: 16px; font-weight: 600; font-family: var(--font-mono); color: var(--text-primary); margin-top: 2px; }

        .monto-custom-wrap { margin-bottom: 16px; }
        .monto-custom-wrap label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); display: block; margin-bottom: 6px; }

        .monto-input-wrap { position: relative; }
        .monto-input-wrap .prefix { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: var(--text-muted); font-family: var(--font-mono); }
        .monto-input-wrap input {
            width: 100%; padding: 9px 12px 9px 22px;
            background: var(--bg-input); border: 1px solid var(--border-input);
            border-radius: var(--radius-sm); font-family: var(--font-mono);
            font-size: 14px; color: var(--text-primary); outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .monto-input-wrap input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
            background: #fff;
        }

        .nota-wrap { margin-bottom: 16px; }
        .nota-wrap label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); display: block; margin-bottom: 6px; }
        .nota-wrap textarea {
            width: 100%; padding: 8px 12px;
            background: var(--bg-input); border: 1px solid var(--border-input);
            border-radius: var(--radius-sm); font-family: var(--font);
            font-size: 13px; color: var(--text-primary); outline: none;
            resize: none; height: 70px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .nota-wrap textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .nota-wrap textarea::placeholder { color: var(--text-muted); }

        .modal-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            background: var(--bg-hover);
            display: flex; gap: 8px; justify-content: flex-end;
        }

        /* Tooltip en check */
        .check-tooltip {
            position: relative;
        }
        .check-tooltip::after {
            content: attr(data-tip);
            position: absolute;
            bottom: 110%;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.15s;
        }
        .check-tooltip:hover::after { opacity: 1; }

        /* ══════════════════════════════
           RESPONSIVE — MÓVIL
        ══════════════════════════════ */
        @media (max-width: 768px) {

            /* Sidebar oculto en móvil */
            .sidebar { display: none; }
            .main-wrapper { margin-left: 0; }

            /* Topbar compacto */
            .topbar {
                padding: 0 14px;
                height: auto;
                min-height: 56px;
                flex-wrap: wrap;
                gap: 8px;
                padding-top: 10px;
                padding-bottom: 10px;
            }
            .topbar-left h1 { font-size: 14px; }
            .topbar-left .breadcrumb { font-size: 11px; }
            .search-box { width: 100%; order: 3; }
            .search-box input { font-size: 14px; padding: 9px 12px 9px 34px; }
            .topbar-right { flex-wrap: wrap; width: 100%; gap: 8px; }
            #btnSubmitCobros { flex: 1; justify-content: center; font-size: 13px; }

            /* Content padding */
            .content { padding: 14px; }
            .content-header { margin-bottom: 14px; }
            .content-header h2 { font-size: 16px; }
            .content-header p  { font-size: 12px; }

            /* Stats bar — apilado vertical */
            .cobrador-bar {
                flex-direction: column;
                border-radius: var(--radius-md);
            }
            .cobrador-stat {
                border-right: none;
                border-bottom: 1px solid var(--border);
                padding: 10px 16px;
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
            .cobrador-stat:last-child { border-bottom: none; }
            .cobrador-stat-label { font-size: 12px; }
            .cobrador-stat-value { font-size: 16px; }
            .range-wrap { width: 120px; }

            /* Filtros apilados */
            .filter-panel {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding: 12px 14px;
            }
            .filter-group { width: 100%; }
            .filter-input { width: 100%; font-size: 14px; padding: 8px 10px; }
            .filter-divider { display: none; }
            .status-group { flex-wrap: wrap; }
            .status-pill { font-size: 13px; padding: 6px 14px; }
            .filter-actions { width: 100%; }
            .filter-actions .btn-secondary { width: 100%; justify-content: center; }

            /* Tabla → tarjetas en móvil */
            .table-card { border-radius: var(--radius-md); }
            .table-header { flex-direction: column; align-items: flex-start; gap: 8px; padding: 12px 14px; }

            /* Ocultar tabla normal */
            table { display: none; }

            /* Mostrar tarjetas móvil */
            .mobile-cards { display: flex; flex-direction: column; gap: 0; }

            .mobile-card {
                padding: 14px 16px;
                border-bottom: 1px solid var(--border);
                display: flex;
                align-items: flex-start;
                gap: 12px;
            }
            .mobile-card:last-child { border-bottom: none; }

            .mobile-card-cobro {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 6px;
                flex-shrink: 0;
                padding-top: 4px;
            }

            .mobile-card-body { flex: 1; min-width: 0; }

            .mobile-card-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 6px;
            }

            .mobile-card-nombre {
                font-size: 14px;
                font-weight: 600;
                color: var(--text-primary);
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .mobile-card-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px 16px;
                margin-top: 6px;
            }

            .mobile-card-field { display: flex; flex-direction: column; gap: 1px; }
            .mobile-card-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); }
            .mobile-card-value { font-size: 13px; font-weight: 500; color: var(--text-primary); font-family: var(--font-mono); }

            .mobile-card-fecha {
                font-size: 12px;
                font-family: var(--font-mono);
            }

            /* Tags de monto cobrado en móvil */
            .mobile-tag { margin-top: 4px; }

            /* Table footer */
            .table-footer { padding: 10px 14px; flex-direction: column; gap: 8px; align-items: flex-start; }
            .pagination-controls .page-btn { width: 34px; height: 34px; font-size: 13px; }

            /* Modal más grande en móvil */
            .modal { width: 100%; max-width: 100%; border-radius: var(--radius-lg) var(--radius-lg) 0 0; position: fixed; bottom: 0; animation: slideSheet 0.25s ease; }
            @keyframes slideSheet { from{transform:translateY(100%)} to{transform:translateY(0)} }
            .modal-overlay.open { align-items: flex-end; }
            .monto-options { grid-template-columns: 1fr 1fr; }
            .monto-input-wrap input { font-size: 18px; padding: 12px 12px 12px 24px; }
            .modal-footer { padding: 14px 16px 24px; }
        }

        /* Primero — ocultar en desktop */
.mobile-cards { display: none; }

/* Luego — mostrar en móvil (debe estar DESPUÉS) */
@media (max-width: 768px) {
    table { display: none; }
    .mobile-cards { display: flex; flex-direction: column; gap: 0; }
}
    </style>
</head>
<body>

<!-- Sidebar -->

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark"><svg viewBox="0 0 14 14" fill="white"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg></div>
        <span class="logo-text">PrestaCRM</span>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Mi panel</span>
        <a class="nav-item active" href="#">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="12" height="10" rx="1.5"/><path d="M5 7h6M5 10h4"/></svg>
            Mis cobros
        </a>
        <a class="nav-item" href="#">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2,12 6,7 10,9 14,4"/></svg>
            Mi desempeño
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

<!-- Main -->
<div class="main-wrapper">
    <header class="topbar">
        <div class="topbar-left">
            <h1>Mis cobros</h1>
            <div class="breadcrumb">Panel de cobrador · Hoy, <span id="today-date"></span></div>
        </div>
        <div class="topbar-right">
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M16.5 16.5L22 22"/></svg>
                <input type="text" id="globalSearch" placeholder="Buscar por ID o nombre…" oninput="filterTable()">
            </div>
            <button class="btn-primary" id="btnSubmitCobros" onclick="submitCobros()" disabled style="opacity:0.5">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M2 7l3 3 7-7"/></svg>
                Enviar cobros
            </button>
        </div>
    </header>

    <main class="content">
        <div class="content-header">
            <div>
                <h2>Lista de cobro</h2>
                <p>Marca pago completo con ✓ o registra un monto parcial con el botón $</p>
            </div>
        </div>

        <!-- Stats bar -->
        <div class="cobrador-bar">
            <div class="cobrador-stat">
                <div class="cobrador-stat-label">Mi rango</div>
                <div class="cobrador-stat-value">Diamante</div>
                <div class="cobrador-stat-sub">Nivel máximo</div>
            </div>
            <div class="cobrador-stat">
                <div class="cobrador-stat-label">Monto máximo</div>
                <div class="cobrador-stat-value accent" id="montoMax">$200,000</div>
            </div>
            <div class="cobrador-stat">
                <div class="cobrador-stat-label">Cobrado hoy</div>
                <div class="cobrador-stat-value" id="montoCobrado">$0</div>
                <div class="range-wrap">
                    <div class="range-track"><div class="range-fill" id="cobroFill" style="width:0%"></div></div>
                </div>
            </div>
            <div class="cobrador-stat">
                <div class="cobrador-stat-label">Completos</div>
                <div class="cobrador-stat-value" id="checkCount" style="color:#16a34a">0</div>
            </div>
            <div class="cobrador-stat">
                <div class="cobrador-stat-label">Parciales</div>
                <div class="cobrador-stat-value" id="parcialCount" style="color:#ca8a04">0</div>
            </div>
            <div class="cobrador-stat">
                <div class="cobrador-stat-label">Pendientes</div>
                <div class="cobrador-stat-value" id="pendientesCount" style="color:var(--text-secondary)">—</div>
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
                    <span class="status-pill pill-activo" data-status="Activo" onclick="togglePill(this)"><span class="dot"></span> Activo</span>
                    <span class="status-pill pill-pendiente" data-status="Pendiente" onclick="togglePill(this)"><span class="dot"></span> Pendiente</span>
                    <span class="status-pill pill-atrasado" data-status="Atrasado" onclick="togglePill(this)"><span class="dot"></span> Atrasado</span>
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
                    <div class="table-title">Clientes asignados</div>
                    <div class="table-count" id="tableCount">Cargando…</div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;font-size:12px;color:var(--text-muted)">
                    <span style="display:flex;align-items:center;gap:5px">
                        <span style="width:16px;height:16px;border-radius:50%;border:2px solid #16a34a;background:#16a34a;display:inline-flex;align-items:center;justify-content:center">
                            <svg width="8" height="8" viewBox="0 0 12 12" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg>
                        </span>
                        Pago completo
                    </span>
                    <span style="display:flex;align-items:center;gap:5px">
                        <span style="width:16px;height:16px;border-radius:4px;border:1.5px solid #ca8a04;background:#fef9c3;display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#854d0e">$</span>
                        Pago parcial
                    </span>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="cobro-col">Cobro</th>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Celular</th>
                        <th>Cuota</th>
                        <th>Saldo actual</th>
                        <th>Fecha de cobro</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody id="tableBody">

                    <tr data-status="Activo" data-pago="2100" data-id="1042" data-nombre="Laura Méndez">
                        <td class="cobro-col">
                            <div class="cobro-actions">
                                <button class="check-btn check-tooltip" data-tip="Pago completo $2,100"
                                    onclick="toggleCheck(this)">
                                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg>
                                </button>
                                <button class="parcial-btn check-tooltip" data-tip="Registrar monto parcial"
                                    onclick="openParcial(this)">
                                    <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M3 6h8"/></svg>
                                </button>
                            </div>
                            <div id="tag-1042"></div>
                        </td>
                        <td class="td-id">#1042</td>
                        <td class="td-name"><span class="initials">LM</span>Laura Méndez</td>
                        <td class="td-numeric">55 1234 5678</td>
                        <td class="td-amount">$2,100</td>
                        <td class="td-amount">$38,200</td>
                        <td class="fecha-hoy td-numeric">Hoy</td>
                        <td><span class="badge badge-activo"><span class="dot"></span>Activo</span></td>
                    </tr>

                    <tr data-status="Atrasado" data-pago="2050" data-id="1029" data-nombre="Ana Torres">
                        <td class="cobro-col">
                            <div class="cobro-actions">
                                <button class="check-btn check-tooltip" data-tip="Pago completo $2,050"
                                    onclick="toggleCheck(this)">
                                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg>
                                </button>
                                <button class="parcial-btn check-tooltip" data-tip="Registrar monto parcial"
                                    onclick="openParcial(this)">
                                    <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M3 6h8"/></svg>
                                </button>
                            </div>
                            <div id="tag-1029"></div>
                        </td>
                        <td class="td-id">#1029</td>
                        <td class="td-name"><span class="initials" style="background:#fee2e2;color:#991b1b">AT</span>Ana Torres</td>
                        <td class="td-numeric">55 9900 1122</td>
                        <td class="td-amount">$2,050</td>
                        <td class="td-amount">$5,100</td>
                        <td class="fecha-hoy td-numeric">Hoy — 5 días atraso</td>
                        <td><span class="badge badge-atrasado"><span class="dot"></span>Atrasado</span></td>
                    </tr>

                    <tr data-status="Activo" data-pago="2800" data-id="1038" data-nombre="Carlos Rivas">
                        <td class="cobro-col">
                            <div class="cobro-actions">
                                <button class="check-btn check-tooltip" data-tip="Pago completo $2,800"
                                    onclick="toggleCheck(this)">
                                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg>
                                </button>
                                <button class="parcial-btn check-tooltip" data-tip="Registrar monto parcial"
                                    onclick="openParcial(this)">
                                    <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M3 6h8"/></svg>
                                </button>
                            </div>
                            <div id="tag-1038"></div>
                        </td>
                        <td class="td-id">#1038</td>
                        <td class="td-name"><span class="initials">CR</span>Carlos Rivas</td>
                        <td class="td-numeric">55 8765 4321</td>
                        <td class="td-amount">$2,800</td>
                        <td class="td-amount">$71,500</td>
                        <td class="fecha-prox td-numeric">Mañana</td>
                        <td><span class="badge badge-activo"><span class="dot"></span>Activo</span></td>
                    </tr>

                    <tr data-status="Pendiente" data-pago="3100" data-id="1011" data-nombre="Jorge López">
                        <td class="cobro-col">
                            <div class="cobro-actions">
                                <button class="check-btn check-tooltip" data-tip="Pago completo $3,100"
                                    onclick="toggleCheck(this)">
                                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg>
                                </button>
                                <button class="parcial-btn check-tooltip" data-tip="Registrar monto parcial"
                                    onclick="openParcial(this)">
                                    <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M3 6h8"/></svg>
                                </button>
                            </div>
                            <div id="tag-1011"></div>
                        </td>
                        <td class="td-id">#1011</td>
                        <td class="td-name"><span class="initials" style="background:#fef9c3;color:#854d0e">JL</span>Jorge López</td>
                        <td class="td-numeric">55 5566 7788</td>
                        <td class="td-amount">$3,100</td>
                        <td class="td-amount">$115,200</td>
                        <td class="fecha-ok td-numeric">15 Mar</td>
                        <td><span class="badge badge-pendiente"><span class="dot"></span>Pendiente</span></td>
                    </tr>

                    <tr data-status="Activo" data-pago="2200" data-id="1018" data-nombre="Sofía Ramírez">
                        <td class="cobro-col">
                            <div class="cobro-actions">
                                <button class="check-btn check-tooltip" data-tip="Pago completo $2,200"
                                    onclick="toggleCheck(this)">
                                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg>
                                </button>
                                <button class="parcial-btn check-tooltip" data-tip="Registrar monto parcial"
                                    onclick="openParcial(this)">
                                    <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M3 6h8"/></svg>
                                </button>
                            </div>
                            <div id="tag-1018"></div>
                        </td>
                        <td class="td-id">#1018</td>
                        <td class="td-name"><span class="initials">SR</span>Sofía Ramírez</td>
                        <td class="td-numeric">55 3344 5500</td>
                        <td class="td-amount">$2,200</td>
                        <td class="td-amount">$21,000</td>
                        <td class="fecha-ok td-numeric">16 Mar</td>
                        <td><span class="badge badge-activo"><span class="dot"></span>Activo</span></td>
                    </tr>

                </tbody>
            </table>
            <!-- ══ TARJETAS MÓVIL (visibles solo en pantalla pequeña) ══ -->
            <div class="mobile-cards" id="mobileCards">

                <div class="mobile-card" data-status="Activo" data-pago="2100" data-id="1042" data-nombre="Laura Méndez">
                    <div class="mobile-card-cobro">
                        <button class="check-btn" onclick="toggleCheck(this)"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg></button>
                        <button class="parcial-btn" onclick="openParcialCard(this)"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M3 6h8"/></svg></button>
                        <div id="ctag-1042" class="mobile-tag"></div>
                    </div>
                    <div class="mobile-card-body">
                        <div class="mobile-card-top">
                            <div class="mobile-card-nombre"><span class="initials">LM</span>Laura Méndez</div>
                            <span class="badge badge-activo"><span class="dot"></span>Activo</span>
                        </div>
                        <div class="mobile-card-grid">
                            <div class="mobile-card-field"><span class="mobile-card-label">ID</span><span class="mobile-card-value">#1042</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Cuota</span><span class="mobile-card-value">$2,100</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Saldo</span><span class="mobile-card-value">$38,200</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Cobro</span><span class="mobile-card-value mobile-card-fecha fecha-hoy">Hoy</span></div>
                        </div>
                    </div>
                </div>

                <div class="mobile-card" data-status="Atrasado" data-pago="2050" data-id="1029" data-nombre="Ana Torres">
                    <div class="mobile-card-cobro">
                        <button class="check-btn" onclick="toggleCheck(this)"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg></button>
                        <button class="parcial-btn" onclick="openParcialCard(this)"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M3 6h8"/></svg></button>
                        <div id="ctag-1029" class="mobile-tag"></div>
                    </div>
                    <div class="mobile-card-body">
                        <div class="mobile-card-top">
                            <div class="mobile-card-nombre"><span class="initials" style="background:#fee2e2;color:#991b1b">AT</span>Ana Torres</div>
                            <span class="badge badge-atrasado"><span class="dot"></span>Atrasado</span>
                        </div>
                        <div class="mobile-card-grid">
                            <div class="mobile-card-field"><span class="mobile-card-label">ID</span><span class="mobile-card-value">#1029</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Cuota</span><span class="mobile-card-value">$2,050</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Saldo</span><span class="mobile-card-value">$5,100</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Cobro</span><span class="mobile-card-value mobile-card-fecha fecha-hoy">Hoy — 5 días</span></div>
                        </div>
                    </div>
                </div>

                <div class="mobile-card" data-status="Activo" data-pago="2800" data-id="1038" data-nombre="Carlos Rivas">
                    <div class="mobile-card-cobro">
                        <button class="check-btn" onclick="toggleCheck(this)"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg></button>
                        <button class="parcial-btn" onclick="openParcialCard(this)"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M3 6h8"/></svg></button>
                        <div id="ctag-1038" class="mobile-tag"></div>
                    </div>
                    <div class="mobile-card-body">
                        <div class="mobile-card-top">
                            <div class="mobile-card-nombre"><span class="initials">CR</span>Carlos Rivas</div>
                            <span class="badge badge-activo"><span class="dot"></span>Activo</span>
                        </div>
                        <div class="mobile-card-grid">
                            <div class="mobile-card-field"><span class="mobile-card-label">ID</span><span class="mobile-card-value">#1038</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Cuota</span><span class="mobile-card-value">$2,800</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Saldo</span><span class="mobile-card-value">$71,500</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Cobro</span><span class="mobile-card-value mobile-card-fecha fecha-prox">Mañana</span></div>
                        </div>
                    </div>
                </div>

                <div class="mobile-card" data-status="Pendiente" data-pago="3100" data-id="1011" data-nombre="Jorge López">
                    <div class="mobile-card-cobro">
                        <button class="check-btn" onclick="toggleCheck(this)"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg></button>
                        <button class="parcial-btn" onclick="openParcialCard(this)"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M3 6h8"/></svg></button>
                        <div id="ctag-1011" class="mobile-tag"></div>
                    </div>
                    <div class="mobile-card-body">
                        <div class="mobile-card-top">
                            <div class="mobile-card-nombre"><span class="initials" style="background:#fef9c3;color:#854d0e">JL</span>Jorge López</div>
                            <span class="badge badge-pendiente"><span class="dot"></span>Pendiente</span>
                        </div>
                        <div class="mobile-card-grid">
                            <div class="mobile-card-field"><span class="mobile-card-label">ID</span><span class="mobile-card-value">#1011</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Cuota</span><span class="mobile-card-value">$3,100</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Saldo</span><span class="mobile-card-value">$115,200</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Cobro</span><span class="mobile-card-value mobile-card-fecha fecha-ok">15 Mar</span></div>
                        </div>
                    </div>
                </div>

                <div class="mobile-card" data-status="Activo" data-pago="2200" data-id="1018" data-nombre="Sofía Ramírez">
                    <div class="mobile-card-cobro">
                        <button class="check-btn" onclick="toggleCheck(this)"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg></button>
                        <button class="parcial-btn" onclick="openParcialCard(this)"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M7 2v10M3 6h8"/></svg></button>
                        <div id="ctag-1018" class="mobile-tag"></div>
                    </div>
                    <div class="mobile-card-body">
                        <div class="mobile-card-top">
                            <div class="mobile-card-nombre"><span class="initials">SR</span>Sofía Ramírez</div>
                            <span class="badge badge-activo"><span class="dot"></span>Activo</span>
                        </div>
                        <div class="mobile-card-grid">
                            <div class="mobile-card-field"><span class="mobile-card-label">ID</span><span class="mobile-card-value">#1018</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Cuota</span><span class="mobile-card-value">$2,200</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Saldo</span><span class="mobile-card-value">$21,000</span></div>
                            <div class="mobile-card-field"><span class="mobile-card-label">Cobro</span><span class="mobile-card-value mobile-card-fecha fecha-ok">16 Mar</span></div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- ══ FIN TARJETAS MÓVIL ══ -->

            <div class="table-footer">
                <span class="pagination-info" id="cobradoFooter">Cobrado hoy: $0</span>
                <div class="pagination-controls">
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">›</button>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- ══════════════════════════════════════
     MODAL — Pago parcial
══════════════════════════════════════ -->
<div class="modal-overlay" id="modalParcial" onclick="closeModalOutside(event)">
    <div class="modal">
        <div class="modal-header">
            <h3>Registrar pago — <span id="modal-nombre" style="color:var(--accent)"></span></h3>
            <button class="modal-close" onclick="closeParcial()">×</button>
        </div>
        <div class="modal-body">

            <!-- Info del cliente -->
            <div class="modal-cliente-info">
                <div class="modal-info-item">
                    <span class="modal-info-label">Préstamo ID</span>
                    <span class="modal-info-value" id="modal-id">—</span>
                </div>
                <div class="modal-info-item">
                    <span class="modal-info-label">Cuota esperada</span>
                    <span class="modal-info-value" id="modal-cuota">—</span>
                </div>
            </div>

            <!-- Opciones rápidas -->
            <div class="monto-options">
                <div class="monto-option" id="opt-completo" onclick="selectOpcion('completo')">
                    <div class="monto-option-label">Pago completo</div>
                    <div class="monto-option-value" id="opt-completo-val">—</div>
                </div>
                <div class="monto-option" id="opt-parcial" onclick="selectOpcion('parcial')">
                    <div class="monto-option-label">Pago parcial</div>
                    <div class="monto-option-value">Otro monto</div>
                </div>
            </div>

            <!-- Monto personalizado -->
            <div class="monto-custom-wrap">
                <label>Monto cobrado</label>
                <div class="monto-input-wrap">
                    <span class="prefix">$</span>
                    <input type="number" id="modal-monto" placeholder="0.00" min="1" step="0.01"
                        oninput="onMontoInput()">
                </div>
            </div>

            <!-- Nota -->
            <div class="nota-wrap">
                <label>Nota <span style="font-weight:400;text-transform:none;letter-spacing:0">(opcional)</span></label>
                <textarea id="modal-nota" placeholder="Ej: Cliente pagó parcial por situación económica, acuerda cubrir el resto el viernes…"></textarea>
            </div>

        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeParcial()">Cancelar</button>
            <button class="btn-primary" id="modal-submit-btn" onclick="confirmarPago()">
                Confirmar pago
            </button>
        </div>
    </div>
</div>

<script>
/* ════════════════════════════════════════
   STATE
════════════════════════════════════════ */
const maxCobro = 200000;

// cobros[id] = { tipo: 'completo'|'parcial', monto: number, nota: string }
const cobros = {};

let modalRow = null; // row activa en el modal

/* ════════════════════════════════════════
   DATE
════════════════════════════════════════ */
document.getElementById('today-date').textContent =
    new Date().toLocaleDateString('es-MX', { weekday:'long', year:'numeric', month:'long', day:'numeric' });

/* ════════════════════════════════════════
   PAGO COMPLETO — checkmark
════════════════════════════════════════ */
function toggleCheck(btn) {
    const row   = btn.closest('tr');
    const id    = row.dataset.id;
    const pago  = parseFloat(row.dataset.pago);
    const was   = btn.classList.contains('checked');

    // Si había un parcial en esta fila, quitarlo
    if (cobros[id]) {
        const parcialBtn = row.querySelector('.parcial-btn');
        parcialBtn.classList.remove('has-parcial');
        delete cobros[id];
    }

    btn.classList.toggle('checked', !was);
    row.classList.toggle('cobro-completo', !was);

    if (!was) {
        cobros[id] = { tipo: 'completo', monto: pago, nota: '' };
        setTag(id, pago, 'completo');
    } else {
        delete cobros[id];
        setTag(id, 0, null);
    }

    updateStats();
}

/* ════════════════════════════════════════
   MODAL — PAGO PARCIAL
════════════════════════════════════════ */
function openParcial(btn) {
    modalRow = btn.closest('tr');
    const id     = modalRow.dataset.id;
    const pago   = parseFloat(modalRow.dataset.pago);
    const nombre = modalRow.dataset.nombre;

    document.getElementById('modal-nombre').textContent  = nombre;
    document.getElementById('modal-id').textContent      = '#' + id;
    document.getElementById('modal-cuota').textContent   = '$' + pago.toLocaleString();
    document.getElementById('opt-completo-val').textContent = '$' + pago.toLocaleString();

    // Pre-fill si ya había un monto registrado
    const existing = cobros[id];
    if (existing) {
        document.getElementById('modal-monto').value = existing.monto;
        document.getElementById('modal-nota').value  = existing.nota || '';
        selectOpcion(existing.tipo === 'completo' ? 'completo' : 'parcial');
    } else {
        document.getElementById('modal-monto').value = '';
        document.getElementById('modal-nota').value  = '';
        selectOpcion(null);
    }

    document.getElementById('modalParcial').classList.add('open');
    setTimeout(() => document.getElementById('modal-monto').focus(), 200);
}

function closeParcial() {
    document.getElementById('modalParcial').classList.remove('open');
    modalRow = null;
}

function closeModalOutside(e) {
    if (e.target === document.getElementById('modalParcial')) closeParcial();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeParcial(); });

/* ── Opciones rápidas dentro del modal ── */
function selectOpcion(tipo) {
    document.getElementById('opt-completo').classList.toggle('selected', tipo === 'completo');
    document.getElementById('opt-parcial').classList.toggle('selected',  tipo === 'parcial');

    if (tipo === 'completo' && modalRow) {
        document.getElementById('modal-monto').value = modalRow.dataset.pago;
    } else if (tipo === 'parcial') {
        document.getElementById('modal-monto').value = '';
        document.getElementById('modal-monto').focus();
    }
}

function onMontoInput() {
    const pago  = modalRow ? parseFloat(modalRow.dataset.pago) : 0;
    const monto = parseFloat(document.getElementById('modal-monto').value) || 0;
    document.getElementById('opt-completo').classList.toggle('selected', monto === pago);
    document.getElementById('opt-parcial').classList.toggle('selected',  monto > 0 && monto !== pago);
}

/* ── Confirmar pago desde modal ── */
function confirmarPago() {
    if (!modalRow) return;

    const id    = modalRow.dataset.id;
    const pago  = parseFloat(modalRow.dataset.pago);
    const monto = parseFloat(document.getElementById('modal-monto').value);
    const nota  = document.getElementById('modal-nota').value.trim();

    if (!monto || monto <= 0) {
        document.getElementById('modal-monto').style.borderColor = '#dc2626';
        document.getElementById('modal-monto').focus();
        return;
    }

    document.getElementById('modal-monto').style.borderColor = '';

    const tipo = monto >= pago ? 'completo' : 'parcial';

    // Quitar checkmark si había
    const checkBtn = modalRow.querySelector('.check-btn');
    checkBtn.classList.remove('checked');
    modalRow.classList.remove('cobro-completo');

    // Marcar parcial btn
    const parcialBtn = modalRow.querySelector('.parcial-btn');
    parcialBtn.classList.add('has-parcial');

    // Marcar fila
    modalRow.classList.toggle('cobro-parcial', tipo === 'parcial');
    modalRow.classList.toggle('cobro-completo', tipo === 'completo');

    cobros[id] = { tipo, monto, nota };
    setTag(id, monto, tipo);

    updateStats();
    closeParcial();
}

/* ════════════════════════════════════════
   TAG bajo los botones
════════════════════════════════════════ */
function setTag(id, monto, tipo) {
    const el = document.getElementById('tag-' + id);
    if (!el) return;
    if (!tipo || monto <= 0) { el.innerHTML = ''; return; }
    const color = tipo === 'completo' ? '#dcfce7' : '#fef9c3';
    const text  = tipo === 'completo' ? '#166534' : '#854d0e';
    el.innerHTML = `<span class="monto-cobrado-tag" style="background:${color};color:${text}">$${monto.toLocaleString()}</span>`;
}

/* ════════════════════════════════════════
   STATS
════════════════════════════════════════ */
function updateStats() {
    let total     = 0;
    let completos = 0;
    let parciales = 0;
    let pendientes = 0;

    document.querySelectorAll('#tableBody tr').forEach(row => {
        if (row.style.display === 'none') return;
        const id   = row.dataset.id;
        const cobro = cobros[id];
        if (cobro) {
            total += cobro.monto;
            if (cobro.tipo === 'completo') completos++;
            else parciales++;
        } else {
            pendientes++;
        }
    });

    const pct = Math.min(100, (total / maxCobro) * 100);
    document.getElementById('montoCobrado').textContent = '$' + total.toLocaleString();
    document.getElementById('cobroFill').style.width    = pct.toFixed(1) + '%';
    document.getElementById('checkCount').textContent   = completos;
    document.getElementById('parcialCount').textContent = parciales;
    document.getElementById('pendientesCount').textContent = pendientes;
    document.getElementById('cobradoFooter').textContent   = `Cobrado hoy: $${total.toLocaleString()} · ${completos} completos · ${parciales} parciales`;

    const submitBtn = document.getElementById('btnSubmitCobros');
    const hayCobros = Object.keys(cobros).length > 0;
    submitBtn.disabled = !hayCobros;
    submitBtn.style.opacity = hayCobros ? '1' : '0.5';
}

/* ════════════════════════════════════════
   SUBMIT — envía al backend
════════════════════════════════════════ */
function submitCobros() {
    if (Object.keys(cobros).length === 0) return;

    const resumen = Object.entries(cobros).map(([id, c]) =>
        `#${id}: $${c.monto.toLocaleString()} (${c.tipo})`
    ).join('\n');

    // TODO backend: fetch('/php/registrar_cobros.php', { method:'POST', body: JSON.stringify(cobros) })
    alert('Cobros a registrar:\n\n' + resumen + '\n\n→ El backend recibirá estos datos vía POST');
}

/* ════════════════════════════════════════
   PAGO PARCIAL DESDE TARJETA MÓVIL
════════════════════════════════════════ */
function openParcialCard(btn) {
    // Reutiliza el mismo modal — busca datos en .mobile-card
    const card = btn.closest('.mobile-card') || btn.closest('tr');
    openParcialFromElement(card, btn);
}

function openParcialFromElement(el, btn) {
    modalRow = el;
    const id     = el.dataset.id;
    const pago   = parseFloat(el.dataset.pago);
    const nombre = el.dataset.nombre;

    document.getElementById('modal-nombre').textContent  = nombre;
    document.getElementById('modal-id').textContent      = '#' + id;
    document.getElementById('modal-cuota').textContent   = '$' + pago.toLocaleString();
    document.getElementById('opt-completo-val').textContent = '$' + pago.toLocaleString();

    const existing = cobros[id];
    if (existing) {
        document.getElementById('modal-monto').value = existing.monto;
        document.getElementById('modal-nota').value  = existing.nota || '';
        selectOpcion(existing.tipo === 'completo' ? 'completo' : 'parcial');
    } else {
        document.getElementById('modal-monto').value = '';
        document.getElementById('modal-nota').value  = '';
        selectOpcion(null);
    }

    document.getElementById('modalParcial').classList.add('open');
    setTimeout(() => document.getElementById('modal-monto').focus(), 300);
}

/* Sobreescribir openParcial para manejar ambos (tabla + tarjeta) */
function openParcial(btn) {
    const row = btn.closest('tr');
    openParcialFromElement(row, btn);
}

/* ════════════════════════════════════════
   SYNC TAGS EN TARJETAS MÓVIL
════════════════════════════════════════ */
function setTag(id, monto, tipo) {
    // Desktop tag
    const el = document.getElementById('tag-' + id);
    // Mobile tag
    const cel = document.getElementById('ctag-' + id);

    [el, cel].forEach(tag => {
        if (!tag) return;
        if (!tipo || monto <= 0) { tag.innerHTML = ''; return; }
        const color = tipo === 'completo' ? '#dcfce7' : '#fef9c3';
        const text  = tipo === 'completo' ? '#166534' : '#854d0e';
        tag.innerHTML = `<span class="monto-cobrado-tag" style="background:${color};color:${text};font-size:10px">$${monto.toLocaleString()}</span>`;
    });
}

/* Sync checkmark en tarjeta móvil cuando se confirma desde modal */
function syncMobileCard(id, tipo) {
    const card = document.querySelector(`.mobile-card[data-id="${id}"]`);
    if (!card) return;
    const checkBtn   = card.querySelector('.check-btn');
    const parcialBtn = card.querySelector('.parcial-btn');
    checkBtn.classList.toggle('checked',       tipo === 'completo');
    parcialBtn.classList.toggle('has-parcial', tipo === 'parcial');
    card.classList.toggle('cobro-completo', tipo === 'completo');
    card.classList.toggle('cobro-parcial',  tipo === 'parcial');
}

/* ════════════════════════════════════════
   OVERRIDE confirmarPago para sync móvil
════════════════════════════════════════ */
const _confirmarPago = confirmarPago;
// Redefine confirmarPago to also sync mobile
window.confirmarPago = function() {
    if (!modalRow) return;
    const id    = modalRow.dataset.id;
    const pago  = parseFloat(modalRow.dataset.pago);
    const monto = parseFloat(document.getElementById('modal-monto').value);
    const nota  = document.getElementById('modal-nota').value.trim();

    if (!monto || monto <= 0) {
        document.getElementById('modal-monto').style.borderColor = '#dc2626';
        document.getElementById('modal-monto').focus();
        return;
    }
    document.getElementById('modal-monto').style.borderColor = '';

    const tipo = monto >= pago ? 'completo' : 'parcial';

    // Sync tabla desktop
    const tableRow = document.querySelector(`#tableBody tr[data-id="${id}"]`);
    if (tableRow) {
        const checkBtn   = tableRow.querySelector('.check-btn');
        const parcialBtn = tableRow.querySelector('.parcial-btn');
        checkBtn.classList.toggle('checked',       tipo === 'completo');
        parcialBtn.classList.toggle('has-parcial', tipo === 'parcial');
        tableRow.classList.toggle('cobro-parcial',  tipo === 'parcial');
        tableRow.classList.toggle('cobro-completo', tipo === 'completo');
    }

    // Sync tarjeta móvil
    syncMobileCard(id, tipo);

    cobros[id] = { tipo, monto, nota };
    setTag(id, monto, tipo);
    updateStats();
    closeParcial();
};

/* ════════════════════════════════════════
   FILTERS — tabla + tarjetas
════════════════════════════════════════ */
let activeFilters = new Set(['Activo', 'Pendiente', 'Atrasado']);

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

    // Filtrar filas de tabla (desktop)
    document.querySelectorAll('#tableBody tr').forEach(row => {
        const show = activeFilters.has(row.dataset.status)
            && (!id || row.cells[1]?.textContent.toLowerCase().includes(id))
            && (!q  || row.textContent.toLowerCase().includes(q));
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    // Filtrar tarjetas (móvil) — misma lógica
    document.querySelectorAll('.mobile-card').forEach(card => {
        const show = activeFilters.has(card.dataset.status)
            && (!id || card.dataset.id.toLowerCase().includes(id))
            && (!q  || card.textContent.toLowerCase().includes(q));
        card.style.display = show ? '' : 'none';
    });

    document.getElementById('tableCount').textContent = `${visible} cliente${visible !== 1 ? 's' : ''} visibles`;
    updateStats();
}

function resetFilters() {
    document.getElementById('filterId').value = '';
    document.getElementById('globalSearch').value = '';
    activeFilters = new Set(['Activo','Pendiente','Atrasado']);
    document.querySelectorAll('.status-pill').forEach(p => p.classList.remove('inactive'));
    filterTable();
}

window.addEventListener('load', filterTable);
</script>
</body>
</html>