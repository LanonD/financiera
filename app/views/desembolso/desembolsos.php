<?php // Variables: $prestamos_pendientes ?>
<div class="content-header">
    <div><h2>Desembolsos pendientes</h2><p>Entrega efectivo y recopila documentos del cliente</p></div>
</div>

<?php if (empty($prestamos_pendientes)): ?>
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:60px 24px;text-align:center;color:var(--text-muted)">
    <div style="font-size:15px;font-weight:500;color:var(--text-secondary)">Sin desembolsos pendientes</div>
    <div style="font-size:13px;margin-top:6px">No hay préstamos asignados para entregar hoy</div>
</div>
<?php else: ?>
<?php foreach ($prestamos_pendientes as $p): ?>
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:16px" id="card-<?= $p['id'] ?>">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div style="display:flex;align-items:center;gap:12px">
            <span class="initials"><?= strtoupper(substr($p['cliente_nombre'],0,2)) ?></span>
            <div>
                <div style="font-size:15px;font-weight:600"><?= htmlspecialchars($p['cliente_nombre']) ?></div>
                <div style="font-size:12px;color:var(--text-muted);font-family:var(--font-mono)"><?= htmlspecialchars($p['celular']??'') ?></div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:12px;color:var(--text-muted);font-family:var(--font-mono)">Préstamo #<?= $p['id'] ?></span>
            <span id="badge-<?= $p['id'] ?>" style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;background:#fef9c3;color:#854d0e;font-size:12px;font-weight:600">Pendiente entrega</span>
        </div>
    </div>

    <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <!-- Monto e info -->
        <div>
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:4px">Monto a entregar</div>
            <div style="font-size:28px;font-weight:600;font-family:var(--font-mono);letter-spacing:-.03em;margin-bottom:12px">$<?= number_format($p['monto'],2,'.',',') ?></div>
            <div style="display:flex;flex-direction:column;gap:8px">
                <?php foreach([['Dirección',$p['cliente_direccion']??'—'],['Promotor',$p['promotor_nombre']??'—'],['Fecha',$p['fecha_inicio']??date('Y-m-d')]] as [$l,$v]): ?>
                <div style="display:flex;gap:8px">
                    <span style="font-size:11px;font-weight:600;color:var(--text-muted);width:80px;flex-shrink:0"><?= $l ?></span>
                    <span style="font-size:13px;font-family:var(--font-mono)"><?= htmlspecialchars((string)$v) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Documentos -->
        <div>
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:12px">Documentos a recopilar</div>
            <div style="display:flex;flex-direction:column;gap:8px" id="docs-<?= $p['id'] ?>">
                <?php foreach([['pagare','Pagaré firmado',true],['ine','Foto de INE',true],['comprobante','Comprobante domicilio',true],['vivienda','Foto de vivienda',false]] as [$key,$label,$req]): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;background:var(--bg-hover);border-radius:var(--radius-sm);border:1px solid var(--border)" id="docItem-<?= $p['id'] ?>-<?= $key ?>">
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:8px;height:8px;border-radius:50%;background:var(--text-muted)" id="docDot-<?= $p['id'] ?>-<?= $key ?>"></div>
                        <span style="font-size:12px;font-weight:500"><?= $label ?><?= !$req ? ' <span style="font-size:10px;color:var(--text-muted)">(opcional)</span>' : '' ?></span>
                    </div>
                    <label style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border:1px solid var(--border-input);border-radius:var(--radius-sm);font-size:11px;font-weight:500;cursor:pointer;color:var(--text-secondary);transition:all .15s" onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'" onmouseout="this.style.borderColor='var(--border-input)';this.style.color='var(--text-secondary)'">
                        📎 Subir
                        <input type="file" style="display:none" accept=".pdf,.jpg,.jpeg,.png" <?= $key==='vivienda'?'capture="environment"':'' ?> onchange="onDocUpload(this,'<?= $p['id'] ?>','<?= $key ?>')">
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Confirmación -->
        <div style="grid-column:1/-1;border-top:1px solid var(--border);padding-top:16px;margin-top:4px">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:12px">Confirmar entrega</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px">
                <?php foreach([["monto-{$p['id']}",'Monto entregado','number',$p['monto']],["forma-{$p['id']}",'Forma de entrega','select',null],["hora-{$p['id']}",'Hora de entrega','time',date('H:i')]] as [$fid,$flabel,$ftype,$fval]): ?>
                <div>
                    <label style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);display:block;margin-bottom:5px"><?= $flabel ?></label>
                    <?php if($ftype==='select'): ?>
                    <select id="<?= $fid ?>" style="width:100%;padding:8px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;outline:none">
                        <option value="efectivo">Efectivo</option><option value="transferencia">Transferencia</option>
                    </select>
                    <?php else: ?>
                    <input type="<?= $ftype ?>" id="<?= $fid ?>" value="<?= $fval ?>" step="<?= $ftype==='number'?'0.01':'any' ?>"
                        style="width:100%;padding:8px 11px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:13px;outline:none">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <textarea id="nota-<?= $p['id'] ?>" rows="2" placeholder="Observaciones de la visita…"
                style="width:100%;padding:8px 12px;background:var(--bg-input);border:1px solid var(--border-input);border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;resize:none;outline:none;margin-bottom:12px"></textarea>
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                <div style="font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:6px">
                    ⚠️ Asegúrate de tener el pagaré firmado antes de confirmar
                </div>
                <button onclick="confirmarDesembolso(<?= $p['id'] ?>, <?= $p['monto'] ?>)"
                    style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#16a34a;color:white;border:none;border-radius:var(--radius-sm);font-family:var(--font);font-size:13px;font-weight:600;cursor:pointer"
                    id="btn-<?= $p['id'] ?>">
                    ✓ Confirmar entrega
                </button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<script>
