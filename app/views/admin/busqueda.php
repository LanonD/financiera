<div class="content-header">
    <div><h2>Búsqueda avanzada</h2><p>Busca clientes y préstamos por nombre o teléfono</p></div>
</div>
<div class="filter-panel" style="margin-bottom:20px">
    <form method="GET" action="<?= APP_URL ?>/busqueda" style="display:flex;gap:10px;align-items:flex-end;width:100%">
        <div class="filter-group" style="flex:1">
            <label>Buscar por nombre o teléfono</label>
            <input class="filter-input" name="q" value="<?= htmlspecialchars($q??'') ?>" placeholder="Ej: Laura Méndez ó 55 1234…" style="width:100%">
        </div>
        <button type="submit" class="btn-primary">Buscar</button>
    </form>
</div>
<?php if($q): ?>
<div class="table-card" style="margin-bottom:16px">
    <div class="table-header"><div class="table-title">Clientes encontrados</div><div class="table-count"><?= count($clientes) ?></div></div>
    <table>
        <thead><tr><th>Nombre</th><th>Celular</th><th>Dirección</th><th>CURP</th><th>Promotor</th></tr></thead>
        <tbody>
        <?php if(empty($clientes)): ?><tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted)">Sin resultados</td></tr>
        <?php else: foreach($clientes as $c): ?>
        <tr>
            <td class="td-name"><span class="initials"><?= strtoupper(substr($c['nombre'],0,2)) ?></span><?= htmlspecialchars($c['nombre']) ?></td>
            <td class="td-numeric"><?= htmlspecialchars($c['celular']??'—') ?></td>
            <td><?= htmlspecialchars($c['direccion']??'—') ?></td>
            <td class="td-numeric" style="font-size:11px"><?= $c['curp']??'—' ?></td>
            <td><?= htmlspecialchars($c['promotor_nombre']??'—') ?></td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<div class="table-card">
    <div class="table-header"><div class="table-title">Préstamos relacionados</div><div class="table-count"><?= count($prestamos) ?></div></div>
    <table>
        <thead><tr><th>ID</th><th>Cliente</th><th>Monto</th><th>Saldo</th><th>Estatus</th><th></th></tr></thead>
        <tbody>
        <?php if(empty($prestamos)): ?><tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted)">Sin resultados</td></tr>
        <?php else: foreach($prestamos as $p): $badge=match($p['estatus']){'Activo'=>'badge-activo','Atrasado'=>'badge-atrasado','Finalizado'=>'badge-finalizado',default=>'badge-pendiente'}; ?>
        <tr>
            <td class="td-id">#<?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['cliente_nombre']) ?></td>
            <td class="td-amount">$<?= number_format($p['monto'],2,'.',',') ?></td>
            <td class="td-amount">$<?= number_format($p['saldo_actual'],2,'.',',') ?></td>
            <td><span class="badge <?= $badge ?>"><span class="dot"></span><?= $p['estatus'] ?></span></td>
            <td><a class="action-btn edit" href="<?= APP_URL ?>/prestamos/detalle?id=<?= $p['id'] ?>">Ver</a></td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:60px 24px;text-align:center;color:var(--text-muted)">
    <div style="font-size:15px;font-weight:500;color:var(--text-secondary)">Ingresa un término de búsqueda</div>
</div>
<?php endif; ?>
