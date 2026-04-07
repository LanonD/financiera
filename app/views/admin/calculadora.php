<?php // Variables: $result, $errores, $input ?>
<div class="content-header">
    <div><h2>Calculadora de amortización</h2><p>Calcula la tabla de pagos de cualquier préstamo</p></div>
</div>

<div style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start">

    <!-- Formulario -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;position:sticky;top:80px">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);font-size:14px;font-weight:600">Parámetros del préstamo</div>
        <form method="POST" action="<?= APP_URL ?>/calculadora" style="padding:20px;display:flex;flex-direction:column;gap:14px">

            <?php if (!empty($errores)): ?>
            <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:var(--radius-sm);padding:10px 14px">
                <?php foreach($errores as $e): ?>
                <div style="font-size:12px;color:#991b1b">• <?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php
            $fields = [
                ['principal',   'Monto del préstamo ($)', 'number', '50000',   '0.01'],
                ['tasa_diaria', 'Tasa de interés diaria (%)', 'number', '1', '0.0001'],
                ['num_pagos',   'Número de pagos', 'number', '24', '1'],
            ];
            foreach ($fields as [$name, $label, $type, $placeholder, $step]):
                $val = $input[$name] ?? '';
            ?>
            <div>
                <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px"><?= $label ?></label>
                <input type="<?= $type ?>" name="<?= $name ?>" value="<?= htmlspecialchars((string)$val) ?>"
                    placeholder="<?= $placeholder ?>" step="<?= $step ?>" required
                    style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:14px;color:var(--text-primary);outline:none">
            </div>
            <?php endforeach; ?>

            <div>
                <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Frecuencia de pago</label>
                <select name="frecuencia" style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:14px;outline:none">
                    <?php foreach(['Mensual','Quincenal','Semanal','Diario'] as $f): ?>
                    <option <?= ($input['frecuencia'] ?? 'Mensual') === $f ? 'selected' : '' ?>><?= $f ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Fecha de inicio</label>
                <input type="date" name="fecha_inicio" value="<?= $input['fecha_inicio'] ?? date('Y-m-d') ?>"
                    style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:14px;outline:none">
            </div>

            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:11px">
                Calcular amortización
            </button>
        </form>
    </div>

    <!-- Resultado -->
    <div>
    <?php if ($result): ?>
        <!-- Resumen -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px">
            <?php foreach([
                ['Cuota fija',       '$'.number_format($result['cuota'],2,'.',','),           '#3b82f6'],
                ['Total a pagar',    '$'.number_format($result['total_pago'],2,'.',','),       'var(--text-primary)'],
                ['Total de interés', '$'.number_format($result['total_interes'],2,'.',','),    '#ca8a04'],
            ] as [$l,$v,$c]): ?>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px 18px">
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:6px"><?= $l ?></div>
                <div style="font-size:22px;font-weight:600;font-family:var(--font-mono);color:<?= $c ?>"><?= $v ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Tabla de amortización -->
        <div class="table-card">
            <div class="table-header">
                <div class="table-title">Tabla de amortización</div>
                <div style="font-size:12px;color:var(--text-muted)"><?= $result['num_pagos'] ?> pagos · <?= $result['frecuencia'] ?> · <?= $result['tasa_periodo'] ?>% por período</div>
            </div>
            <table>
                <thead>
                    <tr><th>#</th><th>Fecha</th><th>Cuota</th><th>Capital</th><th>Interés</th><th>Saldo</th></tr>
                </thead>
                <tbody>
                <?php foreach ($result['tabla'] as $fila): ?>
                <tr>
                    <td class="td-id"><?= $fila['pago'] ?></td>
                    <td class="td-numeric"><?= $fila['fecha'] ?></td>
                    <td class="td-amount">$<?= number_format($fila['cuota'],2,'.',',') ?></td>
                    <td class="td-amount">$<?= number_format($fila['capital'],2,'.',',') ?></td>
                    <td class="td-amount" style="color:#ca8a04">$<?= number_format($fila['interes'],2,'.',',') ?></td>
                    <td class="td-amount">$<?= number_format($fila['saldo'],2,'.',',') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:60px 24px;text-align:center;color:var(--text-muted)">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:.4"><rect x="6" y="6" width="28" height="28" rx="4"/><path d="M13 20h14M20 13v14"/></svg>
            <div style="font-size:15px;font-weight:500;color:var(--text-secondary)">Ingresa los parámetros y presiona Calcular</div>
            <div style="font-size:13px;margin-top:6px">La tabla de amortización aparecerá aquí</div>
        </div>
    <?php endif; ?>
    </div>
</div>
