<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    use HasFactory;

    protected $table = 'empleados';

    protected $fillable = [
        'usuario_id', 'nombre', 'celular', 'email', 'fijo', 'direccion',
        'puesto', 'roles', 'rango', 'capacidad_maxima', 'monto_ocupado',
        'ine', 'pagare', 'contrato', 'comprobante',
        'latitud', 'longitud',
        'contacto_nombre', 'contacto_telefono', 'contacto_direccion',
        'contacto_nombre2', 'contacto_telefono2', 'contacto_direccion2',
        'activo',
    ];

    protected $casts = [
        'activo'           => 'boolean',
        'monto_ocupado'    => 'decimal:2',
        'capacidad_maxima' => 'integer',
        'roles'            => 'array',   // JSON ["promo","collector"]
    ];

    /**
     * Returns the priority order for picking the primary puesto from multiple roles.
     * Used for sidebar routing & middleware.
     */
    public static function primaryRole(array $roles): string
    {
        $priority = ['admin', 'promo', 'collector', 'desembolso'];
        foreach ($priority as $r) {
            if (in_array($r, $roles)) return $r;
        }
        return $roles[0] ?? 'promo';
    }

    /** Whether this employee has a specific role */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? [$this->puesto]);
    }

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'promotor_id');
    }

    public function prestamosComoPromotor()
    {
        return $this->hasMany(Prestamo::class, 'promotor_id');
    }

    public function prestamosComoCobrador()
    {
        return $this->hasMany(Prestamo::class, 'cobrador_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'cobrador_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorPuesto($query, string $puesto)
    {
        return $query->where('puesto', $puesto);
    }
}
