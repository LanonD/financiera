<?php
// Variables: $prestamos, $cobradores, $filtroDesde, $filtroHasta, $filtroSinCobrador, $filtroBusqueda
$hoy       = date('Y-m-d');
$manana    = date('Y-m-d', strtotime('+1 day'));
$semFin    = date('Y-m-d', strtotime('+6 days'));
$quinceFin = date('Y-m-d', strtotime('+14 days'));

// Detectar si hay algún filtro activo
$filtroActivo = ($filtroDesde ?? '') !== '' || ($filtroHasta ?? '') !== ''
             || ($filtroSinCobrador ?? false) || ($filtroBusqueda ?? '') !== '';

// ── Separar por urgencia para la sección de asignación ──────────────────────
$atrasados  = array_filter($prestamos, fn($p) => $p['estatus'] === 'Atrasado' || (int)($p['dias_atraso'] ?? 0) > 0);
$cobrarHoy  = array_filter($prestamos, fn($p) => $p['proximo_pago'] === $hoy && (int)($p['dias_atraso'] ?? 0) <= 0);
$futuros    = array_filter($prestamos, fn($p) => ($p['proximo_pago'] > $hoy || $p['proximo_pago'] === null) && (int)($p['dias_atraso'] ?? 0) <= 0);
$sinCobrador = array_filter($prestamos, fn($p) => empty($p['cobrador_id']));

// ── Seguimiento: asignados con cobro de hoy / mañana / atrasados / ya cobrados hoy
$seguimiento = array_filter($prestamos, function ($p) use ($hoy, $manana) {
    if (empty($p['cobrador_id'])) return false;
    $dias      = (int)($p['dias_atraso'] ?? 0);
    $pagadoHoy = (int)($p['pagado_hoy'] ?? 0);
    return $pagadoHoy > 0
        || $p['proximo_pago'] === $hoy
        || $p['proximo_pago'] === $manana
        || $dias > 0;
});

// ── Stats para el seguimiento ────────────────────────────────────────────────
$totalSeg   = count($seguimiento);
$cobradosHoy= count(array_filter($seguimiento, fn($p) => (int)($p['pagado_hoy'] ?? 0) > 0));
$pendHoy    = count(array_filter($seguimiento, fn($p) => $p['proximo_pago'] === $hoy && !(int)($p['pagado_hoy'] ?? 0)));
$atrasadosSeg = count(array_filter($seguimiento, fn($p) => (int)($p['dias_atraso'] ?? 0) > 0 && !(int)($p['pagado_hoy'] ?? 0)));

// ── Helper para etiqueta de estado del cobro ─────────────────────────────────
function cobroStatusBadge(array $p, string $hoy, string $manana): array {
    $dias      = (int)($p['dias_atraso'] ?? 0);
    $pagadoHoy = (int)($p['pagado_hoy'] ?? 0);
    if ($pagadoHoy > 0) {
        $tipo = $p['tipo_pago_hoy'] === 'Parcial' ? 'Parcial hoy' : 'Cobrado hoy';
        return [$tipo, '#dcfce7', '#166534', '✓'];
    }
    if ($dias > 0) return ["Atrasado {$dias}d", '#fee2e2', '#991b1b', '⚠'];
    if ($p['proximo_pago'] === $hoy)    return ['Pendiente hoy', '#fef9c3', '#854d0e', '⏳'];
    if ($p['proximo_pago'] === $manana) return ['Cobrar mañana', '#eff6ff', '#1e40af', '📅'];
    return ['—', 'var(--bg-hover)', 'var(--text-muted)', ''];
}
?>

