<?php

use App\Models\Financiamiento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separa "a qué ventana de cobro pertenece un rendimiento" de "en qué
     * fecha real se cobró". Antes ambas cosas se derivaban de la misma
     * columna `fecha`, así que un cobro atrasado (registrado con la fecha
     * real, posterior al corte teórico) se enrutaba a la ventana SIGUIENTE
     * en vez de saldar la ventana vencida. El backfill preserva el enrutado
     * actual de los registros existentes (no reescribe historial); sólo los
     * cobros nuevos usan la asignación correcta vía Financiamiento::estadoCobros().
     */
    public function up(): void
    {
        if (!Schema::hasTable('financiamiento_movimientos')) {
            return;
        }

        if (!Schema::hasColumn('financiamiento_movimientos', 'periodo')) {
            Schema::table('financiamiento_movimientos', function (Blueprint $table) {
                $table->unsignedInteger('periodo')->nullable()->after('tipo');
            });
        }

        Financiamiento::with('movimientos')->get()->each(function (Financiamiento $f) {
            foreach ($f->movimientos->where('tipo', 'rendimiento') as $m) {
                if ($m->periodo === null) {
                    $m->periodo = $f->periodoDeFecha($m->fecha);
                    $m->saveQuietly();
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('financiamiento_movimientos')) {
            return;
        }

        if (Schema::hasColumn('financiamiento_movimientos', 'periodo')) {
            Schema::table('financiamiento_movimientos', function (Blueprint $table) {
                $table->dropColumn('periodo');
            });
        }
    }
};
