<?php
// Variables: $prestamo, $pagos
$pagados   = array_filter($pagos, fn($p) => $p['estatus'] === 'Pagado');
$pendientes= array_filter($pagos, fn($p) => in_array($p['estatus'], ['Pendiente','Atrasado']));
$total_pagado = array_sum(array_column(iterator_to_array((function() use ($pagados){ yield from $pagados; })()), 'monto_cobrado'));
$pct = $prestamo['monto'] > 0 ? round((($prestamo['monto'] - $prestamo['saldo_actual']) / $prestamo['monto']) * 100) : 0;
function m($v){ return '$'.number_format((float)$v,2,'.',','); }
?>
<div class="content-header">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="<?= APP_URL ?>/prestamos" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--text-muted);text-decoration:none;padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--bg-card)">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M8 2L4 6l4 4"/></svg>
            Volver
        </a>
        <div>
            <h2>Préstamo #<?= $prestamo['id'] ?></h2>
            <p><?= htmlspecialchars($prestamo['cliente_nombre'] ?? '—') ?></p>
        </div>
    </div>
    <?php
    $badge = match($prestamo['estatus']) { 'Activo' => 'badge-activo', 'Atrasado' => 'badge-atrasado', 'Retirado' => 'badge-retirado', default => 'badge-pendiente' };
    ?>
    <span class="badge <?= $badge ?>" style="font-size:13px;padding:6px 14px"><span class="dot"></span><?= $prestamo['estatus'] ?></span>
</div>

<!-- KPI cards -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
    <?php foreach([
        ['Saldo pendiente', m($prestamo['saldo_actual']), '#3b82f6'],
        ['Monto original',  m($prestamo['monto']),        'var(--text-secondary)'],
        ['Cuota',           m($prestamo['cuota']),         'var(--text-secondary)'],
        ['Pagos realizados',count($pagados).' / '.count($pagos), '#16a34a'],
    ] as [$label, $val, $color]): ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px 18px">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:6px"><?= $label ?></div>
        <div style="font-size:22px;font-weight:600;font-family:var(--font-mono);color:<?= $color ?>;letter-spacing:-.02em"><?= $val ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Panel de interés diario -->
