<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotor_id')->constrained('empleados')->onDelete('restrict');
            $table->string('nombre', 100);
            $table->string('celular', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('fijo', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('curp', 18)->unique()->nullable();
            $table->enum('ocupacion', ['Empleado', 'Negocio propio', 'Independiente', 'Otro'])->nullable();
            // Documentos
            $table->string('ine')->nullable();
            $table->string('pagare')->nullable();
            $table->string('contrato')->nullable();
            $table->string('comprobante')->nullable();
            $table->string('foto_vivienda')->nullable();
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
        Schema::dropIfExists('clientes');
    }
};
