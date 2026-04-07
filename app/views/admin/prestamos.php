<?php
// Variables disponibles: $prestamos, $pageTitle, $breadcrumb
$puesto = $_SESSION['puesto'] ?? 'admin';
?>
<div class="content-header">
    <div>
        <h2><?= $puesto === 'promo' ? 'Mis préstamos' : 'Todos los préstamos' ?></h2>
        <p><?= $puesto === 'promo' ? 'Cartera personal asignada' : 'Gestión completa de créditos' ?></p>
    </div>
    <?php if ($puesto === 'promo'): ?>
    <button class="btn-primary" onclick="document.getElementById('modalNuevoPrestamo').classList.add('open')">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:13px;height:13px"><path d="M7 2v10M2 7h10"/></svg>
        Nuevo préstamo
    </button>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="filter-panel">
    <div class="filter-group">
        <label>Buscar</label>
        <input class="filter-input" type="text" id="globalSearch" placeholder="Nombre o ID…" oninput="filterTable()">
    </div>
    <div class="filter-divider"></div>
    <div class="filter-group">
        <label>Estatus</label>
        <div class="status-group">
            <span class="status-pill pill-activo"    data-status="Activo"    onclick="togglePill(this)"><span class="dot"></span> Activo</span>
            <span class="status-pill pill-pendiente" data-status="Pendiente" onclick="togglePill(this)"><span class="dot"></span> Pendiente</span>
            <span class="status-pill pill-atrasado"  data-status="Atrasado"  onclick="togglePill(this)"><span class="dot"></span> Atrasado</span>
            <span class="status-pill" data-status="Retirado" onclick="togglePill(this)" style="background:#f1f5f9;color:#64748b;border-color:#cbd5e1"><span class="dot" style="background:#94a3b8"></span> Retirado</span>
        </div>
    </div>
    <div class="filter-actions">
        <button class="btn-secondary" onclick="resetFilters()">Limpiar</button>
    </div>
</div>

<div class="table-card">
    <div class="table-header">
        <div>
            <div class="table-title">Préstamos</div>
            <div class="table-count" id="tableCount"><?= count($prestamos) ?> registros</div>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Cliente</th><th>Monto</th><th>Cuota</th>
                <th>Esquema</th><th>Saldo actual</th><th>Fecha inicio</th>
                <th>Estatus</th><th>Acción</th>
            </tr>
        </thead>
        <tbody id="tableBody">
        <?php if (empty($prestamos)): ?>
        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted)">No hay préstamos registrados</td></tr>
        <?php else: ?>
        <?php foreach ($prestamos as $row):
            $badge = match($row['estatus'] ?? '') {
                'Activo'    => 'badge-activo',
                'Atrasado'  => 'badge-atrasado',
                'Retirado'  => 'badge-retirado',
                default     => 'badge-pendiente'
            };
            $nombre = $row['cliente_nombre'] ?? $row['nombre'] ?? '—';
        ?>
        <tr data-status="<?= $row['estatus'] ?>">
            <td class="td-id">#<?= $row['id'] ?></td>
            <td class="td-name">
                <span class="initials"><?= strtoupper(substr($nombre, 0, 2)) ?></span>
                <?= htmlspecialchars($nombre) ?>
            </td>
            <td class="td-amount">$<?= number_format($row['monto'], 2, '.', ',') ?></td>
            <td class="td-amount">$<?= number_format($row['cuota'], 2, '.', ',') ?></td>
            <td class="td-numeric"><?= $row['frecuencia'] ?></td>
            <td class="td-amount">$<?= number_format($row['saldo_actual'], 2, '.', ',') ?></td>
            <td class="td-numeric"><?= $row['fecha_inicio'] ?? '—' ?></td>
            <td><span class="badge <?= $badge ?>"><span class="dot"></span><?= $row['estatus'] ?></span></td>
            <td><a class="action-btn edit" href="<?= APP_URL ?>/prestamos/detalle?id=<?= $row['id'] ?>">Ver</a></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($puesto === 'promo'): ?>
<!-- Modal nuevo préstamo -->
<div class="modal-overlay" id="modalNuevoPrestamo" onclick="if(event.target===this)this.classList.remove('open')">
<div class="modal" style="width:520px">
    <div class="modal-header">
        <h3>Registrar nuevo préstamo</h3>
        <button class="modal-close" onclick="document.getElementById('modalNuevoPrestamo').classList.remove('open')">×</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/prestamos/crear" onsubmit="this.querySelector('[type=submit]').disabled=true;this.querySelector('[type=submit]').textContent='Registrando…'">
    <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div style="grid-column:1/-1">
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Cliente</label>
            <select name="cliente_id" required style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
                <option value="">Seleccionar cliente…</option>
                <?php foreach ($clientes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php foreach([['monto','Monto ($)','number','50000'],['tasa_diaria','Tasa diaria (%)','number','1'],['num_pagos','Número de pagos','number','24']] as [$n,$l,$t,$p]): ?>
        <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px"><?= $l ?></label>
            <input type="<?= $t ?>" name="<?= $n ?>" placeholder="<?= $p ?>" required step="any" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:13px;outline:none">
        </div>
        <?php endforeach; ?>
        <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Frecuencia</label>
            <select name="frecuencia" style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
                <option>Mensual</option><option>Quincenal</option><option>Semanal</option><option>Diario</option>
            </select>
        </div>
        <div>
            <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px">Fecha inicio</label>
            <input type="date" name="fecha_inicio" value="<?= date('Y-m-d') ?>" required style="width:100%;padding:9px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="document.getElementById('modalNuevoPrestamo').classList.remove('open')">Cancelar</button>
        <button type="submit" class="btn-primary">Registrar préstamo</button>
    </div>
    </form>
</div>
</div>
<?php endif; ?>

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
<script>
let activeFilters = new Set(['Activo','Pendiente','Atrasado','Retirado']);
function togglePill(el){const s=el.dataset.status;activeFilters.has(s)?(activeFilters.delete(s),el.classList.add('inactive')):(activeFilters.add(s),el.classList.remove('inactive'));filterTable();}
function filterTable(){const q=document.getElementById('globalSearch').value.trim().toLowerCase();let v=0;document.querySelectorAll('#tableBody tr[data-status]').forEach(r=>{const show=activeFilters.has(r.dataset.status)&&(!q||r.textContent.toLowerCase().includes(q));r.style.display=show?'':'none';if(show)v++;});document.getElementById('tableCount').textContent=v+' registros';}
function resetFilters(){document.getElementById('globalSearch').value='';activeFilters=new Set(['Activo','Pendiente','Atrasado','Retirado']);document.querySelectorAll('.status-pill').forEach(p=>p.classList.remove('inactive'));filterTable();}
window.addEventListener('load',filterTable);
</script>
