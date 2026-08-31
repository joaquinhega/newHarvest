<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Correcciones de alineación DB:
     * 1. companies.path → logo_path (semántica correcta para ETL futuro)
     * 2. leave_requests.status '' → 'aprobada' (registros con approved_by seteado)
     */
    public function up(): void
    {
        // 1. Renombrar columna path → logo_path en companies
        Schema::table('companies', function (Blueprint $table) {
            $table->renameColumn('path', 'logo_path');
        });

        // 2. Corregir status vacío en leave_requests
        // Los registros con approved_by_user_id y action_at seteados
        // fueron procesados — el '' es un bug del seed, no un estado real.
        DB::statement("
            UPDATE leave_requests
            SET status = 'aprobada'
            WHERE status = '' AND approved_by_user_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->renameColumn('logo_path', 'path');
        });

        // No revertimos el fix de datos — '' no es un estado válido del ENUM
    }
};
