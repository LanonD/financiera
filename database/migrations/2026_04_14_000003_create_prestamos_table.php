<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('promotor_id')->constrained('empleados')->onDelete('restrict');
            $table->foreignId('cobrador_id')->nullable()->constrained('empleados')->onDelete('set null');
            $table->foreignId('desembolso_id')->nullable()->constrained('empleados')->onDelete('set null');

            // Términos del préstamo
            $table->decimal('monto', 12, 2);
            $table->decimal('tasa_diaria', 8, 4)->default(0);
            $table->integer('num_pagos');
            $table->enum('frecuencia', ['Diario', 'Semanal', 'Quincenal', 'Mensual']);
            $table->decimal('cuota', 12, 2)->default(0);
            $table->decimal('saldo_actual', 12, 2)->default(0);

            // Interés diario acumulado
            $table->decimal('interes_acumulado', 12, 2)->default(0);
            $table->date('fecha_ultimo_interes')->nullable();
            $table->boolean('interes_activo')->default(true);

            // Mora
            $table->decimal('interes_diario', 12, 2)->default(0);
            $table->boolean('interes_mora_activo')->default(false);

            // Fechas
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();

            // Estatus
            $table->enum('estatus', ['Pendiente', 'Activo', 'Atrasado', 'Finalizado', 'Cancelado', 'Retirado'])
                  ->default('Pendiente');

            // Desembolso
            $table->decimal('monto_entregado', 12, 2)->nullable();
            $table->enum('forma_entrega', ['efectivo', 'transferencia'])->nullable();
            $table->date('fecha_entrega')->nullable();
            $table->text('nota_entrega')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
