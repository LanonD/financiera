<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            // Drop the existing foreign key constraint first
            $table->dropForeign(['promotor_id']);

            // Re-define the column as nullable, then re-add the constraint
            $table->unsignedBigInteger('promotor_id')->nullable()->change();

            // Re-add foreign key allowing null (onDelete restrict still applies for non-null)
            $table->foreign('promotor_id')
                  ->references('id')->on('empleados')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropForeign(['promotor_id']);
            $table->unsignedBigInteger('promotor_id')->nullable(false)->change();
            $table->foreign('promotor_id')
                  ->references('id')->on('empleados')
                  ->onDelete('restrict');
        });
    }
};
