@php
    $fmt = fn($n) => '$' . number_format((float)$n, 2, '.', ',');
    $fecha = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';

    $estatusColor = match($prestamo->estatus) {
        'Activo'       => ['#dcfce7', '#166534'],
        'Atrasado'     => ['#fee2e2', '#991b1b'],
        'Finalizado'   => ['#f1f5f9', '#475569'],
        'Retirado'     => ['#f1f5f9', '#64748b'],
        'Refinanciado' => ['#e0f2fe', '#0369a1'],
        'Pendiente'    => ['#fef9c3', '#854d0e'],
        default        => ['#f1f5f9', '#64748b'],
    };

    $frecTxt = match($prestamo->frecuencia) {
        'diario'    => 'Diario',
        'semanal'   => 'Semanal',
        'quincenal' => 'Quincenal',
        'mensual'   => 'Mensual',
        default     => ucfirst($prestamo->frecuencia ?? '—'),
    };

    // Totales de la tabla
    $totCuota   = 0; $totCobrado = 0; $totCapital = 0; $totInteres = 0;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 26px 34px 46px 34px; }
    * { box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        color: #1f2937;
        font-size: 10px;
        margin: 0;
        line-height: 1.4;
    }
    .muted  { color: #6b7280; }
    .mono   { font-family: 'DejaVu Sans Mono', monospace; }
    .right  { text-align: right; }
    .center { text-align: center; }
    .b      { font-weight: bold; }

    /* Header */
    .header {
        border-bottom: 2px solid #111827;
        padding-bottom: 12px;
        margin-bottom: 16px;
    }
    .brand {
        font-size: 19px;
        font-weight: bold;
        color: #111827;
        letter-spacing: .5px;
    }
    .doc-title {
        font-size: 12px;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-top: 2px;
    }
    .badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: bold;
    }

    /* Info blocks */
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .info-table td { vertical-align: top; padding: 0; width: 50%; }
    .panel {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 10px 12px;
    }
    .panel-title {
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: #9ca3af;
        margin-bottom: 6px;
        border-bottom: 1px solid #f3f4f6;
        padding-bottom: 4px;
    }
    .kv { width: 100%; border-collapse: collapse; }
    .kv td { padding: 2px 0; font-size: 10px; }
    .kv td.k { color: #6b7280; width: 42%; }
    .kv td.v { text-align: right; font-weight: bold; color: #111827; }

    /* Resumen financiero */
    .summary { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .summary td {
        border: 1px solid #e5e7eb;
        padding: 9px 10px;
        text-align: center;
        width: 25%;
    }
    .summary .lbl {
        font-size: 8px; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af;
        display: block; margin-bottom: 4px;
    }
    .summary .val { font-size: 14px; font-weight: bold; }

    /* Tabla de pagos */
    .section-title {
        font-size: 11px; font-weight: bold; color: #111827;
        margin: 4px 0 6px 0; padding-bottom: 3px; border-bottom: 1px solid #d1d5db;
    }
    table.pagos { width: 100%; border-collapse: collapse; }
    table.pagos th {
        background: #111827; color: #fff; font-size: 8.5px; font-weight: bold;
        text-transform: uppercase; letter-spacing: .3px;
        padding: 6px 5px; text-align: left;
    }
    table.pagos th.right { text-align: right; }
    table.pagos th.center { text-align: center; }
    table.pagos td {
        padding: 5px 5px; font-size: 9px; border-bottom: 1px solid #eef0f2;
    }
    table.pagos tbody tr:nth-child(even) { background: #f9fafb; }
    table.pagos tfoot td {
        border-top: 2px solid #111827; border-bottom: none;
        font-weight: bold; font-size: 9.5px; padding: 7px 5px;
        background: #f3f4f6;
    }
    .pill {
        display: inline-block; padding: 1px 7px; border-radius: 10px;
        font-size: 8px; font-weight: bold;
    }

    .footer-note {
        margin-top: 18px; font-size: 8.5px; color: #9ca3af;
        border-top: 1px solid #e5e7eb; padding-top: 8px;
    }
</style>
</head>
<body>

    {{-- ── Encabezado ── --}}
    <div class="header">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="vertical-align:top;">
                    <div class="brand">{{ $prestamista }}</div>
                    <div class="doc-title">Estado de Cuenta</div>
                </td>
                <td style="vertical-align:top; text-align:right;">
                    <div class="b" style="font-size:13px;">Préstamo #{{ $prestamo->id }}</div>
                    <div class="muted" style="margin-top:2px;">Emitido: {{ $fecha(now()) }}</div>
                    <div style="margin-top:5px;">
                        <span class="badge" style="background:{{ $estatusColor[0] }}; color:{{ $estatusColor[1] }};">{{ $prestamo->estatus }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── Datos del cliente y del préstamo ── --}}
    <table class="info-table">
        <tr>
            <td style="padding-right:8px;">
                <div class="panel">
                    <div class="panel-title">Cliente</div>
                    <table class="kv">
                        <tr><td class="k">Nombre</td><td class="v">{{ $prestamo->cliente?->nombre ?? '—' }}</td></tr>
                        <tr><td class="k">Teléfono</td><td class="v">{{ $prestamo->cliente?->telefono ?? '—' }}</td></tr>
                        <tr><td class="k">Dirección</td><td class="v">{{ $prestamo->cliente?->direccion ?? '—' }}</td></tr>
                        @if($prestamo->promotor)
                        <tr><td class="k">Promotor</td><td class="v">{{ $prestamo->promotor->nombre }}</td></tr>
                        @endif
                        @if($prestamo->cobrador)
                        <tr><td class="k">Cobrador</td><td class="v">{{ $prestamo->cobrador->nombre }}</td></tr>
                        @endif
                    </table>
                </div>
            </td>
            <td style="padding-left:8px;">
                <div class="panel">
                    <div class="panel-title">Condiciones del préstamo</div>
                    <table class="kv">
                        <tr><td class="k">Monto entregado</td><td class="v mono">{{ $fmt($prestamo->monto_entregado) }}</td></tr>
                        <tr><td class="k">Total a pagar</td><td class="v mono">{{ $fmt($prestamo->monto) }}</td></tr>
                        <tr><td class="k">Interés pactado</td><td class="v mono">{{ $fmt($interesAcordadoTotal) }}</td></tr>
                        <tr><td class="k">N.º de pagos</td><td class="v">{{ $prestamo->num_pagos }} · {{ $frecTxt }}</td></tr>
                        <tr><td class="k">Cuota</td><td class="v mono">{{ $fmt($prestamo->cuota) }}</td></tr>
                        <tr><td class="k">Inicio</td><td class="v">{{ $fecha($prestamo->fecha_inicio) }}</td></tr>
                        <tr><td class="k">Vencimiento</td><td class="v">{{ $fecha($prestamo->fecha_fin) }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── Resumen financiero ── --}}
    <table class="summary">
        <tr>
            <td>
                <span class="lbl">Total cobrado</span>
                <span class="val mono" style="color:#16a34a;">{{ $fmt($totalCobrado) }}</span>
            </td>
            <td>
                <span class="lbl">Capital recuperado</span>
                <span class="val mono">{{ $fmt($capitalCobrado) }}</span>
            </td>
            <td>
                <span class="lbl">Interés cobrado</span>
                <span class="val mono">{{ $fmt($interesCobrado) }}</span>
            </td>
            <td>
                <span class="lbl">Balance restante</span>
                <span class="val mono" style="color:#dc2626;">{{ $fmt($balanceRestante) }}</span>
            </td>
        </tr>
    </table>

    {{-- ── Detalle del balance ── --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:16px; font-size:9.5px;">
        <tr>
            <td class="muted" style="padding:2px 0;">Principal pendiente: <span class="mono b" style="color:#111827;">{{ $fmt($principalRestante) }}</span></td>
            <td class="muted" style="padding:2px 0;">Interés pendiente: <span class="mono b" style="color:#111827;">{{ $fmt($interesRestante) }}</span></td>
            <td class="muted" style="padding:2px 0;">Mora acumulada: <span class="mono b" style="color:{{ $interesPendiente > 0 ? '#c2410c' : '#111827' }};">{{ $fmt($interesPendiente) }}</span></td>
            <td class="muted" style="padding:2px 0; text-align:right;">Último pago: <span class="b" style="color:#111827;">{{ $fecha($ultimaFechaPago) }}</span></td>
        </tr>
    </table>

    {{-- ── Plan / historial de pagos ── --}}
    <div class="section-title">Plan de pagos</div>
    <table class="pagos">
        <thead>
            <tr>
                <th class="center" style="width:26px;">#</th>
                <th>Tipo</th>
                <th>Programada</th>
                <th>Pago</th>
                <th class="right">Cuota</th>
                <th class="right">Cobrado</th>
                <th class="right">Capital</th>
                <th class="right">Interés</th>
                <th class="right">Saldo</th>
                <th>Estatus</th>
            </tr>
        </thead>
        <tbody>
        @foreach($pagos as $p)
            @php
                $tipoPago = $p->tipo_pago ?? 'plan';
                $esDimmed = in_array($tipoPago, ['congelado', 'liquidado']);

                $capShow = in_array($p->estatus, ['Pagado', 'Parcial']) ? ($capitalDisplay[$p->id] ?? 0) : $p->capital;
                $intShow = in_array($p->estatus, ['Pagado', 'Parcial']) ? ($interesDisplay[$p->id] ?? 0) : $p->interes;

                $totCuota   += (float) $p->monto_cuota;
                if (!$esDimmed) {
                    $totCobrado += (float) ($p->monto_cobrado ?? 0);
                    $totCapital += (float) ($capitalDisplay[$p->id] ?? 0);
                    $totInteres += (float) ($interesDisplay[$p->id] ?? 0);
                }

                $tipoLabel = match($tipoPago) {
                    'extra'     => 'Extra',
                    'agendado'  => 'Agendado',
                    'congelado' => 'Diferido',
                    'liquidado' => 'Liquidado',
                    default     => 'Plan',
                };

                $estatusLabel = match(true) {
                    $tipoPago === 'congelado' => 'Diferido',
                    $tipoPago === 'liquidado' => 'Liquidado',
                    default                   => $p->estatus,
                };

                $pillCol = match($estatusLabel) {
                    'Pagado'    => ['#dcfce7', '#166534'],
                    'Parcial'   => ['#fef9c3', '#854d0e'],
                    'Atrasado'  => ['#fee2e2', '#991b1b'],
                    'Diferido'  => ['#ffedd5', '#c2410c'],
                    'Liquidado' => ['#f3f4f6', '#6b7280'],
                    default     => ['#f3f4f6', '#6b7280'],
                };
            @endphp
            <tr @if($esDimmed) style="color:#9ca3af;" @endif>
                <td class="center">{{ $p->numero_pago }}</td>
                <td>{{ $tipoLabel }}</td>
                <td class="mono">{{ $fecha($p->fecha_programada) }}</td>
                <td class="mono">{{ $p->fecha_pago ? $fecha($p->fecha_pago) : '—' }}</td>
                <td class="right mono">{{ $fmt($p->monto_cuota) }}</td>
                <td class="right mono">{{ $esDimmed ? '—' : ($p->monto_cobrado !== null ? $fmt($p->monto_cobrado) : '—') }}</td>
                <td class="right mono">{{ $fmt($capShow) }}</td>
                <td class="right mono">{{ $fmt($intShow) }}</td>
                <td class="right mono">{{ $fmt($saldoDisplay[$p->id] ?? $p->saldo_restante) }}</td>
                <td><span class="pill" style="background:{{ $pillCol[0] }}; color:{{ $pillCol[1] }};">{{ $estatusLabel }}</span></td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="right">TOTALES</td>
                <td class="right mono">{{ $fmt($totCuota) }}</td>
                <td class="right mono">{{ $fmt($totCobrado) }}</td>
                <td class="right mono">{{ $fmt($totCapital) }}</td>
                <td class="right mono">{{ $fmt($totInteres) }}</td>
                <td class="right mono" style="color:#dc2626;">{{ $fmt($balanceRestante) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer-note">
        Documento generado automáticamente por {{ $prestamista }} el {{ $fecha(now()) }} a las {{ now()->format('H:i') }} h.
        Este estado de cuenta refleja los movimientos registrados a la fecha de emisión y tiene carácter informativo.
    </div>

</body>
</html>
