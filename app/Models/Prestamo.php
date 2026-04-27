<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prestamo extends Model
{
    use HasFactory;

    protected $table = 'prestamos';

    protected $fillable = [
        'cliente_id', 'promotor_id', 'cobrador_id', 'desembolso_id',
        'monto', 'tasa_diaria', 'num_pagos', 'frecuencia', 'cuota', 'saldo_actual',
        'interes_acumulado', 'fecha_ultimo_interes', 'interes_activo',
        'interes_diario', 'interes_mora_activo',
        'fecha_inicio', 'fecha_fin', 'estatus',
        'monto_entregado', 'forma_entrega', 'fecha_entrega', 'nota_entrega',
        'doc_ine', 'doc_pagare', 'doc_comprobante', 'doc_foto_domicilio',
    ];

    protected $casts = [
        'monto'               => 'decimal:2',
        'tasa_diaria'         => 'decimal:4',
        'cuota'               => 'decimal:2',
        'saldo_actual'        => 'decimal:2',
        'interes_acumulado'   => 'decimal:2',
        'interes_diario'      => 'decimal:2',
        'monto_entregado'     => 'decimal:2',
        'interes_activo'      => 'boolean',
        'interes_mora_activo' => 'boolean',
        'fecha_inicio'        => 'date',
        'fecha_fin'           => 'date',
        'fecha_ultimo_interes'=> 'date',
        'fecha_entrega'       => 'date',
    ];

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function promotor()
    {
        return $this->belongsTo(Empleado::class, 'promotor_id');
    }

    public function cobrador()
    {
        return $this->belongsTo(Empleado::class, 'cobrador_id');
    }

    public function desembolso()
    {
        return $this->belongsTo(Empleado::class, 'desembolso_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'prestamo_id')->orderBy('numero_pago');
    }

    public function pagosPendientes()
    {
        return $this->hasMany(Pago::class, 'prestamo_id')
                    ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                    ->orderBy('numero_pago');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->whereIn('estatus', ['Activo', 'Atrasado']);
    }

    public function scopePendientes($query)
    {
        return $query->where('estatus', 'Pendiente');
    }

    public function scopeAtrasados($query)
    {
        return $query->where('estatus', 'Atrasado');
    }

    // Helpers
    public function estaAtrasado(): bool
    {
        return $this->pagosPendientes()
                    ->where('fecha_programada', '<', now()->toDateString())
                    ->exists();
    }

    public function proximoPago()
    {
        return $this->pagosPendientes()->first();
    }
}
