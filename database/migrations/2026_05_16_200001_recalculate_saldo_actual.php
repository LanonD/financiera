<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula saldo_actual para todos los préstamos activos/atrasados.
 *
 * Antes: saldo_actual = saldo de capital restante (no bajaba con pagos de solo-interés).
 * Ahora: saldo_actual = monto_retornar - suma de lo cobrado en cuotas (excluye mora).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Para cada préstamo activo/atrasado/pendiente, recalcular saldo
        $prestamos = DB::table('prestamos')
            ->whereIn('estatus', ['Activo', 'Atrasado', 'Pendiente'])
            ->get(['id', 'monto', 'saldo_actual']);

        foreach ($prestamos as $p) {
            // Suma de monto_cobrado de pagos reales (excluye liquidados y congelados)
            $cobrado = DB::table('pagos')
                ->where('prestamo_id', $p->id)
                ->whereIn('estatus', ['Pagado', 'Parcial'])
                ->whereNotIn('tipo_pago', ['liquidado', 'congelado'])
                ->whereNotNull('monto_cobrado')
                ->where('monto_cobrado', '>', 0)
                ->sum('monto_cobrado');

            $nuevoSaldo = max(0, round((float)$p->monto - (float)$cobrado, 2));

            DB::table('prestamos')
                ->where('id', $p->id)
                ->update(['saldo_actual' => $nuevoSaldo]);
        }
    }

    public function down(): void
    {
        // Revertir: saldo_actual = monto_entregado - capital_cobrado
        $prestamos = DB::table('prestamos')
            ->whereIn('estatus', ['Activo', 'Atrasado', 'Pendiente'])
            ->get(['id', 'monto_entregado', 'saldo_actual']);

        foreach ($prestamos as $p) {
            $capitalCobrado = DB::table('pagos')
                ->where('prestamo_id', $p->id)
                ->whereIn('estatus', ['Pagado', 'Parcial'])
                ->whereNotIn('tipo_pago', ['liquidado', 'congelado'])
                ->sum('capital');

            $nuevoSaldo = max(0, round((float)$p->monto_entregado - (float)$capitalCobrado, 2));
            DB::table('prestamos')->where('id', $p->id)->update(['saldo_actual' => $nuevoSaldo]);
        }
    }
};
