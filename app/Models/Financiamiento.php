<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Financiamiento extends Model
{
    protected $table = 'financiamientos';

    protected $fillable = [
        'admin_id', 'capital_actual', 'rendimiento_pct',
        'frecuencia', 'plazo_meses', 'fecha_inicio', 'estatus', 'notas',
    ];

    protected $casts = [
        'capital_actual'  => 'float',
        'rendimiento_pct' => 'float',
        'plazo_meses'     => 'integer',
        'fecha_inicio'    => 'date',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function inversores()
    {
        return $this->hasMany(FinanciamientoInversor::class, 'financiamiento_id')
            ->orderByDesc('es_owner')->orderBy('fecha_ingreso');
    }

    public function movimientos()
    {
        return $this->hasMany(FinanciamientoMovimiento::class, 'financiamiento_id')
            ->orderBy('fecha', 'desc')->orderBy('id', 'desc');
    }

    /** Inversores que siguen dentro del convenio. */
    public function inversoresActivos()
    {
        return $this->inversores->where('estatus', 'Activo');
    }

    /** Suma de % de retorno de los inversores activos. */
    public function getPctRetornoActivosAttribute(): float
    {
        return round((float) $this->inversoresActivos()->sum('pct_retorno'), 2);
    }

    /** Retornos mensuales fijos ($) comprometidos con los inversores activos. */
    public function getFijosMensualesAttribute(): float
    {
        return round((float) $this->inversoresActivos()->sum('retorno_mensual'), 2);
    }

    /** % que se reinvierte: lo que sobra de la tasa total tras pagar retornos. */
    public function getPctReinversionAttribute(): float
    {
        return round(max(0, $this->rendimiento_pct - $this->pct_retorno_activos), 2);
    }

    /** Rendimiento esperado del periodo (semana o mes) sobre el capital vigente. */
    public function getRendimientoPeriodoAttribute(): float
    {
        return round($this->capital_actual * $this->rendimiento_pct / 100, 2);
    }

    /** Etiqueta del periodo según la frecuencia. */
    public function getPeriodoLabelAttribute(): string
    {
        return $this->frecuencia === 'mensual' ? 'mes' : 'semana';
    }

    /**
     * Periodos que caben en un mes (el pct_retorno de los inversores es SIEMPRE
     * mensual; en cuentas semanales se prorratea entre 4 semanas).
     */
    public function getPeriodosPorMesAttribute(): int
    {
        return $this->frecuencia === 'semanal' ? 4 : 1;
    }

    /**
     * Fecha del n-ésimo cobro del calendario (n = 1, 2, …). El primer cobro
     * toca un periodo completo (semana o mes, según la frecuencia del acuerdo)
     * después del inicio del financiamiento.
     */
    public function fechaCobro(int $n): \Carbon\Carbon
    {
        $inicio = $this->fecha_inicio->copy()->startOfDay();

        return $this->frecuencia === 'semanal'
            ? $inicio->addWeeks($n)
            : $inicio->addMonthsNoOverflow($n);
    }

    /**
     * Registra el rendimiento del periodo (el cobro al admin).
     *
     * Retorno FIJO por inversor: su retorno_mensual pactado, prorrateado al
     * periodo de la cuenta. Lo que sobra del monto cobrado se reinvierte; si
     * el cobro no alcanza para los retornos fijos, se escalan proporcionalmente.
     * Lo usan el owner y el supervisor para que el reparto sea idéntico.
     */
    public function registrarRendimiento(float $monto, string $fecha, ?string $nota, bool $capitalizar, ?int $registradoPor = null): FinanciamientoMovimiento
    {
        $detalle   = [];
        $fijos     = [];
        $totalFijo = 0.0;

        foreach ($this->inversoresActivos() as $inv) {
            $fijo = round($inv->retorno_mensual / $this->periodos_por_mes, 2);
            if ($fijo <= 0) continue;
            $fijos[] = ['inv' => $inv, 'fijo' => $fijo];
            $totalFijo += $fijo;
        }

        $escala         = ($totalFijo > 0 && $totalFijo > $monto) ? $monto / $totalFijo : 1.0;
        $montoRetornado = 0.0;

        foreach ($fijos as $fila) {
            $retorno = round($fila['fijo'] * $escala, 2);
            $montoRetornado += $retorno;
            $detalle[] = [
                'inversor_id' => $fila['inv']->id,
                'nombre'      => $fila['inv']->nombre,
                'pct'         => (float) $fila['inv']->pct_retorno,
                'monto'       => $retorno,
            ];
        }

        $montoRetornado   = round($montoRetornado, 2);
        $montoReinversion = round(max(0, $monto - $montoRetornado), 2);

        if ($capitalizar && $montoReinversion > 0) {
            $this->capital_actual = round($this->capital_actual + $montoReinversion, 2);
            $this->save();
        }

        return FinanciamientoMovimiento::create([
            'financiamiento_id' => $this->id,
            'tipo'              => 'rendimiento',
            'monto'             => $monto,
            'monto_reinversion' => $montoReinversion,
            'monto_retornado'   => $montoRetornado,
            'capitalizado'      => $capitalizar && $montoReinversion > 0,
            'detalle'           => $detalle,
            'fecha'             => $fecha,
            'nota'              => $nota,
            'registrado_por'    => $registradoPor,
        ]);
    }
}
