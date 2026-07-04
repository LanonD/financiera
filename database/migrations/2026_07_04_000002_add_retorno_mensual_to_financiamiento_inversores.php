<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('financiamiento_inversores') || Schema::hasColumn('financiamiento_inversores', 'retorno_mensual')) {
            return;
        }

        // Retorno mensual FIJO en pesos por inversor. Es el dato canónico del
        // acuerdo ("$5,000 al mes"); pct_retorno pasa a ser el equivalente
        // informativo (retorno_mensual / aporte × 100).
        Schema::table('financiamiento_inversores', function (Blueprint $table) {
            $table->decimal('retorno_mensual', 14, 2)->default(0)->after('pct_retorno');
        });

        DB::table('financiamiento_inversores')
            ->update(['retorno_mensual' => DB::raw('ROUND(aporte * pct_retorno / 100, 2)')]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('financiamiento_inversores') || !Schema::hasColumn('financiamiento_inversores', 'retorno_mensual')) {
            return;
        }

        Schema::table('financiamiento_inversores', function (Blueprint $table) {
            $table->dropColumn('retorno_mensual');
        });
    }
};
