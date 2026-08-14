<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['id_usuario' => 11111111],
            [
                'username' => 'admin',
                'first_name' => 'Admin',
                'last_name' => 'New Harvest',
                'email' => 'admin@newharvest.com.ar',
                'password' => Hash::make('admin123'),
                'role_id' => 1,
                'active' => true,
            ]
        );

        User::updateOrCreate(
            ['id_usuario' => 25237253],
            [
                'username' => 'paulacap',
                'first_name' => 'Paula',
                'last_name' => 'Cappellani',
                'email' => 'paula@newharvest.com.ar',
                'password' => Hash::make('rrhh123'),
                'role_id' => 2,
                'active' => true,
            ]
        );

        User::updateOrCreate(
            ['id_usuario' => 45360092],
            [
                'username' => 'joaquinhg',
                'first_name' => 'Joaquín',
                'last_name' => 'Herrera',
                'email' => 'joaquin@newharvest.com.ar',
                'password' => Hash::make('chofer123'),
                'role_id' => 3,
                'letter' => 'X',
                'active' => true,
            ]
        );
    }
}
