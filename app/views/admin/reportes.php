<?php
// Variables: $desembolsos_hoy, $cobros_hoy, $cartera, $por_estatus,
//            $cobros_por_cobrador, $cobros_7dias, $atrasados
function fmt($v){ return '$'.number_format((float)$v,2,'.',','); }
?>

<style>
.rpt-grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
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
.chart-bars{display:flex;align-items:flex-end;gap:6px;height:100px;padding:0 4px}
.chart-bar-wrap{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px}
.chart-bar{width:100%;border-radius:3px 3px 0 0;background:var(--accent);transition:height .4s}
.chart-day{font-size:9px;color:var(--text-muted);text-align:center}
</style>

<!-- KPIs del día -->
<div class="rpt-grid-4">
    <div class="kpi">
        <div class="kpi-label">Cobrado hoy</div>
        <div class="kpi-value" style="color:#16a34a"><?= fmt($cobros_hoy['total']) ?></div>
        <div class="kpi-sub"><?= $cobros_hoy['num'] ?> cobro(s) registrado(s)</div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Desembolsado hoy</div>
        <div class="kpi-value" style="color:#3b82f6"><?= fmt($desembolsos_hoy['total']) ?></div>
        <div class="kpi-sub"><?= $desembolsos_hoy['num'] ?> préstamo(s) entregado(s)</div>
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

<div class="rpt-grid-2">
    <!-- Gráfica últimos 7 días -->
    <div class="rpt-card">
        <div class="rpt-card-header">Cobros — últimos 7 días</div>
        <div class="rpt-card-body" style="padding-top:20px">
        <?php
        $maxVal = max(1, max(array_column($cobros_7dias, 'total') ?: [1]));
        $dias7  = [];
        for ($d = 6; $d >= 0; $d--) {
            $fecha = date('Y-m-d', strtotime("-$d days"));
            $dias7[$fecha] = 0;
        }
        foreach ($cobros_7dias as $row) { $dias7[$row['dia']] = (float)$row['total']; }
        ?>
        <div class="chart-bars">
        <?php foreach ($dias7 as $fecha => $total): ?>
            <div class="chart-bar-wrap">
                <div style="font-size:10px;color:var(--text-muted);font-family:var(--font-mono);text-align:center"><?= $total > 0 ? '$'.number_format($total/1000,0).'k' : '' ?></div>
                <div class="chart-bar" style="height:<?= $maxVal > 0 ? round($total/$maxVal*80) : 0 ?>px;background:<?= date('Y-m-d') === $fecha ? 'var(--accent)' : '#93c5fd' ?>"></div>
                <div class="chart-day"><?= date('d/m', strtotime($fecha)) ?></div>
            </div>
        <?php endforeach; ?>
        </div>
        </div>
    </div>

    <!-- Cobros por cobrador -->
    <div class="rpt-card">
        <div class="rpt-card-header">Cobros de hoy por cobrador</div>
        <div class="rpt-card-body">
        <?php if (empty($cobros_por_cobrador)): ?>
        <p style="color:var(--text-muted);font-size:13px;text-align:center;padding:16px 0">Sin cobros registrados hoy</p>
        <?php else:
        $maxC = max(array_column($cobros_por_cobrador, 'total'));
        foreach ($cobros_por_cobrador as $cc): ?>
        <div class="bar-wrap">
            <div class="bar-label"><?= htmlspecialchars($cc['nombre']) ?></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= $maxC > 0 ? round($cc['total']/$maxC*100) : 0 ?>%;background:#16a34a"></div></div>
            <div class="bar-val" style="color:#16a34a"><?= fmt($cc['total']) ?></div>
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

    <!-- Préstamos atrasados -->
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
