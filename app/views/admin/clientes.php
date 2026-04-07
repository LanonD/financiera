<?php $puesto = $_SESSION['puesto'] ?? 'admin'; ?>
<div class="content-header">
    <div><h2><?= $puesto==='promo'?'Mis clientes':'Todos los clientes' ?></h2><p>Gestión de clientes del sistema</p></div>
    <?php if ($puesto==='promo'): ?>
    <button class="btn-primary" onclick="document.getElementById('modalCliente').classList.add('open')">+ Nuevo cliente</button>
    <?php endif; ?>
</div>
<div class="table-card">
    <div class="table-header"><div class="table-title">Clientes</div><div class="table-count"><?= count($clientes) ?> registros</div></div>
    <table>
        <thead><tr><th>Nombre</th><th>Celular</th><th>Correo</th><th>Dirección</th><th>CURP</th><th>Promotor</th><th></th></tr></thead>
        <tbody>
        <?php if(empty($clientes)): ?>
        <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted)">No hay clientes registrados</td></tr>
        <?php else: ?>
        <?php foreach($clientes as $c): ?>
        <tr>
            <td class="td-name"><span class="initials"><?= strtoupper(substr($c['nombre'],0,2)) ?></span><?= htmlspecialchars($c['nombre']) ?></td>
            <td class="td-numeric"><?= htmlspecialchars($c['celular']??'—') ?></td>
            <td style="font-size:12px;color:var(--text-secondary)"><?= $c['email'] ? htmlspecialchars($c['email']) : '—' ?></td>
            <td><?= htmlspecialchars($c['direccion']??'—') ?></td>
            <td class="td-numeric" style="font-size:11px"><?= $c['curp']??'—' ?></td>
            <td><?= htmlspecialchars($c['promotor_nombre']??'—') ?></td>
            <td>
                <a class="action-btn" href="<?= APP_URL ?>/clientes/detalle?id=<?= $c['id'] ?>">Ver</a>
                <?php if(($_SESSION['puesto']??'') === 'admin'): ?>
                <a class="action-btn" href="<?= APP_URL ?>/clientes/editar?id=<?= $c['id'] ?>">Editar</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php if($puesto==='promo'): ?>
<div class="modal-overlay" id="modalCliente" onclick="if(event.target===this)this.classList.remove('open')">
<div class="modal" style="width:500px">
    <div class="modal-header"><h3>Registrar cliente</h3><button class="modal-close" onclick="document.getElementById('modalCliente').classList.remove('open')">×</button></div>
    <form method="POST" action="<?= APP_URL ?>/clientes/crear">
    <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <?php foreach([['nombre','Nombre completo','text',''],['celular','Celular','tel',''],['email','Correo electrónico','email',''],['fijo','Teléfono fijo','tel',''],['curp','CURP','text',''],['direccion','Dirección','text','']] as [$n,$l,$t,$p]): ?>
    <div <?= in_array($n,['nombre','email','direccion'])?'style="grid-column:1/-1"':'' ?>>
        <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px"><?= $l ?></label>
        <input type="<?= $t ?>" name="<?= $n ?>" placeholder="<?= $p ?>" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
    </div>
    <?php endforeach; ?>
    <div>
        <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Ocupación</label>
        <select name="ocupacion" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
            <option>Empleado</option><option>Negocio propio</option>
        </select>
    </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn-secondary" onclick="document.getElementById('modalCliente').classList.remove('open')">Cancelar</button><button type="submit" class="btn-primary">Registrar</button></div>
    </form>
</div></div>
<style>.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center;backdrop-filter:blur(2px)}.modal-overlay.open{display:flex}.modal{background:var(--bg-card);border-radius:var(--radius-lg);max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.15);overflow:hidden}.modal-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}.modal-header h3{font-size:14px;font-weight:600}.modal-close{width:26px;height:26px;border:none;background:var(--bg-hover);border-radius:6px;cursor:pointer;font-size:16px;color:var(--text-muted)}.modal-body{padding:20px}.modal-footer{padding:14px 20px;border-top:1px solid var(--border);background:var(--bg-hover);display:flex;gap:8px;justify-content:flex-end}</style>
<?php endif; ?>
