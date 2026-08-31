<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Solo dos roles: rrhh (backoffice web, incluye operaciones y admin)
        // y chofer (solo app mobile).
        Role::updateOrCreate(
            ['id' => 1],
            ['name' => 'rrhh', 'description' => 'RRHH / Administración']
        );

        Role::updateOrCreate(
            ['id' => 2],
            ['name' => 'chofer', 'description' => 'Chofer Operativo']
        );
    }
}
