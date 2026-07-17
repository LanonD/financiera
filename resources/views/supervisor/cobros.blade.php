@extends('layouts.app')

@section('title', 'Cobros a admins')

@push('styles')
<style>
.sup-header{margin-bottom:22px}
.sup-kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:22px}
.sup-kpi{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;position:relative;overflow:hidden;box-shadow:var(--shadow-sm)}
.sup-kpi-accent{position:absolute;top:0;left:0;width:3px;height:100%}
.sup-kpi-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text3);margin-bottom:6px}
.sup-kpi-value{font-size:22px;font-weight:800;letter-spacing:-.03em;color:var(--text);line-height:1;font-variant-numeric:tabular-nums}
.sup-kpi-sub{font-size:11px;color:var(--text2);margin-top:4px}

.sup-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:14px;overflow:hidden;box-shadow:var(--shadow-sm)}
.sup-card.cobrado{border-color:rgba(16,185,129,.35)}
.sup-card-top{display:flex;align-items:center;gap:14px;padding:18px 22px;flex-wrap:wrap}
.sup-avatar{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:800;color:#fff;flex-shrink:0}
.sup-nombre{font-size:16px;font-weight:800;color:var(--text)}
.sup-sub{font-size:12px;color:var(--text2);margin-top:2px}
.sup-monto-box{margin-left:auto;text-align:right}
.sup-monto-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text3)}
.sup-monto{font-size:24px;font-weight:800;letter-spacing:-.03em;color:#10b981;font-variant-numeric:tabular-nums}
.sup-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:99px;font-size:11px;font-weight:700;white-space:nowrap}
.sup-pill-ok{background:#f0fdf4;color:#10b981}
.sup-pill-pend{background:#fff7ed;color:#ea580c}
.sup-pill-late{background:#fef2f2;color:#dc2626}
.sup-prox{font-size:12px;color:var(--text2);margin-top:5px}
.sup-prox b{color:var(--text)}

.sup-form{display:flex;gap:10px;align-items:end;padding:16px 22px;border-top:1px dashed var(--border);background:#fcfcfb;flex-wrap:wrap}
.sup-field{flex:1;min-width:130px}
.sup-field label{display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);margin-bottom:4px}
.sup-field input{width:100%;padding:9px 12px;background:#fff;border:1.5px solid var(--border);border-radius:8px;font-size:14px;font-family:var(--font);color:var(--text);outline:none;transition:border-color .15s,box-shadow .15s}
.sup-field input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(16,185,129,.12)}
.sup-btn{background:var(--accent);color:#fff;border:none;border-radius:8px;padding:10px 20px;font-size:13px;font-weight:700;cursor:pointer;font-family:var(--font);white-space:nowrap;transition:background .15s}
.sup-btn:hover{background:var(--accent-hover)}
.sup-usar{background:#eff6ff;color:#1d4ed8;border:none;border-radius:6px;padding:3px 9px;font-size:10px;font-weight:700;cursor:pointer;white-space:nowrap}
.sup-usar:hover{background:#dbeafe}

.sup-hist-toggle{background:none;border:none;color:var(--text3);font-size:11px;font-weight:700;cursor:pointer;padding:10px 22px;width:100%;text-align:left;font-family:var(--font)}
.sup-hist-toggle:hover{color:var(--text2)}
.sup-hist{display:none;border-top:1px solid var(--border)}
.sup-hist.open{display:block}
.sup-hist table{width:100%;border-collapse:collapse}
.sup-hist th{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);text-align:left;padding:8px 22px;background:#f9fafb;border-bottom:1px solid var(--border)}
.sup-hist td{font-size:12px;color:var(--text);padding:9px 22px;border-bottom:1px solid var(--border);font-variant-numeric:tabular-nums}
.sup-hist tr:last-child td{border-bottom:none}

.sup-empty{background:var(--card);border:1px dashed var(--border);border-radius:var(--radius);padding:46px;text-align:center;color:var(--text3);font-size:13px}
@media(max-width:640px){.sup-kpi-grid{grid-template-columns:1fr}.sup-monto-box{margin-left:0;text-align:left;width:100%}}
</style>
@endpush

@section('content')

@php
    $money  = fn($v) => '$' . number_format($v, 2);
    $fmtPct = fn($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
    $fecha  = fn($d) => ucfirst($d->locale('es')->isoFormat('dddd D [de] MMMM') . ($d->year !== now()->year ? $d->format(' Y') : ''));
    $avatarColors = ['#10b981','#6366f1','#f59e0b','#ec4899','#14b8a6','#8b5cf6','#ef4444','#0ea5e9'];
    $hoy = now()->startOfDay();
@endphp

<div class="sup-header">
    <h2 style="font-size:22px;font-weight:800;letter-spacing:-.03em;margin-bottom:3px">Cobros a administradores</h2>
    <p style="font-size:13px;color:var(--text2)">Cada cuenta tiene su propio calendario: el primer cobro toca una semana (o un mes) después del inicio del financiamiento y de ahí en adelante cada periodo. Registra aquí lo que cobres; el sistema reparte los retornos y la reinversión.</p>
</div>

@if($errors->any())
    <div class="alert alert-error" style="margin-bottom:14px">
        @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
    </div>
@endif

{{-- ── Resumen ─────────────────────────────────────────────── --}}
<div class="sup-kpi-grid">
    <div class="sup-kpi">
        <div class="sup-kpi-accent" style="background:#6366f1"></div>
        <div class="sup-kpi-label">Siguiente cobro</div>
        @if($resumen['siguiente'])
            @php $s = $resumen['siguiente']; $sNombre = $s->admin->alias ?: ($s->admin->nombre ?: $s->admin->usuario); @endphp
            <div class="sup-kpi-value" style="font-size:17px">{{ $s->parcial ? 'Hoy (completar cobro)' : $fecha($s->proximo_cobro) }}</div>
            <div class="sup-kpi-sub">a <b>{{ $sNombre }}</b> · {{ $money($s->restante_periodo) }}{{ !$s->parcial && $s->cobros_atrasados > 0 ? ' · ya venció' : '' }}</div>
        @else
            <div class="sup-kpi-value">—</div>
            <div class="sup-kpi-sub">sin cuentas activas</div>
        @endif
    </div>
    <div class="sup-kpi">
        <div class="sup-kpi-accent" style="background:#dc2626"></div>
        <div class="sup-kpi-label">Cobros atrasados</div>
        <div class="sup-kpi-value" style="color:{{ $resumen['atrasadas'] > 0 ? '#dc2626' : 'var(--text)' }}">{{ $money($resumen['monto_atrasado']) }}</div>
        <div class="sup-kpi-sub">{{ $resumen['atrasadas'] > 0 ? $resumen['atrasadas'] . ' de ' . $resumen['cuentas'] . ' cuenta' . ($resumen['cuentas'] === 1 ? '' : 's') . ' con cobros vencidos' : 'todas las cuentas al corriente' }}</div>
    </div>
    <div class="sup-kpi">
        <div class="sup-kpi-accent" style="background:#10b981"></div>
        <div class="sup-kpi-label">Cobrado este mes</div>
        <div class="sup-kpi-value">{{ $money($resumen['cobrado_mes']) }}</div>
        <div class="sup-kpi-sub">suma de los cobros registrados en el mes</div>
    </div>
</div>

{{-- ── Tarjetas por admin ──────────────────────────────────── --}}
@forelse($financiamientos as $f)
@php
    $color   = $avatarColors[$f->admin_id % count($avatarColors)];
    $nombre  = $f->admin->alias ?: ($f->admin->nombre ?: $f->admin->usuario);
    $perLbl  = $f->periodo_label;
@endphp
<div class="sup-card {{ $f->cobros_atrasados === 0 ? 'cobrado' : '' }}" id="supCard{{ $f->id }}">
    <div class="sup-card-top">
        <div class="sup-avatar" style="background:{{ $color }}">{{ strtoupper(substr($nombre, 0, 1)) }}</div>
        <div>
            <div class="sup-nombre">{{ $nombre }}</div>
            <div class="sup-sub">
                se cobra cada {{ $perLbl }} · {{ $fmtPct($f->rendimiento_pct) }}% sobre {{ $money($f->capital_actual) }}
                · inició el {{ $f->fecha_inicio->format('d/m/Y') }}
                @if($f->admin->celular) · 📱 {{ $f->admin->celular }} @endif
            </div>
            <div class="sup-prox">
                @if($f->parcial)
                    Cobro parcial del periodo en curso: llevas <b>{{ $money($f->cobrado_periodo) }}</b> de {{ $money($f->esperado_periodo) }}
                @elseif($f->cobros_atrasados > 0)
                    Cobro pendiente desde el <b>{{ $fecha($f->proximo_cobro) }}</b>
                @else
                    Próximo cobro: <b>{{ $fecha($f->proximo_cobro) }}</b>
                @endif
            </div>
            <div style="margin-top:6px">
                @if($f->parcial)
                    <span class="sup-pill sup-pill-pend">Cobro parcial · restan {{ $money($f->restante_periodo) }}</span>
                @elseif($f->cobros_atrasados > 1)
                    <span class="sup-pill sup-pill-late">Atrasado · {{ $f->cobros_atrasados }} cobros vencidos</span>
                @elseif($f->cobros_atrasados === 1)
                    <span class="sup-pill {{ $f->proximo_cobro->eq($hoy) ? 'sup-pill-pend' : 'sup-pill-late' }}">
                        {{ $f->proximo_cobro->eq($hoy) ? 'Toca cobrar hoy' : 'Atrasado · 1 cobro vencido' }}
                    </span>
                @else
                    <span class="sup-pill sup-pill-ok">✓ Al corriente</span>
                @endif
                @if($f->ultimo_cobro)
                    <span style="font-size:11px;color:var(--text3);margin-left:6px">último cobro: {{ $money($f->ultimo_cobro->monto) }} el {{ $f->ultimo_cobro->fecha->format('d/m/Y') }}</span>
                @endif
            </div>
        </div>
        <div class="sup-monto-box">
            <div class="sup-monto-label">{{ $f->parcial ? 'Resta por cobrar' : 'Cobro del ' . $f->proximo_cobro->format('d/m') }}</div>
            <div class="sup-monto">{{ $money($f->restante_periodo) }}</div>
            @if($f->parcial || $f->cobrado_periodo > 0)
                <div style="font-size:11px;color:var(--text3)">esperado {{ $money($f->esperado_periodo) }} − cobrado {{ $money($f->cobrado_periodo) }}</div>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('supervisor.cobros.rendimiento', $f->id) }}" class="sup-form">
        @csrf
        <div class="sup-field">
            <label style="display:flex;align-items:center;justify-content:space-between">
                <span>Monto cobrado</span>
                <button type="button" class="sup-usar" onclick="supUsar({{ $f->id }}, {{ $f->restante_periodo }})">usar {{ $money($f->restante_periodo) }}</button>
            </label>
            <input type="number" name="monto" id="supMonto{{ $f->id }}" step="0.01" min="0.01" required placeholder="0.00">
        </div>
        <div class="sup-field">
            <label>Nota (opcional)</label>
            <input type="text" name="nota" maxlength="255" placeholder="Ej. cobro {{ $perLbl }} en efectivo">
        </div>
        <button type="submit" class="sup-btn"
            onclick="return confirm('¿Registrar el cobro a {{ $nombre }} por el monto capturado?')">Registrar cobro</button>
        <div style="flex-basis:100%;font-size:11px;color:var(--text3);margin-top:-4px">Se registra con la fecha y hora de hoy ({{ $hoy->format('d/m/Y') }}), el momento real del cobro; el sistema decide solo a qué {{ $perLbl }} corresponde y marca si llegó a tiempo o tarde.</div>
    </form>

    <button type="button" class="sup-hist-toggle" onclick="document.getElementById('supHist{{ $f->id }}').classList.toggle('open')">
        Ver historial de cobros ({{ $f->cobros->count() }}) ▾
    </button>
    <div class="sup-hist" id="supHist{{ $f->id }}">
        @if($f->cobros->isEmpty())
            <div style="padding:16px 22px;font-size:12px;color:var(--text3)">Todavía no hay cobros registrados a este admin.</div>
        @else
        <table>
            <thead><tr><th>Fecha</th><th>Monto cobrado</th><th>Registró</th><th>Nota</th></tr></thead>
            <tbody>
            @foreach($f->cobros->take(12) as $m)
                <tr>
                    <td>{{ $m->fecha->format('d/m/Y') }}</td>
                    <td style="font-weight:700">{{ $money($m->monto) }}</td>
                    <td style="color:var(--text2)">{{ $m->registradoPor?->usuario ?? '—' }}</td>
                    <td style="color:var(--text2)">{{ $m->nota ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@empty
<div class="sup-empty">
    No hay cuentas de inversión activas por cobrar.<br>
    El owner es quien crea las cuentas de financiamiento a los admins.
</div>
@endforelse

<script>
function supUsar(id, monto) {
    const input = document.getElementById('supMonto' + id);
    input.value = monto.toFixed(2);
    input.focus();
}

// Tras registrar un cobro, llevar la vista a la tarjeta correspondiente
@if(session('open_financiamiento'))
    document.getElementById('supCard{{ (int) session('open_financiamiento') }}')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
@endif
</script>

@endsection
