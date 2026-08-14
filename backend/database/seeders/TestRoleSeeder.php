<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class TestRoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['id' => 1], ['name' => 'admin', 'description' => 'Administrador']);
        Role::firstOrCreate(['id' => 2], ['name' => 'rrhh', 'description' => 'Recursos Humanos']);
        Role::firstOrCreate(['id' => 3], ['name' => 'chofer', 'description' => 'Chofer Operativo']);
    }
}