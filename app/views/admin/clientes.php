<?php
$puesto = $_SESSION['puesto'] ?? 'admin';

// Construir lista única de promotores para el filtro
$promotoresList = [];
foreach ($clientes as $c) {
    $pn = $c['promotor_nombre'] ?? '';
    if ($pn && !in_array($pn, $promotoresList)) $promotoresList[] = $pn;
}
sort($promotoresList);
?>
<div class="content-header">
    <div>
        <h2><?= $puesto === 'promo' ? 'Mis clientes' : 'Todos los clientes' ?></h2>
        <p>Gestión de clientes del sistema</p>
    </div>
    <?php if ($puesto === 'promo'): ?>
    <button class="btn-primary" onclick="document.getElementById('modalCliente').classList.add('open')">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:13px;height:13px"><path d="M7 2v10M2 7h10"/></svg>
        Nuevo cliente
    </button>
    <?php endif; ?>
</div>

<!-- Filtros JS -->
<div class="filter-panel">
    <div class="filter-group">
        <label>Buscar</label>
        <input class="filter-input" type="text" id="cSearch"
               placeholder="Nombre, celular, CURP…" oninput="filtrarClientes()"
               style="min-width:220px">
    </div>

    <div class="filter-divider"></div>

    <div class="filter-group">
        <label>Préstamo</label>
        <div class="status-group">
            <span class="status-pill pill-activo" data-cl="todos"   onclick="setPillCl(this)">Todos</span>
            <span class="status-pill"             data-cl="con"     onclick="setPillCl(this)">Con préstamo</span>
            <span class="status-pill"             data-cl="sin"     onclick="setPillCl(this)">Sin préstamo</span>
        </div>
    </div>

    <?php if ($puesto === 'admin' && !empty($promotoresList)): ?>
    <div class="filter-divider"></div>
    <div class="filter-group">
        <label>Promotor</label>
        <select class="filter-input" id="cPromotor" onchange="filtrarClientes()" style="min-width:150px">
            <option value="">Todos</option>
            <?php foreach ($promotoresList as $pn): ?>
            <option value="<?= htmlspecialchars($pn) ?>"><?= htmlspecialchars($pn) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="filter-actions">
        <button class="btn-secondary" onclick="resetFiltrosClientes()">Limpiar</button>
    </div>
</div>

<div class="table-card">
    <div class="table-header">
        <div>
            <div class="table-title">Clientes</div>
            <div class="table-count" id="cCount"><?= count($clientes) ?> registros</div>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Nombre</th><th>Celular</th><th>Correo</th>
                <th>Dirección</th><th>CURP</th><th>Préstamo</th>
                <th>Promotor</th><th></th>
            </tr>
        </thead>
        <tbody id="cBody">
        <?php if (empty($clientes)): ?>
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted)">No hay clientes registrados</td></tr>
        <?php else: ?>
        <?php foreach ($clientes as $c):
            $tieneP = !empty($c['prestamos_activos']);
            $pnorm  = strtolower($c['nombre'] . ' ' . ($c['celular'] ?? '') . ' ' . ($c['curp'] ?? '') . ' ' . ($c['promotor_nombre'] ?? ''));
        ?>
        <tr data-busqueda="<?= htmlspecialchars($pnorm) ?>"
            data-prestamo="<?= $tieneP ? 'con' : 'sin' ?>"
            data-promotor="<?= htmlspecialchars($c['promotor_nombre'] ?? '') ?>">
            <td class="td-name">
                <span class="initials"><?= strtoupper(substr($c['nombre'], 0, 2)) ?></span>
                <?= htmlspecialchars($c['nombre']) ?>
            </td>
            <td class="td-numeric"><?= htmlspecialchars($c['celular'] ?? '—') ?></td>
            <td style="font-size:12px;color:var(--text-secondary)"><?= $c['email'] ? htmlspecialchars($c['email']) : '—' ?></td>
            <td><?= htmlspecialchars($c['direccion'] ?? '—') ?></td>
            <td class="td-numeric" style="font-size:11px"><?= $c['curp'] ?? '—' ?></td>
            <td>
                <?php if ($tieneP): ?>
                <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:#dcfce7;color:#166534">Con préstamo</span>
                <?php else: ?>
                <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:#f1f5f9;color:#64748b">Sin préstamo</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($c['promotor_nombre'] ?? '—') ?></td>
            <td style="display:flex;gap:6px">
                <a class="action-btn" href="<?= APP_URL ?>/clientes/detalle?id=<?= $c['id'] ?>">Ver</a>
                <?php if ($puesto === 'admin'): ?>
                <a class="action-btn" href="<?= APP_URL ?>/clientes/editar?id=<?= $c['id'] ?>">Editar</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($puesto === 'promo'): ?>
