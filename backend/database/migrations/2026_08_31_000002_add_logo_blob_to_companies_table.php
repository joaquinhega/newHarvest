<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->longBlob('logo_blob')->nullable()->after('logo_path');
            $table->string('logo_mime', 100)->nullable()->after('logo_blob');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['logo_blob', 'logo_mime']);
        });
    }
};
