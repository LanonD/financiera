<?php
// Variables: $prestamos, $cobradores
$hoy = date('Y-m-d');

// Separar por urgencia
$atrasados  = array_filter($prestamos, fn($p) => $p['estatus'] === 'Atrasado' || (int)($p['dias_atraso'] ?? 0) > 0);
$cobrarHoy  = array_filter($prestamos, fn($p) => $p['proximo_pago'] === $hoy && (int)($p['dias_atraso'] ?? 0) <= 0);
$futuros    = array_filter($prestamos, fn($p) => $p['proximo_pago'] > $hoy || $p['proximo_pago'] === null);

$sinCobrador = array_filter($prestamos, fn($p) => empty($p['cobrador_id']));
?>

<style>
.asign-section{margin-bottom:24px}
.asign-section-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;padding:8px 0 10px;display:flex;align-items:center;gap:8px}
.asign-section-title .dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.cobrador-select{padding:6px 10px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:12px;outline:none;cursor:pointer;width:100%;max-width:180px;color:var(--text-primary)}
.cobrador-select.changed{border-color:var(--accent);background:var(--accent-light)}
.tag-hoy{display:inline-flex;align-items:center;padding:1px 7px;border-radius:8px;font-size:10px;font-weight:700;background:#fef9c3;color:#854d0e;margin-left:6px}
.tag-atrasado{background:#fee2e2;color:#991b1b}
.save-bar{position:sticky;bottom:0;background:var(--bg-card);border-top:1px solid var(--border);padding:12px 20px;display:flex;align-items:center;justify-content:space-between;z-index:10;margin:0 -24px;padding-left:24px;padding-right:24px}
</style>

<div class="content-header">
    <div>
        <h2>Asignar cobradores</h2>
        <p>Asigna qué cobrador atiende cada préstamo activo</p>
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

<?php if (empty($prestamos)): ?>
<div class="table-card" style="text-align:center;padding:50px;color:var(--text-muted)">
    No hay préstamos activos o atrasados para asignar.
</div>
<?php else: ?>

<form method="POST" action="<?= APP_URL ?>/cobros/asignar" id="formAsignar">

<?php
// Helper: render a section of loans
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
                $dias   = (int)($p['dias_atraso'] ?? 0);
                $esHoy  = $p['proximo_pago'] === $hoy;
                $rowBg  = $dias > 0 ? 'background:#fff5f5' : ($esHoy ? 'background:#fffbeb' : '');
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
    const original = sel.dataset.original;
    const actual   = sel.value;
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
    btn.disabled    = cambios === 0;
    btn.style.opacity = cambios > 0 ? '1' : '.5';
    info.textContent = cambios > 0
        ? cambios + ' cambio(s) sin guardar'
        : 'Sin cambios pendientes';
}

function resetTodo() {
    document.querySelectorAll('.cobrador-select.changed').forEach(sel => {
        sel.value = sel.dataset.original;
        sel.classList.remove('changed');
    });
    cambios = 0;
    actualizarBarra();
}

// Asignación rápida: asignar mismo cobrador a toda una sección
document.querySelectorAll('.cobrador-select').forEach(sel => {
    sel.dataset.original = sel.value;
});
</script>