<?php if (!empty($interesInfo) && in_array($prestamo['estatus'], ['Activo','Atrasado'])): ?>
<?php $interesActivo = (int)($prestamo['interes_activo'] ?? 1); ?>
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;margin-bottom:16px">
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:13px;font-weight:600">Saldo con interés en tiempo real</span>
            <?php if (!$interesActivo): ?>
            <span style="font-size:11px;padding:2px 8px;background:#fef3c7;border:1px solid #fcd34d;border-radius:999px;color:#92400e;font-weight:600">⏸ Interés pausado</span>
            <?php endif; ?>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:11px;color:var(--text-muted)">Actualizado: <?= date('d/m/Y') ?></span>
            <?php if (($_SESSION['puesto'] ?? '') === 'admin'): ?>
            <form method="POST" action="<?= APP_URL ?>/prestamos/toggle-interes" style="margin:0">
                <input type="hidden" name="prestamo_id" value="<?= $prestamo['id'] ?>">
                <button type="submit"
                    style="font-size:11px;padding:4px 12px;border-radius:999px;border:1px solid <?= $interesActivo ? '#fca5a5' : '#86efac' ?>;background:<?= $interesActivo ? 'rgba(220,38,38,.08)' : 'rgba(22,163,74,.08)' ?>;color:<?= $interesActivo ? '#dc2626' : '#16a34a' ?>;cursor:pointer;font-weight:600"
                    onclick="return confirm('<?= $interesActivo ? '¿Pausar el interés diario de este préstamo? El cron no lo acumulará hasta que lo reactives.' : '¿Reanudar el interés diario? El cron volverá a acumular interés en este préstamo.' ?>')">
                    <?= $interesActivo ? '⏸ Pausar interés' : '▶ Reanudar interés' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <div style="padding:16px 18px;display:grid;grid-template-columns:repeat(4,1fr);gap:0;border-bottom:1px solid var(--border)">
        <?php
        $iCards = [
            ['Principal adeudado', '$'.number_format($interesInfo['principal'],2,'.',','), '#3b82f6', 'El monto de capital que aún debe el cliente'],
            ['Interés acumulado',  '$'.number_format($interesInfo['interes_acumulado'],2,'.',','), '#f59e0b', 'Interés generado y no pagado hasta hoy'],
            ['Interés por día',    '$'.number_format($interesInfo['interes_diario'],2,'.',','), '#8b5cf6', 'Interés que genera este préstamo cada día (tasa '.$interesInfo['tasa_diaria'].'%)'],
            ['Total adeudado',     '$'.number_format($interesInfo['total_adeudado'],2,'.',','), '#dc2626', 'Principal + todo el interés acumulado'],
        ];
        foreach ($iCards as $i => [$lbl, $val, $clr, $tip]):
        ?>
        <div style="padding:14px 18px<?= $i > 0 ? ';border-left:1px solid var(--border)' : '' ?>" title="<?= $tip ?>">
            <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:5px"><?= $lbl ?></div>
            <div style="font-size:20px;font-weight:700;font-family:var(--font-mono);color:<?= $clr ?>"><?= $val ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- Barra visual: principal vs interés -->
    <?php
    $total = $interesInfo['total_adeudado'];
    $pctP  = $total > 0 ? round($interesInfo['principal']         / $total * 100) : 100;
    $pctI  = $total > 0 ? round($interesInfo['interes_acumulado'] / $total * 100) : 0;
    ?>
    <div style="padding:12px 18px">
        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-bottom:6px">
            <span>Composición del saldo</span>
            <span><?= $pctP ?>% capital · <?= $pctI ?>% interés</span>
        </div>
        <div style="height:8px;background:var(--bg-input);border-radius:4px;overflow:hidden;display:flex">
            <div style="height:100%;width:<?= $pctP ?>%;background:#3b82f6;border-radius:4px 0 0 4px"></div>
            <div style="height:100%;width:<?= $pctI ?>%;background:#f59e0b"></div>
        </div>
        <?php if ($interesInfo['dias_sin_acumular'] > 0): ?>
        <div style="margin-top:8px;font-size:11px;color:#ca8a04">
            El cron no se ha ejecutado en <?= $interesInfo['dias_sin_acumular'] ?> día(s) — el interés mostrado incluye ese período calculado al momento.
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Progreso -->
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:18px 20px;margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
        <span style="font-size:13px;color:var(--text-secondary)">Progreso del préstamo</span>
        <span style="font-size:13px;font-weight:600;font-family:var(--font-mono);color:var(--accent)"><?= $pct ?>% pagado</span>
    </div>
    <div style="height:8px;background:var(--bg-input);border-radius:4px;overflow:hidden">
        <div style="height:100%;width:<?= $pct ?>%;background:var(--accent);border-radius:4px"></div>
    </div>
    <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:11px;color:var(--text-muted);font-family:var(--font-mono)">
        <span>Pagado: <?= m($prestamo['monto'] - $prestamo['saldo_actual']) ?></span>
        <span>Restante: <?= m($prestamo['saldo_actual']) ?></span>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <!-- Info del crédito -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden">
        <div style="padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Detalles del crédito</div>
        <div style="padding:16px 18px;display:grid;grid-template-columns:1fr 1fr;gap:10px 20px">
            <?php foreach([
                ['Frecuencia',   $prestamo['frecuencia']],
                ['Num. pagos',   $prestamo['num_pagos']],
                ['Tasa diaria',  $prestamo['tasa_diaria'].'%'],
                ['Fecha inicio', $prestamo['fecha_inicio'] ?? '—'],
                ['Promotor',     $prestamo['promotor_nombre'] ?? '—'],
                ['Cobrador',     $prestamo['cobrador_nombre'] ?? '—'],
            ] as [$l, $v]): ?>
            <div>
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted)"><?= $l ?></div>
                <div style="font-size:13px;font-weight:500;font-family:var(--font-mono);color:var(--text-primary);margin-top:2px"><?= htmlspecialchars((string)$v) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- Info del cliente -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden">
        <div style="padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600">Datos del cliente</div>
        <div style="padding:16px 18px;display:grid;grid-template-columns:1fr;gap:10px">
            <?php foreach([
                ['Nombre',    $prestamo['cliente_nombre']  ?? '—'],
                ['Celular',   $prestamo['cliente_celular'] ?? '—'],
                ['Dirección', $prestamo['cliente_direccion'] ?? '—'],
            ] as [$l, $v]): ?>
            <div>
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted)"><?= $l ?></div>
                <div style="font-size:13px;font-weight:500;font-family:var(--font-mono);color:var(--text-primary);margin-top:2px"><?= htmlspecialchars((string)$v) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
