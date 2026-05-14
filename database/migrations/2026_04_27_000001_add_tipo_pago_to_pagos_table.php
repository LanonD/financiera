<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // 'plan'=cuota normal, 'extra'=cobro inmediato, 'agendado'=programado fuera del plan, 'congelado'=diferido/payment hold
            $table->string('tipo_pago', 20)->default('plan')->after('estatus');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('tipo_pago');
        });
    }
};
