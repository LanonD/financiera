<div class="content-header">
    <div style="display:flex; align-items:center; gap: 1rem;">
        <a href="<?= APP_URL ?>/empleados" class="btn-secondary" style="padding: 6px 10px;">← Volver</a>
        <div>
            <h2>Detalle de Empleado: <?= htmlspecialchars($empleado['nombre']) ?></h2>
            <p>Puesto: <?= ucfirst($empleado['puesto']) ?> | Celular: <?= htmlspecialchars($empleado['celular']) ?></p>
        </div>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Préstamos Activos Asignados</div>
        <div class="kpi-value"><?= count($prestamosActivos) ?></div>
        <span class="kpi-trend flat">En curso</span>
    </div>
    <div class="kpi-card yellow">
        <div class="kpi-label">Pendientes / Por tratar</div>
        <div class="kpi-value"><?= count($pendientes) ?></div>
        <span class="kpi-trend flat">Requieren atención</span>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">Historial (Recientes)</div>
        <div class="kpi-value"><?= count($historial) ?></div>
        <span class="kpi-trend up">Acciones completadas</span>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr; gap: 20px; margin-top:20px;">
    
    <!-- Prestamos Activos -->
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Préstamos Activos</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Monto</th>
                    <th>Cuota</th>
                    <th>Saldo Actual</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prestamosActivos)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:20px; color:var(--text-muted);">No hay préstamos activos asignados.</td>
                </tr>
                <?php else: foreach ($prestamosActivos as $row): 
                    $badge = match($row['estatus']) {
                        'Activo'     => 'badge-activo',
                        'Pendiente'  => 'badge-pendiente',
                        'Atrasado'   => 'badge-atrasado',
                        'Finalizado' => 'badge-finalizado',
                        default      => 'badge-pendiente'
                    };
                ?>
                <tr>
                    <td class="td-id">#<?= $row['id'] ?></td>
                    <td class="td-name"><?= htmlspecialchars($row['cliente_nombre']) ?></td>
                    <td class="td-amount">$<?= number_format($row['monto'] ?? 0, 2) ?></td>
                    <td class="td-amount">$<?= number_format($row['cuota'], 2) ?></td>
                    <td class="td-amount">$<?= number_format($row['saldo_actual'], 2) ?></td>
                    <td><span class="badge <?= $badge ?>"><span class="dot"></span><?= $row['estatus'] ?></span></td>
                    <td>
                        <a class="action-btn edit" href="<?= APP_URL ?>/prestamos/detalle?id=<?= $row['id'] ?>">Ver Préstamo</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pendientes (Desembolsos para promotor o nada para otros) -->
    <?php if ($empleado['puesto'] === 'promo'): ?>
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Desembolsos Pendientes</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Monto</th>
                    <th>Fecha Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pendientes)): ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:20px; color:var(--text-muted);">No hay desembolsos pendientes.</td>
                </tr>
                <?php else: foreach ($pendientes as $row): ?>
                <tr>
                    <td class="td-id">#<?= $row['id'] ?></td>
                    <td class="td-name"><?= htmlspecialchars($row['cliente_nombre']) ?></td>
                    <td class="td-amount">$<?= number_format($row['monto'], 2) ?></td>
                    <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                    <td>
                        <a class="action-btn edit" href="<?= APP_URL ?>/prestamos/detalle?id=<?= $row['id'] ?>">Ver Préstamo</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Historial (Pagos cobrados para cobrador) -->
    <?php if ($empleado['puesto'] === 'collector'): ?>
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Últimos Pagos Cobrados (Historial)</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Préstamo</th>
                    <th>Cliente</th>
                    <th>Monto Cobrado</th>
                    <th>Tipo</th>
                    <th>Fecha Cobro</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historial)): ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:20px; color:var(--text-muted);">No hay historial reciente de cobros.</td>
                </tr>
                <?php else: foreach ($historial as $row): ?>
                <tr>
                    <td class="td-id"><a href="<?= APP_URL ?>/prestamos/detalle?id=<?= $row['prestamo_id'] ?>">#<?= $row['prestamo_id'] ?></a></td>
                    <td class="td-name"><?= htmlspecialchars($row['cliente_nombre']) ?></td>
                    <td class="td-amount">$<?= number_format($row['monto_cobrado'], 2) ?></td>
                    <td><span class="badge badge-activo"><?= ucfirst($row['tipo_cobro']) ?></span></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['fecha_pago'])) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>
