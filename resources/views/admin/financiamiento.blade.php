@extends('layouts.app')

@section('title', 'Cartera financiada')

@push('styles')
<style>
.cf-kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:22px}
.cf-kpi{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;position:relative;overflow:hidden;box-shadow:var(--shadow-sm)}
.cf-kpi-accent{position:absolute;top:0;left:0;width:3px;height:100%}
.cf-kpi-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:6px}
.cf-kpi-value{font-size:21px;font-weight:800;letter-spacing:-.03em;color:var(--text);line-height:1.15;font-variant-numeric:tabular-nums}
.cf-kpi-sub{font-size:11px;color:var(--text2);margin-top:4px}

.cf-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:14px;overflow:hidden;box-shadow:var(--shadow-sm)}
.cf-top{display:flex;align-items:center;gap:14px;padding:18px 22px;flex-wrap:wrap}
.cf-titulo{font-size:16px;font-weight:800;color:var(--text)}
.cf-sub{font-size:12px;color:var(--text2);margin-top:2px}
.cf-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:99px;font-size:11px;font-weight:700;white-space:nowrap}
.cf-pill-ok{background:#f0fdf4;color:#10b981}
.cf-pill-pend{background:#fff7ed;color:#ea580c}
.cf-pill-late{background:#fef2f2;color:#dc2626}
.cf-pago-box{margin-left:auto;text-align:right}
.cf-pago-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)}
.cf-pago{font-size:24px;font-weight:800;letter-spacing:-.03em;font-variant-numeric:tabular-nums}
.cf-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:0;border-top:1px solid var(--border)}
.cf-stat{padding:13px 22px;border-right:1px solid var(--border)}
.cf-stat:last-child{border-right:none}
.cf-stat-k{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:3px}
.cf-stat-v{font-size:14px;font-weight:800;color:var(--text);font-variant-numeric:tabular-nums}
.cf-chart-box{border-top:1px solid var(--border);padding:16px 22px}
.cf-chart-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:3px}
.cf-chart-sub{font-size:11px;color:var(--text2);margin-bottom:10px}
.cf-hist-toggle{background:none;border:none;color:var(--text3);font-size:11px;font-weight:700;cursor:pointer;padding:11px 22px;width:100%;text-align:left;font-family:var(--font);border-top:1px solid var(--border)}
.cf-hist-toggle:hover{color:var(--text2)}
.cf-hist{display:none}
.cf-hist.open{display:block}
.cf-hist table{width:100%;border-collapse:collapse}
.cf-hist th{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);text-align:left;padding:8px 22px;background:#f9fafb;border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.cf-hist td{font-size:12px;color:var(--text);padding:9px 22px;border-bottom:1px solid var(--border);font-variant-numeric:tabular-nums}
.cf-hist tr:last-child td{border-bottom:none}
@media(max-width:760px){.cf-kpi-grid{grid-template-columns:1fr}.cf-stats{grid-template-columns:1fr 1fr}.cf-pago-box{margin-left:0;text-align:left;width:100%}}
</style>
@endpush

@section('content')

