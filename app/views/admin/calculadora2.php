<?php // Variables: $result, $errores, $input ?>
<div class="content-header">
    <div>
        <h2>Calculadora 2 — Pago fijo acordado</h2>
        <p>Acuerda el total a devolver con el cliente y genera pagos redondos en efectivo</p>
    </div>
    <a href="<?= APP_URL ?>/calculadora" style="font-size:12px;color:var(--text-muted);text-decoration:none;padding:6px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--bg-card)">
        Ir a Calculadora 1 →
    </a>
</div>

<div style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start">

    <!-- Formulario -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;position:sticky;top:80px">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
            <div style="font-size:14px;font-weight:600">Parámetros del acuerdo</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Sin tasa de interés — el costo ya está en el total a retornar</div>
        </div>
        <form method="POST" action="<?= APP_URL ?>/calculadora2" style="padding:20px;display:flex;flex-direction:column;gap:14px">

            <?php if (!empty($errores)): ?>
            <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:var(--radius-sm);padding:10px 14px">
                <?php foreach($errores as $e): ?>
                <div style="font-size:12px;color:#991b1b">• <?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div>
                <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">
                    Dinero entregado ($)
                </label>
                <input type="number" name="monto_entregado"
                    value="<?= htmlspecialchars((string)($input['monto_entregado'] ?? '')) ?>"
                    placeholder="50000" step="0.01" min="1" required
                    style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:14px;color:var(--text-primary);outline:none"
                    oninput="calcGanancia()">
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Monto real que recibe el cliente</div>
            </div>

            <div>
                <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">
                    Total a retornar ($)
                </label>
                <input type="number" name="monto_retornar"
                    value="<?= htmlspecialchars((string)($input['monto_retornar'] ?? '')) ?>"
                    placeholder="65000" step="0.01" min="1" required
                    style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:14px;color:var(--text-primary);outline:none"
                    oninput="calcGanancia()">
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Suma total de todos los pagos del cliente</div>
            </div>

            <!-- Preview de ganancia en tiempo real -->
            <div id="gananciaPreview" style="display:none;background:rgba(22,163,74,.08);border:1px solid rgba(22,163,74,.25);border-radius:var(--radius-sm);padding:10px 14px">
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:#166534;margin-bottom:4px">Ganancia del acuerdo</div>
                <div id="gananciaValor" style="font-size:20px;font-weight:700;font-family:var(--font-mono);color:#16a34a"></div>
                <div id="gananciaPct" style="font-size:11px;color:#166534;margin-top:2px"></div>
            </div>

            <div>
                <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Número de pagos</label>
                <input type="number" name="num_pagos"
                    value="<?= htmlspecialchars((string)($input['num_pagos'] ?? '')) ?>"
                    placeholder="10" step="1" min="1" required
                    style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:14px;color:var(--text-primary);outline:none">
            </div>

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
                <input type="date" name="fecha_inicio"
                    value="<?= $input['fecha_inicio'] ?? date('Y-m-d') ?>"
                    style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:14px;outline:none">
            </div>

            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:11px">
                Generar plan de pagos
            </button>
        </form>
    </div>

    <!-- Resultado -->
    <div>
    <?php if ($result): ?>

        <!-- Resumen -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px">
            <?php foreach([
                ['Dinero entregado', '$'.number_format($result['monto_entregado'],2,'.',','), '#3b82f6'],
                ['Total a cobrar',   '$'.number_format($result['monto_retornar'],2,'.',','),  'var(--text-primary)'],
                ['Ganancia',         '$'.number_format($result['ganancia'],2,'.',','),         '#16a34a'],
                ['Rentabilidad',     round($result['ganancia'] / max(1,$result['monto_entregado']) * 100, 1).'%', '#f59e0b'],
            ] as [$l,$v,$c]): ?>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px 18px">
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:6px"><?= $l ?></div>
                <div style="font-size:22px;font-weight:600;font-family:var(--font-mono);color:<?= $c ?>"><?= $v ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Estructura de pagos -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px 18px">
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:10px">Estructura de pagos</div>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                    <span style="font-size:13px">Pago 1 (ajuste)</span>
                    <span style="font-size:16px;font-weight:700;font-family:var(--font-mono);color:#f59e0b">$<?= number_format($result['primer_pago'],2,'.',',') ?></span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                    <span style="font-size:13px">Pagos 2–<?= $result['num_pagos'] ?> (iguales)</span>
                    <span style="font-size:16px;font-weight:700;font-family:var(--font-mono);color:#16a34a">$<?= number_format($result['cuota_base'],2,'.',',') ?></span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0">
                    <span style="font-size:13px;font-weight:600">Total</span>
                    <span style="font-size:16px;font-weight:700;font-family:var(--font-mono)">$<?= number_format($result['total_pago'],2,'.',',') ?></span>
                </div>
            </div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px 18px">
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:10px">Notas para efectivo</div>
                <div style="font-size:13px;color:var(--text-secondary);line-height:1.6">
                    <?php if ($result['num_pagos'] > 1): ?>
                    • El <strong>primer pago</strong> es de <span style="color:#f59e0b;font-family:var(--font-mono);font-weight:700">$<?= number_format($result['primer_pago'],0,'.',',') ?></span> (incluye ajuste de redondeo)<br>
                    • Los siguientes <strong><?= $result['num_pagos'] - 1 ?> pagos</strong> son de <span style="color:#16a34a;font-family:var(--font-mono);font-weight:700">$<?= number_format($result['cuota_base'],0,'.',',') ?></span> cada uno<br>
                    <?php else: ?>
                    • Pago único de <span style="color:#16a34a;font-family:var(--font-mono);font-weight:700">$<?= number_format($result['primer_pago'],0,'.',',') ?></span><br>
                    <?php endif; ?>
                    • Frecuencia: <strong><?= $result['frecuencia'] ?></strong><br>
                    • <?= $result['num_pagos'] ?> pagos en total
                </div>
            </div>
        </div>

        <!-- Tabla de pagos -->
        <div class="table-card">
            <div class="table-header">
                <div class="table-title">Plan de pagos</div>
                <div style="font-size:12px;color:var(--text-muted)"><?= $result['num_pagos'] ?> pagos · <?= $result['frecuencia'] ?></div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th class="td-amount">Cuota</th>
                        <th class="td-amount">Capital</th>
                        <th class="td-amount">Costo crédito</th>
                        <th class="td-amount">Saldo restante</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($result['tabla'] as $fila):
                    $esPrimero = $fila['pago'] === 1 && $result['num_pagos'] > 1;
                ?>
                <tr <?= $esPrimero ? 'style="background:rgba(245,158,11,.06)"' : '' ?>>
                    <td class="td-id"><?= $fila['pago'] ?> <?= $esPrimero ? '<span style="font-size:10px;color:#ca8a04">(ajuste)</span>' : '' ?></td>
                    <td class="td-numeric"><?= date('d/m/Y', strtotime($fila['fecha'])) ?></td>
                    <td class="td-amount" style="font-weight:700;color:<?= $esPrimero ? '#ca8a04' : '#16a34a' ?>">$<?= number_format($fila['cuota'],2,'.',',') ?></td>
                    <td class="td-amount">$<?= number_format($fila['capital'],2,'.',',') ?></td>
                    <td class="td-amount" style="color:#f59e0b">$<?= number_format($fila['interes'],2,'.',',') ?></td>
                    <td class="td-amount">$<?= number_format($fila['saldo'],2,'.',',') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight:600;border-top:2px solid var(--border)">
                        <td colspan="2" style="padding:10px 12px;font-size:13px">Totales</td>
                        <td class="td-amount">$<?= number_format($result['total_pago'],2,'.',',') ?></td>
                        <td class="td-amount">$<?= number_format($result['total_capital'],2,'.',',') ?></td>
                        <td class="td-amount" style="color:#f59e0b">$<?= number_format($result['total_interes'],2,'.',',') ?></td>
                        <td class="td-amount">$0.00</td>
                    </tr>
                </tfoot>
            </table>
        </div>

    <?php else: ?>
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:60px 24px;text-align:center;color:var(--text-muted)">
            <svg width="44" height="44" viewBox="0 0 44 44" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:14px;opacity:.4">
                <rect x="6" y="6" width="32" height="32" rx="4"/>
                <path d="M14 22h16M22 14v8"/>
                <circle cx="32" cy="32" r="6" fill="var(--bg-card)" stroke="currentColor"/>
                <path d="M32 29v3l2 2"/>
            </svg>
            <div style="font-size:15px;font-weight:500;color:var(--text-secondary)">Ingresa el acuerdo con el cliente y presiona Generar</div>
            <div style="font-size:13px;margin-top:6px">El plan de pagos en efectivo aparecerá aquí</div>
            <div style="margin-top:20px;padding:14px 20px;background:var(--bg-input);border-radius:var(--radius-md);display:inline-block;text-align:left;font-size:12px;color:var(--text-muted)">
                <strong style="color:var(--text-secondary)">¿Cómo funciona?</strong><br>
                1. Define cuánto dinero le entregas al cliente<br>
                2. Acuerda el total que él te devolverá<br>
                3. El sistema genera pagos redondos para facilitar el cobro en efectivo<br>
                4. El primer pago absorbe el ajuste de redondeo; los demás son iguales
            </div>
        </div>
    <?php endif; ?>
    </div>
</div>

<script>
function calcGanancia() {
    const entregado = parseFloat(document.querySelector('[name=monto_entregado]').value) || 0;
    const retornar  = parseFloat(document.querySelector('[name=monto_retornar]').value)  || 0;
    const preview   = document.getElementById('gananciaPreview');
    if (entregado > 0 && retornar > entregado) {
        const ganancia = retornar - entregado;
        const pct      = ((ganancia / entregado) * 100).toFixed(1);
        document.getElementById('gananciaValor').textContent = '$' + ganancia.toLocaleString('es-MX', {minimumFractionDigits:2});
        document.getElementById('gananciaPct').textContent   = pct + '% de rentabilidad sobre lo entregado';
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', calcGanancia);
</script>
