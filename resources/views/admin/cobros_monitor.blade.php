@extends('layouts.app')

@section('title', 'Monitor de cobros')

@push('styles')
<style>
/* ── Globals ─────────────────────────────────────────────────── */
.mon-wrap{display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:start}

/* ── Sidebar ─────────────────────────────────────────────────── */
.mon-sidebar{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;position:sticky;top:76px}
.mon-sidebar-head{padding:12px 16px;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text3)}
.mon-cob{display:flex;align-items:center;gap:12px;padding:13px 16px;border-bottom:1px solid var(--border);cursor:pointer;text-decoration:none;transition:background .12s;position:relative}
.mon-cob:last-child{border-bottom:none}
.mon-cob:hover{background:#f9fafb}
.mon-cob.active{background:#eff6ff}
.mon-cob.active::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--accent);border-radius:0 2px 2px 0}
.mon-cob-av{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0}
.mon-cob-name{font-size:13px;font-weight:600;color:var(--text);line-height:1.2}
.mon-cob-sub{font-size:11px;color:var(--text3);margin-top:2px}
.mon-prog-bar{height:3px;border-radius:2px;background:#e5e7eb;margin-top:5px;overflow:hidden}
.mon-prog-fill{height:100%;border-radius:2px;background:#16a34a;transition:width .3s}
.mon-badge{display:inline-flex;align-items:center;justify-content:center;min-width:19px;height:19px;padding:0 5px;border-radius:999px;font-size:10px;font-weight:700;line-height:1;flex-shrink:0}

/* ── Main panel ──────────────────────────────────────────────── */
.mon-main{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}

/* Profile header */
.mon-profile{position:relative;padding:20px 22px 18px;border-bottom:1px solid var(--border)}
.mon-profile-band{position:absolute;top:0;left:0;right:0;height:48px;opacity:.12}
.mon-profile-inner{position:relative;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap}
.mon-profile-av{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff;flex-shrink:0;box-shadow:0 4px 12px rgba(0,0,0,.18)}
.mon-profile-name{font-size:17px;font-weight:700;color:var(--text);line-height:1.2}
.mon-profile-sub{font-size:12px;color:var(--text3);margin-top:3px}

/* Stats strip */
.mon-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border);border-bottom:1px solid var(--border)}
.mon-stat{background:var(--card);padding:16px 12px;text-align:center}
.mon-stat-val{font-size:26px;font-weight:800;font-family:monospace;letter-spacing:-.03em;line-height:1}
.mon-stat-lbl{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text3);margin-top:3px}

