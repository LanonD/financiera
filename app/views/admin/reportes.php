<?php
// Variables: $resumen, $cobros_hoy, $desembolsos_hoy, $cartera, $cobros_rango,
//            $cobros_por_cobrador, $por_estatus, $atrasados, $fecha_desde, $fecha_hasta
function fmt($v){ return '$'.number_format((float)$v,2,'.',','); }
$rangoLabel = ($fecha_desde === $fecha_hasta)
    ? date('d/m/Y', strtotime($fecha_desde))
    : date('d/m/Y', strtotime($fecha_desde)) . ' — ' . date('d/m/Y', strtotime($fecha_hasta));
?>

<style>
.rpt-filters{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px 18px;display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;margin-bottom:20px}
.rpt-filters label{display:block;font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px}
.rpt-filters input[type=date]{background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text-primary);font-size:13px;padding:6px 10px;min-width:140px}
.rpt-filters .btn-primary{padding:7px 18px;font-size:13px}
.rpt-filter-sep{flex:1}
.rpt-period-badge{font-size:11px;color:var(--text-muted);background:var(--bg-input);padding:4px 10px;border-radius:999px;border:1px solid var(--border);white-space:nowrap}
.rpt-grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.rpt-grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px}
.rpt-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
.kpi{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:18px 20px}
.kpi-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:6px}
.kpi-value{font-size:26px;font-weight:700;font-family:var(--font-mono);letter-spacing:-.02em;line-height:1}
.kpi-sub{font-size:12px;color:var(--text-muted);margin-top:4px;font-family:var(--font-mono)}
.rpt-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden}
.rpt-card-header{padding:12px 18px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:space-between}
.rpt-card-body{padding:16px 18px}
.bar-wrap{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.bar-label{width:110px;flex-shrink:0;font-size:12px;color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar-track{flex:1;height:8px;background:var(--bg-input);border-radius:4px;overflow:hidden}
.bar-fill{height:100%;border-radius:4px;transition:width .4s}
.bar-val{width:90px;text-align:right;font-size:12px;font-weight:600;font-family:var(--font-mono)}
.status-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)}
.status-row:last-child{border-bottom:none}
.chart-bars{display:flex;align-items:flex-end;gap:3px;height:100px;padding:0 2px;overflow-x:auto}
.chart-bar-wrap{min-width:28px;flex:1;display:flex;flex-direction:column;align-items:center;gap:4px}
.chart-bar{width:100%;border-radius:3px 3px 0 0;transition:height .4s}
.chart-day{font-size:9px;color:var(--text-muted);text-align:center;white-space:nowrap}
.split-bar{display:flex;height:10px;border-radius:5px;overflow:hidden;margin:10px 0}
.split-bar-a{background:#16a34a;transition:width .4s}
.split-bar-b{background:#dc2626;transition:width .4s}
.punctuality-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px}
.punct-box{border-radius:var(--radius-sm);padding:12px;text-align:center}
.punct-num{font-size:22px;font-weight:700;font-family:var(--font-mono);line-height:1}
.punct-label{font-size:11px;margin-top:3px;opacity:.8}
.decomp-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
.decomp-row:last-child{border-bottom:none}
</style>

<!-- Filtro de fechas -->
<form method="GET" class="rpt-filters" action="">
    <div>
        <label>Desde</label>
        <input type="date" name="desde" value="<?= htmlspecialchars($fecha_desde) ?>" max="<?= date('Y-m-d') ?>">
    </div>
    <div>
        <label>Hasta</label>
        <input type="date" name="hasta" value="<?= htmlspecialchars($fecha_hasta) ?>" max="<?= date('Y-m-d') ?>">
    </div>
    <button type="submit" class="btn-primary">Filtrar</button>
    <div class="rpt-filter-sep"></div>
    <div>
        <div style="font-size:10px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Período</div>
        <span class="rpt-period-badge"><?= htmlspecialchars($rangoLabel) ?></span>
    </div>
    <!-- Atajos rápidos -->
    <div style="display:flex;gap:6px;flex-wrap:wrap">
        <?php
        $atajos = [
            'Hoy'      => [date('Y-m-d'), date('Y-m-d')],
            'Esta sem' => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d')],
            'Este mes' => [date('Y-m-01'), date('Y-m-d')],
            'Mes ant'  => [date('Y-m-01', strtotime('first day of last month')), date('Y-t', strtotime('last month'))],
        ];
        foreach ($atajos as $label => [$d, $h]):
        ?>
        <a href="?desde=<?= $d ?>&hasta=<?= $h ?>"
           style="font-size:11px;padding:4px 10px;border-radius:999px;border:1px solid var(--border);color:var(--text-secondary);text-decoration:none;background:<?= ($d===$fecha_desde&&$h===$fecha_hasta)?'var(--accent)':'var(--bg-input)' ?>;<?= ($d===$fecha_desde&&$h===$fecha_hasta)?'color:#fff;border-color:var(--accent)':'' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>
</form>

<!-- KPIs: hoy fijos + resumen del período -->
<div class="rpt-grid-4">
    <div class="kpi">
        <div class="kpi-label">Cobrado en período</div>
        <div class="kpi-value" style="color:#16a34a"><?= fmt($resumen['total_monto']) ?></div>
        <div class="kpi-sub"><?= (int)$resumen['total_cobros'] ?> cobro(s) · <?= htmlspecialchars($rangoLabel) ?></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Principal cobrado</div>
        <div class="kpi-value" style="color:#3b82f6"><?= fmt($resumen['total_capital']) ?></div>
        <div class="kpi-sub">Interés cobrado: <?= fmt($resumen['total_interes']) ?></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Saldo en cartera</div>
        <div class="kpi-value" style="color:var(--text-primary)"><?= fmt($cartera['saldo_total']) ?></div>
        <div class="kpi-sub"><?= $cartera['num_prestamos'] ?> préstamos activos/atrasados</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Interés acumulado total</div>
        <div class="kpi-value" style="color:#f59e0b"><?= fmt($cartera['interes_total']) ?></div>
        <div class="kpi-sub">Deuda total: <?= fmt($cartera['deuda_total']) ?></div>
    </div>
</div>

<!-- Puntualidad + Desglose principal/interés + Hoy -->
<div class="rpt-grid-3" style="margin-bottom:20px">

    <!-- Puntualidad -->
    <div class="rpt-card">
        <div class="rpt-card-header">Puntualidad de cobros</div>
        <div class="rpt-card-body">
        <?php
        $totalCobros = max(1, (int)$resumen['total_cobros']);
        $aTiempoNum  = (int)$resumen['a_tiempo_num'];
        $tardeNum    = (int)$resumen['tarde_num'];
        $aTiempoPct  = round($aTiempoNum / $totalCobros * 100);
        $tardePct    = 100 - $aTiempoPct;
        ?>
        <div class="split-bar">
            <div class="split-bar-a" style="width:<?= $aTiempoPct ?>%"></div>
            <div class="split-bar-b" style="width:<?= $tardePct ?>%"></div>
        </div>
        <div class="punctuality-grid">
            <div class="punct-box" style="background:rgba(22,163,74,.12)">
                <div class="punct-num" style="color:#16a34a"><?= $aTiempoPct ?>%</div>
                <div class="punct-label" style="color:#16a34a">A tiempo</div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px"><?= $aTiempoNum ?> cobros · <?= fmt($resumen['a_tiempo_monto']) ?></div>
            </div>
            <div class="punct-box" style="background:rgba(220,38,38,.10)">
                <div class="punct-num" style="color:#dc2626"><?= $tardePct ?>%</div>
                <div class="punct-label" style="color:#dc2626">Con atraso</div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px"><?= $tardeNum ?> cobros · <?= fmt($resumen['tarde_monto']) ?></div>
            </div>
        </div>
        </div>
    </div>

    <!-- Desglose principal / interés -->
    <div class="rpt-card">
        <div class="rpt-card-header">Composición de cobros</div>
        <div class="rpt-card-body">
        <?php
        $totalMonto   = max(1, (float)$resumen['total_monto']);
        $capPct       = $totalMonto > 0 ? round((float)$resumen['total_capital'] / $totalMonto * 100) : 0;
        $intPct       = 100 - $capPct;
        ?>
        <div class="split-bar" style="margin-top:4px">
            <div style="background:#3b82f6;height:10px;border-radius:5px 0 0 5px;width:<?= $capPct ?>%"></div>
            <div style="background:#f59e0b;height:10px;border-radius:0 5px 5px 0;width:<?= $intPct ?>%"></div>
        </div>
        <div style="display:flex;gap:12px;margin-top:10px;font-size:13px">
            <span style="display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:2px;background:#3b82f6;flex-shrink:0"></span>Principal</span>
            <span style="display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:2px;background:#f59e0b;flex-shrink:0"></span>Interés</span>
        </div>
        <div class="decomp-row" style="margin-top:8px">
            <span>Principal</span>
            <span style="font-family:var(--font-mono);font-weight:600;color:#3b82f6"><?= fmt($resumen['total_capital']) ?> <small style="color:var(--text-muted);font-weight:400">(<?= $capPct ?>%)</small></span>
        </div>
        <div class="decomp-row">
            <span>Interés</span>
            <span style="font-family:var(--font-mono);font-weight:600;color:#f59e0b"><?= fmt($resumen['total_interes']) ?> <small style="color:var(--text-muted);font-weight:400">(<?= $intPct ?>%)</small></span>
        </div>
        <div class="decomp-row" style="font-weight:600">
            <span>Total</span>
            <span style="font-family:var(--font-mono);color:#16a34a"><?= fmt($resumen['total_monto']) ?></span>
        </div>
        </div>
    </div>

    <!-- Actividad de hoy -->
    <div class="rpt-card">
        <div class="rpt-card-header">Actividad de hoy</div>
        <div class="rpt-card-body">
        <div class="decomp-row">
            <span>Cobrado hoy</span>
            <div>
                <span style="font-family:var(--font-mono);font-weight:700;color:#16a34a"><?= fmt($cobros_hoy['total']) ?></span>
                <div style="font-size:11px;color:var(--text-muted)"><?= $cobros_hoy['num'] ?> cobro(s)</div>
            </div>
        </div>
        <div class="decomp-row">
            <span>Desembolsado hoy</span>
            <div>
                <span style="font-family:var(--font-mono);font-weight:700;color:#3b82f6"><?= fmt($desembolsos_hoy['total']) ?></span>
                <div style="font-size:11px;color:var(--text-muted)"><?= $desembolsos_hoy['num'] ?> préstamo(s)</div>
            </div>
        </div>
        <div class="decomp-row" style="border-bottom:none">
            <span>Interés pend. cartera</span>
            <div>
                <span style="font-family:var(--font-mono);font-weight:700;color:#f59e0b"><?= fmt($cartera['interes_total']) ?></span>
                <div style="font-size:11px;color:var(--text-muted)">acumulado sin cobrar</div>
            </div>
        </div>
        </div>
    </div>
</div>

<div class="rpt-grid-2">
    <!-- Gráfica cobros en rango -->
    <div class="rpt-card">
        <div class="rpt-card-header">
            Cobros por día
            <span style="font-size:11px;color:var(--text-muted);font-weight:400"><?= htmlspecialchars($rangoLabel) ?></span>
        </div>
        <div class="rpt-card-body" style="padding-top:20px">
        <?php
        // Build a map dia => data for every day in range
        $diasMap = [];
        $cur = strtotime($fecha_desde);
        $end = strtotime($fecha_hasta);
        while ($cur <= $end) {
            $diasMap[date('Y-m-d', $cur)] = ['total' => 0, 'principal' => 0, 'interes_dia' => 0];
            $cur = strtotime('+1 day', $cur);
        }
        foreach ($cobros_rango as $row) {
            if (isset($diasMap[$row['dia']])) $diasMap[$row['dia']] = $row;
        }
        $maxVal = max(1, max(array_column($diasMap, 'total') ?: [1]));
        $hoyStr = date('Y-m-d');
        ?>
        <div class="chart-bars">
        <?php foreach ($diasMap as $fecha => $data): ?>
            <div class="chart-bar-wrap" title="<?= date('d/m', strtotime($fecha)) ?> · <?= fmt($data['total']) ?>">
                <div style="font-size:9px;color:var(--text-muted);font-family:var(--font-mono);text-align:center"><?= $data['total'] > 0 ? '$'.number_format((float)$data['total']/1000,0).'k' : '' ?></div>
                <div class="chart-bar" style="height:<?= $maxVal > 0 ? max(2, round((float)$data['total']/$maxVal*80)) : 2 ?>px;background:<?= $fecha === $hoyStr ? 'var(--accent)' : '#93c5fd' ?>"></div>
                <div class="chart-day"><?= date('d/m', strtotime($fecha)) ?></div>
            </div>
        <?php endforeach; ?>
        </div>
        </div>
    </div>

    <!-- Cobros por cobrador en rango -->
    <div class="rpt-card">
        <div class="rpt-card-header">
            Cobros por cobrador
            <span style="font-size:11px;color:var(--text-muted);font-weight:400"><?= htmlspecialchars($rangoLabel) ?></span>
        </div>
        <div class="rpt-card-body">
        <?php if (empty($cobros_por_cobrador)): ?>
        <p style="color:var(--text-muted);font-size:13px;text-align:center;padding:16px 0">Sin cobros en este período</p>
        <?php else:
        $maxC = max(array_column($cobros_por_cobrador, 'total'));
        foreach ($cobros_por_cobrador as $cc):
            $aTiempoP = $cc['num'] > 0 ? round($cc['a_tiempo'] / $cc['num'] * 100) : 0;
        ?>
        <div class="bar-wrap" style="margin-bottom:12px">
            <div class="bar-label" style="width:100px" title="<?= htmlspecialchars($cc['nombre']) ?>"><?= htmlspecialchars($cc['nombre']) ?></div>
            <div style="flex:1">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px">
                    <div class="bar-track" style="flex:1"><div class="bar-fill" style="width:<?= $maxC > 0 ? round((float)$cc['total']/$maxC*100) : 0 ?>%;background:#16a34a"></div></div>
                    <span style="font-size:12px;font-weight:600;font-family:var(--font-mono);color:#16a34a;width:80px;text-align:right"><?= fmt($cc['total']) ?></span>
                </div>
                <div style="font-size:11px;color:var(--text-muted);display:flex;gap:12px">
                    <span>Principal: <b style="color:var(--text-secondary)"><?= fmt($cc['principal']) ?></b></span>
                    <span>Interés: <b style="color:#f59e0b"><?= fmt($cc['interes_cobrador']) ?></b></span>
                    <span style="color:<?= $aTiempoP >= 80 ? '#16a34a' : ($aTiempoP >= 50 ? '#ca8a04' : '#dc2626') ?>"><?= $aTiempoP ?>% a tiempo</span>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<div class="rpt-grid-2">
    <!-- Préstamos por estatus -->
    <div class="rpt-card">
        <div class="rpt-card-header">Préstamos por estatus</div>
        <div class="rpt-card-body" style="padding:0 18px">
        <?php
        $colorMap = ['Activo'=>'#16a34a','Atrasado'=>'#dc2626','Pendiente'=>'#ca8a04','Finalizado'=>'#3b82f6','Retirado'=>'#94a3b8','Cancelado'=>'#6b7280'];
        foreach ($por_estatus as $st): $clr = $colorMap[$st['estatus']] ?? '#6b7280'; ?>
        <div class="status-row">
            <div style="display:flex;align-items:center;gap:8px">
                <span style="width:8px;height:8px;border-radius:50%;background:<?= $clr ?>;flex-shrink:0"></span>
                <span style="font-size:13px;font-weight:500"><?= $st['estatus'] ?></span>
            </div>
            <div style="display:flex;gap:20px;align-items:center">
                <span style="font-size:12px;color:var(--text-muted)"><?= $st['num'] ?> préstamo(s)</span>
                <span style="font-size:13px;font-weight:600;font-family:var(--font-mono);color:<?= $clr ?>"><?= fmt($st['saldo']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- Préstamos atrasados top 10 -->
    <div class="rpt-card">
        <div class="rpt-card-header">
            Préstamos con mayor atraso
            <span style="font-size:11px;color:var(--text-muted);font-weight:400">Top 10</span>
        </div>
        <div style="overflow-x:auto">
        <?php if (empty($atrasados)): ?>
        <p style="color:var(--text-muted);font-size:13px;text-align:center;padding:20px">Sin préstamos atrasados</p>
        <?php else: ?>
        <table style="width:100%">
            <thead><tr>
                <th>Cliente</th><th class="td-amount">Saldo</th><th class="td-amount">Interés</th><th class="td-numeric">Días</th>
            </tr></thead>
            <tbody>
            <?php foreach ($atrasados as $a):
                $clr = $a['dias_atraso'] > 15 ? '#dc2626' : ($a['dias_atraso'] > 7 ? '#d97706' : '#ca8a04');
            ?>
            <tr>
                <td><a href="<?= APP_URL ?>/prestamos/detalle?id=<?= $a['id'] ?>" style="color:var(--accent);text-decoration:none;font-size:13px"><?= htmlspecialchars($a['cliente']) ?></a></td>
                <td class="td-amount"><?= fmt($a['saldo_actual']) ?></td>
                <td class="td-amount" style="color:#f59e0b"><?= fmt($a['interes_acumulado']) ?></td>
                <td class="td-numeric" style="color:<?= $clr ?>;font-weight:600"><?= $a['dias_atraso'] ?>d</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        </div>
    </div>
</div>
