<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar columna notified_at para registrar cuándo se notificó al empleado
        DB::statement("ALTER TABLE salary_receipts ADD COLUMN notified_at TIMESTAMP NULL DEFAULT NULL AFTER employer_signed_at");
        DB::statement("ALTER TABLE salary_receipts ADD COLUMN notified_by_user_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER notified_at");

        // Actualizar enum para reflejar los nuevos estados semánticos
        // 'firmado_empleado' pasa a significar "completo" (ambas firmas, pendiente de Drive)
        // 'archivado' pasa a significar "en_drive" (ya subido a Google Drive)
        // Los valores en DB no cambian para no romper datos existentes
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE salary_receipts DROP COLUMN notified_at");
        DB::statement("ALTER TABLE salary_receipts DROP COLUMN notified_by_user_id");
    }
};
