<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->string('doc_ine')->nullable()->after('nota_entrega');
            $table->string('doc_pagare')->nullable()->after('doc_ine');
            $table->string('doc_comprobante')->nullable()->after('doc_pagare');
            $table->string('doc_foto_domicilio')->nullable()->after('doc_comprobante');
        });
    }

    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropColumn(['doc_ine', 'doc_pagare', 'doc_comprobante', 'doc_foto_domicilio']);
        });
    }
};
