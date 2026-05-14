<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── empleados ────────────────────────────────────────────────────────
        Schema::table('empleados', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_id')->nullable()->after('id');
            $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
        });

        // ── clientes ─────────────────────────────────────────────────────────
        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_id')->nullable()->after('id');
            $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
        });

        // ── prestamos ────────────────────────────────────────────────────────
        Schema::table('prestamos', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_id')->nullable()->after('id');
            $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
        });

        // ── Data migration: asignar registros existentes al primer admin ─────
        $firstAdmin = DB::table('users')->where('puesto', 'admin')->orderBy('id')->first();
        if ($firstAdmin) {
            DB::table('empleados')->whereNull('admin_id')->update(['admin_id' => $firstAdmin->id]);
            DB::table('clientes')->whereNull('admin_id')->update(['admin_id'  => $firstAdmin->id]);
            DB::table('prestamos')->whereNull('admin_id')->update(['admin_id' => $firstAdmin->id]);
        }
    }

    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropColumn('admin_id');
        });
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropColumn('admin_id');
        });
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropColumn('admin_id');
        });
    }
};
