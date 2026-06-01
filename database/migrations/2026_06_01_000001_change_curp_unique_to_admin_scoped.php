<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Drop the global unique index on curp
            $table->dropUnique(['curp']);
            // Add composite unique: same admin cannot register the same CURP twice,
            // but different admins can share the same client (same CURP).
            $table->unique(['admin_id', 'curp'], 'clientes_admin_id_curp_unique');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_admin_id_curp_unique');
            $table->unique('curp');
        });
    }
};
