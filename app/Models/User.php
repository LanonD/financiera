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
        'password',
        'puesto',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'activo'   => 'boolean',
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

    // Redireccion por rol después del login
    public function dashboardRoute(): string
    {
        return match($this->puesto) {
            'admin'       => 'dashboard',
            'promo'       => 'prestamos.index',
            'collector'   => 'cobros.index',
            'desembolso'  => 'desembolsos.index',
            default       => 'dashboard',
        };
    }
}
