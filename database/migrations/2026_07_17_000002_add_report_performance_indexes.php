<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para el reporte semanal del owner y, en general, para todas las
 * consultas que filtran pagos/préstamos por estatus y por fecha. Sin estos,
 * las agregaciones (SUM/COUNT con GROUP BY) hacen full scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->index(['admin_id', 'estatus'], 'prestamos_admin_estatus_idx');
            $table->index('estatus', 'prestamos_estatus_idx');
            $table->index('fecha_entrega', 'prestamos_fecha_entrega_idx');
        });

        Schema::table('pagos', function (Blueprint $table) {
            // El acceso a pagos parte del préstamo; el estatus filtra casi todas
            // las agregaciones (payable / reales), por eso el compuesto.
            $table->index(['prestamo_id', 'estatus'], 'pagos_prestamo_estatus_idx');
            $table->index('estatus', 'pagos_estatus_idx');
            $table->index('fecha_programada', 'pagos_fecha_programada_idx');
            $table->index('fecha_pago', 'pagos_fecha_pago_idx');
        });
    }

    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropIndex('prestamos_admin_estatus_idx');
            $table->dropIndex('prestamos_estatus_idx');
            $table->dropIndex('prestamos_fecha_entrega_idx');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex('pagos_prestamo_estatus_idx');
            $table->dropIndex('pagos_estatus_idx');
            $table->dropIndex('pagos_fecha_programada_idx');
            $table->dropIndex('pagos_fecha_pago_idx');
        });
    }
};
