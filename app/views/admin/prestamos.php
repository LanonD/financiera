<?php
// Variables disponibles: $prestamos, $filtros, $pageTitle, $breadcrumb
$puesto = $_SESSION['puesto'] ?? 'admin';
$f = $filtros ?? [];
$hayFiltros = !empty($f['frecuencia']) || $f['monto_min'] > 0 || $f['monto_max'] > 0
           || !empty($f['desde']) || !empty($f['hasta']);

$frecuencias = ['Diario','Semanal','Quincenal','Mensual'];

// Construir listas para selects JS (promotor / cobrador)
$listaPromotores = $listaCobradoresP = [];
foreach ($prestamos as $r) {
    $pn = $r['promotor_nombre'] ?? '';
    $cn = $r['cobrador_nombre'] ?? '';
    if ($pn && !in_array($pn, $listaPromotores))  $listaPromotores[]  = $pn;
    if ($cn && !in_array($cn, $listaCobradoresP)) $listaCobradoresP[] = $cn;
}
sort($listaPromotores);
sort($listaCobradoresP);
?>
<div class="content-header">
    <div>
        <h2><?= $puesto === 'promo' ? 'Mis préstamos' : 'Todos los préstamos' ?></h2>
        <p><?= $puesto === 'promo' ? 'Cartera personal asignada' : 'Gestión completa de créditos' ?></p>
    </div>
    <?php if (in_array($puesto, ['admin', 'promo'])): ?>
    <a href="<?= APP_URL ?>/prestamos/nuevo" class="btn-primary" style="text-decoration:none">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:13px;height:13px"><path d="M7 2v10M2 7h10"/></svg>
        Nuevo préstamo
    </a>
    <?php endif; ?>
</div>

<!-- Filtros servidor (GET) -->
<form method="GET" action="<?= APP_URL ?>/prestamos" id="frmFiltros">
<div class="filter-panel" style="flex-direction:column;align-items:stretch;gap:12px">

    <!-- Fila 1: Frecuencia -->
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span style="font-size:12px;font-weight:600;color:var(--text-muted);min-width:90px">Frecuencia</span>
        <div class="status-group">
            <span class="status-pill <?= empty($f['frecuencia']) ? 'pill-activo' : '' ?>"
                  style="cursor:pointer" onclick="setFrecuencia('')">Todas</span>
            <?php foreach ($frecuencias as $fr): ?>
            <span class="status-pill <?= ($f['frecuencia'] === $fr) ? 'pill-activo' : '' ?>"
                  style="cursor:pointer" onclick="setFrecuencia('<?= $fr ?>')"><?= $fr ?></span>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="frecuencia" id="hFrecuencia" value="<?= htmlspecialchars($f['frecuencia']) ?>">
    </div>

    <!-- Fila 2: Monto + Fecha a cobrar + Buscar -->
    <div style="display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap">
        <div class="filter-group" style="margin:0">
            <label>Monto prestado</label>
            <div style="display:flex;gap:6px;align-items:center">
                <input class="filter-input" type="number" name="monto_min" min="0" step="100"
                       placeholder="Desde $" value="<?= $f['monto_min'] > 0 ? $f['monto_min'] : '' ?>"
                       style="width:110px">
                <span style="color:var(--text-muted);font-size:13px">–</span>
                <input class="filter-input" type="number" name="monto_max" min="0" step="100"
                       placeholder="Hasta $" value="<?= $f['monto_max'] > 0 ? $f['monto_max'] : '' ?>"
                       style="width:110px">
            </div>
        </div>

        <div class="filter-divider"></div>

        <div class="filter-group" style="margin:0">
            <label>Fecha a cobrar</label>
            <div style="display:flex;gap:6px;align-items:center">
                <input class="filter-input" type="date" name="desde"
                       value="<?= htmlspecialchars($f['desde']) ?>" style="width:140px">
                <span style="color:var(--text-muted);font-size:13px">–</span>
                <input class="filter-input" type="date" name="hasta"
                       value="<?= htmlspecialchars($f['hasta']) ?>" style="width:140px">
            </div>
        </div>

        <div class="filter-divider"></div>

        <div style="display:flex;gap:8px">
            <button type="submit" class="btn-primary" style="height:36px;padding:0 16px;font-size:13px">Filtrar</button>
            <?php if ($hayFiltros): ?>
            <a href="<?= APP_URL ?>/prestamos" class="btn-secondary"
               style="height:36px;padding:0 14px;font-size:13px;text-decoration:none;display:flex;align-items:center">
               Limpiar
            </a>
            <?php endif; ?>
        </div>
    </div>

