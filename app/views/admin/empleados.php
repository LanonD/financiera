<div class="content-header">
    <div><h2>Empleados</h2><p>Gestión de promotores, cobradores y desembolso</p></div>
    <button class="btn-primary" onclick="document.getElementById('modalEmpleado').classList.add('open')">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:13px;height:13px"><path d="M7 2v10M2 7h10"/></svg>
        Nuevo empleado
    </button>
</div>

<?php if (isset($_GET['ok'])): ?>
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:var(--radius-sm);padding:10px 16px;margin-bottom:16px;font-size:13px;color:#166534;font-weight:500">
    <?= $_GET['ok'] === 'actualizado' ? 'Empleado actualizado correctamente.' : 'Empleado eliminado correctamente.' ?>
</div>
<?php elseif (isset($_GET['error'])): ?>
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:var(--radius-sm);padding:10px 16px;margin-bottom:16px;font-size:13px;color:#991b1b;font-weight:500">
    <?= $_GET['error'] === 'usuario_duplicado' ? 'Ese nombre de usuario ya está en uso. Elige otro.' : 'Error al guardar los cambios.' ?>
</div>
<?php endif; ?>

<!-- Filtros JS -->
<div class="filter-panel">
    <div class="filter-group">
        <label>Buscar</label>
        <input class="filter-input" type="text" id="eSearch"
               placeholder="Nombre, usuario, celular…" oninput="filtrarEmpleados()"
               style="min-width:220px">
    </div>
    <div class="filter-divider"></div>
    <div class="filter-group">
        <label>Tipo</label>
        <div class="status-group">
            <span class="status-pill pill-activo" data-ep="todos"       onclick="setPillEmp(this)">Todos</span>
            <span class="status-pill"             data-ep="promo"       onclick="setPillEmp(this)">Promotores</span>
            <span class="status-pill"             data-ep="collector"   onclick="setPillEmp(this)">Cobradores</span>
            <span class="status-pill"             data-ep="desembolso"  onclick="setPillEmp(this)">Desembolso</span>
        </div>
    </div>
    <div class="filter-divider"></div>
    <div class="filter-group">
        <label>Rango</label>
        <select class="filter-input" id="eRango" onchange="filtrarEmpleados()" style="min-width:120px">
            <option value="">Todos</option>
            <option>Bronce</option><option>Plata</option><option>Oro</option>
            <option>Platino</option><option>Diamante</option>
        </select>
    </div>
    <div class="filter-actions">
        <button class="btn-secondary" onclick="resetFiltrosEmp()">Limpiar</button>
    </div>
</div>

<?php
$totalEmp = count($promotores) + count($cobradores) + count($desembolso);
$secciones = [
    ['Promotores',  'promo',      $promotores],
    ['Cobradores',  'collector',  $cobradores],
    ['Desembolso',  'desembolso', $desembolso],
];
?>

<div id="eTotal" style="font-size:12px;color:var(--text-muted);margin-bottom:10px">
    <?= $totalEmp ?> empleados en total
</div>

