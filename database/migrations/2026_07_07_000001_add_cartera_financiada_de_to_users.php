<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin "sombra" que funge como espacio de datos de la cartera
        // financiada de un admin real: todos los clientes/préstamos/empleados
        // creados en esa cartera cuelgan de él, aislados de la cartera propia.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cartera_financiada_de')
                ->nullable()
                ->after('presupuesto')
                ->constrained('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cartera_financiada_de');
        });
    }
};
