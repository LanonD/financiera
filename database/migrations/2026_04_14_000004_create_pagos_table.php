<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_id')->constrained('prestamos')->onDelete('cascade');
            $table->foreignId('cobrador_id')->nullable()->constrained('empleados')->onDelete('set null');

            $table->integer('numero_pago');
            $table->decimal('monto_cuota', 12, 2);
            $table->decimal('interes', 12, 2)->default(0);
            $table->decimal('capital', 12, 2)->default(0);
            $table->decimal('saldo_restante', 12, 2)->default(0);

            // Cobro
            $table->decimal('monto_cobrado', 12, 2)->nullable();
            $table->enum('tipo_cobro', ['completo', 'parcial'])->nullable();
            $table->text('nota_cobro')->nullable();

            // Fechas
            $table->date('fecha_programada');
            $table->date('fecha_pago')->nullable();

            $table->enum('estatus', ['Pendiente', 'Pagado', 'Parcial', 'Atrasado'])->default('Pendiente');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
