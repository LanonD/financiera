<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            // Self-referencing FK: links a collector/desembolsador to their promotor's team
            $table->unsignedBigInteger('promotor_id')->nullable()->after('admin_id');
            $table->foreign('promotor_id')->references('id')->on('empleados')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropForeign(['promotor_id']);
            $table->dropColumn('promotor_id');
        });
    }
};