</div>
</form>

<!-- Filtros cliente (JS) -->
<div class="filter-panel" style="margin-top:0;border-top:none;padding-top:10px;flex-wrap:wrap;gap:10px">
    <div class="filter-group">
        <label>Buscar</label>
        <input class="filter-input" type="text" id="globalSearch"
               placeholder="Nombre, ID, promotor…" oninput="filterTable()" style="min-width:200px">
    </div>
    <div class="filter-divider"></div>
    <div class="filter-group">
        <label>Estatus</label>
        <div class="status-group">
            <span class="status-pill pill-activo"     data-status="Activo"    onclick="togglePill(this)"><span class="dot"></span> Activo</span>
            <span class="status-pill pill-pendiente"  data-status="Pendiente" onclick="togglePill(this)"><span class="dot"></span> Pendiente</span>
            <span class="status-pill pill-atrasado"   data-status="Atrasado"  onclick="togglePill(this)"><span class="dot"></span> Atrasado</span>
            <span class="status-pill pill-finalizado" data-status="Finalizado" onclick="togglePill(this)"><span class="dot"></span> Finalizado</span>
            <span class="status-pill" data-status="Retirado" onclick="togglePill(this)"
                  style="background:#f1f5f9;color:#64748b;border-color:#cbd5e1"><span class="dot" style="background:#94a3b8"></span> Retirado</span>
        </div>
    </div>
    <?php if ($puesto === 'admin' && !empty($listaPromotores)): ?>
    <div class="filter-divider"></div>
    <div class="filter-group">
        <label>Promotor</label>
        <select class="filter-input" id="jsPromotor" onchange="filterTable()" style="min-width:140px">
            <option value="">Todos</option>
            <?php foreach ($listaPromotores as $pn): ?>
            <option value="<?= htmlspecialchars($pn) ?>"><?= htmlspecialchars($pn) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <?php if (!empty($listaCobradoresP)): ?>
    <div class="filter-divider"></div>
    <div class="filter-group">
        <label>Cobrador</label>
        <select class="filter-input" id="jsCobrador" onchange="filterTable()" style="min-width:140px">
            <option value="">Todos</option>
            <?php foreach ($listaCobradoresP as $cn): ?>
            <option value="<?= htmlspecialchars($cn) ?>"><?= htmlspecialchars($cn) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
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
        <?php if ($hayFiltros): ?>
        <div style="font-size:12px;color:#f59e0b;font-weight:500">
            ⚠ Filtros activos — <a href="<?= APP_URL ?>/prestamos" style="color:#f59e0b">ver todos</a>
        </div>
        <?php endif; ?>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Cliente</th><th>Monto</th><th>Cuota</th>
                <th>Frecuencia</th><th>Próximo cobro</th><th>Saldo pendiente</th>
                <th>Estatus</th><th>Acción</th>
            </tr>
        </thead>
        <tbody id="tableBody">
        <?php if (empty($prestamos)): ?>
        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted)">No hay préstamos con los filtros seleccionados</td></tr>
        <?php else: ?>
        <?php foreach ($prestamos as $row):
            $badge = match($row['estatus'] ?? '') {
                'Activo'     => 'badge-activo',
                'Atrasado'   => 'badge-atrasado',
                'Finalizado' => 'badge-finalizado',
                'Retirado'   => 'badge-retirado',
                default      => 'badge-pendiente'
            };
            $nombre        = $row['cliente_nombre'] ?? $row['nombre'] ?? '—';
            $saldoTotal    = ($row['saldo_actual'] ?? 0) + ($row['interes_acumulado'] ?? 0);
            $proximoCobro  = $row['proximo_pago'] ?? null;
            $hoy           = date('Y-m-d');
            $cobrovencido  = $proximoCobro && $proximoCobro < $hoy;
            $promotorNom   = $row['promotor_nombre'] ?? '';
            $cobradorNom   = $row['cobrador_nombre'] ?? '';
            $busqueda      = strtolower($nombre . ' ' . $row['id'] . ' ' . $promotorNom . ' ' . $cobradorNom);
        ?>
        <tr data-status="<?= $row['estatus'] ?>"
            data-promotor="<?= htmlspecialchars($promotorNom) ?>"
            data-cobrador="<?= htmlspecialchars($cobradorNom) ?>"
            data-busqueda="<?= htmlspecialchars($busqueda) ?>">
            <td class="td-id">#<?= $row['id'] ?></td>
            <td class="td-name">
                <span class="initials"><?= strtoupper(substr($nombre, 0, 2)) ?></span>
                <?= htmlspecialchars($nombre) ?>
            </td>
            <td class="td-amount">$<?= number_format($row['monto'], 0, '.', ',') ?></td>
            <td class="td-amount">$<?= number_format($row['cuota'], 0, '.', ',') ?></td>
            <td class="td-numeric"><?= $row['frecuencia'] ?></td>
            <td class="td-numeric" style="<?= $cobrovencido ? 'color:#ef4444;font-weight:600' : '' ?>">
                <?= $proximoCobro ? date('d/m/Y', strtotime($proximoCobro)) : '—' ?>
                <?= $cobrovencido ? ' <span style="font-size:10px;background:#fee2e2;color:#dc2626;padding:1px 5px;border-radius:4px">Vencido</span>' : '' ?>
            </td>
            <td class="td-amount">$<?= number_format($saldoTotal, 0, '.', ',') ?></td>
            <td><span class="badge <?= $badge ?>"><span class="dot"></span><?= $row['estatus'] ?></span></td>
            <td><a class="action-btn edit" href="<?= APP_URL ?>/prestamos/detalle?id=<?= $row['id'] ?>">Ver</a></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