const docsState = {};
function onDocUpload(input, prestamoId, docKey) {
    if (!input.files[0]) return;
    if (!docsState[prestamoId]) docsState[prestamoId] = {};
    docsState[prestamoId][docKey] = true;
    const dot  = document.getElementById('docDot-'+prestamoId+'-'+docKey);
    const item = document.getElementById('docItem-'+prestamoId+'-'+docKey);
    if (dot)  { dot.style.background='#16a34a'; }
    if (item) { item.style.borderColor='#bbf7d0'; item.style.background='#f0fdf4'; }
    const label = input.parentElement;
    label.textContent = '✅ ' + input.files[0].name.substring(0,18);
}

function confirmarDesembolso(id, montoOriginal) {
    if (!docsState[id] || !docsState[id]['pagare']) {
        alert('⚠️ El pagaré firmado es obligatorio antes de confirmar.');
        return;
    }
    const monto = parseFloat(document.getElementById('monto-'+id).value);
    const forma = document.getElementById('forma-'+id).value;
    const hora  = document.getElementById('hora-'+id).value;
    const nota  = document.getElementById('nota-'+id).value;
    if (!monto||monto<=0) { alert('Ingresa el monto entregado.'); return; }

    fetch('<?= APP_URL ?>/desembolsos/confirmar', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ prestamo_id:id, monto, forma, hora, nota, docs:docsState[id]||{} })
    }).then(r=>r.json()).then(d => {
        if (d.ok) {
            document.getElementById('badge-'+id).textContent = '✓ Entregado';
            document.getElementById('badge-'+id).style.cssText = 'display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;background:#dcfce7;color:#166534;font-size:12px;font-weight:600';
            document.getElementById('btn-'+id).disabled = true;
            document.getElementById('btn-'+id).style.opacity = '.5';
        } else { alert('Error: '+(d.error||'intenta de nuevo')); }
    }).catch(()=>alert('Error de conexión'));
}
</script>
