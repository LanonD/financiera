<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('financiamientos')) {
            return;
        }

        Schema::table('financiamientos', function (Blueprint $table) {
            if (!Schema::hasColumn('financiamientos', 'frecuencia')) {
                $table->enum('frecuencia', ['semanal', 'mensual'])->default('semanal')->after('rendimiento_pct');
            }

            if (!Schema::hasColumn('financiamientos', 'plazo_meses')) {
                $table->unsignedSmallInteger('plazo_meses')->default(12)->after('frecuencia');
            }
        });

        foreach ([
            'capital_inicial' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'pct_reinversion' => 'DECIMAL(5,2) NOT NULL DEFAULT 0',
            'pct_inversor' => 'DECIMAL(5,2) NOT NULL DEFAULT 0',
        ] as $column => $definition) {
            if (Schema::hasColumn('financiamientos', $column)) {
                DB::statement("ALTER TABLE financiamientos MODIFY COLUMN {$column} {$definition}");
            }
        }

        if (!Schema::hasTable('financiamiento_inversores')) {
            Schema::create('financiamiento_inversores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('financiamiento_id')->constrained('financiamientos')->onDelete('cascade');
                $table->string('nombre', 120);
                $table->boolean('es_owner')->default(false);
                $table->decimal('aporte', 14, 2);
                $table->decimal('pct_retorno', 5, 2)->default(0);
                $table->decimal('retorno_mensual', 14, 2)->default(0);
                $table->date('fecha_ingreso');
                $table->date('fecha_limite')->nullable();
                $table->enum('estatus', ['Activo', 'Retirado', 'Transferido'])->default('Activo');
                $table->date('fecha_salida')->nullable();
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('financiamiento_inversores', 'retorno_mensual')) {
            Schema::table('financiamiento_inversores', function (Blueprint $table) {
                $table->decimal('retorno_mensual', 14, 2)->default(0)->after('pct_retorno');
            });
        }

        if (!Schema::hasTable('financiamiento_movimientos')) {
            Schema::create('financiamiento_movimientos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('financiamiento_id')->constrained('financiamientos')->onDelete('cascade');
                $table->foreignId('inversor_id')->nullable()->constrained('financiamiento_inversores')->nullOnDelete();
                $table->string('tipo', 30);
                $table->decimal('monto', 14, 2);
                $table->decimal('monto_reinversion', 14, 2)->default(0);
                $table->decimal('monto_retornado', 14, 2)->default(0);
                $table->boolean('capitalizado')->default(false);
                $table->json('detalle')->nullable();
                $table->date('fecha');
                $table->string('nota', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financiamiento_movimientos');
        Schema::dropIfExists('financiamiento_inversores');

        Schema::table('financiamientos', function (Blueprint $table) {
            if (Schema::hasColumn('financiamientos', 'plazo_meses')) {
                $table->dropColumn('plazo_meses');
            }

            if (Schema::hasColumn('financiamientos', 'frecuencia')) {
                $table->dropColumn('frecuencia');
            }
        });
    }
};
