<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanciamientoInversor extends Model
{
    protected $table = 'financiamiento_inversores';

    protected $fillable = [
        'financiamiento_id', 'nombre', 'es_owner', 'aporte', 'pct_retorno',
        'fecha_ingreso', 'fecha_limite', 'estatus', 'fecha_salida',
    ];

    protected $casts = [
        'es_owner'      => 'boolean',
        'aporte'        => 'float',
        'pct_retorno'   => 'float',
        'fecha_ingreso' => 'date',
        'fecha_limite'  => 'date',
        'fecha_salida'  => 'date',
    ];

    public function financiamiento()
    {
        return $this->belongsTo(Financiamiento::class, 'financiamiento_id');
    }

    /** ¿El convenio ya venció? (retirarse ahora pierde el derecho al capital) */
    public function getConvenioVencidoAttribute(): bool
    {
        return $this->fecha_limite !== null && now()->startOfDay()->gt($this->fecha_limite);
    }
}
