<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('prestamos', 'descanso_domingos')) {
            Schema::table('prestamos', function (Blueprint $table) {
                $table->boolean('descanso_domingos')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('prestamos', 'descanso_domingos')) {
            Schema::table('prestamos', function (Blueprint $table) {
                $table->dropColumn('descanso_domingos');
            });
        }
    }
};
