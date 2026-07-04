<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('financiamiento_movimientos')) {
            return;
        }

        DB::statement('ALTER TABLE financiamiento_movimientos MODIFY COLUMN tipo VARCHAR(30) NOT NULL');

        Schema::table('financiamiento_movimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('financiamiento_movimientos', 'inversor_id')) {
                $table->foreignId('inversor_id')
                    ->nullable()
                    ->after('financiamiento_id')
                    ->constrained('financiamiento_inversores')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('financiamiento_movimientos', 'monto_retornado')) {
                $table->decimal('monto_retornado', 14, 2)->default(0)->after('monto_reinversion');
            }

            if (!Schema::hasColumn('financiamiento_movimientos', 'detalle')) {
                $table->json('detalle')->nullable()->after('capitalizado');
            }
        });

        if (Schema::hasColumn('financiamiento_movimientos', 'monto_inversor')) {
            DB::statement('UPDATE financiamiento_movimientos SET monto_retornado = monto_inversor WHERE monto_retornado = 0');
            DB::statement('ALTER TABLE financiamiento_movimientos MODIFY COLUMN monto_inversor DECIMAL(12,2) NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('financiamiento_movimientos')) {
            return;
        }

        Schema::table('financiamiento_movimientos', function (Blueprint $table) {
            if (Schema::hasColumn('financiamiento_movimientos', 'detalle')) {
                $table->dropColumn('detalle');
            }

            if (Schema::hasColumn('financiamiento_movimientos', 'monto_retornado')) {
                $table->dropColumn('monto_retornado');
            }

            if (Schema::hasColumn('financiamiento_movimientos', 'inversor_id')) {
                $table->dropConstrainedForeignId('inversor_id');
            }
        });
    }
};
