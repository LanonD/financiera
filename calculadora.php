<?php session_start(); ?>
<?php
/* =============================================================
   CALCULADORA DE PRÉSTAMOS — PrestaCRM
   Fórmula: Cuota fija (annuity / amortización francesa)

   C = P × (r × (1+r)^n) / ((1+r)^n − 1)

   Donde:
     P = Principal
     r = tasa por período (tasa_diaria × días_entre_pagos)
     n = número de pagos
     C = cuota fija
============================================================= */

// ── Frecuencias disponibles (días entre cada pago) ──────────
$frecuencias = [
    'diario'    => ['dias' => 1,  'label' => 'Diario'],
    'semanal'   => ['dias' => 7,  'label' => 'Semanal'],
    'quincenal' => ['dias' => 14, 'label' => 'Quincenal (cada 2 semanas)'],
    'mensual'   => ['dias' => 30, 'label' => 'Mensual'],
];

// ── Leer y validar inputs ────────────────────────────────────
$result   = null;
$errors   = [];
$input    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input['principal']      = trim($_POST['principal']      ?? '');
    $input['tasa_diaria']    = trim($_POST['tasa_diaria']    ?? '');
    $input['num_pagos']      = trim($_POST['num_pagos']      ?? '');
    $input['frecuencia']     = trim($_POST['frecuencia']     ?? '');
    $input['fecha_inicio']   = trim($_POST['fecha_inicio']   ?? '');

    // Validaciones
    if ($input['principal'] === '' || !is_numeric($input['principal']) || floatval($input['principal']) <= 0)
        $errors[] = 'El monto del préstamo debe ser un número positivo.';

    if ($input['tasa_diaria'] === '' || !is_numeric($input['tasa_diaria']) || floatval($input['tasa_diaria']) <= 0 || floatval($input['tasa_diaria']) > 100)
        $errors[] = 'La tasa de interés diaria debe estar entre 0.01% y 100%.';

    if ($input['num_pagos'] === '' || !ctype_digit($input['num_pagos']) || intval($input['num_pagos']) < 1 || intval($input['num_pagos']) > 365)
        $errors[] = 'El número de pagos debe ser un entero entre 1 y 365.';

    if (!array_key_exists($input['frecuencia'], $frecuencias))
        $errors[] = 'Selecciona una frecuencia de pago válida.';

    // ── Calcular si no hay errores ───────────────────────────
    if (empty($errors)) {

        $P   = floatval($input['principal']);
        $td  = floatval($input['tasa_diaria']) / 100;   // tasa diaria como decimal
        $n   = intval($input['num_pagos']);
        $d   = $frecuencias[$input['frecuencia']]['dias']; // días entre pagos

        // Tasa por período
        $r = $td * $d;

        // Cuota fija (formula annuity)
        // Si tasa = 0 (edge case), cuota = P/n
        if ($r == 0) {
            $cuota = $P / $n;
        } else {
            $cuota = $P * ($r * pow(1 + $r, $n)) / (pow(1 + $r, $n) - 1);
        }

        // ── Tabla de amortización ────────────────────────────
        $tabla          = [];
        $saldo          = $P;
        $total_interes  = 0;
        $total_capital  = 0;
        $total_pago     = 0;

        // Fecha de inicio para calcular fechas de pago
        $fecha_inicio = !empty($input['fecha_inicio'])
            ? new DateTime($input['fecha_inicio'])
            : new DateTime();

        for ($i = 1; $i <= $n; $i++) {
            $interes   = $saldo * $r;
            $capital   = $cuota - $interes;
            
            // Último pago: ajustar por redondeo
            if ($i === $n) {
                $capital = $saldo;
                $cuota_real = $capital + $interes;
            } else {
                $cuota_real = $cuota;
            }

            $saldo_nuevo = $saldo - $capital;

            // Fecha de este pago
            $fecha_pago = clone $fecha_inicio;
            $fecha_pago->modify('+' . ($d * $i) . ' days');

            $tabla[] = [
                'pago'        => $i,
                'fecha'       => $fecha_pago->format('d/m/Y'),
                'cuota'       => $cuota_real,
                'interes'     => $interes,
                'capital'     => $capital,
                'saldo'       => max(0, $saldo_nuevo),
            ];

            $total_interes += $interes;
            $total_capital += $capital;
            $total_pago    += $cuota_real;
            $saldo          = $saldo_nuevo;
        }

        $result = [
            'principal'      => $P,
            'cuota'          => $cuota,
            'tasa_periodo'   => $r * 100,
            'tasa_diaria'    => $td * 100,
            'num_pagos'      => $n,
            'frecuencia'     => $frecuencias[$input['frecuencia']]['label'],
            'dias_periodo'   => $d,
            'total_pago'     => $total_pago,
            'total_interes'  => $total_interes,
            'total_capital'  => $total_capital,
            'tabla'          => $tabla,
        ];
    }
}