<div class="modal-overlay" id="modalCliente" onclick="if(event.target===this)this.classList.remove('open')">
<div class="modal" style="width:500px">
    <div class="modal-header">
        <h3>Registrar cliente</h3>
        <button class="modal-close" onclick="document.getElementById('modalCliente').classList.remove('open')">×</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/clientes/crear">
    <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <?php foreach ([['nombre','Nombre completo','text'],['celular','Celular','tel'],['email','Correo electrónico','email'],['fijo','Teléfono fijo','tel'],['curp','CURP','text'],['direccion','Dirección','text']] as [$n,$l,$t]): ?>
    <div <?= in_array($n, ['nombre','email','direccion']) ? 'style="grid-column:1/-1"' : '' ?>>
        <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px"><?= $l ?></label>
        <input type="<?= $t ?>" name="<?= $n ?>" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
    </div>
    <?php endforeach; ?>
    <div>
        <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Ocupación</label>
        <select name="ocupacion" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
            <option>Empleado</option><option>Negocio propio</option>
        </select>
    </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="document.getElementById('modalCliente').classList.remove('open')">Cancelar</button>
        <button type="submit" class="btn-primary">Registrar</button>
    </div>
    </form>
</div></div>
<?php endif; ?>

<script>
let clFiltroP = 'todos';

function setPillCl(el) {
    clFiltroP = el.dataset.cl;
    document.querySelectorAll('[data-cl]').forEach(p => p.classList.remove('pill-activo'));
    el.classList.add('pill-activo');
    filtrarClientes();
}

function filtrarClientes() {
    const q        = (document.getElementById('cSearch')?.value  || '').trim().toLowerCase();
    const promotor = (document.getElementById('cPromotor')?.value || '').toLowerCase();
    let v = 0;
    document.querySelectorAll('#cBody tr[data-busqueda]').forEach(r => {
        const matchQ = !q        || r.dataset.busqueda.includes(q);
        const matchP = clFiltroP === 'todos' || r.dataset.prestamo === clFiltroP;
        const matchR = !promotor || r.dataset.promotor.toLowerCase() === promotor;
        const show   = matchQ && matchP && matchR;
        r.style.display = show ? '' : 'none';
        if (show) v++;
    });
    document.getElementById('cCount').textContent = v + ' registros';
}

function resetFiltrosClientes() {
    const s = document.getElementById('cSearch');
    const p = document.getElementById('cPromotor');
    if (s) s.value = '';
    if (p) p.value = '';
    clFiltroP = 'todos';
    document.querySelectorAll('[data-cl]').forEach(el => el.classList.remove('pill-activo'));
    document.querySelector('[data-cl="todos"]')?.classList.add('pill-activo');
    filtrarClientes();
}

window.addEventListener('load', filtrarClientes);
</script>

<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center;backdrop-filter:blur(2px)}
.modal-overlay.open{display:flex}
.modal{background:var(--bg-card);border-radius:var(--radius-lg);max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.15);overflow:hidden}
.modal-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.modal-header h3{font-size:14px;font-weight:600}
.modal-close{width:26px;height:26px;border:none;background:var(--bg-hover);border-radius:6px;cursor:pointer;font-size:16px;color:var(--text-muted)}
.modal-body{padding:20px}
.modal-footer{padding:14px 20px;border-top:1px solid var(--border);background:var(--bg-hover);display:flex;gap:8px;justify-content:flex-end}
</style>
