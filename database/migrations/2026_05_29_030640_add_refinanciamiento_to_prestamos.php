<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend estatus enum with 'Refinanciado'
        DB::statement("ALTER TABLE prestamos MODIFY COLUMN estatus ENUM('Pendiente','Activo','Atrasado','Finalizado','Cancelado','Retirado','Refinanciado') NOT NULL DEFAULT 'Pendiente'");

        // Extend forma_entrega enum with 'refinanciamiento'
        DB::statement("ALTER TABLE prestamos MODIFY COLUMN forma_entrega ENUM('efectivo','transferencia','refinanciamiento') NULL");

        // Column to link the new loan back to the original refinanced loan
        Schema::table('prestamos', function (Blueprint $table) {
            $table->unsignedBigInteger('refinanciado_por')->nullable()->after('nota_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropColumn('refinanciado_por');
        });

        DB::statement("ALTER TABLE prestamos MODIFY COLUMN forma_entrega ENUM('efectivo','transferencia') NULL");
        DB::statement("ALTER TABLE prestamos MODIFY COLUMN estatus ENUM('Pendiente','Activo','Atrasado','Finalizado','Cancelado','Retirado') NOT NULL DEFAULT 'Pendiente'");
    }
};
