<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->string('nombre', 100);
            $table->string('celular', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('fijo', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->enum('puesto', ['admin', 'promo', 'collector', 'desembolso']);
            $table->enum('rango', ['Bronce', 'Plata', 'Oro', 'Platino', 'Diamante'])->default('Bronce');
            $table->integer('capacidad_maxima')->default(0);
            $table->decimal('monto_ocupado', 12, 2)->default(0);
            // Documentos
            $table->string('ine')->nullable();
            $table->string('pagare')->nullable();
            $table->string('contrato')->nullable();
            $table->string('comprobante')->nullable();
            // Geolocalización
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            // Contacto de emergencia 1
            $table->string('contacto_nombre', 100)->nullable();
            $table->string('contacto_telefono', 20)->nullable();
            $table->string('contacto_direccion', 255)->nullable();
            // Contacto de emergencia 2
            $table->string('contacto_nombre2', 100)->nullable();
            $table->string('contacto_telefono2', 20)->nullable();
            $table->string('contacto_direccion2', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
