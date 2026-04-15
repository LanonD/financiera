<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'promotor_id', 'nombre', 'celular', 'email', 'fijo', 'direccion',
        'curp', 'ocupacion',
        'ine', 'pagare', 'contrato', 'comprobante', 'foto_vivienda',
        'latitud', 'longitud',
        'contacto_nombre', 'contacto_telefono', 'contacto_direccion',
        'contacto_nombre2', 'contacto_telefono2', 'contacto_direccion2',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relaciones
    public function promotor()
    {
        return $this->belongsTo(Empleado::class, 'promotor_id');
    }

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class, 'cliente_id');
    }

    public function prestamosActivos()
    {
        return $this->hasMany(Prestamo::class, 'cliente_id')
                    ->whereIn('estatus', ['Activo', 'Atrasado']);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
