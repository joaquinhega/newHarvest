<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lote 7: soporte de logo institucional para companies.
     *
     * Decisión arquitectónica (ver Docs RRHH 2026, Fase 2): los archivos
     * pesados y de alto volumen (recibos, certificados) van a Google Drive
     * para no sobrecargar el disco/DB del servidor. Los logos de empresa
     * son la excepción: una imagen chica y fija por empresa (no crecen en
     * el tiempo), así que se guardan directamente como BLOB en MySQL.
     *
     * `logo_path` (legacy, columna `path` original) se conserva por ahora
     * para no romper referencias existentes del sistema PHP legacy.
     *
     * Nota: longBlob() no existe en versiones antiguas del Schema Builder
     * de Laravel, se usa DB::statement raw para máxima compatibilidad.
     */
    public function up(): void
    {
        // LONGBLOB: hasta 4 GB, suficiente para cualquier logo de empresa.
        DB::statement("ALTER TABLE `companies` ADD COLUMN `logo_blob` LONGBLOB NULL AFTER `logo_path`");
        DB::statement("ALTER TABLE `companies` ADD COLUMN `logo_mime` VARCHAR(100) NULL AFTER `logo_blob`");
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['logo_blob', 'logo_mime']);
        });
    }
};