/* Progress bar under stats */
.mon-progress-wrap{padding:10px 20px 0;border-bottom:1px solid var(--border)}
.mon-progress-track{height:6px;border-radius:4px;background:#e5e7eb;overflow:hidden;margin:6px 0}
.mon-progress-done{height:100%;border-radius:4px;background:linear-gradient(90deg,#16a34a,#22c55e);transition:width .4s}

/* Section labels */
.mon-section-lbl{padding:8px 22px;background:#f9fafb;border-bottom:1px solid var(--border);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text3);display:flex;align-items:center;justify-content:space-between}

/* Loan rows */
.mon-row{display:grid;grid-template-columns:44px 1fr auto auto 28px;align-items:center;padding:12px 22px;border-bottom:1px solid var(--border);gap:0;transition:background .1s}
.mon-row:last-child{border-bottom:none}
.mon-row:hover{background:#f9fafb}
.mon-row.pagado{background:#f0fdf4}
.mon-row.pagado:hover{background:#f0fdf4}
.mon-row.futuro{opacity:.55}

/* Checkmark button */
.mon-chk{width:30px;height:30px;border-radius:50%;border:2px solid #d1d5db;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s;background:#fff;flex-shrink:0}
.mon-chk:hover:not(.done){border-color:var(--accent);background:#eff6ff}
.mon-chk.done{background:#16a34a;border-color:#16a34a;cursor:default}
.mon-chk svg{width:13px;height:13px;opacity:0;color:#fff;transition:opacity .1s}
.mon-chk.done svg{opacity:1}
.mon-chk.future-chk{border-color:#e5e7eb;cursor:default;background:#f9fafb}

/* Client info */
.mon-client-name{font-size:13px;font-weight:600;color:var(--text);padding-left:14px}
.mon-client-sub{font-size:11px;color:var(--text3);padding-left:14px;margin-top:2px;display:flex;gap:8px;flex-wrap:wrap}
.mon-mora-chip{display:inline-flex;align-items:center;gap:3px;background:#fff7ed;color:#c2410c;border-radius:5px;padding:1px 6px;font-size:10px;font-weight:700}

/* Amount + status */
.mon-amount{font-size:14px;font-weight:700;font-family:monospace;color:var(--text);text-align:right;padding:0 14px;white-space:nowrap}
.mon-status{text-align:right}
.mon-status-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:8px;font-size:11px;font-weight:700;white-space:nowrap}

/* External link */
.mon-link{display:flex;align-items:center;justify-content:center;color:#d1d5db;transition:color .12s}
.mon-link:hover{color:var(--accent)}

/* Empty */
.mon-empty{padding:52px 20px;text-align:center;color:var(--text3)}

/* Modal overrides */
.ow-modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.4);backdrop-filter:blur(5px);z-index:1000;align-items:center;justify-content:center}
.ow-modal-overlay.open{display:flex}
.ow-modal{background:#fff;border-radius:18px;width:380px;max-width:calc(100vw - 24px);box-shadow:0 24px 64px rgba(0,0,0,.2);overflow:hidden}
.ow-modal-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.ow-modal-title{font-size:16px;font-weight:700}
.ow-modal-close{background:#f1f5f9;border:none;width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:17px;color:var(--text3);display:flex;align-items:center;justify-content:center}
.ow-modal-body{padding:20px 24px;display:grid;gap:14px}
.ow-modal-footer{padding:14px 24px;background:#f8fafc;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
.ow-field label{display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:6px}
.ow-field input{width:100%;padding:10px 13px;background:#f9fafb;border:1.5px solid var(--border);border-radius:8px;font-size:15px;font-family:monospace;outline:none;box-sizing:border-box;transition:border-color .15s}
.ow-field input:focus{border-color:var(--accent);background:#fff}

/* Responsive */
@media(max-width:900px){
    .mon-wrap{grid-template-columns:1fr}
    .mon-sidebar{position:static;display:flex;overflow-x:auto;gap:6px;flex-direction:row;padding:10px;border-radius:var(--radius);background:transparent;border:none;scrollbar-width:none}
    .mon-sidebar::-webkit-scrollbar{display:none}
    .mon-sidebar-head{display:none}
    .mon-cob{border:1px solid var(--border);border-radius:10px;background:var(--card);flex-direction:column;text-align:center;gap:5px;flex:0 0 auto;width:100px;padding:10px 8px}
    .mon-cob.active{border-color:var(--accent)}
    .mon-cob.active::before{display:none}
    .mon-prog-bar{display:none}
}
@media(max-width:640px){
    .mon-stats{grid-template-columns:repeat(3,1fr)}
    .mon-row{grid-template-columns:38px 1fr auto 28px;padding:10px 14px}
    .mon-status{display:none}
    .mon-profile{padding:14px 16px 14px}
    .mon-progress-wrap{padding:8px 16px 0}
    .mon-section-lbl{padding:7px 16px}
    .mon-amount{padding:0 8px;font-size:13px}
}
</style>
@endpush

@section('content')

@php
// Fecha en español
$meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$dias  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$hoy   = now();
$fechaEs = ucfirst($dias[$hoy->dayOfWeek]) . ', ' . $hoy->day . ' de ' . $meses[$hoy->month - 1] . ' ' . $hoy->year;
@endphp

{{-- ── Page header ────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:10px">
    <div>
        <h2 style="font-size:20px;font-weight:700;margin-bottom:3px">Monitor de cobros</h2>
        <p style="color:var(--text2);font-size:13px">{{ $fechaEs }}</p>
    </div>
    <a href="{{ route('cobros.asignar') }}" class="btn btn-sm" style="background:#f3f4f6;color:var(--text)">
        Asignar cobros
        <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:11px;height:11px"><path d="M2 6h8M6 2l4 4-4 4"/></svg>
    </a>
</div>

@if($cobradores->isEmpty())
<div class="card" style="text-align:center;padding:64px 24px;color:var(--text3)">
    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" style="margin:0 auto 14px;display:block;opacity:.25"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7"/></svg>
    <p style="font-size:15px;font-weight:600;color:var(--text2);margin-bottom:6px">No hay cobradores activos</p>
    <p style="font-size:13px">Crea empleados con rol de cobrador para verlos aquí.</p>
</div>
@else

<div class="mon-wrap">

    {{-- ── Sidebar ──────────────────────────────────────────────── --}}
    <div class="mon-sidebar">
        <div class="mon-sidebar-head">Cobradores</div>
        @foreach($cobradores as $cob)
        @php
            $palette  = ['#3b82f6','#6366f1','#8b5cf6','#ec4899','#10b981','#f59e0b','#ef4444','#0ea5e9','#14b8a6'];
            $color    = $palette[crc32($cob->nombre) % count($palette)];
            $data     = $prestamosPorCobrador->get($cob->id);
            $activo   = ($cobradorSel === $cob->id) || (!$cobradorSel && $loop->first);
            $pct      = ($data && $data->total_hoy > 0) ? round($data->cobrados / $data->total_hoy * 100) : 0;
            $todoDone = $data && $data->total_hoy > 0 && $data->cobrados === $data->total_hoy;
        @endphp
        <a href="{{ route('cobros.monitor', ['cobrador' => $cob->id]) }}"
           class="mon-cob {{ $activo ? 'active' : '' }}">
            <div class="mon-cob-av" style="background:{{ $color }}">{{ strtoupper(substr($cob->nombre,0,2)) }}</div>
            <div style="flex:1;min-width:0">
                <div class="mon-cob-name">{{ $cob->nombre }}</div>
                <div class="mon-cob-sub">
                    @if($data && $data->total_hoy > 0)
                        {{ $data->cobrados }}/{{ $data->total_hoy }} cobrados hoy
                    @else
                        Sin cobros hoy
                    @endif
                </div>
                @if($data && $data->total_hoy > 0)
                <div class="mon-prog-bar">
                    <div class="mon-prog-fill" style="width:{{ $pct }}%;background:{{ $todoDone ? '#16a34a' : '#3b82f6' }}"></div>
                </div>
                @endif
            </div>
            @if($todoDone)
                <span class="mon-badge" style="background:#dcfce7;color:#16a34a">✓</span>
            @elseif($data && $data->pendientes > 0)
                <span class="mon-badge" style="background:#fee2e2;color:#dc2626">{{ $data->pendientes }}</span>
            @endif
        </a>
        @endforeach
    </div>

    {{-- ── Main ────────────────────────────────────────────────── --}}
    <div>
    @if(!$cobradorSelObj)
    <div class="mon-main" style="padding:52px 20px;text-align:center;color:var(--text3)">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" style="margin:0 auto 12px;display:block;opacity:.3"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7"/></svg>
        Selecciona un cobrador en el panel izquierdo.
    </div>
    @else
    @php
        $csel     = $cobradorSelObj;
        $palette  = ['#3b82f6','#6366f1','#8b5cf6','#ec4899','#10b981','#f59e0b','#ef4444','#0ea5e9','#14b8a6'];
        $colorSel = $palette[crc32($csel->nombre) % count($palette)];
        $pctHoy   = $csel->total_hoy > 0 ? round($csel->cobrados / $csel->total_hoy * 100) : 0;
        $totalLoans = $csel->loans_hoy->count() + $csel->loans_otros->count();
        // Total expected today
        $totalEsperado = $csel->loans_hoy->sum('cuota_hoy');
    @endphp

    <div class="mon-main">

        {{-- Profile header --}}
        <div class="mon-profile">
            <div class="mon-profile-band" style="background:{{ $colorSel }}"></div>
            <div class="mon-profile-inner">
                <div style="display:flex;align-items:center;gap:14px">
                    <div class="mon-profile-av" style="background:{{ $colorSel }}">
                        {{ strtoupper(substr($csel->nombre,0,2)) }}
                    </div>
                    <div>
                        <div class="mon-profile-name">{{ $csel->nombre }}</div>
                        <div class="mon-profile-sub">
                            {{ $csel->rango ?? 'Cobrador' }}
                            @if($csel->total_hoy > 0)
                             · {{ $csel->cobrados }}/{{ $csel->total_hoy }} cobros completados hoy
                            @else
                             · Sin cobros programados hoy
                            @endif
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;align-items:center">
                    @if($csel->celular)
                    <a href="https://wa.me/52{{ preg_replace('/\D/','',$csel->celular) }}" target="_blank"
                       class="btn btn-sm" style="background:#dcfce7;color:#16a34a">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px"><path d="M10 0C4.477 0 0 4.477 0 10c0 1.763.46 3.417 1.264 4.857L0 20l5.285-1.384A9.958 9.958 0 0010 20c5.523 0 10-4.477 10-10S15.523 0 10 0zm0 18.182a8.173 8.173 0 01-4.163-1.136l-.298-.178-3.136.822.837-3.056-.195-.314A8.182 8.182 0 1110 18.182zm4.504-6.13c-.247-.124-1.463-.722-1.69-.804-.227-.083-.392-.124-.557.124-.165.247-.638.804-.782.969-.144.165-.288.185-.535.062-.247-.124-1.043-.384-1.987-1.225-.734-.655-1.23-1.464-1.374-1.71-.144-.247-.015-.381.108-.504.111-.11.247-.288.37-.432.124-.144.165-.247.247-.412.083-.165.041-.309-.02-.432-.062-.124-.557-1.343-.763-1.838-.2-.483-.405-.418-.557-.426l-.474-.008c-.165 0-.432.062-.659.309-.227.247-.866.846-.866 2.063s.887 2.392 1.011 2.557c.124.165 1.746 2.665 4.231 3.737.591.255 1.052.407 1.411.521.593.188 1.132.162 1.559.098.475-.071 1.463-.598 1.669-1.176.206-.577.206-1.072.144-1.176-.062-.103-.227-.165-.474-.289z"/></svg>
                        WhatsApp
                    </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="mon-stats">
            <div class="mon-stat">
                <div class="mon-stat-val" style="color:#16a34a">{{ $csel->cobrados }}</div>
                <div class="mon-stat-lbl">Cobrados hoy</div>
            </div>
            <div class="mon-stat">
                <div class="mon-stat-val" style="color:{{ $csel->pendientes > 0 ? '#f59e0b' : 'var(--text)' }}">{{ $csel->pendientes }}</div>
                <div class="mon-stat-lbl">Pendientes</div>
            </div>
            <div class="mon-stat">
                <div class="mon-stat-val">{{ $totalLoans }}</div>
                <div class="mon-stat-lbl">Total asignados</div>
            </div>
        </div>

        {{-- Progress bar --}}
        @if($csel->total_hoy > 0)
        <div class="mon-progress-wrap">
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px">
                <span style="color:var(--text3);font-weight:600">Progreso de hoy</span>
                <span style="font-weight:700;color:{{ $pctHoy >= 100 ? '#16a34a' : 'var(--text2)' }}">{{ $pctHoy }}%</span>
            </div>
            <div class="mon-progress-track">
                <div class="mon-progress-done" style="width:{{ $pctHoy }}%;background:{{ $pctHoy >= 100 ? 'linear-gradient(90deg,#16a34a,#22c55e)' : 'linear-gradient(90deg,#3b82f6,#6366f1)' }}"></div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text3);padding-bottom:10px">
                <span>${{ number_format($totalEsperado, 0, '.', ',') }} esperados hoy</span>
                <span>{{ $csel->loans_hoy->count() }} préstamos</span>
            </div>
        </div>
        @endif

        {{-- Loan list --}}
        @if($csel->loans_hoy->isEmpty() && $csel->loans_otros->isEmpty())
        <div class="mon-empty">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" style="margin:0 auto 12px;display:block;opacity:.25"><path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2h9"/></svg>
            <div style="font-size:14px;font-weight:600;color:var(--text2);margin-bottom:4px">Sin préstamos asignados</div>
            <div style="font-size:12px">Este cobrador no tiene préstamos en su cartera.</div>
        </div>
        @else

        {{-- HOY & ATRASADOS --}}
        @if($csel->loans_hoy->isNotEmpty())
        <div class="mon-section-lbl">
            <span>Cobros de hoy &amp; atrasados</span>
            <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;color:var(--text2)">{{ $csel->loans_hoy->count() }} préstamos</span>
        </div>
        @foreach($csel->loans_hoy as $loan)
        @php
            $yaPago  = $loan->pagado_hoy;
            $atrasado = $loan->estatus === 'Atrasado' && !$yaPago;
        @endphp
        <div class="mon-row {{ $yaPago ? 'pagado' : '' }}" id="loan-row-{{ $loan->id }}">

            {{-- Check --}}
            <div class="mon-chk {{ $yaPago ? 'done' : '' }}" id="chk-{{ $loan->id }}"
                 onclick="{{ $yaPago ? '' : "registrarCobro({$loan->id},{$loan->pago_id_hoy},{$loan->cuota_hoy},{$loan->mora})" }}"
                 title="{{ $yaPago ? 'Cobrado hoy' : 'Registrar cobro' }}"
                 style="{{ $yaPago ? 'cursor:default' : '' }}">
                <svg viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M2 6.5l3.5 3.5 5.5-6"/></svg>
            </div>

            {{-- Info --}}
            <div style="min-width:0">
                <div class="mon-client-name">{{ $loan->cliente?->nombre ?? '—' }}</div>
                <div class="mon-client-sub">
                    <span>#{{ $loan->id }}</span>
                    @if($loan->proximo_pago)
                    <span>Vence {{ \Carbon\Carbon::parse($loan->proximo_pago)->format('d/m/Y') }}</span>
                    @endif
                    @if($loan->mora > 0)
                    <span class="mon-mora-chip">
                        <svg viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="2" style="width:8px;height:8px"><path d="M5 2v3l2 2"/><circle cx="5" cy="5" r="4"/></svg>
                        +${{ number_format($loan->mora,0) }} mora
                    </span>
                    @endif
                </div>
            </div>

            {{-- Amount --}}
            <div class="mon-amount">${{ number_format($loan->cuota_hoy,0,'.',',') }}</div>

            {{-- Status --}}
            <div class="mon-status">
                @if($yaPago)
                <span class="mon-status-chip" style="background:#dcfce7;color:#16a34a">
                    <svg viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:9px;height:9px"><path d="M1.5 5l2.5 2.5 4.5-5"/></svg>
                    Cobrado
                </span>
                @elseif($atrasado)
                <span class="mon-status-chip" style="background:#fee2e2;color:#dc2626">Atrasado</span>
                @else
                <span class="mon-status-chip" style="background:#fef9c3;color:#854d0e">Pendiente</span>
                @endif
            </div>

            {{-- Link --}}
            <div>
                <a href="{{ route('prestamos.show', $loan->id) }}" target="_blank" class="mon-link" title="Ver préstamo">
                    <svg viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" style="width:13px;height:13px"><path d="M4.5 2H3a1 1 0 00-1 1v7a1 1 0 001 1h7a1 1 0 001-1V8.5M8 2h3v3M5.5 7.5L11 2"/></svg>
                </a>
            </div>
        </div>
        @endforeach
        @endif

        {{-- PRÓXIMOS --}}
        @if($csel->loans_otros->isNotEmpty())
        <div class="mon-section-lbl">
            <span>Próximos cobros</span>
            <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;color:var(--text2)">{{ $csel->loans_otros->count() }} préstamos</span>
        </div>
        @foreach($csel->loans_otros as $loan)
        <div class="mon-row futuro">
            <div class="mon-chk future-chk"></div>
            <div style="min-width:0">
                <div class="mon-client-name">{{ $loan->cliente?->nombre ?? '—' }}</div>
                <div class="mon-client-sub">
                    <span>#{{ $loan->id }}</span>
                    @if($loan->proximo_pago)
                    <span>Vence {{ \Carbon\Carbon::parse($loan->proximo_pago)->format('d/m/Y') }}</span>
                    @endif
                </div>
            </div>
            <div class="mon-amount">${{ number_format($loan->cuota_hoy,0,'.',',') }}</div>
            <div class="mon-status">
                <span class="mon-status-chip" style="background:#f3f4f6;color:var(--text2)">Futuro</span>
            </div>
            <div>
                <a href="{{ route('prestamos.show', $loan->id) }}" target="_blank" class="mon-link">
                    <svg viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" style="width:13px;height:13px"><path d="M4.5 2H3a1 1 0 00-1 1v7a1 1 0 001 1h7a1 1 0 001-1V8.5M8 2h3v3M5.5 7.5L11 2"/></svg>
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

{{-- ── Modal: confirmar cobro ─────────────────────────────────── --}}
<div class="ow-modal-overlay" id="modalPago">
    <div class="ow-modal">
        <div class="ow-modal-header">
            <div>
                <div class="ow-modal-title">Registrar cobro</div>
                <div style="font-size:12px;color:var(--text3);margin-top:2px" id="mpCliente">—</div>
            </div>
            <button class="ow-modal-close" onclick="cerrarModal()">&times;</button>
        </div>

        <div class="ow-modal-body">
            {{-- Cuota programada card --}}
            <div style="background:#f9fafb;border:1px solid var(--border);border-radius:10px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between">
                <div>
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:4px">Cuota programada</div>
                    <div style="font-size:24px;font-weight:800;font-family:monospace;color:var(--text);line-height:1" id="mpCuota">—</div>
                    <div id="mpMoraRow" style="font-size:12px;color:#c2410c;margin-top:4px;display:none">
                        + <span id="mpMora">$0</span> mora
                    </div>
                </div>
                <button type="button" id="mpBtnFill"
                    style="background:#eff6ff;color:var(--accent);border:none;padding:8px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer"
                    onclick="fillExact()">
                    Cobro exacto
                </button>
            </div>

            <div class="ow-field">
                <label>Monto cobrado ($)</label>
                <input type="number" id="mpMonto" step="0.01" min="0.01" placeholder="0.00"
                       style="font-size:18px;font-weight:700">
            </div>

            <div class="ow-field">
                <label style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3)">Nota (opcional)</label>
                <input type="text" id="mpNota" maxlength="200" placeholder="ej. Pagó en efectivo"
                       style="font-size:13px;font-family:var(--font)">
            </div>
        </div>

        <div class="ow-modal-footer">
            <button type="button" class="btn" style="background:#f3f4f6;color:var(--text)" onclick="cerrarModal()">Cancelar</button>
            <button type="button" class="btn btn-primary" id="mpBtnConfirmar" onclick="confirmarPago()">
                <svg viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:12px;height:12px"><path d="M2 6.5l3.5 3.5 5.5-6"/></svg>
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
    pendingLoan = { loanId, pagoId, cuota: parseFloat(cuota), mora: parseFloat(mora) };
    const row    = document.getElementById('loan-row-' + loanId);
    const nombre = row?.querySelector('.mon-client-name')?.textContent?.trim() || '—';

    document.getElementById('mpCliente').textContent = nombre;
    document.getElementById('mpCuota').textContent   = '$' + parseFloat(cuota).toLocaleString('es-MX',{minimumFractionDigits:2});

    const moraRow = document.getElementById('mpMoraRow');
    if (mora > 0) {
        document.getElementById('mpMora').textContent = '$' + parseFloat(mora).toLocaleString('es-MX',{minimumFractionDigits:2});
        moraRow.style.display = '';
    } else {
        moraRow.style.display = 'none';
    }

    document.getElementById('mpMonto').value = (parseFloat(cuota) + parseFloat(mora)).toFixed(2);
    document.getElementById('mpNota').value  = '';

    const btn = document.getElementById('mpBtnConfirmar');
    btn.disabled = false;
    btn.innerHTML = '<svg viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:12px;height:12px"><path d="M2 6.5l3.5 3.5 5.5-6"/></svg> Confirmar cobro';

    document.getElementById('modalPago').classList.add('open');
    setTimeout(() => document.getElementById('mpMonto').select(), 80);
}

function fillExact() {
    if (!pendingLoan) return;
    document.getElementById('mpMonto').value = (pendingLoan.cuota + pendingLoan.mora).toFixed(2);
    document.getElementById('mpMonto').focus();
}

function cerrarModal() {
    document.getElementById('modalPago').classList.remove('open');
    pendingLoan = null;
}

document.getElementById('modalPago')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});

// Enter key confirms
document.getElementById('mpMonto')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') confirmarPago();
});

async function confirmarPago() {
    if (!pendingLoan) return;
    const monto = parseFloat(document.getElementById('mpMonto').value);
    const input = document.getElementById('mpMonto');
    if (!monto || monto <= 0) {
        input.style.borderColor = '#ef4444';
        input.focus();
        setTimeout(() => input.style.borderColor = '', 1500);
        return;
    }
    const nota = document.getElementById('mpNota').value.trim();
    const btn  = document.getElementById('mpBtnConfirmar');
    btn.disabled = true;
    btn.textContent = 'Registrando…';

    const payload = {
        [pendingLoan.loanId]: {
            tipo: monto >= (pendingLoan.cuota + pendingLoan.mora) ? 'completo' : 'parcial',
            monto,
            nota
        }
    };

    try {
        const resp = await fetch('{{ route('cobros.registrar') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (data.ok) {
            // Update row UI
            const chk = document.getElementById('chk-' + pendingLoan.loanId);
            const row = document.getElementById('loan-row-' + pendingLoan.loanId);
            if (chk) { chk.classList.add('done'); chk.style.cursor = 'default'; chk.onclick = null; }
            if (row) {
                row.classList.add('pagado');
                const statusEl = row.querySelector('.mon-status');
                if (statusEl) statusEl.innerHTML = '<span class="mon-status-chip" style="background:#dcfce7;color:#16a34a"><svg viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:9px;height:9px"><path d="M1.5 5l2.5 2.5 4.5-5"/></svg> Cobrado</span>';
            }
            cerrarModal();
            // Update KPI counters
            const cobEl  = document.querySelector('.mon-stat:nth-child(1) .mon-stat-val');
            const pendEl = document.querySelector('.mon-stat:nth-child(2) .mon-stat-val');
            if (cobEl)  cobEl.textContent  = parseInt(cobEl.textContent  || '0') + 1;
            if (pendEl) pendEl.textContent = Math.max(0, parseInt(pendEl.textContent || '1') - 1);
            // Update progress bar
            const fill = document.querySelector('.mon-progress-done');
            const pct  = document.querySelector('.mon-progress-wrap span:last-child[style*="font-weight:700"]');
            if (fill) {
                const total  = parseInt(cobEl?.textContent || 0) + Math.max(0, parseInt(pendEl?.textContent || 0));
                const cobrados = parseInt(cobEl?.textContent || 0);
                const newPct = total > 0 ? Math.round(cobrados / total * 100) : 0;
                fill.style.width = newPct + '%';
                if (pct) pct.textContent = newPct + '%';
            }
        } else {
            alert('Error: ' + (data.error || 'No se pudo registrar el cobro.'));
            btn.disabled = false;
            btn.innerHTML = '<svg viewBox="0 0 13 13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:12px;height:12px"><path d="M2 6.5l3.5 3.5 5.5-6"/></svg> Confirmar cobro';
        }
    } catch(e) {
        alert('Error de conexión. Verifica tu red e intenta de nuevo.');
        btn.disabled = false;
    }
}
</script>
@endpush

@endsection
