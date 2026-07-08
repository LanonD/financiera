<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE clientes c
            SET c.ine = (
                SELECT p.doc_ine
                FROM prestamos p
                WHERE p.cliente_id = c.id AND p.doc_ine IS NOT NULL AND p.doc_ine <> ''
                ORDER BY p.id DESC
                LIMIT 1
            )
            WHERE (c.ine IS NULL OR c.ine = '')
              AND EXISTS (
                SELECT 1 FROM prestamos p
                WHERE p.cliente_id = c.id AND p.doc_ine IS NOT NULL AND p.doc_ine <> ''
              )
        ");

        DB::statement("
            UPDATE clientes c
            SET c.comprobante = (
                SELECT p.doc_comprobante
                FROM prestamos p
                WHERE p.cliente_id = c.id AND p.doc_comprobante IS NOT NULL AND p.doc_comprobante <> ''
                ORDER BY p.id DESC
                LIMIT 1
            )
            WHERE (c.comprobante IS NULL OR c.comprobante = '')
              AND EXISTS (
                SELECT 1 FROM prestamos p
                WHERE p.cliente_id = c.id AND p.doc_comprobante IS NOT NULL AND p.doc_comprobante <> ''
              )
        ");

        DB::statement("
            UPDATE clientes c
            SET c.foto_vivienda = (
                SELECT p.doc_foto_domicilio
                FROM prestamos p
                WHERE p.cliente_id = c.id AND p.doc_foto_domicilio IS NOT NULL AND p.doc_foto_domicilio <> ''
                ORDER BY p.id DESC
                LIMIT 1
            )
            WHERE (c.foto_vivienda IS NULL OR c.foto_vivienda = '')
              AND EXISTS (
                SELECT 1 FROM prestamos p
                WHERE p.cliente_id = c.id AND p.doc_foto_domicilio IS NOT NULL AND p.doc_foto_domicilio <> ''
              )
        ");
    }

    public function down(): void
    {
        // No se revierten referencias porque los archivos originales siguen existiendo
        // y podrian haber sido actualizados desde el perfil del cliente.
    }
};
