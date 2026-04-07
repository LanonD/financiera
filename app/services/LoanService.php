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
            $capital    = $cuota - $interes;

            // Último pago: liquida el saldo exacto
            if ($i === $num_pagos) {
                $capital    = $saldo;
                $cuota_real = round($capital + $interes, 2);
            } else {
                $cuota_real = round($cuota, 2);
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
            'cuota'         => round($cuota, 2),
            'total_pago'    => round($total_pago, 2),
            'total_interes' => round($total_interes, 2),
            'total_capital' => round($total_capital, 2),
            'tabla'         => $tabla,
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
