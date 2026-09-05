<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

/**
 * Carga en `employees` a las personas que aparecen en el PDF real de
 * liquidación (RECIBOS 05-2026) pero todavía no tenían legajo cargado.
 * Los datos (CUIL, cargo, fecha de ingreso) salen del propio PDF y se
 * vinculan al user_id existente en `users` para que el login ya funcione.
 *
 * IMPORTANTE — no toca a los 5 empleados que ya existían (Admin, Gatica,
 * Palacios, Robles, Amaya). Se detectó que Amaya, Palacios y Robles tienen
 * el CUIL distinto entre la base actual y el PDF (difiere solo el dígito
 * verificador). No se corrige automáticamente — requiere que Joaco confirme
 * cuál de las dos fuentes es la correcta antes de tocar datos de nómina.
 */
class EmployeeRosterSeeder extends Seeder
{
    public function run(): void
    {
        $roster = [
            // user_id,  first_name,        last_name,   cuil,           position,        hire_date
            [23955835, 'Osvaldo Gustavo', 'Garritano',    '20-23955835-7', 'Gerente',              '2016-06-01'],
            [29057026, 'Mauricio Fernando','Camaño',      '20-29057026-4', 'Chofer',               '2016-12-01'],
            [25237253, 'Paula D.',        'Cappellani',   '27-25237253-4', 'Administrativo',       '2017-09-01'],
            [31282681, 'Juan Manuel',     'Barta',        '20-31282681-0', 'Chofer',               '2018-11-22'],
            [31546179, 'Miguel Angel',    'Quiroga',      '20-31546179-1', 'Chofer',               '2019-04-25'],
            [37624656, 'Gustavo',         'Sotomayor Valero', '20-37624656-7', 'Chofer',           '2019-11-27'],
            [45144024, 'Genaro',          'Garritano',    '20-45144024-2', 'Auxiliar',             '2026-03-01'],
            [23342848, 'Edgardo Cesar',   'Gonzalez',     '20-23342848-6', 'Chofer',               '2022-12-01'],
            [21739605, 'Angel Ceferino',  'Peña',         '20-21739605-1', 'Chofer inicial',       '2023-11-01'],
            [43354030, 'Federica',        'Merino Melendo', '27-43354030-7', 'Auxiliar',           '2024-01-02'],
            [23589588, 'Miriam Mabel',    'Morales',      '27-23589588-4', 'Chofer inicial',       '2024-11-01'],
        ];

        foreach ($roster as [$userId, $firstName, $lastName, $cuil, $position, $hireDate]) {
            Employee::updateOrCreate(
                ['cuil' => $cuil],
                [
                    'user_id'   => $userId,
                    'first_name'=> $firstName,
                    'last_name' => $lastName,
                    'position'  => $position,
                    'hire_date' => $hireDate,
                    'status'    => 'activo',
                    'borrado'   => false,
                ]
            );
        }

        $this->command?->info('EmployeeRosterSeeder: ' . count($roster) . ' empleados cargados/actualizados.');
        $this->command?->warn('Nota: Amaya, Palacios y Robles NO se tocaron — su CUIL en la base difiere del CUIL real leído en el PDF (solo el dígito verificador). Confirmar manualmente cuál es correcto antes de corregir.');
    }
}
