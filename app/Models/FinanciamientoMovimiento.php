<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanciamientoMovimiento extends Model
{
    protected $table = 'financiamiento_movimientos';

    protected $fillable = [
        'financiamiento_id', 'inversor_id', 'tipo', 'monto',
        'monto_reinversion', 'monto_retornado', 'capitalizado',
        'detalle', 'fecha', 'nota', 'registrado_por',
    ];

    protected $casts = [
        'monto'             => 'float',
        'monto_reinversion' => 'float',
        'monto_retornado'   => 'float',
        'capitalizado'      => 'boolean',
        'detalle'           => 'array',
        'fecha'             => 'date',
    ];

    public function financiamiento()
    {
        return $this->belongsTo(Financiamiento::class, 'financiamiento_id');
    }

    public function inversor()
    {
        return $this->belongsTo(FinanciamientoInversor::class, 'inversor_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
