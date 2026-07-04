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
}
