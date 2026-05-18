<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public function getAuthIdentifierName(): string
    {
        return 'usuario';
    }

    protected $fillable = [
        'usuario',
        'nombre',
        'alias',
        'password',
        'puesto',
        'activo',
        'celular',
        'presupuesto',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password'     => 'hashed',
        'activo'       => 'boolean',
        'presupuesto'  => 'decimal:2',
    ];

    public function empleado()
    {
        return $this->hasOne(Empleado::class, 'usuario_id');
    }

    /**
     * All roles this user has (from empleado.roles JSON or fallback to puesto).
     * Used by RoleMiddleware instead of Spatie's hasAnyRole().
     */
    public function getAllRoles(): array
    {
        $emp = $this->empleado;
        if ($emp && !empty($emp->roles)) {
            return $emp->roles;
        }
        return $this->puesto ? [$this->puesto] : [];
    }

    /**
     * Retorna el admin_id que corresponde a este usuario.
     * - Si es admin: su propio id.
     * - Si es promo/collector/desembolso: el admin_id de su registro en empleados.
     * Se usa para aislar datos entre administradores (multi-tenancy).
     */
    public function adminId(): ?int
    {
        if ($this->puesto === 'admin') return $this->id;
        return $this->empleado?->admin_id;
    }

    // Redireccion por rol después del login
    public function dashboardRoute(): string
    {
        return match($this->puesto) {
            'owner'       => 'owner.dashboard',
            'admin'       => 'dashboard',
            'promo'       => 'prestamos.index',
            'collector'   => 'cobros.index',
            'desembolso'  => 'desembolsos.index',
            default       => 'dashboard',
        };
    }
    // Obtener datos para el dashboard administrador
    
    public function empleados()
    {
        return $this->hasMany(User::class, 'admin_id');
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'admin_id');
    }

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class, 'admin_id');
    }
}
