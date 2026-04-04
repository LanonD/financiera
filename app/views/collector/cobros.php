<?php $pageTitle = 'Mis cobros'; $breadcrumb = 'Panel de cobrador'; ?>
<div class="content-header"><div><h2>Mis cobros</h2><p>Vista de cobrador — conectando datos reales</p></div></div>
<div class="table-card" style="padding:24px;text-align:center;color:#6b7280">
    <p>Vista de cobrador lista para conectar con la BD.</p>
    <p style="margin-top:8px;font-size:12px;font-family:monospace">Cobrador: <?= htmlspecialchars($cobrador['nombre'] ?? '—') ?></p>
</div>

