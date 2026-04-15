<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            // JSON array of roles, e.g. ["promo","collector"]
            $table->json('roles')->nullable()->after('puesto');
        });

        // Seed existing rows: set roles = [puesto] for every existing employee
        DB::statement("UPDATE empleados SET roles = JSON_ARRAY(puesto) WHERE roles IS NULL");
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn('roles');
        });
    }
};