@php
    $money = fn($v) => '$' . number_format($v, 2);
    $fmtPct = fn($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
    $fecha  = fn($d) => ucfirst($d->locale('es')->isoFormat('dddd D [de] MMMM') . ($d->year !== now()->year ? $d->format(' Y') : ''));
    $prox   = $resumen['proximo'];
@endphp

<div style="margin-bottom:22px">
    <h2 style="font-size:22px;font-weight:800;letter-spacing:-.03em;margin-bottom:3px">Cartera financiada</h2>
    <p style="font-size:13px;color:var(--text2)">El capital de inversión que trabajas por separado de tu cartera. Cada periodo pagas el rendimiento pactado; el supervisor registra los pagos.</p>
</div>

{{-- ── Resumen ─────────────────────────────────────────────── --}}
<div class="cf-kpi-grid">
    <div class="cf-kpi">
        <div class="cf-kpi-accent" style="background:#10b981"></div>
        <div class="cf-kpi-label">Capital en inversión</div>
        <div class="cf-kpi-value">{{ $money($resumen['capital']) }}</div>
        <div class="cf-kpi-sub">capital financiado con el que trabajas hoy</div>
    </div>
    <div class="cf-kpi">
        <div class="cf-kpi-accent" style="background:{{ $resumen['atrasados'] > 0 ? '#dc2626' : '#6366f1' }}"></div>
        <div class="cf-kpi-label">{{ $resumen['atrasados'] > 0 ? 'Pagos vencidos' : 'Próximo pago' }}</div>
        @if($resumen['atrasados'] > 0)
            <div class="cf-kpi-value" style="color:#dc2626">{{ $money($resumen['monto_atrasado']) }}</div>
            <div class="cf-kpi-sub">{{ $resumen['atrasados'] }} pago{{ $resumen['atrasados'] === 1 ? '' : 's' }} pendiente{{ $resumen['atrasados'] === 1 ? '' : 's' }} desde el {{ $prox->fecha->format('d/m/Y') }}</div>
        @else
            <div class="cf-kpi-value" style="font-size:17px">{{ $prox->parcial ? 'Hoy (completar pago)' : $fecha($prox->fecha) }}</div>
            <div class="cf-kpi-sub">{{ $money($prox->monto) }} de rendimiento{{ $prox->cuenta > 1 ? ' · suma de ' . $prox->cuenta . ' inversiones' : '' }}</div>
        @endif
    </div>
    <div class="cf-kpi">
        <div class="cf-kpi-accent" style="background:#0ea5e9"></div>
        <div class="cf-kpi-label">Pagado histórico</div>
        <div class="cf-kpi-value">{{ $money($resumen['pagado']) }}</div>
        <div class="cf-kpi-sub">rendimientos pagados desde el inicio</div>
    </div>
</div>

{{-- ── Cuentas ─────────────────────────────────────────────── --}}
@foreach($financiamientos as $f)
@php
    $e = $f->estado;
    $perLbl = $f->periodo_label;
@endphp
<div class="cf-card">
    <div class="cf-top">
        <div>
            <div class="cf-titulo">Financiamiento {{ $f->frecuencia }} al {{ $fmtPct($f->rendimiento_pct) }}%</div>
            <div class="cf-sub">
                inició el {{ $f->fecha_inicio->format('d/m/Y') }} · convenio {{ $f->plazo_meses }} meses ·
                pagas el {{ $fmtPct($f->rendimiento_pct) }}% del capital cada {{ $perLbl }}
            </div>
            <div style="margin-top:7px">
                @if($e->parcial)
                    <span class="cf-pill cf-pill-pend">Pago parcial · restan {{ $money($e->restante) }}</span>
                @elseif($e->atrasados > 1)
                    <span class="cf-pill cf-pill-late">{{ $e->atrasados }} pagos vencidos · {{ $money($e->monto_atrasado) }}</span>
                @elseif($e->atrasados === 1)
                    <span class="cf-pill cf-pill-late">Pago vencido desde el {{ $e->proximo->format('d/m/Y') }}</span>
                @else
                    <span class="cf-pill cf-pill-ok">✓ Al corriente</span>
                @endif
                @if($f->pagos->isNotEmpty())
                    <span style="font-size:11px;color:var(--text3);margin-left:6px">último pago: {{ $money($f->pagos->first()->monto) }} el {{ $f->pagos->first()->fecha->format('d/m/Y') }}</span>
                @endif
            </div>
        </div>
        <div class="cf-pago-box">
            <div class="cf-pago-label">{{ $e->parcial ? 'Resta por pagar' : 'Pago del ' . $e->proximo->format('d/m') }}</div>
            <div class="cf-pago" style="color:{{ $e->atrasados > 0 ? '#dc2626' : '#10b981' }}">{{ $money($e->restante) }}</div>
            @if($e->cobrado > 0)
                <div style="font-size:11px;color:var(--text3)">esperado {{ $money($e->esperado) }} − pagado {{ $money($e->cobrado) }}</div>
            @endif
        </div>
    </div>

    <div class="cf-stats">
        <div class="cf-stat">
            <div class="cf-stat-k">Capital en inversión</div>
            <div class="cf-stat-v">{{ $money($f->capital_actual) }}</div>
        </div>
        <div class="cf-stat">
            <div class="cf-stat-k">Rendimiento por {{ $perLbl }}</div>
            <div class="cf-stat-v">{{ $money($f->rendimiento_periodo) }}</div>
        </div>
        <div class="cf-stat">
            <div class="cf-stat-k">Pagos realizados</div>
            <div class="cf-stat-v">{{ $f->pagos->count() }}</div>
        </div>
        <div class="cf-stat">
            <div class="cf-stat-k">Total pagado</div>
            <div class="cf-stat-v">{{ $money($f->pagos->sum('monto')) }}</div>
        </div>
    </div>

    <div class="cf-chart-box">
        <div class="cf-chart-title">Rendimiento acumulado · esperado vs pagado</div>
        <div class="cf-chart-sub">La línea punteada es lo que se espera según la tasa; la verde es lo que has pagado.</div>
        <div style="position:relative;height:200px;width:100%;min-width:0"><canvas id="cfChart{{ $f->id }}"></canvas></div>
    </div>

    <button type="button" class="cf-hist-toggle" onclick="document.getElementById('cfHist{{ $f->id }}').classList.toggle('open')">
        Ver historial de pagos ({{ $f->pagos->count() }}) ▾
    </button>
    <div class="cf-hist" id="cfHist{{ $f->id }}">
        @if($f->pagos->isEmpty())
            <div style="padding:16px 22px;font-size:12px;color:var(--text3);border-top:1px solid var(--border)">Todavía no hay pagos registrados.</div>
        @else
        <table>
            <thead><tr><th>Fecha</th><th>Monto pagado</th><th>Nota</th></tr></thead>
            <tbody>
            @foreach($f->pagos->take(20) as $p)
                <tr>
                    <td>{{ $p->fecha->format('d/m/Y') }}</td>
                    <td style="font-weight:700">{{ $money($p->monto) }}</td>
                    <td style="color:var(--text2)">{{ $p->nota ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endforeach

<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
const cfFmt  = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
const cfFmt0 = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
const cfGraficas = @json($financiamientos->mapWithKeys(fn($f) => [$f->id => $f->grafica]));

// Línea vertical punteada en "Hoy"
function cfHoyLinePlugin(labels) {
    const idx = labels.indexOf('Hoy');
    return {
        id: 'cfHoyLine',
        afterDatasetsDraw(chart) {
            if (idx < 0) return;
            const x = chart.scales.x.getPixelForValue(idx);
            const { top, bottom } = chart.chartArea;
            const ctx = chart.ctx;
            ctx.save();
            ctx.strokeStyle = 'rgba(99,102,241,.5)';
            ctx.setLineDash([4, 4]);
            ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(x, top); ctx.lineTo(x, bottom); ctx.stroke();
            ctx.fillStyle = 'rgba(99,102,241,.85)';
            ctx.font = '700 9px Sora';
            ctx.textAlign = 'center';
            ctx.fillText('HOY', x, top + 10);
            ctx.restore();
        },
    };
}

Object.entries(cfGraficas).forEach(([id, g]) => {
    new Chart(document.getElementById('cfChart' + id), {
        type: 'line',
        data: {
            labels: g.labels,
            datasets: [
                { label: 'Esperado (según la tasa)', data: g.teorico, borderColor: '#94a3b8', backgroundColor: 'transparent',
                  borderDash: [6, 4], tension: .2, pointRadius: 0, borderWidth: 2 },
                { label: 'Pagado', data: g.real, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.10)',
                  fill: true, tension: .15, borderWidth: 2.5, spanGaps: false,
                  pointRadius: ctx => g.cobros[ctx.dataIndex] ? 4 : 0,
                  pointHoverRadius: 6, pointBackgroundColor: '#10b981', pointBorderColor: '#fff', pointBorderWidth: 1.5 },
            ],
        },
        options: {
            maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, font: { size: 10, family: 'Sora' }, padding: 10 } },
                tooltip: { callbacks: {
                    label: c => c.parsed.y === null ? null : ' ' + c.dataset.label + ': ' + cfFmt.format(c.parsed.y),
                    afterBody: items => (items.some(i => i.datasetIndex === 1) && g.cobros[items[0].dataIndex]) ? ['● Pago registrado este día'] : [],
                } },
            },
            scales: {
                y: { ticks: { font: { size: 9, family: 'Sora' }, callback: v => cfFmt0.format(v) }, grid: { color: 'rgba(15,22,35,.05)' } },
                x: { ticks: { font: { size: 9, family: 'Sora' }, maxTicksLimit: 12 }, grid: { display: false } },
            },
        },
        plugins: [cfHoyLinePlugin(g.labels)],
    });
});
</script>

@endsection