let activeFilters = new Set(['Activo','Pendiente','Atrasado','Retirado','Finalizado']);

function setFrecuencia(val) {
    document.getElementById('hFrecuencia').value = val;
    document.getElementById('frmFiltros').submit();
}

function togglePill(el) {
    const s = el.dataset.status;
    activeFilters.has(s) ? (activeFilters.delete(s), el.classList.add('inactive'))
                         : (activeFilters.add(s),    el.classList.remove('inactive'));
    filterTable();
}

function filterTable() {
    const q        = document.getElementById('globalSearch').value.trim().toLowerCase();
    const promotor = (document.getElementById('jsPromotor')?.value || '').toLowerCase();
    const cobrador = (document.getElementById('jsCobrador')?.value || '').toLowerCase();
    let v = 0;
    document.querySelectorAll('#tableBody tr[data-status]').forEach(r => {
        const matchStatus   = activeFilters.has(r.dataset.status);
        const matchQ        = !q        || (r.dataset.busqueda || '').includes(q);
        const matchPromotor = !promotor || (r.dataset.promotor || '').toLowerCase() === promotor;
        const matchCobrador = !cobrador || (r.dataset.cobrador || '').toLowerCase() === cobrador;
        const show = matchStatus && matchQ && matchPromotor && matchCobrador;
        r.style.display = show ? '' : 'none';
        if (show) v++;
    });
    document.getElementById('tableCount').textContent = v + ' registros';
}

function resetFilters() {
    document.getElementById('globalSearch').value = '';
    const jp = document.getElementById('jsPromotor');
    const jc = document.getElementById('jsCobrador');
    if (jp) jp.value = '';
    if (jc) jc.value = '';
    activeFilters = new Set(['Activo','Pendiente','Atrasado','Retirado','Finalizado']);
    document.querySelectorAll('.status-pill[data-status]').forEach(p => p.classList.remove('inactive'));
    filterTable();
}

window.addEventListener('load', filterTable);
</script>
