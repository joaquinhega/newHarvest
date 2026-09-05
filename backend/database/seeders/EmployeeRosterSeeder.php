<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

/**
 * Carga en `employees` a las personas que aparecen en el PDF real de
 * liquidación (RECIBOS 05-2026) pero todavía no tenían legajo cargado, y
 * corrige el CUIL de los 3 empleados que ya existían con un dígito
 * verificador distinto al del PDF.
 *
 * Toda la data de esta tabla (incluida la que ya existía antes de este
 * seeder) es de prueba/desarrollo — confirmado por Joaco. El PDF real de
 * liquidación es la fuente de verdad para el CUIL cuando hay conflicto.
 * birth_date, phone y address para los 11 empleados nuevos son datos
 * ficticios de relleno (el PDF no trae esa información), en el mismo
 * estilo que los registros de prueba que ya existían.
 */
class EmployeeRosterSeeder extends Seeder
{
    public function run(): void
    {
        // Corrección de CUIL: la base tenía datos de prueba con el dígito
        // verificador mal cargado. El PDF real de liquidación es la fuente
        // correcta. Se busca por user_id (estable) en vez de por cuil viejo.
        $cuilFixes = [
            27362473 => '20-27362473-3', // Amaya, Hernan — antes 20-27362473-8
            34499168 => '20-34499168-6', // Palacios, Manuel — antes 20-34499168-2
            23347133 => '20-23347133-0', // Robles, Fernando Dario — antes 20-23347133-4
        ];

        foreach ($cuilFixes as $userId => $correctCuil) {
            Employee::where('user_id', $userId)->update(['cuil' => $correctCuil]);
        }

        $roster = [
            // user_id, first_name, last_name, cuil, position, hire_date, birth_date, phone, address
            [23955835, 'Osvaldo Gustavo',  'Garritano',        '20-23955835-7', 'Gerente',        '2016-06-01', '1978-03-14', '+54 9 261 511-2233', 'Ciudad, Mendoza'],
            [29057026, 'Mauricio Fernando','Camaño',           '20-29057026-4', 'Chofer',         '2016-12-01', '1982-07-22', '+54 9 261 522-3344', 'Guaymallén, Mendoza'],
            [25237253, 'Paula D.',         'Cappellani',       '27-25237253-4', 'Administrativo', '2017-09-01', '1987-11-05', '+54 9 261 533-4455', 'Godoy Cruz, Mendoza'],
            [31282681, 'Juan Manuel',      'Barta',            '20-31282681-0', 'Chofer',         '2018-11-22', '1980-02-18', '+54 9 261 544-5566', 'Las Heras, Mendoza'],
            [31546179, 'Miguel Angel',     'Quiroga',          '20-31546179-1', 'Chofer',         '2019-04-25', '1985-09-30', '+54 9 261 555-6677', 'Maipú, Mendoza'],
            [37624656, 'Gustavo',          'Sotomayor Valero', '20-37624656-7', 'Chofer',         '2019-11-27', '1988-05-12', '+54 9 261 566-7788', 'Rivadavia, Mendoza'],
            [45144024, 'Genaro',           'Garritano',        '20-45144024-2', 'Auxiliar',       '2026-03-01', '2002-08-09', '+54 9 261 577-8899', 'Ciudad, Mendoza'],
            [23342848, 'Edgardo Cesar',    'Gonzalez',         '20-23342848-6', 'Chofer',         '2022-12-01', '1984-12-27', '+54 9 261 588-9900', 'Luján de Cuyo, Mendoza'],
            [21739605, 'Angel Ceferino',   'Peña',             '20-21739605-1', 'Chofer inicial', '2023-11-01', '1993-04-03', '+54 9 261 599-0011', 'Godoy Cruz, Mendoza'],
            [43354030, 'Federica',         'Merino Melendo',   '27-43354030-7', 'Auxiliar',       '2024-01-02', '1997-10-19', '+54 9 261 600-1122', 'Ciudad, Mendoza'],
            [23589588, 'Miriam Mabel',     'Morales',          '27-23589588-4', 'Chofer inicial', '2024-11-01', '1986-06-25', '+54 9 261 611-2233', 'Maipú, Mendoza'],
        ];

        foreach ($roster as [$userId, $firstName, $lastName, $cuil, $position, $hireDate, $birthDate, $phone, $address]) {
            Employee::updateOrCreate(
                ['cuil' => $cuil],
                [
                    'user_id'    => $userId,
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'position'   => $position,
                    'hire_date'  => $hireDate,
                    'birth_date' => $birthDate,
                    'phone'      => $phone,
                    'address'    => $address,
                    'status'     => 'activo',
                    'borrado'    => false,
                ]
            );
        }

        $this->command?->info('EmployeeRosterSeeder: 3 CUIL corregidos (Amaya, Palacios, Robles) + ' . count($roster) . ' empleados cargados/actualizados.');
        $this->command?->warn('Nota: birth_date, phone y address de los 11 empleados nuevos son datos ficticios de prueba — el PDF de recibos no trae esa información.');
    }
}
