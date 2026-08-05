<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Centinela de adminId() cuando el owner está en "vista global": el macro
     * de query deAdmin() lo interpreta como "sin filtro de admin" (todos los
     * administradores). Las consultas no refactorizadas que lo comparen con
     * where('admin_id', -1) simplemente no encuentran nada (fail-safe).
     */
    public const ADMIN_GLOBAL = -1;

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
        'cartera_financiada_de',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password'     => 'hashed',
        'activo'       => 'boolean',
        'presupuesto'  => 'decimal:2',
        'tours_vistos' => 'array',
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
        // En vista global el owner navega la interfaz de admin conservando
        // también sus permisos de owner.
        if ($this->esVistaGlobal()) {
            return ['owner', 'admin'];
        }

        $emp = $this->empleado;
        if ($emp && !empty($emp->roles)) {
            return $emp->roles;
        }
        return $this->puesto ? [$this->puesto] : [];
    }

    /**
     * ¿El owner activó la "vista global"? (POV de admin con los datos de
     * TODOS los administradores juntos; sólo lectura, exclusivo del owner).
     */
    public function esVistaGlobal(): bool
    {
        return $this->puesto === 'owner' && (bool) session('vista_global');
    }

    /**
     * Admin "sombra" de la cartera financiada de este admin (espacio de datos
     * separado: sus clientes/préstamos/empleados cuelgan de él).
     */
    public function carteraFinanciada()
    {
        return $this->hasOne(User::class, 'cartera_financiada_de');
    }

    /** Cartera activa en esta sesión para un admin financiado. */
    public function carteraActiva(): string
    {
        return session('cartera_activa') === 'financiada' ? 'financiada' : 'propia';
    }

    /**
     * Retorna el admin_id que corresponde a este usuario.
     * - Si es admin: su propio id, o el de su admin sombra cuando trabaja en
     *   la cartera financiada (las 2 carteras no comparten información).
     * - Si es promo/collector/desembolso: el admin_id de su registro en empleados.
     * Se usa para aislar datos entre administradores (multi-tenancy).
     */
    public function adminId(): ?int
    {
        if ($this->esVistaGlobal()) {
            return self::ADMIN_GLOBAL;
        }
        if ($this->puesto === 'admin') {
            if ($this->carteraActiva() === 'financiada' && ($sombra = $this->carteraFinanciada)) {
                return $sombra->id;
            }
            return $this->id;
        }
        return $this->empleado?->admin_id;
    }

    private ?bool $esFinanciadoCache = null;

    /** ¿Este admin tiene una cuenta de inversión (financiamiento) activa? */
    public function esAdminFinanciado(): bool
    {
        return $this->esFinanciadoCache ??= ($this->puesto === 'admin'
            && Financiamiento::where('admin_id', $this->id)->where('estatus', 'Activo')->exists());
    }

    // Redireccion por rol después del login
    public function dashboardRoute(): string
    {
        // El admin financiado maneja 2 carteras: al entrar escoge una
        if ($this->esAdminFinanciado()) {
            return 'carteras.seleccion';
        }

        return match($this->puesto) {
            'owner'       => 'owner.dashboard',
            'supervisor'  => 'supervisor.cobros.index',
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