// Next pending payment date (for the default date input)
$proximoPendiente = null;
foreach ($pagos as $pg) {
    if (in_array($pg['estatus'], ['Pendiente','Atrasado'])) {
        $proximoPendiente = $pg['fecha_programada'];
        break;
    }
}
$hayPendientes = $proximoPendiente !== null;
?>

<?php if (isset($_GET['ok'])): ?>
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:var(--radius-sm);padding:10px 16px;margin-bottom:16px;font-size:13px;color:#166534;font-weight:500">
    <?= $_GET['ok'] === 'meta' ? 'Datos generales actualizados correctamente.' : 'Condiciones actualizadas. La tabla de pagos fue recalculada correctamente.' ?>
</div>
<?php elseif (isset($_GET['error'])): ?>
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:var(--radius-sm);padding:10px 16px;margin-bottom:16px;font-size:13px;color:#991b1b;font-weight:500">
    <?= $_GET['error'] === 'finalizado' ? 'Este préstamo ya no tiene pagos pendientes.' : 'Datos inválidos. Verifica los campos.' ?>
</div>
<?php endif; ?>

<?php if (($_SESSION['puesto'] ?? '') === 'admin'): ?>
<!-- Editar datos generales del préstamo (solo admin) -->
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:16px">
    <button onclick="toggleMeta()" id="metaToggleBtn"
        style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:none;border:none;cursor:pointer;font-family:var(--font)">
        <div style="display:flex;align-items:center;gap:10px">
            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="7.5" r="6"/><path d="M7.5 4v4l2.5 1.5"/></svg>
            <span style="font-size:13px;font-weight:600">Editar datos generales</span>
        </div>
        <svg id="metaChevron" width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="transition:transform .2s;color:var(--text-muted)">
            <path d="M3 5l4 4 4-4"/>
        </svg>
    </button>
    <div id="metaPanel" style="display:none;border-top:1px solid var(--border);padding:20px">
        <form method="POST" action="<?= APP_URL ?>/prestamos/meta" onsubmit="this.querySelector('[type=submit]').disabled=true">
            <input type="hidden" name="prestamo_id" value="<?= $prestamo['id'] ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:16px">
                <div>
                    <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:6px">Promotor</label>
                    <select name="promotor_id" style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
                        <option value="0">— Sin asignar —</option>
                        <?php foreach($promotores as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $prestamo['promotor_id']==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:6px">Cobrador</label>
                    <select name="cobrador_id" style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
                        <option value="0">— Sin asignar —</option>
                        <?php foreach($cobradores as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $prestamo['cobrador_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:6px">Estatus</label>
                    <select name="estatus" style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
                        <?php foreach(['Activo','Atrasado','Pendiente','Finalizado','Retirado'] as $st): ?>
                        <option <?= $prestamo['estatus']===$st?'selected':'' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:6px">Saldo principal ($)</label>
                    <input type="number" name="saldo_actual" value="<?= $prestamo['saldo_actual'] ?>" step="0.01" min="0"
                        style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:13px;outline:none">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:6px">Interés acumulado ($)</label>
                    <input type="number" name="interes_acumulado" value="<?= $interesInfo['interes_acumulado'] ?? 0 ?>" step="0.01" min="0"
                        style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:13px;outline:none">
                </div>
            </div>
            <button type="submit" class="btn-primary" style="padding:9px 20px"
                onclick="return confirm('¿Guardar los cambios en los datos generales del préstamo?')">
                Guardar cambios
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Editar condiciones -->
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:16px">
    <button onclick="toggleEdit()" id="editToggleBtn"
        style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:none;border:none;cursor:pointer;font-family:var(--font)">
        <div style="display:flex;align-items:center;gap:10px">
            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.5 1.5l3 3-9 9H1.5v-3l9-9z"/>
            </svg>
            <span style="font-size:13px;font-weight:600">Editar condiciones del préstamo</span>
            <?php if (!$hayPendientes): ?>
            <span style="font-size:11px;padding:2px 8px;background:#f4f5f7;border-radius:10px;color:var(--text-muted)">Sin pagos pendientes</span>
            <?php endif; ?>
        </div>
        <svg id="editChevron" width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="transition:transform .2s;color:var(--text-muted)">
            <path d="M3 5l4 4 4-4"/>
        </svg>
    </button>

    <div id="editPanel" style="display:none;border-top:1px solid var(--border);padding:20px">
        <?php if (!$hayPendientes): ?>
        <p style="font-size:13px;color:var(--text-muted);margin:0">No hay pagos pendientes para recalcular.</p>
        <?php else: ?>
        <?php
            $pendientesCount = count(array_filter($pagos, fn($p) => in_array($p['estatus'], ['Pendiente','Atrasado'])));
            $maxPagos        = $prestamo['frecuencia'] === 'Diario' ? 31 : 12;
        ?>
        <form method="POST" action="<?= APP_URL ?>/prestamos/editar">
            <input type="hidden" name="prestamo_id" value="<?= $prestamo['id'] ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:14px;align-items:flex-end">
                <div>
                    <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:6px">
                        Tasa de interés diaria (%)
                    </label>
                    <input type="number" name="tasa_diaria"
                        value="<?= $prestamo['tasa_diaria'] ?>"
                        step="0.0001" min="0.0001" required
                        style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:14px;outline:none">
                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px">
                        Actual: <strong><?= $prestamo['tasa_diaria'] ?>%</strong>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:6px">
                        Fecha del próximo pago
                    </label>
                    <input type="date" name="fecha_primer_pago"
                        value="<?= $proximoPendiente ?>"
                        required
                        style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:14px;outline:none">
                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px">
                        Frecuencia: <strong><?= $prestamo['frecuencia'] ?></strong>
                    </div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:6px">
                        Núm. de pagos pendientes
                    </label>
                    <select name="num_pagos" required
                        style="width:100%;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:14px;outline:none;cursor:pointer">
                        <?php for ($n = 1; $n <= $maxPagos; $n++): ?>
                        <option value="<?= $n ?>" <?= $n === $pendientesCount ? 'selected' : '' ?>>
                            <?= $n ?> pago<?= $n > 1 ? 's' : '' ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px">
                        Actual: <strong><?= $pendientesCount ?></strong> · Máx: <strong><?= $maxPagos ?></strong>
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn-primary" style="padding:9px 20px;white-space:nowrap"
                        onclick="return confirm('¿Recalcular los pagos pendientes con las nuevas condiciones?\nLos pagos ya realizados no se modificarán.')">
                        Recalcular pagos
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Historial de pagos -->
<div class="table-card">
    <div class="table-header"><div class="table-title">Historial de pagos</div></div>
    <table>
        <thead>
            <tr><th>#</th><th>Fecha prog.</th><th>Cuota</th><th>Capital</th><th>Interés</th><th>Saldo</th><th>Cobrado</th><th>Fecha pago</th><th>Días atraso</th><th>Estatus</th></tr>
        </thead>
        <tbody>
        <?php foreach ($pagos as $p):
            $ps  = $p['estatus'];
            $pbg = match($ps) { 'Pagado' => '#dcfce7', 'Parcial' => '#fef9c3', 'Atrasado' => '#fee2e2', default => '#f4f5f7' };
            $ptx = match($ps) { 'Pagado' => '#166534', 'Parcial' => '#854d0e', 'Atrasado' => '#991b1b', default => '#6b7280' };

            // Calcular días de atraso
            $prog = new DateTime($p['fecha_programada']);
            if ($p['fecha_pago']) {
                // Pagado: ¿cuántos días tardó respecto a la fecha programada?
                $pagado     = new DateTime(substr($p['fecha_pago'], 0, 10));
                $diasAtraso = (int)$pagado->diff($prog)->days * ($pagado > $prog ? 1 : -1);
            } elseif ($prog < new DateTime('today')) {
                // No pagado y ya venció
                $diasAtraso = (int)(new DateTime('today'))->diff($prog)->days;
            } else {
                $diasAtraso = null; // Pendiente futuro
            }

            if ($diasAtraso === null) {
                $atrasoHtml = '<span style="color:var(--text-muted);font-size:12px">—</span>';
            } elseif ($diasAtraso <= 0) {
                $atrasoHtml = '<span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:#dcfce7;color:#166534">A tiempo</span>';
            } elseif ($diasAtraso <= 2) {
                $atrasoHtml = '<span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:#fef9c3;color:#854d0e">' . $diasAtraso . ' día' . ($diasAtraso > 1 ? 's' : '') . '</span>';
            } elseif ($diasAtraso <= 7) {
                $atrasoHtml = '<span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:#ffedd5;color:#c2410c">' . $diasAtraso . ' días</span>';
            } else {
                $atrasoHtml = '<span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:#fee2e2;color:#991b1b">' . $diasAtraso . ' días</span>';
            }
        ?>
        <tr>
            <td class="td-id"><?= $p['numero_pago'] ?></td>
            <td class="td-numeric"><?= $p['fecha_programada'] ?></td>
            <td class="td-amount"><?= m($p['monto_cuota']) ?></td>
            <td class="td-amount"><?= m($p['capital']) ?></td>
            <td class="td-amount"><?= m($p['interes']) ?></td>
            <td class="td-amount"><?= m($p['saldo_restante']) ?></td>
            <td class="td-amount"><?= $p['monto_cobrado'] ? m($p['monto_cobrado']) : '—' ?></td>
            <td class="td-numeric"><?= $p['fecha_pago'] ? substr($p['fecha_pago'],0,10) : '—' ?></td>
            <td><?= $atrasoHtml ?></td>
            <td><span style="display:inline-flex;align-items:center;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:600;background:<?= $pbg ?>;color:<?= $ptx ?>"><?= $ps ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <?php
            $tCuota   = array_sum(array_column($pagos, 'monto_cuota'));
            $tCapital = array_sum(array_column($pagos, 'capital'));
            $tInteres = array_sum(array_column($pagos, 'interes'));
            $tCobrado = array_sum(array_filter(array_column($pagos, 'monto_cobrado')));
            $tPend    = array_sum(array_column(
                array_filter($pagos, fn($p) => in_array($p['estatus'], ['Pendiente','Atrasado','Parcial'])),
                'monto_cuota'
            ));
        ?>
        <tr style="background:var(--bg-hover);border-top:2px solid var(--border);font-weight:600">
            <td colspan="2" style="padding:10px 12px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted)">Totales</td>
            <td class="td-amount" style="font-weight:700"><?= m($tCuota) ?></td>
            <td class="td-amount" style="font-weight:700"><?= m($tCapital) ?></td>
            <td class="td-amount" style="font-weight:700;color:#ca8a04"><?= m($tInteres) ?></td>
            <td class="td-amount" style="color:var(--text-muted)">—</td>
            <td class="td-amount" style="font-weight:700;color:#16a34a"><?= $tCobrado > 0 ? m($tCobrado) : '—' ?></td>
            <td colspan="2" style="color:var(--text-muted);font-size:12px;padding-left:12px">
                <?= count(array_filter($pagos, fn($p) => $p['estatus'] === 'Pagado')) ?> pagados ·
                <?= count(array_filter($pagos, fn($p) => $p['estatus'] === 'Atrasado')) ?> atrasados
            </td>
            <td class="td-amount" style="font-weight:700;color:#991b1b"><?= $tPend > 0 ? m($tPend).' pendiente' : '—' ?></td>
        </tr>
        </tfoot>
    </table>
</div>

<script>
// Auto-open edit panel if there's a success/error from a recalculation
<?php if (isset($_GET['ok']) || isset($_GET['error'])): ?>
toggleEdit();
<?php endif; ?>

function toggleEdit() {
    const panel   = document.getElementById('editPanel');
    const chevron = document.getElementById('editChevron');
    const open    = panel.style.display === 'none';
    panel.style.display   = open ? 'block' : 'none';
    chevron.style.transform = open ? 'rotate(180deg)' : '';
}
function toggleMeta() {
    const panel   = document.getElementById('metaPanel');
    const chevron = document.getElementById('metaChevron');
    const open    = panel.style.display === 'none';
    panel.style.display    = open ? 'block' : 'none';
    chevron.style.transform = open ? 'rotate(180deg)' : '';
}
</script>
