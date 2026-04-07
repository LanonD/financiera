<?php
// Variables: $cliente, $promotores
?>
<a href="<?= APP_URL ?>/clientes/detalle?id=<?= $cliente['id'] ?>" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--text-muted);margin-bottom:16px;text-decoration:none">
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11L5 7l4-4"/></svg>
    Volver al detalle
</a>

<div class="content-header">
    <div><h2>Editar cliente</h2><p><?= htmlspecialchars($cliente['nombre']) ?></p></div>
</div>

<div class="table-card" style="max-width:700px">
<form method="POST" action="<?= APP_URL ?>/clientes/editar" onsubmit="this.querySelector('[type=submit]').disabled=true">
    <input type="hidden" name="id" value="<?= $cliente['id'] ?>">
    <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:16px">

        <?php
        $fields = [
            ['nombre',    'Nombre completo',      'text',  '',  true,  '1/-1'],
            ['celular',   'Celular',               'tel',   '',  false, ''],
            ['email',     'Correo electrónico',    'email', '',  false, ''],
            ['fijo',      'Teléfono fijo',         'tel',   '',  false, ''],
            ['curp',      'CURP',                  'text',  '',  false, ''],
            ['direccion', 'Dirección',             'text',  '',  false, '1/-1'],
        ];
        foreach ($fields as [$name, $label, $type, $placeholder, $required, $span]):
        ?>
        <div <?= $span ? "style=\"grid-column:$span\"" : '' ?>>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px"><?= $label ?></label>
            <input type="<?= $type ?>" name="<?= $name ?>"
                value="<?= htmlspecialchars($cliente[$name] ?? '') ?>"
                <?= $required ? 'required' : '' ?>
                style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
        </div>
        <?php endforeach; ?>

        <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Ocupación</label>
            <select name="ocupacion" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
                <?php foreach(['Empleado','Negocio propio','Independiente','Otro'] as $op): ?>
                <option <?= $cliente['ocupacion']===$op?'selected':'' ?>><?= $op ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Promotor</label>
            <select name="promotor_id" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
                <?php foreach($promotores as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $cliente['promotor_id']==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--border);background:var(--bg-hover);display:flex;gap:8px;justify-content:flex-end">
        <a href="<?= APP_URL ?>/clientes/detalle?id=<?= $cliente['id'] ?>" class="btn-secondary" style="text-decoration:none;padding:8px 16px;border-radius:var(--radius-sm);font-size:13px">Cancelar</a>
        <button type="submit" class="btn-primary">Guardar cambios</button>
    </div>
</form>
</div>