// ── Helpers ──────────────────────────────────────────────────
function money(float $v): string {
    return '$' . number_format($v, 2, '.', ',');
}

function pct(float $v): string {
    return number_format($v, 4) . '%';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DE-SA — Calculadora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <style>
        /* ── Page-specific styles ── */
        .calc-layout {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 20px;
            align-items: start;
        }

        /* Form panel */
        .form-panel {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
            position: sticky;
            top: 76px;
        }

        .form-panel-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-panel-header h3 {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-panel-body {
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .form-panel-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            background: var(--bg-hover);
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .field label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
        }

        .field input,
        .field select {
            padding: 9px 12px;
            background: var(--bg-input);
            border: 1px solid var(--border-input);
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 13px;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
            background: #fff;
        }

        .field input::placeholder { color: var(--text-muted); }

        .field-hint {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .input-prefix-wrap {
            position: relative;
        }

        .input-prefix-wrap .prefix {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            color: var(--text-muted);
            font-family: var(--font-mono);
            pointer-events: none;
        }

        .input-prefix-wrap input  { padding-left: 22px; }
        .input-suffix-wrap .suffix {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            color: var(--text-muted);
            font-family: var(--font-mono);
            pointer-events: none;
        }
        .input-suffix-wrap { position: relative; }
        .input-suffix-wrap input { padding-right: 24px; }

        /* Errors */
        .error-box {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            border-radius: var(--radius-sm);
            padding: 10px 14px;
        }

        .error-box p {
            font-size: 12px;
            color: #991b1b;
            margin-bottom: 2px;
        }

        .error-box p:last-child { margin-bottom: 0; }

        /* Results area */
        .results-area {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Summary cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .summary-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 14px 16px;
        }

        .summary-card-label {
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .summary-card-value {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            font-family: var(--font-mono);
            letter-spacing: -0.02em;
            line-height: 1;
        }

        .summary-card-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .summary-card.accent  { border-top: 3px solid var(--accent); }
        .summary-card.green   { border-top: 3px solid #16a34a; }
        .summary-card.red     { border-top: 3px solid #dc2626; }
        .summary-card.yellow  { border-top: 3px solid #ca8a04; }

        /* Info row */
        .info-row {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .info-item-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
        }

        .info-item-value {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
            font-family: var(--font-mono);
        }

        .info-sep {
            width: 1px;
            height: 30px;
            background: var(--border);
        }

        /* Amortization table */
        .amort-table thead tr { background: var(--bg-sidebar); }
        .amort-table th { color: rgba(155,168,188,0.8); }
        .amort-table td { font-family: var(--font-mono); font-size: 12px; }
        .amort-table .col-pago   { color: var(--text-muted); font-weight: 500; }
        .amort-table .col-fecha  { color: var(--text-secondary); }
        .amort-table .col-cuota  { font-weight: 600; color: var(--text-primary); }
        .amort-table .col-int    { color: #dc2626; }
        .amort-table .col-cap    { color: #16a34a; }
        .amort-table .col-saldo  { color: var(--text-primary); }

        .amort-table tfoot td {
            background: var(--bg-hover);
            font-weight: 600;
            border-top: 2px solid var(--border);
        }

        /* Empty state */
        .empty-state {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 60px 24px;
            text-align: center;
        }

        .empty-icon {
            width: 52px; height: 52px;
            background: var(--bg-input);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
        }

        .empty-icon svg {
            width: 24px; height: 24px;
            color: var(--text-muted);
            fill: none; stroke: currentColor; stroke-width: 1.5;
        }

        .empty-state h3 {
            font-size: 15px; font-weight: 600;
            color: var(--text-primary); margin-bottom: 6px;
        }

        .empty-state p {
            font-size: 13px; color: var(--text-secondary);
            max-width: 300px; margin: 0 auto; line-height: 1.6;
        }

        /* Print */
        @media print {
            .sidebar, .topbar, .form-panel, .content-header { display: none !important; }
            .main-wrapper { margin-left: 0 !important; }
            .calc-layout { grid-template-columns: 1fr !important; }
            .content { padding: 0 !important; }
        }

        @media (max-width: 1100px) {
            .calc-layout { grid-template-columns: 1fr; }
            .form-panel { position: static; }
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark">
            <svg viewBox="0 0 14 14"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg>
        </div>
        <span class="logo-text">DE-SA</span>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Principal</span>
        <a class="nav-item" href="admin_view.php">
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
        <a class="nav-item active" href="calculadora.php">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 8h6M8 5v6"/></svg>
            Calculadora
        </a>
        <span class="nav-section-label">Análisis</span>
        <a class="nav-item" href="#">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2,12 6,7 10,9 14,4"/></svg>
            Gráficas
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-avatar">AG</div>
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
            <h1>Calculadora de préstamos</h1>
            <div class="breadcrumb">Herramientas · Amortización de cuota fija</div>
        </div>
        <div class="topbar-right">
            <?php if ($result): ?>
            <button class="btn-secondary" onclick="window.print()">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M4 6V2h8v4M4 12H2V7h12v5h-2M4 10h8v4H4v-4z"/></svg>
                Imprimir tabla
            </button>
            <?php endif; ?>
        </div>
    </header>

    <main class="content">
        <div class="content-header">
            <div>
                <h2>Simulador de amortización</h2>
                <p>Calcula cuotas fijas con interés diario sobre saldo insoluto</p>
            </div>
        </div>

        <div class="calc-layout">

            <!-- ── LEFT: Form ── -->
            <div class="form-panel">
                <div class="form-panel-header">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;color:var(--accent)"><rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 8h6M8 5v6"/></svg>
                    <h3>Parámetros del préstamo</h3>
                </div>

                <form method="POST" action="">
                    <div class="form-panel-body">

                        <?php if (!empty($errors)): ?>
                        <div class="error-box">
                            <?php foreach ($errors as $e): ?>
                                <p>⚠ <?= htmlspecialchars($e) ?></p>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="field">
                            <label>Monto del préstamo</label>
                            <div class="input-prefix-wrap">
                                <span class="prefix">$</span>
                                <input
                                    type="number"
                                    name="principal"
                                    min="1"
                                    step="0.01"
                                    placeholder="10,000.00"
                                    value="<?= htmlspecialchars($input['principal'] ?? '') ?>"
                                    required>
                            </div>
                        </div>

                        <div class="field">
                            <label>Interés diario (%)</label>
                            <div class="input-suffix-wrap">
                                <input
                                    type="number"
                                    name="tasa_diaria"
                                    min="0.01"
                                    max="100"
                                    step="0.01"
                                    placeholder="1.00"
                                    value="<?= htmlspecialchars($input['tasa_diaria'] ?? '') ?>"
                                    required>
                                <span class="suffix">%</span>
                            </div>
                            <span class="field-hint">Se aplica sobre el saldo pendiente cada día</span>
                        </div>

                        <div class="field">
                            <label>Frecuencia de pago</label>
                            <select name="frecuencia" required>
                                <option value="">Seleccionar…</option>
                                <?php foreach ($frecuencias as $key => $freq): ?>
                                <option value="<?= $key ?>"
                                    <?= (($input['frecuencia'] ?? '') === $key) ? 'selected' : '' ?>>
                                    <?= $freq['label'] ?> (cada <?= $freq['dias'] ?> día<?= $freq['dias'] > 1 ? 's' : '' ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label>Número de pagos</label>
                            <input
                                type="number"
                                name="num_pagos"
                                min="1"
                                max="365"
                                step="1"
                                placeholder="4"
                                value="<?= htmlspecialchars($input['num_pagos'] ?? '') ?>"
                                required>
                            <span class="field-hint">Cuántos pagos tendrá el plan</span>
                        </div>

                        <div class="field">
                            <label>Fecha de inicio <span style="font-weight:400;text-transform:none;letter-spacing:0">(opcional)</span></label>
                            <input
                                type="date"
                                name="fecha_inicio"
                                value="<?= htmlspecialchars($input['fecha_inicio'] ?? date('Y-m-d')) ?>">
                            <span class="field-hint">Para calcular las fechas de cada pago</span>
                        </div>

                    </div>

                    <div class="form-panel-footer">
                        <button type="submit" class="btn-primary" style="width:100%;justify-content:center">
                            <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 2v10M2 7h10"/></svg>
                            Calcular tabla de pagos
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── RIGHT: Results ── -->
            <div class="results-area">

                <?php if ($result): ?>

                <!-- Summary cards -->
                <div class="summary-grid">
                    <div class="summary-card accent">
                        <div class="summary-card-label">Cuota fija por pago</div>
                        <div class="summary-card-value"><?= money($result['cuota']) ?></div>
                        <div class="summary-card-sub"><?= $result['frecuencia'] ?></div>
                    </div>
                    <div class="summary-card green">
                        <div class="summary-card-label">Total a pagar</div>
                        <div class="summary-card-value"><?= money($result['total_pago']) ?></div>
                        <div class="summary-card-sub"><?= $result['num_pagos'] ?> pagos</div>
                    </div>
                    <div class="summary-card red">
                        <div class="summary-card-label">Total de intereses</div>
                        <div class="summary-card-value"><?= money($result['total_interes']) ?></div>
                        <div class="summary-card-sub"><?= number_format(($result['total_interes'] / $result['principal']) * 100, 1) ?>% del principal</div>
                    </div>
                    <div class="summary-card yellow">
                        <div class="summary-card-label">Tasa por período</div>
                        <div class="summary-card-value"><?= number_format($result['tasa_periodo'], 2) ?>%</div>
                        <div class="summary-card-sub"><?= $result['tasa_diaria'] ?>% × <?= $result['dias_periodo'] ?> días</div>
                    </div>
                </div>

                <!-- Info row -->
                <div class="info-row">
                    <div class="info-item">
                        <span class="info-item-label">Principal</span>
                        <span class="info-item-value"><?= money($result['principal']) ?></span>
                    </div>
                    <div class="info-sep"></div>
                    <div class="info-item">
                        <span class="info-item-label">Interés diario</span>
                        <span class="info-item-value"><?= $result['tasa_diaria'] ?>%</span>
                    </div>
                    <div class="info-sep"></div>
                    <div class="info-item">
                        <span class="info-item-label">Frecuencia</span>
                        <span class="info-item-value"><?= $result['frecuencia'] ?></span>
                    </div>
                    <div class="info-sep"></div>
                    <div class="info-item">
                        <span class="info-item-label">No. pagos</span>
                        <span class="info-item-value"><?= $result['num_pagos'] ?></span>
                    </div>
                    <div class="info-sep"></div>
                    <div class="info-item">
                        <span class="info-item-label">Tasa período</span>
                        <span class="info-item-value"><?= number_format($result['tasa_periodo'], 2) ?>%</span>
                    </div>
                    <div class="info-sep"></div>
                    <div class="info-item">
                        <span class="info-item-label">Capital total</span>
                        <span class="info-item-value"><?= money($result['total_capital']) ?></span>
                    </div>
                </div>

                <!-- Amortization table -->
                <div class="table-card">
                    <div class="table-header">
                        <div>
                            <div class="table-title">Tabla de amortización</div>
                            <div class="table-count">
                                Cuota fija de <?= money($result['cuota']) ?> —
                                <span style="color:#16a34a">verde = capital</span> ·
                                <span style="color:#dc2626">rojo = interés</span>
                            </div>
                        </div>
                    </div>
                    <table class="amort-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Cuota</th>
                                <th>Interés</th>
                                <th>Capital</th>
                                <th>Saldo restante</th>
                                <th>% Interés</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['tabla'] as $row): ?>
                            <tr>
                                <td class="col-pago"><?= $row['pago'] ?></td>
                                <td class="col-fecha"><?= $row['fecha'] ?></td>
                                <td class="col-cuota"><?= money($row['cuota']) ?></td>
                                <td class="col-int"><?= money($row['interes']) ?></td>
                                <td class="col-cap"><?= money($row['capital']) ?></td>
                                <td class="col-saldo"><?= money($row['saldo']) ?></td>
                                <td>
                                    <?php $pct_int = $row['cuota'] > 0 ? ($row['interes'] / $row['cuota']) * 100 : 0; ?>
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <div style="flex:1;height:4px;background:var(--bg-input);border-radius:2px;overflow:hidden;min-width:50px">
                                            <div style="height:100%;width:<?= number_format($pct_int, 1) ?>%;background:#dc2626;border-radius:2px"></div>
                                        </div>
                                        <span style="font-size:11px;color:var(--text-muted);min-width:36px;text-align:right"><?= number_format($pct_int, 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" style="font-family:var(--font);font-size:12px;color:var(--text-muted)">TOTALES</td>
                                <td class="col-cuota"><?= money($result['total_pago']) ?></td>
                                <td class="col-int"><?= money($result['total_interes']) ?></td>
                                <td class="col-cap"><?= money($result['total_capital']) ?></td>
                                <td class="col-saldo">$0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <?php elseif (!empty($errors)): ?>

                <!-- Error state -->
                <div class="empty-state">
                    <div class="empty-icon" style="background:#fee2e2">
                        <svg viewBox="0 0 16 16" style="color:#dc2626"><circle cx="8" cy="8" r="6"/><path d="M8 5v3M8 10v.5"/></svg>
                    </div>
                    <h3>Revisa los datos ingresados</h3>
                    <p>Corrige los errores en el formulario e intenta de nuevo.</p>
                </div>

                <?php else: ?>

                <!-- Empty state -->
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 16 16"><rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 8h6M8 5v6"/></svg>
                    </div>
                    <h3>Ingresa los parámetros del préstamo</h3>
                    <p>Completa el formulario de la izquierda para generar la tabla de amortización con cuotas fijas.</p>
                </div>

                <?php endif; ?>

            </div><!-- end results-area -->
        </div><!-- end calc-layout -->

    </main>
</div>

</body>
</html>
