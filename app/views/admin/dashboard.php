<?php
// Variables ya disponibles: $kpis, $prestamos
?>
<div class="content-header">
    <div>
        <h2>Cartera de préstamos</h2>
        <p>Gestión y seguimiento de todos los créditos activos</p>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Cartera total</div>
        <div class="kpi-value">$<?= number_format($kpis['cartera_total'] ?? 0, 0, '.', ',') ?></div>
        <span class="kpi-trend flat"><?= $kpis['total'] ?? 0 ?> préstamos</span>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">Activos</div>
        <div class="kpi-value"><?= $kpis['activos'] ?? 0 ?></div>
        <span class="kpi-trend up">En curso</span>
    </div>
    <div class="kpi-card yellow">
        <div class="kpi-label">Pendientes</div>
        <div class="kpi-value"><?= $kpis['pendientes'] ?? 0 ?></div>
        <span class="kpi-trend flat">Por aprobar</span>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">Atrasados</div>
        <div class="kpi-value"><?= $kpis['atrasados'] ?? 0 ?></div>
        <span class="kpi-trend down">Requieren atención</span>
    </div>
</div>

<div class="filter-panel">
    <div class="filter-group">
        <label>Préstamo ID</label>
        <input class="filter-input" type="text" id="filterId" placeholder="ej. 1042" oninput="filterTable()">
    </div>
    <div class="filter-divider"></div>
    <div class="filter-group">
        <label>Estatus</label>
        <div class="status-group">
            <span class="status-pill pill-activo"    data-status="Activo"    onclick="togglePill(this)"><span class="dot"></span> Activo</span>
            <span class="status-pill pill-pendiente" data-status="Pendiente" onclick="togglePill(this)"><span class="dot"></span> Pendiente</span>
            <span class="status-pill pill-atrasado"  data-status="Atrasado"  onclick="togglePill(this)"><span class="dot"></span> Atrasado</span>
        </div>
    </div>
    <div class="filter-actions">
        <button class="btn-secondary" onclick="resetFilters()">Limpiar</button>
    </div>
</div>

<div class="table-card">
    <div class="table-header">
        <div class="table-title">Todos los préstamos</div>
        <div class="table-count" id="tableCount"></div>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Nombre</th><th>Monto</th><th>Pagos</th>
                <th>Cuota</th><th>Esquema</th><th>Interés</th>
                <th>Saldo actual</th><th>Estatus</th><th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tableBody">
        <?php foreach ($prestamos as $row):
            $badge = match($row['estatus']) {
                'Activo'    => 'badge-activo',
                'Pendiente' => 'badge-pendiente',
                'Atrasado'  => 'badge-atrasado',
                default     => 'badge-pendiente'
            };
        ?>
        <tr data-status="<?= $row['estatus'] ?>">
            <td class="td-id">#<?= $row['id'] ?></td>
            <td class="td-name">
                <span class="initials"><?= strtoupper(substr($row['cliente_nombre'], 0, 2)) ?></span>
                <?= htmlspecialchars($row['cliente_nombre']) ?>
            </td>
            <td class="td-amount">$<?= number_format($row['monto'], 2, '.', ',') ?></td>
            <td class="td-numeric"><?= $row['num_pagos'] ?></td>
            <td class="td-amount">$<?= number_format($row['cuota'], 2, '.', ',') ?></td>
            <td class="td-numeric"><?= $row['frecuencia'] ?></td>
            <td class="td-numeric"><?= $row['tasa_diaria'] ?>%</td>
            <td class="td-amount">$<?= number_format($row['saldo_actual'], 2, '.', ',') ?></td>
            <td><span class="badge <?= $badge ?>"><span class="dot"></span><?= $row['estatus'] ?></span></td>
            <td>
                <a class="action-btn edit" href="<?= APP_URL ?>/prestamos/detalle?id=<?= $row['id'] ?>">Ver</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="table-footer">
        <span class="pagination-info" id="tableFooter"></span>
    </div>
</div>

<script>
let activeFilters = new Set(['Activo','Pendiente','Atrasado']);
function togglePill(el){const s=el.dataset.status;activeFilters.has(s)?(activeFilters.delete(s),el.classList.add('inactive')):(activeFilters.add(s),el.classList.remove('inactive'));filterTable();}
function filterTable(){const id=document.getElementById('filterId').value.trim().toLowerCase();let v=0;document.querySelectorAll('#tableBody tr').forEach(r=>{const show=activeFilters.has(r.dataset.status)&&(!id||r.cells[0].textContent.toLowerCase().includes(id));r.style.display=show?'':'none';if(show)v++;});document.getElementById('tableCount').textContent=v+' registros';}
function resetFilters(){document.getElementById('filterId').value='';activeFilters=new Set(['Activo','Pendiente','Atrasado']);document.querySelectorAll('.status-pill').forEach(p=>p.classList.remove('inactive'));filterTable();}
window.addEventListener('load',filterTable);
</script>
