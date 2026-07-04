<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cuenta de inversión conjunta financiada a un admin. El capital lo aportan
        // uno o varios inversores (el owner "Derian" y/o externos); es independiente
        // de la billetera propia del admin.
        Schema::create('financiamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->decimal('capital_actual', 14, 2)->default(0);   // aportes + reinversión capitalizada − retiros
            $table->decimal('rendimiento_pct', 5, 2);               // tasa total del periodo (ej. 18.00)
            $table->enum('frecuencia', ['semanal', 'mensual'])->default('semanal');
            $table->unsignedSmallInteger('plazo_meses')->default(12); // convenio: meses para que un inversor pueda retirar su capital
            $table->date('fecha_inicio');
            $table->enum('estatus', ['Activo', 'Finalizado'])->default('Activo');
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        // Participantes de la inversión. La reinversión no se guarda: es lo que sobra
        // de la tasa total tras restar los % de retorno de los inversores activos.
        Schema::create('financiamiento_inversores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financiamiento_id')->constrained('financiamientos')->onDelete('cascade');
            $table->string('nombre', 120);
            $table->boolean('es_owner')->default(false);            // participación de Derian (el owner)
            $table->decimal('aporte', 14, 2);                        // capital aportado vigente (crece si recibe transferencias)
            $table->decimal('pct_retorno', 5, 2)->default(0);        // puntos del capital total que se le retornan por periodo
            $table->date('fecha_ingreso');
            $table->date('fecha_limite')->nullable();                // vencimiento del convenio (ingreso + plazo); null = sin límite (owner)
            $table->enum('estatus', ['Activo', 'Retirado', 'Transferido'])->default('Activo');
            $table->date('fecha_salida')->nullable();
            $table->timestamps();
        });

        Schema::create('financiamiento_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financiamiento_id')->constrained('financiamientos')->onDelete('cascade');
            $table->foreignId('inversor_id')->nullable()->constrained('financiamiento_inversores')->nullOnDelete();
            // rendimiento | aporte | retiro (owner) | salida_inversor (devolución) | transferencia_owner (venció convenio)
            $table->string('tipo', 30);
            $table->decimal('monto', 14, 2);
            $table->decimal('monto_reinversion', 14, 2)->default(0); // parte capitalizada (tipo rendimiento)
            $table->decimal('monto_retornado', 14, 2)->default(0);   // parte pagada a inversores (tipo rendimiento)
            $table->boolean('capitalizado')->default(false);
            $table->json('detalle')->nullable();                     // desglose de retornos por inversor
            $table->date('fecha');
            $table->string('nota', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financiamiento_movimientos');
        Schema::dropIfExists('financiamiento_inversores');
        Schema::dropIfExists('financiamientos');
    }
};
