<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Prestamo extends Model
{
    use HasFactory;

    /**
     * Cuando un flujo ya registra su propio mensaje descriptivo del cambio de
     * estatus (cancelación, refinanciamiento, liquidación), activa esta bandera
     * ANTES de guardar para que el registro automático no genere un duplicado.
     */
    public bool $skipStatusLog = false;

    protected static function booted(): void
    {
        // El formulario de nuevo préstamo y el dashboard cachean qué clientes
        // tienen préstamo activo; cualquier alta o cambio de estatus debe invalidarlos
        static::saved(function (self $prestamo) {
            if ($prestamo->wasRecentlyCreated || $prestamo->wasChanged('estatus')) {
                Cache::forget("clientes_con_prestamo_{$prestamo->admin_id}");
                Cache::forget("dashboard_kpis_{$prestamo->admin_id}");
                Cache::forget("dashboard_prestamos_{$prestamo->admin_id}");
            }
        });

        // Registrar en la actividad del préstamo CUALQUIER cambio de estatus
        // (Pendiente→Activo, Activo→Atrasado, →Finalizado, etc.) de forma
        // centralizada, sin depender de que cada controlador lo registre.
        static::updated(function (self $prestamo) {
            if (!$prestamo->wasChanged('estatus') || $prestamo->skipStatusLog) {
                return;
            }
            $de = $prestamo->getOriginal('estatus');
            $a  = $prestamo->estatus;

            PrestamoActividad::log(
                $prestamo->id,
                'estatus',
                self::mensajeCambioEstatus($de, $a),
                ['de' => $de, 'a' => $a, 'automatico' => true]
            );
        });
    }

    /**
     * Texto legible para un cambio de estatus, según el destino.
     */
    public static function mensajeCambioEstatus(?string $de, string $a): string
    {
        $de = $de ?: '—';

        return match ($a) {
            'Atrasado'     => "El préstamo pasó a Atrasado por cuotas vencidas (antes: {$de}).",
            'Activo'       => $de === 'Atrasado'
                ? 'El préstamo se puso al corriente (Atrasado → Activo).'
                : "El préstamo pasó a Activo (antes: {$de}).",
            'Finalizado'   => "Préstamo finalizado — deuda saldada (antes: {$de}).",
            'Retirado'     => "Préstamo retirado (antes: {$de}).",
            'Refinanciado' => "Préstamo refinanciado (antes: {$de}).",
            'Pendiente'    => "El préstamo pasó a Pendiente (antes: {$de}).",
            default        => "Estatus actualizado: {$de} → {$a}.",
        };
    }

    protected $table = 'prestamos';

    protected $fillable = [
        'admin_id', 'cliente_id', 'promotor_id', 'cobrador_id', 'desembolso_id',
        'monto', 'tasa_diaria', 'num_pagos', 'frecuencia', 'cuota', 'saldo_actual',
        'interes_acumulado', 'fecha_ultimo_interes', 'interes_activo',
        'interes_diario', 'interes_mora_activo',
        'fecha_inicio', 'fecha_fin', 'estatus',
        'monto_entregado', 'forma_entrega', 'fecha_entrega', 'nota_entrega',
        'doc_ine', 'doc_pagare', 'doc_comprobante', 'doc_foto_domicilio',
        'payment_hold', 'descanso_domingos', 'refinanciado_por',
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
        'payment_hold'        => 'boolean',
        'descanso_domingos'   => 'boolean',
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

    /**
     * Finaliza el préstamo si ya quedó liquidado:
     * sin cuotas Pendiente/Atrasado, o con saldo y mora en $0
     * (p. ej. el último pago se registró como Parcial porque la cuota
     * nominal era mayor al saldo restante tras cobros extra o ajustes).
     */
    public function finalizarSiLiquidado(): bool
    {
        if (!in_array($this->estatus, ['Activo', 'Atrasado'])) {
            return false;
        }

        $pendientes = Pago::where('prestamo_id', $this->id)
            ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
            ->count();
        $deudaLiquidada = (float)$this->saldo_actual <= 0
            && (float)$this->interes_acumulado <= 0;

        if ($pendientes > 0 && !$deudaLiquidada) {
            return false;
        }

        if ($pendientes > 0) {
            // Deuda en $0 con cuotas aún pendientes → marcarlas como liquidadas
            Pago::where('prestamo_id', $this->id)
                ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                ->update([
                    'estatus'       => 'Pagado',
                    'monto_cobrado' => 0,
                    'tipo_cobro'    => 'completo',
                    'tipo_pago'     => 'liquidado',
                    'fecha_pago'    => now()->toDateString(),
                    'nota_cobro'    => 'Liquidación anticipada',
                ]);
        }

        $estatusAnterior           = $this->estatus;
        $this->estatus             = 'Finalizado';
        $this->payment_hold        = false;
        $this->interes_mora_activo = false;
        $this->interes_diario      = 0;
        $this->skipStatusLog       = true; // mensaje propio abajo; evita duplicado del hook
        $this->save();
        $this->skipStatusLog       = false;

        PrestamoActividad::log($this->id, 'estatus',
            'Préstamo finalizado automáticamente — deuda liquidada.',
            ['de' => $estatusAnterior, 'a' => 'Finalizado']
        );

        return true;
    }

    /**
     * Recalcula el atraso según las cuotas del plan:
     * - 'Atrasado' si hay alguna cuota Pendiente/Atrasado con fecha vencida.
     * - 'Activo'   si no quedan cuotas vencidas (p. ej. tras reprogramar
     *   las fechas a futuro con "cambiar frecuencia").
     * Solo aplica a préstamos en curso (Activo/Atrasado); no toca
     * Pendiente, Finalizado ni Retirado. Devuelve true si cambió el estatus.
     */
    public function recalcularAtraso(): bool
    {
        if (!in_array($this->estatus, ['Activo', 'Atrasado'])) {
            return false;
        }

        $hayVencidos = Pago::where('prestamo_id', $this->id)
            ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
            ->where('fecha_programada', '<', now()->toDateString())
            ->exists();

        $nuevo = $hayVencidos ? 'Atrasado' : 'Activo';
        if ($this->estatus === $nuevo) {
            return false;
        }

        $this->estatus = $nuevo;
        $this->save(); // el hook de saved() registra el cambio en la actividad

        return true;
    }
}
