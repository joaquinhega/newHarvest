<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Tres roles fijos que reflejan la DB real de producción.
     *
     * - rrhh  (id=1): Backoffice web completo — operaciones, RRHH y auditoría.
     *                  Es el rol del personal interno de New Harvest.
     * - chofer (id=2): Solo app mobile. Sin acceso al backoffice web.
     * - admin  (id=3): Superusuario técnico. Igual acceso que rrhh hoy,
     *                  reservado para Panel de Diagnóstico/Telemetría (Fase 4.5).
     *                  Actualmente asignado al desarrollador externo.
     *
     * IDs fijos para compatibilidad con datos históricos y ETL futuro.
     */
    public function run(): void
    {
        Role::updateOrCreate(
            ['id' => 1],
            ['name' => 'rrhh', 'description' => 'RRHH / Administración']
        );

        Role::updateOrCreate(
            ['id' => 2],
            ['name' => 'chofer', 'description' => 'Chofer Operativo']
        );

        Role::updateOrCreate(
            ['id' => 3],
            ['name' => 'admin', 'description' => 'Administrador Técnico']
        );
    }
}
