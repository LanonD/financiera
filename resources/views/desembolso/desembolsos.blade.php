@extends('layouts.app')

@section('title', 'Desembolsos pendientes')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
        <h2 style="font-size:20px;font-weight:700;margin-bottom:4px">Desembolsos pendientes</h2>
        <p style="color:var(--text2);font-size:13px">Préstamos aprobados esperando entrega de dinero</p>
    </div>
    <span style="background:#fef9c3;color:#854d0e;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600">
        {{ $prestamos_pendientes->count() }} pendiente(s)
    </span>
</div>

@if(session('success'))
<div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:13px;color:#166534;font-weight:500">
    {{ session('success') }}
</div>
@endif

@if($prestamos_pendientes->isEmpty())
<div class="card" style="text-align:center;padding:60px 24px;color:var(--text3)">
    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.3"><rect x="6" y="6" width="28" height="28" rx="4"/><path d="M20 14v12M14 20h12"/></svg>
    <div style="font-size:14px;font-weight:500;color:var(--text2);margin-bottom:6px">Sin préstamos pendientes de entrega</div>
    <div style="font-size:12px">Los nuevos préstamos aparecerán aquí cuando sean creados.</div>
</div>
@else

<div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>#ID</th>
                <th>Cliente</th>
                <th>Promotor</th>
                <th style="text-align:right">Monto acordado</th>
                <th style="text-align:right">Cuota</th>
                <th>Frecuencia</th>
                <th>Fecha inicio</th>
                <th>Creado</th>
                <th style="text-align:center">Acción</th>
            </tr>
        </thead>
        <tbody>
        @foreach($prestamos_pendientes as $p)
        <tr>
            <td style="font-size:12px;font-weight:600;color:var(--text2)">#{{ $p->id }}</td>
            <td>
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">{{ strtoupper(substr($p->cliente?->nombre ?? 'XX', 0, 2)) }}</span>
                    <div>
                        <div style="font-size:13px;font-weight:500">{{ $p->cliente?->nombre ?? '—' }}</div>
                        <div style="font-size:11px;color:var(--text2)">{{ $p->cliente?->celular ?? '' }}</div>
                    </div>
                </div>
            </td>
            <td style="font-size:13px">{{ $p->promotor?->nombre ?? '—' }}</td>
            <td style="text-align:right;font-family:monospace;font-size:13px;font-weight:700">${{ number_format($p->monto, 2, '.', ',') }}</td>
            <td style="text-align:right;font-family:monospace;font-size:13px">${{ number_format($p->cuota, 2, '.', ',') }}</td>
            <td style="font-size:13px">{{ $p->frecuencia }}</td>
            <td style="font-family:monospace;font-size:12px">{{ \Carbon\Carbon::parse($p->fecha_inicio)->format('d/m/Y') }}</td>
            <td style="font-size:11px;color:var(--text2)">{{ $p->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
            <td style="text-align:center">
                <button
                    class="btn btn-primary btn-sm"
                    onclick="abrirDesembolso({{ $p->id }}, '{{ addslashes($p->cliente?->nombre ?? '') }}', {{ $p->monto }}, {{ $p->monto_entregado ?? $p->monto }})"
                    style="font-size:12px">
                    ✓ Confirmar entrega
                </button>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

{{-- Modal de confirmación --}}
<div id="modalDesembolso" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center;overflow-y:auto;padding:20px 0">
<div style="background:var(--card);border-radius:var(--radius);width:500px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.15);overflow:hidden;margin:auto">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <h3 style="font-size:14px;font-weight:600">Confirmar desembolso</h3>
        <button onclick="cerrarModal()"
                style="width:26px;height:26px;border:none;background:#f3f4f6;border-radius:6px;cursor:pointer;font-size:16px;color:var(--text2)">×</button>
    </div>
    <div style="padding:20px">

        {{-- Cliente --}}
        <div style="margin-bottom:16px">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:3px">Cliente</div>
            <div style="font-size:14px;font-weight:600" id="dsbClienteNombre">—</div>
        </div>

        {{-- Monto y forma --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">Monto a entregar ($)</label>
                <input type="number" id="dsbMonto" step="0.01" min="1" readonly
                       style="width:100%;padding:9px 12px;background:#f0f0f0;border:1px solid var(--border);border-radius:6px;font-family:monospace;font-size:14px;outline:none;box-sizing:border-box;cursor:not-allowed;color:var(--text2)">
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">Forma de entrega</label>
                <select id="dsbForma"
                        style="width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;outline:none;cursor:pointer">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>
        </div>

        {{-- Documentos requeridos --}}
        <div style="margin-bottom:6px">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:10px;display:flex;align-items:center;gap:6px">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Documentos requeridos
            </div>

            <div style="display:grid;gap:10px">
                {{-- INE --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:4px">
                        INE del cliente <span style="color:#ef4444">*</span>
                    </label>
                    <div id="lbl_ine" style="display:flex;gap:6px">
                        <label style="flex:1;display:flex;align-items:center;gap:6px;padding:9px 12px;background:#f9fafb;border:1.5px dashed var(--border);border-radius:6px;cursor:pointer;font-size:12px;color:var(--text2);transition:border-color .15s;min-width:0">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span style="white-space:nowrap">Subir archivo</span>
                            <input type="file" id="doc_ine_file" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="seleccionarDoc('ine','file')">
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;padding:9px 12px;background:#f9fafb;border:1.5px dashed var(--border);border-radius:6px;cursor:pointer;font-size:12px;color:var(--text2);transition:border-color .15s;white-space:nowrap">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                            Tomar foto
                            <input type="file" id="doc_ine_cam" accept="image/*" capture="environment" style="display:none" onchange="seleccionarDoc('ine','cam')">
                        </label>
                    </div>
                    <div id="txt_ine" style="display:none;margin-top:5px;font-size:11px;color:#166534;padding:5px 8px;background:rgba(22,163,74,.05);border-radius:4px"></div>
                </div>

                {{-- Pagaré --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:4px">
                        Pagaré firmado <span style="color:#ef4444">*</span>
                    </label>
                    <div id="lbl_pagare" style="display:flex;gap:6px">
                        <label style="flex:1;display:flex;align-items:center;gap:6px;padding:9px 12px;background:#f9fafb;border:1.5px dashed var(--border);border-radius:6px;cursor:pointer;font-size:12px;color:var(--text2);transition:border-color .15s;min-width:0">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span style="white-space:nowrap">Subir archivo</span>
                            <input type="file" id="doc_pagare_file" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="seleccionarDoc('pagare','file')">
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;padding:9px 12px;background:#f9fafb;border:1.5px dashed var(--border);border-radius:6px;cursor:pointer;font-size:12px;color:var(--text2);transition:border-color .15s;white-space:nowrap">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                            Tomar foto
                            <input type="file" id="doc_pagare_cam" accept="image/*" capture="environment" style="display:none" onchange="seleccionarDoc('pagare','cam')">
                        </label>
                    </div>
                    <div id="txt_pagare" style="display:none;margin-top:5px;font-size:11px;color:#166534;padding:5px 8px;background:rgba(22,163,74,.05);border-radius:4px"></div>
                </div>

                {{-- Comprobante de domicilio --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:4px">
                        Comprobante de domicilio <span style="color:#ef4444">*</span>
                    </label>
                    <div id="lbl_comprobante" style="display:flex;gap:6px">
                        <label style="flex:1;display:flex;align-items:center;gap:6px;padding:9px 12px;background:#f9fafb;border:1.5px dashed var(--border);border-radius:6px;cursor:pointer;font-size:12px;color:var(--text2);transition:border-color .15s;min-width:0">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span style="white-space:nowrap">Subir archivo</span>
                            <input type="file" id="doc_comprobante_file" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="seleccionarDoc('comprobante','file')">
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;padding:9px 12px;background:#f9fafb;border:1.5px dashed var(--border);border-radius:6px;cursor:pointer;font-size:12px;color:var(--text2);transition:border-color .15s;white-space:nowrap">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                            Tomar foto
                            <input type="file" id="doc_comprobante_cam" accept="image/*" capture="environment" style="display:none" onchange="seleccionarDoc('comprobante','cam')">
                        </label>
                    </div>
                    <div id="txt_comprobante" style="display:none;margin-top:5px;font-size:11px;color:#166534;padding:5px 8px;background:rgba(22,163,74,.05);border-radius:4px"></div>
                </div>

                {{-- Foto domicilio (opcional) --}}
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:4px">
                        Foto del domicilio <span style="font-size:11px;font-weight:400;color:var(--text3)">(opcional)</span>
                    </label>
                    <div id="lbl_foto" style="display:flex;gap:6px">
                        <label style="flex:1;display:flex;align-items:center;gap:6px;padding:9px 12px;background:#f9fafb;border:1.5px dashed var(--border);border-radius:6px;cursor:pointer;font-size:12px;color:var(--text2);transition:border-color .15s;min-width:0">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span style="white-space:nowrap">Subir archivo</span>
                            <input type="file" id="doc_foto_file" accept=".jpg,.jpeg,.png" style="display:none" onchange="seleccionarDoc('foto','file')">
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;padding:9px 12px;background:#f9fafb;border:1.5px dashed var(--border);border-radius:6px;cursor:pointer;font-size:12px;color:var(--text2);transition:border-color .15s;white-space:nowrap">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                            Tomar foto
                            <input type="file" id="doc_foto_cam" accept="image/*" capture="environment" style="display:none" onchange="seleccionarDoc('foto','cam')">
                        </label>
                    </div>
                    <div id="txt_foto" style="display:none;margin-top:5px;font-size:11px;color:#166534;padding:5px 8px;background:rgba(22,163,74,.05);border-radius:4px"></div>
                </div>
            </div>
        </div>

        {{-- Nota --}}
        <div style="margin-top:14px">
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">Nota (opcional)</label>
            <input type="text" id="dsbNota" placeholder="Observaciones del desembolso…"
                   style="width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;outline:none;box-sizing:border-box">
        </div>

        <div id="dsbGanancia" style="display:none;margin-top:12px;padding:10px 14px;background:rgba(22,163,74,.07);border:1px solid rgba(22,163,74,.2);border-radius:6px;font-size:12px;color:#166534"></div>
        <div id="dsbError" style="display:none;margin-top:10px;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;font-size:12px;color:#991b1b"></div>
    </div>

    <div style="padding:14px 20px;border-top:1px solid var(--border);background:#f9fafb;display:flex;gap:8px;justify-content:flex-end">
        <button onclick="cerrarModal()"
                class="btn" style="background:#f3f4f6;border:1px solid var(--border);color:var(--text2)">Cancelar</button>
        <button id="btnConfirmar" onclick="confirmarDesembolso()" class="btn btn-primary">
            ✓ Confirmar entrega
        </button>
    </div>
</div>
</div>

@push('scripts')
<script>
let dsbPrestamoId = null;
let dsbMontoAcordado = 0;

function abrirDesembolso(id, nombre, montoAcordado, montoEntregado) {
    dsbPrestamoId = id;
    dsbMontoAcordado = montoAcordado;
    document.getElementById('dsbClienteNombre').textContent = nombre;
    document.getElementById('dsbMonto').value = montoEntregado || montoAcordado;
    document.getElementById('dsbNota').value = '';
    document.getElementById('dsbForma').value = 'efectivo';
    document.getElementById('dsbError').style.display = 'none';

    // Resetear labels de archivos
    ['ine','pagare','comprobante','foto'].forEach(k => resetLabel(k));

    actualizarGanancia();
    document.getElementById('modalDesembolso').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalDesembolso').style.display = 'none';
}

// Tracks which input (file or cam) was last used per doc key
const docActivo = { ine: null, pagare: null, comprobante: null, foto: null };

function seleccionarDoc(key, tipo) {
    const inputId = key === 'foto'
        ? (tipo === 'file' ? 'doc_foto_file' : 'doc_foto_cam')
        : (tipo === 'file' ? `doc_${key}_file` : `doc_${key}_cam`);
    const input = document.getElementById(inputId);
    if (!input || !input.files || !input.files[0]) return;
    docActivo[key] = input;
    const name = input.files[0].name;
    const txt = document.getElementById('txt_' + key);
    txt.textContent = '✓ ' + (name.length > 40 ? '…' + name.slice(-37) : name);
    txt.style.display = '';
}

function resetLabel(key) {
    docActivo[key] = null;
    ['file','cam'].forEach(tipo => {
        const sfx  = key === 'foto' ? (tipo === 'file' ? 'doc_foto_file' : 'doc_foto_cam')
                                     : `doc_${key}_${tipo}`;
        const el = document.getElementById(sfx);
        if (el) el.value = '';
    });
    const txt = document.getElementById('txt_' + key);
    txt.textContent = '';
    txt.style.display = 'none';
}

function actualizarGanancia() {
    const monto = parseFloat(document.getElementById('dsbMonto').value) || 0;
    const box   = document.getElementById('dsbGanancia');
    if (monto > 0 && dsbMontoAcordado > monto) {
        const gan = dsbMontoAcordado - monto;
        const pct = (gan / monto * 100).toFixed(1);
        box.style.display = '';
        box.innerHTML = `<strong>Ganancia del acuerdo:</strong> $${gan.toLocaleString('es-MX', {minimumFractionDigits:2})} (${pct}% de lo entregado)`;
    } else {
        box.style.display = 'none';
    }
}

function mostrarError(msg) {
    const el = document.getElementById('dsbError');
    el.textContent = msg;
    el.style.display = '';
}

// Compresses an image File to a Blob ≤ maxMB using canvas
function comprimirImagen(file, maxMB = 8) {
    return new Promise(resolve => {
        if (file.size <= maxMB * 1024 * 1024 || !file.type.startsWith('image/')) {
            resolve(file); return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let { width, height } = img;
                // Scale down if wider than 1600px
                if (width > 1600) { height = Math.round(height * 1600 / width); width = 1600; }
                canvas.width = width; canvas.height = height;
                canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                canvas.toBlob(blob => {
                    resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' }));
                }, 'image/jpeg', 0.82);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

async function confirmarDesembolso() {
    const monto = parseFloat(document.getElementById('dsbMonto').value) || 0;
    document.getElementById('dsbError').style.display = 'none';

    if (!dsbPrestamoId || monto <= 0) { mostrarError('Ingresa un monto válido.'); return; }
    if (!docActivo.ine        || !docActivo.ine.files[0])        { mostrarError('El documento INE es requerido.'); return; }
    if (!docActivo.pagare     || !docActivo.pagare.files[0])     { mostrarError('El pagaré firmado es requerido.'); return; }
    if (!docActivo.comprobante|| !docActivo.comprobante.files[0]){ mostrarError('El comprobante de domicilio es requerido.'); return; }

    const btn = document.getElementById('btnConfirmar');
    btn.textContent = 'Preparando archivos…';
    btn.disabled = true;

    try {
        // Compress images before uploading (camera photos can be 5–10 MB)
        const fileIne         = await comprimirImagen(docActivo.ine.files[0]);
        const filePagare      = await comprimirImagen(docActivo.pagare.files[0]);
        const fileComprobante = await comprimirImagen(docActivo.comprobante.files[0]);
        const fileFoto        = docActivo.foto?.files[0]
            ? await comprimirImagen(docActivo.foto.files[0])
            : null;

        btn.textContent = 'Subiendo documentos…';

        const fd = new FormData();
        fd.append('prestamo_id',    dsbPrestamoId);
        fd.append('monto',          monto);
        fd.append('forma',          document.getElementById('dsbForma').value);
        fd.append('nota',           document.getElementById('dsbNota').value || '');
        fd.append('doc_ine',         fileIne);
        fd.append('doc_pagare',      filePagare);
        fd.append('doc_comprobante', fileComprobante);
        if (fileFoto) fd.append('doc_foto_domicilio', fileFoto);

        const resp = await fetch('{{ route("desembolsos.confirmar") }}', {
            method:  'POST',
            headers: {
                'X-CSRF-TOKEN':     '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
            },
            body: fd,
        });

        // Handle non-JSON responses (PHP errors, 413, 500, etc.)
        const text = await resp.text();
        let data;
        try { data = JSON.parse(text); }
        catch {
            const statusMsg = resp.status === 413 ? 'Archivos demasiado grandes para el servidor (413).'
                            : resp.status >= 500  ? `Error interno del servidor (${resp.status}).`
                            : `Respuesta inesperada (${resp.status}).`;
            mostrarError(statusMsg + ' Revisa la consola para más detalles.');
            console.error('Server response:', text);
            btn.textContent = '✓ Confirmar entrega'; btn.disabled = false;
            return;
        }

        if (data.ok) {
            window.location.reload();
        } else {
            mostrarError(data.error || 'No se pudo confirmar. Intenta de nuevo.');
            btn.textContent = '✓ Confirmar entrega'; btn.disabled = false;
        }
    } catch (err) {
        mostrarError('Error de red al subir los archivos. Verifica tu conexión.');
        console.error(err);
        btn.textContent = '✓ Confirmar entrega'; btn.disabled = false;
    }
}
</script>
@endpush

@endsection
