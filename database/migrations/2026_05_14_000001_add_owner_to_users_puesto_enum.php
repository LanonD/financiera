<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ampliar el ENUM de 'puesto' para incluir 'owner'
        DB::statement("ALTER TABLE users MODIFY COLUMN puesto ENUM('owner','admin','promo','collector','desembolso') NOT NULL");
    }

    public function down(): void
    {
        // Revertir (primero elimina los usuarios owner si hubiera)
        DB::statement("UPDATE users SET puesto = 'admin' WHERE puesto = 'owner'");
        DB::statement("ALTER TABLE users MODIFY COLUMN puesto ENUM('admin','promo','collector','desembolso') NOT NULL");
    }
};
