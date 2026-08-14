<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['id' => 1],
            ['name' => 'admin', 'description' => 'Administrador General']
        );

        Role::updateOrCreate(
            ['id' => 2],
            ['name' => 'rrhh', 'description' => 'Recursos Humanos']
        );

        Role::updateOrCreate(
            ['id' => 3],
            ['name' => 'chofer', 'description' => 'Chofer Operativo']
        );

        Role::updateOrCreate(
            ['id' => 4],
            ['name' => 'empresa', 'description' => 'Empresa Cliente']
        );
    }
}