<?php foreach ($secciones as [$titulo, $tipo, $lista]): ?>
<div class="table-card emp-section" data-seccion="<?= $tipo ?>" style="margin-bottom:16px">
    <div class="table-header">
        <div class="table-title"><?= $titulo ?></div>
        <div class="table-count emp-count" id="cnt-<?= $tipo ?>"><?= count($lista) ?></div>
    </div>
    <table>
        <thead>
            <tr><th>Nombre</th><th>Usuario</th><th>Celular</th><th>Correo</th><th>Rango</th><th>Capacidad máx.</th><th></th></tr>
        </thead>
        <tbody id="tbody-<?= $tipo ?>">
        <?php if (empty($lista)): ?>
        <tr class="emp-empty"><td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted)">Sin <?= strtolower($titulo) ?> registrados</td></tr>
        <?php else: foreach ($lista as $e): ?>
        <tr data-busqueda="<?= htmlspecialchars(strtolower($e['nombre'] . ' ' . ($e['usuario'] ?? '') . ' ' . ($e['celular'] ?? '') . ' ' . ($e['email'] ?? ''))) ?>"
            data-puesto="<?= $tipo ?>"
            data-rango="<?= htmlspecialchars($e['rango'] ?? '') ?>">
            <td class="td-name">
                <span class="initials"><?= strtoupper(substr($e['nombre'], 0, 2)) ?></span>
                <?= htmlspecialchars($e['nombre']) ?>
            </td>
            <td class="td-numeric"><?= htmlspecialchars($e['usuario'] ?? '—') ?></td>
            <td class="td-numeric"><?= htmlspecialchars($e['celular'] ?? '—') ?></td>
            <td style="font-size:12px;color:var(--text-secondary)"><?= $e['email'] ? htmlspecialchars($e['email']) : '—' ?></td>
            <td><span class="badge badge-activo"><span class="dot"></span><?= $e['rango'] ?? '—' ?></span></td>
            <td class="td-amount">$<?= number_format($e['capacidad_maxima'] ?? 0, 0, '.', ',') ?></td>
            <td style="display:flex;gap:6px">
                <a class="action-btn" href="<?= APP_URL ?>/empleados/detalle?id=<?= $e['id'] ?>">Ver detalle</a>
                <button class="action-btn" onclick="abrirEditarEmp(<?= htmlspecialchars(json_encode([
                    'id'        => $e['id'],
                    'usuario'   => $e['usuario']         ?? '',
                    'nombre'    => $e['nombre'],
                    'celular'   => $e['celular']         ?? '',
                    'email'     => $e['email']           ?? '',
                    'puesto'    => $e['puesto']          ?? '',
                    'rango'     => $e['rango']           ?? 'Bronce',
                    'capacidad' => $e['capacidad_maxima'] ?? 0,
                ]), ENT_QUOTES) ?>)">Editar</button>
                <button class="action-btn" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5"
                    onclick="confirmarEliminar(<?= $e['id'] ?>, '<?= htmlspecialchars($e['nombre'], ENT_QUOTES) ?>')">
                    Eliminar
                </button>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

<!-- Form oculto para eliminar -->
<form id="formEliminar" method="POST" action="<?= APP_URL ?>/empleados/eliminar" style="display:none">
    <input type="hidden" name="id" id="eliminarId">
</form>

<script>
let eFiltroP = 'todos';

function setPillEmp(el) {
    eFiltroP = el.dataset.ep;
    document.querySelectorAll('[data-ep]').forEach(p => p.classList.remove('pill-activo'));
    el.classList.add('pill-activo');
    filtrarEmpleados();
}

function filtrarEmpleados() {
    const q     = (document.getElementById('eSearch').value || '').trim().toLowerCase();
    const rango = (document.getElementById('eRango').value  || '').toLowerCase();
    let total   = 0;

    document.querySelectorAll('.emp-section').forEach(section => {
        const tipo = section.dataset.seccion;
        // Si filtramos por tipo y no es esta sección, ocultarla completa
        if (eFiltroP !== 'todos' && eFiltroP !== tipo) {
            section.style.display = 'none';
            return;
        }
        section.style.display = '';

        let v = 0;
        const rows = section.querySelectorAll('tr[data-busqueda]');
        rows.forEach(r => {
            const matchQ = !q     || r.dataset.busqueda.includes(q);
            const matchR = !rango || r.dataset.rango.toLowerCase() === rango;
            const show   = matchQ && matchR;
            r.style.display = show ? '' : 'none';
            if (show) v++;
        });

        // Mostrar fila vacía si ninguna coincide
        const emptyRow = section.querySelector('.emp-empty');
        if (emptyRow) emptyRow.style.display = rows.length === 0 ? '' : 'none';

        section.querySelector('.emp-count').textContent = v;
        total += v;
    });

    document.getElementById('eTotal').textContent = total + ' empleados';
}

function resetFiltrosEmp() {
    document.getElementById('eSearch').value = '';
    document.getElementById('eRango').value  = '';
    eFiltroP = 'todos';
    document.querySelectorAll('[data-ep]').forEach(p => p.classList.remove('pill-activo'));
    document.querySelector('[data-ep="todos"]').classList.add('pill-activo');
    filtrarEmpleados();
}

function confirmarEliminar(id, nombre) {
    if (confirm('¿Eliminar al empleado "' + nombre + '"?\nEsta acción desactiva su cuenta y no se puede deshacer.')) {
        document.getElementById('eliminarId').value = id;
        document.getElementById('formEliminar').submit();
    }
}

