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
        // 1. Renombrar path → logo_path de forma compatible con MariaDB < 10.5:
        //    agregar la columna nueva, copiar los datos, eliminar la vieja.
        if (Schema::hasColumn('companies', 'path') && ! Schema::hasColumn('companies', 'logo_path')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('logo_path', 255)->nullable()->after('name');
            });
            DB::statement("UPDATE `companies` SET `logo_path` = `path`");
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('path');
            });
        }

        // 2. Corregir status vacío en leave_requests
        DB::statement("
            UPDATE leave_requests
            SET status = 'aprobada'
            WHERE status = '' AND approved_by_user_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'logo_path') && ! Schema::hasColumn('companies', 'path')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('path', 255)->nullable()->after('name');
            });
            DB::statement("UPDATE `companies` SET `path` = `logo_path`");
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('logo_path');
            });
        }
    }
};