<style>
.filter-bar{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:14px 18px;margin-bottom:20px;display:flex;flex-direction:column;gap:12px}
.filter-bar-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.filter-bar-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);white-space:nowrap;min-width:46px}
.quick-btn{padding:5px 12px;border-radius:20px;border:1px solid var(--border-input);background:var(--bg-hover);font-size:12px;font-weight:500;cursor:pointer;color:var(--text-secondary);transition:all .15s;white-space:nowrap}
.quick-btn:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-light)}
.quick-btn.active{border-color:var(--accent);background:var(--accent);color:#fff}
.filter-date{padding:6px 10px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:12px;outline:none;color:var(--text-primary);cursor:pointer}
.filter-date:focus{border-color:var(--accent)}
.filter-check-label{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:500;cursor:pointer;color:var(--text-secondary);white-space:nowrap}
.filter-check-label input{accent-color:var(--accent);width:14px;height:14px;cursor:pointer}
.filter-sep{width:1px;height:20px;background:var(--border);flex-shrink:0}
.filter-result{font-size:12px;color:var(--text-muted)}
.filter-result strong{color:var(--text-primary);font-weight:700}
.asign-section{margin-bottom:24px}
.asign-section-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;padding:8px 0 10px;display:flex;align-items:center;gap:8px}
.asign-section-title .dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.cobrador-select{padding:6px 10px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:12px;outline:none;cursor:pointer;width:100%;max-width:180px;color:var(--text-primary)}
.cobrador-select.changed{border-color:var(--accent);background:var(--accent-light)}
.tag-hoy{display:inline-flex;align-items:center;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700;background:#fef9c3;color:#854d0e;margin-left:6px}
.tag-atrasado{background:#fee2e2;color:#991b1b}
.save-bar{position:sticky;bottom:0;background:var(--bg-card);border-top:1px solid var(--border);padding:12px 20px;display:flex;align-items:center;justify-content:space-between;z-index:10;margin:0 -24px;padding-left:24px;padding-right:24px}
.seg-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:18px}
.seg-stat{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 14px;text-align:center}
.seg-stat-val{font-size:20px;font-weight:700;line-height:1}
.seg-stat-lbl{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-top:3px}
.cobro-badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:10px;font-size:11px;font-weight:700;gap:4px}
.btn-desasignar{padding:4px 10px;font-size:11px;font-weight:600;border:1px solid #fca5a5;background:#fef2f2;color:#991b1b;border-radius:var(--radius-sm);cursor:pointer;white-space:nowrap}
.btn-desasignar:hover{background:#fee2e2}
.seg-section{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:24px}
.seg-header{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.seg-title{font-size:13px;font-weight:700}
.seg-empty{text-align:center;padding:32px 20px;color:var(--text-muted);font-size:13px}
</style>

<div class="content-header">
    <div>
        <h2>Asignar cobradores</h2>
        <p>Seguimiento de cobros del día y asignación de préstamos activos</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        <?php if (!empty($sinCobrador)): ?>
        <span style="background:#fee2e2;color:#991b1b;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600">
            <?= count($sinCobrador) ?> sin cobrador
        </span>
        <?php endif; ?>
        <span style="font-size:12px;color:var(--text-muted)"><?= count($prestamos) ?> préstamos activos</span>
    </div>
</div>

<?php if (isset($_GET['ok'])): ?>
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:var(--radius-sm);padding:10px 16px;margin-bottom:16px;font-size:13px;color:#166534;font-weight:500">
    <?= (int)$_GET['ok'] ?> asignación(es) guardada(s) correctamente.
</div>
<?php endif; ?>

<!-- ─── BARRA DE FILTROS ──────────────────────────────────────────────────── -->
<form method="GET" action="<?= APP_URL ?>/cobros/asignar" id="formFiltro">
<div class="filter-bar">

    <!-- Fila 1: atajos rápidos de fecha -->
    <div class="filter-bar-row">
        <span class="filter-bar-label">Rápido</span>
        <button type="button" class="quick-btn <?= (!$filtroActivo) ? 'active' : '' ?>"
                onclick="aplicarRapido('','')">Todo</button>
        <button type="button" class="quick-btn <?= ($filtroDesde==='' && $filtroHasta===$hoy && !$filtroSinCobrador) ? 'active' : '' ?>"
                onclick="aplicarRapido('','<?= $hoy ?>')">Hoy + atrasados</button>
        <button type="button" class="quick-btn <?= ($filtroDesde===$hoy && $filtroHasta===$hoy && !$filtroSinCobrador) ? 'active' : '' ?>"
                onclick="aplicarRapido('<?= $hoy ?>','<?= $hoy ?>')">Solo hoy</button>
        <button type="button" class="quick-btn <?= ($filtroDesde===$manana && $filtroHasta===$manana) ? 'active' : '' ?>"
                onclick="aplicarRapido('<?= $manana ?>','<?= $manana ?>')">Mañana</button>
        <button type="button" class="quick-btn <?= ($filtroDesde===$hoy && $filtroHasta===$semFin) ? 'active' : '' ?>"
                onclick="aplicarRapido('<?= $hoy ?>','<?= $semFin ?>')">Esta semana</button>
        <button type="button" class="quick-btn <?= ($filtroDesde===$hoy && $filtroHasta===$quinceFin) ? 'active' : '' ?>"
                onclick="aplicarRapido('<?= $hoy ?>','<?= $quinceFin ?>')">Próximos 15 días</button>
        <span class="filter-sep"></span>
        <label class="filter-check-label">
            <input type="checkbox" name="sin_cobrador" value="1" id="chkSinCobrador"
                   <?= ($filtroSinCobrador ?? false) ? 'checked' : '' ?> onchange="document.getElementById('formFiltro').submit()">
            Solo sin cobrador
        </label>
    </div>

    <!-- Fila 2: rango manual + búsqueda -->
    <div class="filter-bar-row">
        <span class="filter-bar-label">Rango</span>
        <input type="date" class="filter-date" name="desde" id="inputDesde"
               value="<?= htmlspecialchars($filtroDesde ?? '') ?>"
               max="<?= date('Y-m-d', strtotime('+2 years')) ?>">
        <span style="font-size:12px;color:var(--text-muted)">al</span>
        <input type="date" class="filter-date" name="hasta" id="inputHasta"
               value="<?= htmlspecialchars($filtroHasta ?? '') ?>"
               max="<?= date('Y-m-d', strtotime('+2 years')) ?>">
        <span class="filter-sep"></span>
        <input type="text" class="filter-input" name="busqueda" id="inputBusqueda"
               placeholder="Buscar cliente…" value="<?= htmlspecialchars($filtroBusqueda ?? '') ?>"
               style="max-width:200px">
        <button type="submit" class="btn-primary" style="padding:6px 16px;font-size:12px">
            <svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="vertical-align:-1px;margin-right:4px"><circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/></svg>
            Aplicar
        </button>
        <?php if ($filtroActivo): ?>
        <a href="<?= APP_URL ?>/cobros/asignar" class="btn-secondary" style="font-size:12px;padding:6px 14px;text-decoration:none">Limpiar</a>
        <?php endif; ?>

        <span class="filter-sep"></span>
        <span class="filter-result">
            <?php if ($filtroActivo): ?>
                Mostrando <strong><?= count($prestamos) ?></strong> préstamo<?= count($prestamos) !== 1 ? 's' : '' ?>
                <?php if (($filtroDesde ?? '') || ($filtroHasta ?? '')): ?>
                    · <?= $filtroDesde ? date('d/m/Y', strtotime($filtroDesde)) : '…' ?>
                    <?= ($filtroHasta && $filtroHasta !== $filtroDesde) ? '→ '.date('d/m/Y', strtotime($filtroHasta)) : '' ?>
                <?php endif; ?>
            <?php else: ?>
                <strong><?= count($prestamos) ?></strong> préstamos activos en total
            <?php endif; ?>
        </span>
    </div>

</div>
</form>

<!-- ─── SEGUIMIENTO DE COBROS ──────────────────────────────────────────────── -->
<div class="seg-section">
    <div class="seg-header">
        <div class="seg-title">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="vertical-align:-2px;margin-right:5px"><circle cx="8" cy="8" r="6"/><path d="M8 5v3l2 2"/></svg>
            Seguimiento — Hoy &amp; mañana
        </div>
        <span style="font-size:12px;color:var(--text-muted)"><?= date('d/m/Y') ?> · <?= date('d/m/Y', strtotime('+1 day')) ?></span>
    </div>

    <!-- Stats -->
    <div style="padding:16px 18px 0">
        <div class="seg-stats">
            <div class="seg-stat">
                <div class="seg-stat-val"><?= $totalSeg ?></div>
                <div class="seg-stat-lbl">Asignados</div>
            </div>
            <div class="seg-stat">
                <div class="seg-stat-val" style="color:#16a34a"><?= $cobradosHoy ?></div>
                <div class="seg-stat-lbl">Cobrados hoy</div>
            </div>
            <div class="seg-stat">
                <div class="seg-stat-val" style="color:#ca8a04"><?= $pendHoy ?></div>
                <div class="seg-stat-lbl">Pendientes hoy</div>
            </div>
            <div class="seg-stat">
                <div class="seg-stat-val" style="color:#dc2626"><?= $atrasadosSeg ?></div>
                <div class="seg-stat-lbl">Atrasados</div>
            </div>
            <div class="seg-stat">
                <div class="seg-stat-val" style="color:#94a3b8"><?= count($sinCobrador) ?></div>
                <div class="seg-stat-lbl">Sin cobrador</div>
            </div>
        </div>
    </div>

    <?php if (empty($seguimiento)): ?>
    <div class="seg-empty">No hay cobros asignados para hoy o mañana.</div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Cobrador asignado</th>
                <th class="td-numeric">Fecha cobro</th>
                <th class="td-amount">Cuota</th>
                <th class="td-amount">Saldo</th>
                <th style="min-width:140px">Estado cobro</th>
                <th style="width:100px"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($seguimiento as $p):
            [$label, $bg, $clr, $ico] = cobroStatusBadge($p, $hoy, $manana);
            $dias = (int)($p['dias_atraso'] ?? 0);
            $rowBg = $dias > 0 && !(int)$p['pagado_hoy'] ? 'background:#fff5f5' : '';
        ?>
        <tr style="<?= $rowBg ?>">
            <td class="td-name">
                <span class="initials"><?= strtoupper(substr($p['cliente_nombre'],0,2)) ?></span>
                <?= htmlspecialchars($p['cliente_nombre']) ?>
                <?php if ($dias > 0 && !(int)$p['pagado_hoy']): ?>
                    <span class="tag-hoy tag-atrasado"><?= $dias ?>d</span>
                <?php elseif ($p['proximo_pago'] === $hoy && !(int)$p['pagado_hoy']): ?>
                    <span class="tag-hoy">Hoy</span>
                <?php endif; ?>
            </td>
            <td>
                <div style="display:flex;align-items:center;gap:7px">
                    <span class="initials" style="width:24px;height:24px;font-size:9px"><?= strtoupper(substr($p['cobrador_nombre'],0,2)) ?></span>
                    <span style="font-size:13px;font-weight:500"><?= htmlspecialchars($p['cobrador_nombre']) ?></span>
                </div>
            </td>
            <td class="td-numeric">
                <?php if ($p['pagado_hoy'] > 0): ?>
                    <span style="color:var(--text-muted);font-size:12px">Hoy</span>
                <?php elseif ($p['proximo_pago']): ?>
                    <?= date('d/m/Y', strtotime($p['proximo_pago'])) ?>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td class="td-amount">$<?= number_format($p['cuota'],2,'.',',') ?></td>
            <td class="td-amount">$<?= number_format($p['saldo_actual'],2,'.',',') ?></td>
            <td>
                <span class="cobro-badge" style="background:<?= $bg ?>;color:<?= $clr ?>">
                    <?= $ico ? "<span>$ico</span>" : '' ?><?= $label ?>
                </span>
            </td>
            <td style="text-align:right">
                <?php if (!(int)($p['pagado_hoy'] ?? 0)): ?>
                <form method="POST" action="<?= APP_URL ?>/cobros/asignar" style="display:inline" onsubmit="return confirm('¿Desasignar cobrador de este préstamo?')">
                    <input type="hidden" name="asignacion[<?= $p['id'] ?>]" value="0">
                    <button type="submit" class="btn-desasignar">Desasignar</button>
                </form>
                <?php else: ?>
                <span style="font-size:11px;color:var(--text-muted)">Cobrado</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($prestamos)): ?>
<div class="table-card" style="text-align:center;padding:50px;color:var(--text-muted)">
    No hay préstamos activos o atrasados para asignar.
</div>
<?php else: ?>

<!-- ─── FORMULARIO DE ASIGNACIÓN ─────────────────────────────────────────── -->
<form method="POST" action="<?= APP_URL ?>/cobros/asignar" id="formAsignar">

<?php
function renderSeccion(array $lista, string $titulo, string $dotColor, array $cobradores): void {
    if (empty($lista)) return;
    $hoy = date('Y-m-d');
    ?>
    <div class="asign-section">
        <div class="asign-section-title">
            <span class="dot" style="background:<?= $dotColor ?>"></span>
            <?= $titulo ?> <span style="color:var(--text-muted);font-weight:400">(<?= count($lista) ?>)</span>
        </div>
        <div class="table-card" style="margin-bottom:0">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th class="td-amount">Saldo</th>
                    <th class="td-amount">Cuota</th>
                    <th class="td-numeric">Próximo pago</th>
                    <th class="td-numeric">Atraso</th>
                    <th style="min-width:190px">Cobrador asignado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lista as $p):
                $dias  = (int)($p['dias_atraso'] ?? 0);
                $esHoy = $p['proximo_pago'] === $hoy;
                $rowBg = $dias > 0 ? 'background:#fff5f5' : ($esHoy ? 'background:#fffbeb' : '');
            ?>
            <tr style="<?= $rowBg ?>">
                <td class="td-name">
                    <span class="initials"><?= strtoupper(substr($p['cliente_nombre'],0,2)) ?></span>
                    <?= htmlspecialchars($p['cliente_nombre']) ?>
                    <?php if ($dias > 0): ?><span class="tag-hoy tag-atrasado"><?= $dias ?>d atraso</span>
                    <?php elseif ($esHoy): ?><span class="tag-hoy">Hoy</span><?php endif; ?>
                </td>
                <td class="td-amount">$<?= number_format($p['saldo_actual'],2,'.',',') ?></td>
                <td class="td-amount">$<?= number_format($p['cuota'],2,'.',',') ?></td>
                <td class="td-numeric"><?= $p['proximo_pago'] ? date('d/m/Y', strtotime($p['proximo_pago'])) : '—' ?></td>
                <td class="td-numeric">
                    <?php if ($dias > 0): ?>
                    <span style="color:#dc2626;font-weight:600"><?= $dias ?> día<?= $dias>1?'s':'' ?></span>
                    <?php else: ?><span style="color:var(--text-muted)">—</span><?php endif; ?>
                </td>
                <td>
                    <select name="asignacion[<?= $p['id'] ?>]"
                            class="cobrador-select"
                            data-original="<?= $p['cobrador_id'] ?? 0 ?>"
                            onchange="marcarCambio(this)">
                        <option value="0" <?= empty($p['cobrador_id']) ? 'selected' : '' ?>>— Sin asignar —</option>
                        <?php foreach ($cobradores as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $p['cobrador_id'] == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php
}

renderSeccion(array_values($atrasados), 'Atrasados',   '#dc2626', $cobradores);
renderSeccion(array_values($cobrarHoy), 'Cobrar hoy',  '#ca8a04', $cobradores);
renderSeccion(array_values($futuros),   'Próximos',    '#3b82f6', $cobradores);
?>

<!-- Barra fija de guardado -->
<div class="save-bar">
    <div style="font-size:13px;color:var(--text-muted)" id="cambiosInfo">Sin cambios pendientes</div>
    <div style="display:flex;gap:10px">
        <button type="button" class="btn-secondary" onclick="resetTodo()">Deshacer cambios</button>
        <button type="submit" class="btn-primary" id="btnGuardar" disabled style="opacity:.5">
            <svg width="13" height="13" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 7l3 3 7-7"/></svg>
            Guardar asignaciones
        </button>
    </div>
</div>

</form>
<?php endif; ?>

<script>
let cambios = 0;

function marcarCambio(sel) {
    const original    = sel.dataset.original;
    const actual      = sel.value;
    const fueDistinto = sel.classList.contains('changed');
    const esDistinto  = actual !== original;
    sel.classList.toggle('changed', esDistinto);
    if (esDistinto && !fueDistinto) cambios++;
    if (!esDistinto && fueDistinto) cambios--;
    actualizarBarra();
}

function actualizarBarra() {
    const btn  = document.getElementById('btnGuardar');
    const info = document.getElementById('cambiosInfo');
    btn.disabled      = cambios === 0;
    btn.style.opacity = cambios > 0 ? '1' : '.5';
    info.textContent  = cambios > 0 ? cambios + ' cambio(s) sin guardar' : 'Sin cambios pendientes';
}

function resetTodo() {
    document.querySelectorAll('.cobrador-select.changed').forEach(sel => {
        sel.value = sel.dataset.original;
        sel.classList.remove('changed');
    });
    cambios = 0;
    actualizarBarra();
}

document.querySelectorAll('.cobrador-select').forEach(sel => {
    sel.dataset.original = sel.value;
});

// ── Atajos de fecha rápida ───────────────────────────────────────────────────
function aplicarRapido(desde, hasta) {
    document.getElementById('inputDesde').value = desde;
    document.getElementById('inputHasta').value = hasta;
    // No resetear sin_cobrador al cambiar fecha rápida
    document.getElementById('formFiltro').submit();
}
</script>