function abrirEditarEmp(e) {
    document.getElementById('empEditId').value        = e.id;
    document.getElementById('empEditUsuario').value   = e.usuario;
    document.getElementById('empEditNombre').value    = e.nombre;
    document.getElementById('empEditCelular').value   = e.celular;
    document.getElementById('empEditEmail').value     = e.email;
    document.getElementById('empEditCapacidad').value = e.capacidad;
    const p = document.getElementById('empEditPuesto');
    for (let o of p.options) o.selected = o.value === e.puesto;
    const r = document.getElementById('empEditRango');
    for (let o of r.options) o.selected = o.text === e.rango;
    document.getElementById('modalEditarEmp').classList.add('open');
}

window.addEventListener('load', filtrarEmpleados);
</script>

<!-- Modal: nuevo empleado -->
<div class="modal-overlay" id="modalEmpleado" onclick="if(event.target===this)this.classList.remove('open')">
<div class="modal" style="width:480px">
    <div class="modal-header">
        <h3>Nuevo empleado</h3>
        <button class="modal-close" onclick="document.getElementById('modalEmpleado').classList.remove('open')">×</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/empleados/crear">
    <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <?php foreach ([['usuario','Usuario login','text'],['password','Contraseña','password'],['nombre','Nombre completo','text'],['celular','Celular','tel'],['email','Correo electrónico','email']] as [$n,$l,$t]): ?>
    <div <?= in_array($n, ['nombre','email']) ? 'style="grid-column:1/-1"' : '' ?>>
        <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px"><?= $l ?></label>
        <input type="<?= $t ?>" name="<?= $n ?>" <?= $n === 'email' ? '' : 'required' ?> style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
    </div>
    <?php endforeach; ?>
    <div>
        <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Puesto</label>
        <select name="puesto" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
            <option value="promo">Promotor</option>
            <option value="collector">Cobrador</option>
            <option value="desembolso">Desembolso</option>
        </select>
    </div>
    <div>
        <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Rango</label>
        <select name="rango" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
            <option>Bronce</option><option>Plata</option><option>Oro</option><option>Platino</option><option>Diamante</option>
        </select>
    </div>
    <div style="grid-column:1/-1">
        <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Capacidad máxima ($)</label>
        <input type="number" name="capacidad" value="0" step="1000" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:13px;outline:none">
    </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="document.getElementById('modalEmpleado').classList.remove('open')">Cancelar</button>
        <button type="submit" class="btn-primary">Crear empleado</button>
    </div>
    </form>
</div></div>

<!-- Modal: editar empleado -->
<div class="modal-overlay" id="modalEditarEmp" onclick="if(event.target===this)this.classList.remove('open')">
<div class="modal" style="width:480px">
    <div class="modal-header">
        <h3>Editar empleado</h3>
        <button class="modal-close" onclick="document.getElementById('modalEditarEmp').classList.remove('open')">×</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/empleados/editar" onsubmit="this.querySelector('[type=submit]').disabled=true">
    <input type="hidden" name="id" id="empEditId">
    <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Usuario (login)</label>
            <input type="text" name="usuario" id="empEditUsuario" required
                   autocomplete="off" spellcheck="false"
                   style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:13px;outline:none">
        </div>
        <div style="display:flex;align-items:flex-end">
            <p style="font-size:11px;color:var(--text-muted);margin:0 0 10px 0;line-height:1.5">
                Cambia solo si necesitas renombrar la cuenta de acceso. El password no cambia aquí.
            </p>
        </div>
        <div style="grid-column:1/-1">
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Nombre completo</label>
            <input type="text" name="nombre" id="empEditNombre" required style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
        </div>
        <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Celular</label>
            <input type="tel" name="celular" id="empEditCelular" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
        </div>
        <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Correo electrónico</label>
            <input type="email" name="email" id="empEditEmail" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
        </div>
        <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Puesto</label>
            <select name="puesto" id="empEditPuesto" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
                <option value="promo">Promotor</option>
                <option value="collector">Cobrador</option>
                <option value="desembolso">Desembolso</option>
            </select>
        </div>
        <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Rango</label>
            <select name="rango" id="empEditRango" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
                <option>Bronce</option><option>Plata</option><option>Oro</option><option>Platino</option><option>Diamante</option>
            </select>
        </div>
        <div style="grid-column:1/-1">
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Capacidad máxima ($)</label>
            <input type="number" name="capacidad" id="empEditCapacidad" step="1000" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:13px;outline:none">
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="document.getElementById('modalEditarEmp').classList.remove('open')">Cancelar</button>
        <button type="submit" class="btn-primary">Guardar cambios</button>
    </div>
    </form>
</div></div>

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
