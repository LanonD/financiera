<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nuevo puesto: supervisor de admins (cobra el interés de los financiamientos)
        DB::statement("ALTER TABLE users MODIFY COLUMN puesto ENUM('owner','supervisor','admin','promo','collector','desembolso') NOT NULL");

        // Quién registró cada movimiento (owner o supervisor)
        Schema::table('financiamiento_movimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('financiamiento_movimientos', 'registrado_por')) {
                $table->foreignId('registrado_por')
                    ->nullable()
                    ->after('nota')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('financiamiento_movimientos', function (Blueprint $table) {
            if (Schema::hasColumn('financiamiento_movimientos', 'registrado_por')) {
                $table->dropConstrainedForeignId('registrado_por');
            }
        });

        DB::statement("UPDATE users SET puesto = 'admin' WHERE puesto = 'supervisor'");
        DB::statement("ALTER TABLE users MODIFY COLUMN puesto ENUM('owner','admin','promo','collector','desembolso') NOT NULL");
    }
};
