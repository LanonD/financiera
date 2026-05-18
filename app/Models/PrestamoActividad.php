<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PrestamoActividad extends Model
{
    public $timestamps = false;

    protected $table = 'prestamo_actividad';

    protected $fillable = ['prestamo_id', 'user_id', 'tipo', 'descripcion', 'meta'];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public function prestamo() { return $this->belongsTo(Prestamo::class); }
    public function user()     { return $this->belongsTo(User::class); }

    // ── Iconos y colores por tipo ──────────────────────────────────────────────
    public static array $estilos = [
        'creado'       => ['color' => '#3b82f6', 'icon' => '✦',  'label' => 'Creado'],
        'desembolso'   => ['color' => '#8b5cf6', 'icon' => '💸', 'label' => 'Desembolsado'],
        'pago'         => ['color' => '#16a34a', 'icon' => '✓',  'label' => 'Pago registrado'],
        'cobro_extra'  => ['color' => '#10b981', 'icon' => '⚡', 'label' => 'Cobro extra'],
        'agendado'     => ['color' => '#6366f1', 'icon' => '📅', 'label' => 'Cobro agendado'],
        'estatus'      => ['color' => '#f59e0b', 'icon' => '◉',  'label' => 'Cambio de estatus'],
        'cobrador'     => ['color' => '#0ea5e9', 'icon' => '👤', 'label' => 'Cobrador'],
        'mora'         => ['color' => '#ef4444', 'icon' => '⚠',  'label' => 'Mora'],
        'configuracion'=> ['color' => '#6b7280', 'icon' => '⚙',  'label' => 'Configuración'],
        'ajuste'       => ['color' => '#f97316', 'icon' => '✎',  'label' => 'Ajuste manual'],
        'pago_diferido'=> ['color' => '#a855f7', 'icon' => '⏸',  'label' => 'Pago diferido'],
        'edicion'      => ['color' => '#6b7280', 'icon' => '✎',  'label' => 'Edición'],
    ];

    // ── Helper estático para registrar eventos ─────────────────────────────────
    public static function log(int $prestamoId, string $tipo, string $descripcion, ?array $meta = null): void
    {
        static::create([
            'prestamo_id' => $prestamoId,
            'user_id'     => Auth::user()?->id,
            'tipo'        => $tipo,
            'descripcion' => $descripcion,
            'meta'        => $meta,
        ]);
    }
}
