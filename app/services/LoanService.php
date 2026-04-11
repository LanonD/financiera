<?php
class LoanService {

    // ══════════════════════════════════════════════
    //  Fórmula: Amortización de cuota fija (annuity)
    //
    //  C = P × (r × (1+r)^n) / ((1+r)^n − 1)
    //
    //  P = Principal
    //  r = tasa por período (tasa_diaria × días_periodo)
    //  n = número de pagos
    //  C = cuota fija
    // ══════════════════════════════════════════════

    private array $frecuencias = [
        'Diario'    => 1,
        'Semanal'   => 7,
        'Quincenal' => 14,
        'Mensual'   => 30,
    ];

    public function calcularAmortizacion(
        float   $principal,
        float   $tasa_diaria,
        int     $num_pagos,
        string  $frecuencia,
        string  $fecha_inicio,
        ?string $fecha_primer_pago = null   // si se pasa, el primer pago cae en esta fecha exacta
    ): array {

        $dias = $this->frecuencias[$frecuencia] ?? 30;
        $td   = $tasa_diaria / 100;
        $r    = $td * $dias;   // tasa estándar por período (para calcular cuota fija)

        // Cuota fija calculada sobre el período estándar
        $cuota = $r == 0
            ? $principal / $num_pagos
            : $principal * ($r * pow(1 + $r, $num_pagos)) / (pow(1 + $r, $num_pagos) - 1);

        // Redondear cuota a denominación de billete mexicano
        // Los pagos 1..n-1 usan la cuota redondeada (hacia abajo)
        // El ÚLTIMO pago liquida el saldo restante (pago mayor/ajuste)
        $cuotaRedondeada = $num_pagos > 1 ? self::roundDownMexican($cuota) : $cuota;

        $tabla          = [];
        $saldo          = $principal;
        $total_interes  = 0;
        $total_capital  = 0;
        $total_pago     = 0;

        $fechaAnterior = new DateTime($fecha_inicio);
        $fecha         = clone $fechaAnterior;

        for ($i = 1; $i <= $num_pagos; $i++) {
            // Determinar fecha de este pago
            if ($i === 1 && $fecha_primer_pago !== null) {
                $fecha = new DateTime($fecha_primer_pago);
            } else {
                $fecha->modify("+{$dias} days");
            }

            // Interés = saldo × tasa_diaria × días REALES transcurridos
            $diasReales = (int)$fechaAnterior->diff($fecha)->days;
            $interes    = $saldo * $td * $diasReales;
            $capital    = $cuotaRedondeada - $interes;

            // Último pago: liquida el saldo exacto (pago de ajuste/mayor)
            if ($i === $num_pagos) {
                $capital    = $saldo;
                $cuota_real = round($capital + $interes, 2);
            } else {
                $cuota_real = round($cuotaRedondeada, 2);
            }

            $saldo_nuevo = max(0, $saldo - $capital);

            $tabla[] = [
                'pago'    => $i,
                'fecha'   => $fecha->format('Y-m-d'),
                'cuota'   => $cuota_real,
                'interes' => round($interes, 2),
                'capital' => round($capital, 2),
                'saldo'   => round($saldo_nuevo, 2),
            ];

            $total_interes += $interes;
            $total_capital += $capital;
            $total_pago    += $cuota_real;
            $saldo          = $saldo_nuevo;
            $fechaAnterior  = clone $fecha;
        }

        return [
            'principal'     => $principal,
            'tasa_diaria'   => $tasa_diaria,
            'tasa_periodo'  => round($r * 100, 4),
            'num_pagos'     => $num_pagos,
            'frecuencia'    => $frecuencia,
            'dias_periodo'  => $dias,
            'cuota'         => round($cuotaRedondeada, 2),
            'total_pago'    => round($total_pago, 2),
            'total_interes' => round($total_interes, 2),
            'total_capital' => round($total_capital, 2),
            'tabla'         => $tabla,
        ];
    }

    // Redondear hacia abajo a la denominación de billete mexicano más cercana
    // Se usan pasos de $50 para cuotas < $1,000 para minimizar el exceso
    // absorbido por el primer pago.
    private static function roundDownMexican(float $amount): float {
        if ($amount <= 0)    return 0;
        if ($amount < 100)   return floor($amount / 10)  * 10;   // $10s
        if ($amount < 1000)  return floor($amount / 50)  * 50;   // $50s
        if ($amount < 5000)  return floor($amount / 100) * 100;  // $100s
        if ($amount < 20000) return floor($amount / 500) * 500;  // $500s
        return floor($amount / 1000) * 1000;                     // $1000s
    }

