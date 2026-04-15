<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'prestamo_id', 'cobrador_id',
        'numero_pago', 'monto_cuota', 'interes', 'capital', 'saldo_restante',
        'monto_cobrado', 'tipo_cobro', 'nota_cobro',
        'fecha_programada', 'fecha_pago', 'estatus',
    ];

    protected $casts = [
        'monto_cuota'      => 'decimal:2',
        'interes'          => 'decimal:2',
        'capital'          => 'decimal:2',
        'saldo_restante'   => 'decimal:2',
        'monto_cobrado'    => 'decimal:2',
        'fecha_programada' => 'date',
        'fecha_pago'       => 'date',
    ];

    // Relaciones
    public function prestamo()
    {
        return $this->belongsTo(Prestamo::class, 'prestamo_id');
    }

    public function cobrador()
    {
        return $this->belongsTo(Empleado::class, 'cobrador_id');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial']);
    }

    public function scopeDeHoy($query)
    {
        return $query->where('fecha_programada', '<=', now()->toDateString())
                     ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial']);
    }

    public function scopeProximos($query)
    {
        return $query->where('fecha_programada', '>', now()->toDateString())
                     ->where('estatus', 'Pendiente');
    }
}
