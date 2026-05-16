@extends('layouts.app')

@section('title', 'Monitor de cobros')

@push('styles')
<style>
/* Layout */
.mon-wrap{display:grid;grid-template-columns:260px 1fr;gap:20px;align-items:start}
.mon-sidebar{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;position:sticky;top:76px}
.mon-sidebar-header{padding:14px 16px;border-bottom:1px solid var(--border);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)}

/* Cobrador cards */
.mon-cob{display:flex;align-items:center;gap:12px;padding:13px 16px;border-bottom:1px solid var(--border);cursor:pointer;text-decoration:none;transition:background .1s}
.mon-cob:last-child{border-bottom:none}
.mon-cob:hover{background:#f9fafb}
.mon-cob.active{background:#eff6ff;border-left:3px solid var(--accent)}
.mon-cob-avatar{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0}
.mon-cob-name{font-size:13px;font-weight:600;color:var(--text);line-height:1.2}
.mon-cob-stats{font-size:11px;color:var(--text3);margin-top:1px}
.mon-pill{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;border-radius:999px;font-size:10px;font-weight:700;line-height:1}

/* Main panel */
.mon-main{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.mon-main-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.mon-stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border);border-bottom:1px solid var(--border)}
.mon-stat-cell{background:var(--card);padding:14px 18px;text-align:center}
.mon-stat-val{font-size:22px;font-weight:700;letter-spacing:-.02em}
.mon-stat-lbl{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-top:2px}

/* Loan rows */
.mon-loan-row{display:grid;grid-template-columns:auto 1fr auto auto auto;gap:0;padding:13px 20px;border-bottom:1px solid var(--border);align-items:center;transition:background .1s}
.mon-loan-row:last-child{border-bottom:none}
.mon-loan-row.pagado{background:#f0fdf4;opacity:.75}
.mon-loan-row:hover{background:#f9fafb}
.mon-loan-row.pagado:hover{background:#f0fdf4}
.mon-check{width:22px;height:22px;border-radius:50%;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-right:14px;cursor:pointer;transition:all .15s}
.mon-check.done{background:#16a34a;border-color:#16a34a}
.mon-check svg{width:12px;height:12px;color:#fff;opacity:0;transition:opacity .1s}
.mon-check.done svg{opacity:1}
.mon-loan-name{font-size:13px;font-weight:600;color:var(--text)}
.mon-loan-sub{font-size:11px;color:var(--text3);margin-top:1px}
.mon-loan-amount{font-size:14px;font-weight:700;font-family:monospace;color:var(--text);text-align:right;padding:0 16px}
.mon-loan-status{text-align:right}
.mon-atrasado-badge{font-size:10px;font-weight:700;padding:2px 7px;border-radius:8px;background:#fee2e2;color:#dc2626;white-space:nowrap}
.mon-mora-badge{font-size:10px;font-weight:700;padding:2px 7px;border-radius:8px;background:#fff7ed;color:#c2410c;white-space:nowrap}

/* Empty */
.mon-empty{padding:48px 20px;text-align:center;color:var(--text3);font-size:13px}
.mon-section-label{padding:8px 20px;background:#f9fafb;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3)}

/* Quick register overlay */
.mon-confirm-bar{position:fixed;bottom:0;left:220px;right:0;background:#fff;border-top:1px solid var(--border);padding:12px 24px;display:flex;align-items:center;gap:12px;z-index:200;transform:translateY(100%);transition:transform .2s;box-shadow:0 -4px 24px rgba(0,0,0,.08)}
.mon-confirm-bar.open{transform:translateY(0)}

@media(max-width:900px){
    .mon-wrap{grid-template-columns:1fr}
    .mon-sidebar{position:static;display:flex;overflow-x:auto;gap:0;flex-direction:row;border-radius:var(--radius);padding:4px}
    .mon-sidebar-header{display:none}
    .mon-cob{border:1px solid var(--border);border-bottom:1px solid var(--border);border-radius:8px;flex-direction:column;text-align:center;gap:6px;flex:0 0 auto;width:110px;padding:10px 8px}
    .mon-cob.active{border-color:var(--accent);background:#eff6ff}
    .mon-confirm-bar{left:0}
}
@media(max-width:640px){
    .mon-stats-row{grid-template-columns:1fr 1fr}
    .mon-loan-row{grid-template-columns:auto 1fr auto}
    .mon-loan-status{display:none}
}
</style>
@endpush

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
        <h2 style="font-size:20px;font-weight:700;margin-bottom:4px">Monitor de cobros</h2>
        <p style="color:var(--text2);font-size:13px">{{ now()->format('l d \d\e F Y') }} — selecciona un cobrador para ver y registrar sus cobros de hoy</p>
    </div>
    <a href="{{ route('cobros.asignar') }}" class="btn btn-sm" style="background:#f3f4f6;color:var(--text)">
        <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:12px;height:12px"><path d="M2 7h10M7 2l5 5-5 5"/></svg>
        Asignar cobros
    </a>
</div>

@if($cobradores->isEmpty())
<div class="card" style="text-align:center;padding:60px 24px;color:var(--text3)">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.3"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7"/></svg>
    <p style="font-size:15px;font-weight:600;color:var(--text2);margin-bottom:6px">No hay cobradores activos</p>
    <p style="font-size:13px">Crea empleados con rol de cobrador para verlos aquí.</p>
</div>
@else

<div class="mon-wrap">

    {{-- ── Sidebar: cobrador selector ──────────────────────────────── --}}
    <div class="mon-sidebar">
        <div class="mon-sidebar-header">Cobradores</div>
        @foreach($cobradores as $cob)
        @php
            $colors = ['#3b82f6','#6366f1','#8b5cf6','#ec4899','#10b981','#f59e0b','#ef4444','#0ea5e9'];
            $color  = $colors[crc32($cob->nombre) % count($colors)];
            $data   = $prestamosPorCobrador->get($cob->id);
            $activo = ($cobradorSel === $cob->id) || (!$cobradorSel && $loop->first);
        @endphp
        <a href="{{ route('cobros.monitor', ['cobrador' => $cob->id]) }}"
           class="mon-cob {{ $activo ? 'active' : '' }}">
            <div class="mon-cob-avatar" style="background:{{ $color }}">
                {{ strtoupper(substr($cob->nombre, 0, 2)) }}
            </div>
            <div style="flex:1;min-width:0">
                <div class="mon-cob-name">{{ $cob->nombre }}</div>
                <div class="mon-cob-stats">
                    @if($data)
                        <span style="color:{{ $data->cobrados > 0 ? '#16a34a' : 'var(--text3)' }}">
                            {{ $data->cobrados }}/{{ $data->total_hoy }} hoy
                        </span>
                    @else
                        Sin cobros hoy
                    @endif
                </div>
            </div>
            @if($data && $data->pendientes > 0)
            <span class="mon-pill" style="background:#fee2e2;color:#dc2626">{{ $data->pendientes }}</span>
            @elseif($data && $data->cobrados === $data->total_hoy && $data->total_hoy > 0)
            <span class="mon-pill" style="background:#dcfce7;color:#16a34a">✓</span>
            @endif
        </a>
        @endforeach
    </div>

    {{-- ── Main: cobros del cobrador seleccionado ──────────────────── --}}
    <div>
    @if(!$cobradorSelObj)
    <div class="mon-main mon-empty">Selecciona un cobrador en el panel izquierdo.</div>
    @else
    @php
        $csel = $cobradorSelObj;
        $colors = ['#3b82f6','#6366f1','#8b5cf6','#ec4899','#10b981','#f59e0b','#ef4444','#0ea5e9'];
        $colorSel = $colors[crc32($csel->nombre) % count($colors)];
    @endphp

    <div class="mon-main">

        {{-- Header --}}
        <div class="mon-main-header">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:40px;height:40px;border-radius:12px;background:{{ $colorSel }};display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#fff;flex-shrink:0">
                    {{ strtoupper(substr($csel->nombre, 0, 2)) }}
                </div>
                <div>
                    <div style="font-size:16px;font-weight:700;color:var(--text)">{{ $csel->nombre }}</div>
                    <div style="font-size:12px;color:var(--text3)">{{ $csel->rango }} · {{ $csel->total_hoy }} cobros para hoy</div>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                @if($csel->celular)
                <a href="https://wa.me/52{{ preg_replace('/\D/','',$csel->celular) }}" target="_blank"
                   class="btn btn-sm" style="background:#dcfce7;color:#16a34a">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px"><path d="M10 0C4.477 0 0 4.477 0 10c0 1.763.46 3.417 1.264 4.857L0 20l5.285-1.384A9.958 9.958 0 0010 20c5.523 0 10-4.477 10-10S15.523 0 10 0zm0 18.182a8.173 8.173 0 01-4.163-1.136l-.298-.178-3.136.822.837-3.056-.195-.314A8.182 8.182 0 1110 18.182zm4.504-6.13c-.247-.124-1.463-.722-1.69-.804-.227-.083-.392-.124-.557.124-.165.247-.638.804-.782.969-.144.165-.288.185-.535.062-.247-.124-1.043-.384-1.987-1.225-.734-.655-1.23-1.464-1.374-1.71-.144-.247-.015-.381.108-.504.111-.11.247-.288.37-.432.124-.144.165-.247.247-.412.083-.165.041-.309-.02-.432-.062-.124-.557-1.343-.763-1.838-.2-.483-.405-.418-.557-.426l-.474-.008c-.165 0-.432.062-.659.309-.227.247-.866.846-.866 2.063s.887 2.392 1.011 2.557c.124.165 1.746 2.665 4.231 3.737.591.255 1.052.407 1.411.521.593.188 1.132.162 1.559.098.475-.071 1.463-.598 1.669-1.176.206-.577.206-1.072.144-1.176-.062-.103-.227-.165-.474-.289z"/></svg>
                    WhatsApp
                </a>
                @endif
            </div>
        </div>

        {{-- Stats row --}}
        <div class="mon-stats-row">
            <div class="mon-stat-cell">
                <div class="mon-stat-val" style="color:#16a34a">{{ $csel->cobrados }}</div>
                <div class="mon-stat-lbl">Cobrados hoy</div>
            </div>
            <div class="mon-stat-cell">
                <div class="mon-stat-val" style="color:#f59e0b">{{ $csel->pendientes }}</div>
                <div class="mon-stat-lbl">Pendientes</div>
            </div>
            <div class="mon-stat-cell">
                <div class="mon-stat-val">{{ $csel->loans_hoy->count() + $csel->loans_otros->count() }}</div>
                <div class="mon-stat-lbl">Total asignados</div>
            </div>
        </div>

        {{-- Cobros de HOY --}}
        @if($csel->loans_hoy->isEmpty() && $csel->loans_otros->isEmpty())
        <div class="mon-empty">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;display:block;opacity:.3"><path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2h9"/></svg>
            Este cobrador no tiene préstamos asignados.
        </div>
        @else

        @if($csel->loans_hoy->isNotEmpty())
        <div class="mon-section-label">
            Cobros de hoy &amp; atrasados — {{ $csel->loans_hoy->count() }} préstamos
        </div>
        @foreach($csel->loans_hoy as $loan)
        @php $yaPago = $loan->pagado_hoy; @endphp
        <div class="mon-loan-row {{ $yaPago ? 'pagado' : '' }}" id="loan-row-{{ $loan->id }}">
            {{-- Checkmark --}}
            <div class="mon-check {{ $yaPago ? 'done' : '' }}" id="chk-{{ $loan->id }}"
                 onclick="{{ $yaPago ? '' : "registrarCobro({$loan->id}, {$loan->pago_id_hoy}, {$loan->cuota_hoy}, {$loan->mora})" }}"
                 title="{{ $yaPago ? 'Cobrado hoy' : 'Marcar como cobrado' }}"
                 style="{{ $yaPago ? 'cursor:default' : 'cursor:pointer' }}">
                <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M2 6l3 3 5-5"/></svg>
            </div>
            {{-- Cliente --}}
            <div>
                <div class="mon-loan-name">{{ $loan->cliente?->nombre ?? '—' }}</div>
                <div class="mon-loan-sub">
                    #{{ $loan->id }}
                    @if($loan->proximo_pago)
                     · Vence {{ \Carbon\Carbon::parse($loan->proximo_pago)->format('d/m') }}
                    @endif
                    @if($loan->mora > 0)
                     · <span style="color:#c2410c">+${{ number_format($loan->mora, 0) }} mora</span>
                    @endif
                </div>
            </div>
            {{-- Monto --}}
            <div class="mon-loan-amount">${{ number_format($loan->cuota_hoy, 0, '.', ',') }}</div>
            {{-- Status --}}
            <div class="mon-loan-status">
                @if($yaPago)
                <span class="mon-pill" style="background:#dcfce7;color:#16a34a;font-size:11px;padding:3px 8px">✓ Pagado</span>
                @elseif($loan->estatus === 'Atrasado')
                <span class="mon-atrasado-badge">Atrasado</span>
                @else
                <span class="mon-mora-badge" style="background:#fef9c3;color:#854d0e">Pendiente</span>
                @endif
            </div>
            {{-- Link --}}
            <div style="padding-left:12px">
                <a href="{{ route('prestamos.show', $loan->id) }}" target="_blank"
                   style="color:var(--text3);display:flex;align-items:center" title="Ver detalle">
                    <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><path d="M5 2H3a1 1 0 00-1 1v8a1 1 0 001 1h8a1 1 0 001-1V9M9 2h3v3M6.5 7.5L12 2"/></svg>
                </a>
            </div>
        </div>
        @endforeach
        @endif

        @if($csel->loans_otros->isNotEmpty())
        <div class="mon-section-label">Próximos cobros — {{ $csel->loans_otros->count() }} préstamos</div>
        @foreach($csel->loans_otros as $loan)
        <div class="mon-loan-row" style="opacity:.6">
            <div class="mon-check" style="cursor:default;border-color:#e5e7eb"></div>
            <div>
                <div class="mon-loan-name">{{ $loan->cliente?->nombre ?? '—' }}</div>
                <div class="mon-loan-sub">#{{ $loan->id }} · Vence {{ $loan->proximo_pago ? \Carbon\Carbon::parse($loan->proximo_pago)->format('d/m/Y') : '—' }}</div>
            </div>
            <div class="mon-loan-amount">${{ number_format($loan->cuota_hoy, 0, '.', ',') }}</div>
            <div class="mon-loan-status">
                <span class="mon-pill" style="background:#f3f4f6;color:var(--text2);font-size:11px;padding:3px 8px">Futuro</span>
            </div>
            <div style="padding-left:12px">
                <a href="{{ route('prestamos.show', $loan->id) }}" target="_blank"
                   style="color:var(--text3);display:flex;align-items:center">
                    <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="width:13px;height:13px"><path d="M5 2H3a1 1 0 00-1 1v8a1 1 0 001 1h8a1 1 0 001-1V9M9 2h3v3M6.5 7.5L12 2"/></svg>
                </a>
            </div>
        </div>
        @endforeach
        @endif

        @endif
    </div>
    @endif
    </div>

</div>

{{-- ── Modal: confirmar pago rápido ──────────────────────────────── --}}
<div class="ow-modal-overlay" id="modalPago">
    <div class="ow-modal" style="width:360px">
        <div class="ow-modal-header">
            <div>
                <div class="ow-modal-title">Confirmar cobro</div>
                <div style="font-size:12px;color:var(--text3);margin-top:2px" id="mpCliente">—</div>
            </div>
            <button class="ow-modal-close" onclick="cerrarModal()">&times;</button>
        </div>
        <div class="ow-modal-body" style="gap:12px">
            <div style="background:#f9fafb;border:1px solid var(--border);border-radius:8px;padding:12px 14px">
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text3);margin-bottom:4px">Cuota programada</div>
                <div style="font-size:22px;font-weight:700;font-family:monospace;color:var(--text)" id="mpCuota">—</div>
                <div id="mpMoraRow" style="font-size:12px;color:#c2410c;margin-top:4px;display:none">+ <span id="mpMora">$0</span> mora acumulada</div>
            </div>
            <div class="ow-field">
                <label>Monto cobrado ($)</label>
                <input type="number" id="mpMonto" step="0.01" min="0.01"
                       style="width:100%;padding:10px 13px;background:#f9fafb;border:1.5px solid var(--border);border-radius:8px;font-size:16px;font-family:monospace;outline:none"
                       placeholder="0.00">
            </div>
            <div class="ow-field">
                <label>Nota (opcional)</label>
                <input type="text" id="mpNota" maxlength="200"
                       style="width:100%;padding:9px 13px;background:#f9fafb;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:var(--font);outline:none"
                       placeholder="ej. Pagó en efectivo">
            </div>
        </div>
        <div class="ow-modal-footer">
            <button type="button" class="btn" style="background:#f3f4f6;color:var(--text)" onclick="cerrarModal()">Cancelar</button>
            <button type="button" class="btn btn-primary" id="mpBtnConfirmar" onclick="confirmarPago()">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:12px;height:12px"><path d="M2 7l3 3 7-7"/></svg>
                Confirmar cobro
            </button>
        </div>
    </div>
</div>

@endif

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
let pendingLoan = null;

function registrarCobro(loanId, pagoId, cuota, mora) {
    pendingLoan = { loanId, pagoId, cuota, mora };
    const row = document.getElementById('loan-row-' + loanId);
    const nombre = row?.querySelector('.mon-loan-name')?.textContent || '—';
    document.getElementById('mpCliente').textContent = nombre;
    document.getElementById('mpCuota').textContent = '$' + parseFloat(cuota).toLocaleString('es-MX', {minimumFractionDigits:2});
    const moraRow = document.getElementById('mpMoraRow');
    if (mora > 0) {
        document.getElementById('mpMora').textContent = '$' + parseFloat(mora).toLocaleString('es-MX', {minimumFractionDigits:2});
        moraRow.style.display = '';
    } else {
        moraRow.style.display = 'none';
    }
    document.getElementById('mpMonto').value = (parseFloat(cuota) + parseFloat(mora)).toFixed(2);
    document.getElementById('mpNota').value = '';
    document.getElementById('modalPago').classList.add('open');
    setTimeout(() => document.getElementById('mpMonto').focus(), 80);
}

function cerrarModal() {
    document.getElementById('modalPago').classList.remove('open');
    pendingLoan = null;
}

document.getElementById('modalPago')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});

async function confirmarPago() {
    if (!pendingLoan) return;
    const monto = parseFloat(document.getElementById('mpMonto').value);
    if (!monto || monto <= 0) {
        document.getElementById('mpMonto').style.borderColor = '#ef4444';
        return;
    }
    const nota = document.getElementById('mpNota').value.trim();
    const btn  = document.getElementById('mpBtnConfirmar');
    btn.disabled = true;
    btn.textContent = 'Registrando…';

    const payload = {
        [pendingLoan.loanId]: { tipo: monto >= (pendingLoan.cuota + pendingLoan.mora) ? 'completo' : 'parcial', monto, nota }
    };

    try {
        const resp = await fetch('{{ route('cobros.registrar') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (data.ok) {
            // Update UI optimistically
            const chk = document.getElementById('chk-' + pendingLoan.loanId);
            const row = document.getElementById('loan-row-' + pendingLoan.loanId);
            if (chk) { chk.classList.add('done'); chk.style.cursor = 'default'; chk.onclick = null; }
            if (row)  { row.classList.add('pagado'); }
            cerrarModal();
            // Update cobrados count
            const valEl = document.querySelector('.mon-stat-cell:first-child .mon-stat-val');
            if (valEl) valEl.textContent = parseInt(valEl.textContent || '0') + 1;
            const pendEl = document.querySelector('.mon-stat-cell:nth-child(2) .mon-stat-val');
            if (pendEl) pendEl.textContent = Math.max(0, parseInt(pendEl.textContent || '1') - 1);
        } else {
            alert('Error: ' + (data.error || 'No se pudo registrar'));
            btn.disabled = false;
            btn.innerHTML = '<svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:12px;height:12px"><path d="M2 7l3 3 7-7"/></svg> Confirmar cobro';
        }
    } catch(e) {
        alert('Error de conexión');
        btn.disabled = false;
    }
}
</script>
@endpush

@endsection