    // ══════════════════════════════════════════════
    //  Calculadora 2: Pago fijo acordado
    //
    //  El promotor acuerda con el cliente:
    //    - Dinero entregado (monto_entregado)
    //    - Total a devolver  (monto_retornar)
    //  No hay tasa de interés — la "ganancia" ya está incluida
    //  en la diferencia entre retornar y entregado.
    //
    //  Cuota base  = ceil(monto_retornar / n)  → redondeo arriba al peso
    //  Pagos 1..n-1 = cuota_base  (todos iguales)
    //  Último pago  = monto_retornar − cuota_base × (n − 1)  [ajuste mínimo]
    //
    //  Gap máximo garantizado = cuota_base × n − monto_retornar  (≤ n pesos)
    //  Para préstamos típicos el gap suele ser < 10 pesos.
    // ══════════════════════════════════════════════
    public function calcularPagoFijo(
        float  $monto_entregado,
        float  $monto_retornar,
        int    $num_pagos,
        string $frecuencia,
        string $fecha_inicio
    ): array {
        $dias = $this->frecuencias[$frecuencia] ?? 30;

        // Redondear cuota hacia ARRIBA a la decena más cercana (unidades en 0).
        // El último pago absorbe el sobrante exacto.
        $cuotaExacta = $monto_retornar / max(1, $num_pagos);
        $cuotaBase   = $num_pagos > 1 ? (float)(ceil($cuotaExacta / 10) * 10) : (float)$monto_retornar;

        // Último pago = lo que falta después de n-1 pagos normales
        $ultimoPago  = max(0, round($monto_retornar - $cuotaBase * ($num_pagos - 1), 2));

        $tabla        = [];
        $saldo        = $monto_entregado;   // saldo de capital que va bajando
        $total_pago   = 0;
        $total_capital = 0;
        $total_interes = 0;

        $fecha = new DateTime($fecha_inicio);

        for ($i = 1; $i <= $num_pagos; $i++) {
            $fecha->modify("+{$dias} days");

            // Todos los pagos iguales excepto el último (ajuste)
            $cuota_real = ($i === $num_pagos) ? $ultimoPago : $cuotaBase;

            // Distribución proporcional capital/interés por pago
            $ratio   = $monto_retornar > 0 ? $monto_entregado / $monto_retornar : 1;
            $capital = ($i === $num_pagos)
                ? $saldo                                            // último pago: liquidar saldo
                : round($cuota_real * $ratio, 2);
            $interes = round($cuota_real - $capital, 2);

            $saldo_nuevo = max(0, round($saldo - $capital, 2));

            $tabla[] = [
                'pago'    => $i,
                'fecha'   => $fecha->format('Y-m-d'),
                'cuota'   => round($cuota_real, 2),
                'interes' => $interes,
                'capital' => round($capital, 2),
                'saldo'   => $saldo_nuevo,
            ];

            $total_pago    += $cuota_real;
            $total_capital += $capital;
            $total_interes += $interes;
            $saldo          = $saldo_nuevo;
        }

        return [
            'monto_entregado' => $monto_entregado,
            'monto_retornar'  => $monto_retornar,
            'ganancia'        => round($monto_retornar - $monto_entregado, 2),
            'cuota_base'      => $cuotaBase,
            'ultimo_pago'     => $ultimoPago,
            'num_pagos'       => $num_pagos,
            'frecuencia'      => $frecuencia,
            'tabla'           => $tabla,
            'total_pago'      => round($total_pago, 2),
            'total_capital'   => round($total_capital, 2),
            'total_interes'   => round($total_interes, 2),
        ];
    }

    // Calcular solo la cuota (para preview rápido)
    public function calcularCuota(float $principal, float $tasa_diaria, int $num_pagos, string $frecuencia): float {
        $dias = $this->frecuencias[$frecuencia] ?? 30;
        $r    = ($tasa_diaria / 100) * $dias;
        if ($r == 0) return round($principal / $num_pagos, 2);
        $cuota = $principal * ($r * pow(1 + $r, $num_pagos)) / (pow(1 + $r, $num_pagos) - 1);
        return round($cuota, 2);
    }

    // Calcular fecha de fin basada en inicio + pagos + frecuencia
    public function calcularFechaFin(string $fecha_inicio, int $num_pagos, string $frecuencia): string {
        $dias  = $this->frecuencias[$frecuencia] ?? 30;
        $total = $dias * $num_pagos;
        $fecha = new DateTime($fecha_inicio);
        $fecha->modify("+{$total} days");
        return $fecha->format('Y-m-d');
    }

    // Determinar si un préstamo está atrasado
    public function calcularEstatus(array $prestamo, array $pagos_pendientes): string {
        foreach ($pagos_pendientes as $pago) {
            if (new DateTime($pago['fecha_programada']) < new DateTime()) {
                return 'Atrasado';
            }
        }
        return 'Activo';
    }

    // Calcular días de atraso
    public function diasAtraso(string $fecha_programada): int {
        $hoy     = new DateTime();
        $fecha   = new DateTime($fecha_programada);
        $diff    = $hoy->diff($fecha);
        return $fecha < $hoy ? (int)$diff->days : 0;
    }
}
