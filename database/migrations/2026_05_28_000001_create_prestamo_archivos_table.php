<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamo_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_id')->constrained('prestamos')->onDelete('cascade');
            $table->foreignId('subido_por')->nullable()->constrained('users')->onDelete('set null');
            $table->string('nombre_original');
            $table->string('ruta');
            $table->string('tipo_archivo', 10);
            $table->unsignedBigInteger('tamano')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamo_archivos');
    }
};
