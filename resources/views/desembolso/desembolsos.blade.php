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
<div id="modalDesembolso" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center">
<div style="background:var(--card);border-radius:var(--radius);width:440px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.15);overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <h3 style="font-size:14px;font-weight:600">Confirmar desembolso</h3>
        <button onclick="document.getElementById('modalDesembolso').style.display='none'"
                style="width:26px;height:26px;border:none;background:#f3f4f6;border-radius:6px;cursor:pointer;font-size:16px;color:var(--text2)">×</button>
    </div>
    <div style="padding:20px">
        <div style="margin-bottom:16px">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:3px">Cliente</div>
            <div style="font-size:14px;font-weight:600" id="dsbClienteNombre">—</div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div>
                <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">Monto a entregar ($)</label>
                <input type="number" id="dsbMonto" step="0.01" min="1"
                       style="width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-family:monospace;font-size:14px;outline:none;box-sizing:border-box"
                       oninput="actualizarGanancia()">
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

        <div style="margin-top:14px">
            <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:5px">Nota (opcional)</label>
            <input type="text" id="dsbNota" placeholder="Observaciones del desembolso…"
                   style="width:100%;padding:9px 12px;background:#f9fafb;border:1px solid var(--border);border-radius:6px;font-size:13px;outline:none;box-sizing:border-box">
        </div>

        <div id="dsbGanancia" style="display:none;margin-top:12px;padding:10px 14px;background:rgba(22,163,74,.07);border:1px solid rgba(22,163,74,.2);border-radius:6px;font-size:12px;color:#166534"></div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--border);background:#f9fafb;display:flex;gap:8px;justify-content:flex-end">
        <button onclick="document.getElementById('modalDesembolso').style.display='none'"
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
    actualizarGanancia();
    document.getElementById('modalDesembolso').style.display = 'flex';
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

function confirmarDesembolso() {
    const monto = parseFloat(document.getElementById('dsbMonto').value) || 0;
    if (!dsbPrestamoId || monto <= 0) {
        alert('Ingresa un monto válido.');
        return;
    }

    const btn = document.getElementById('btnConfirmar');
    btn.textContent = 'Procesando…';
    btn.disabled = true;

    fetch('{{ route("desembolsos.confirmar") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            prestamo_id: dsbPrestamoId,
            monto:       monto,
            forma:       document.getElementById('dsbForma').value,
            nota:        document.getElementById('dsbNota').value || null,
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'No se pudo confirmar.'));
            btn.textContent = '✓ Confirmar entrega';
            btn.disabled = false;
        }
    })
    .catch(() => {
        alert('Error de conexión. Intenta de nuevo.');
        btn.textContent = '✓ Confirmar entrega';
        btn.disabled = false;
    });
}
</script>
@endpush

@endsection
