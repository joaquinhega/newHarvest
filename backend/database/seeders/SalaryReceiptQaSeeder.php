<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\SalaryReceipt;
use Illuminate\Database\Seeder;

class SalaryReceiptQaSeeder extends Seeder
{
    /**
     * Crea un recibo de sueldo con conceptos dinámicos, listo para probar
     * el PDF del Lote 1 sin tener que cargar nada a mano.
     *
     * Uso: php artisan db:seed --class=SalaryReceiptQaSeeder
     */
    public function run(): void
    {
        $employee = Employee::where('cuil', '20-34499168-6')->first()
            ?? Employee::where('borrado', false)->first();

        if (! $employee) {
            $this->command->error('No hay ningún empleado en la base para asociar el recibo de QA.');
            return;
        }

        $receipt = SalaryReceipt::create([
            'employee_id' => $employee->id,
            'period' => 'Agosto 2026 (QA)',
            'net_amount' => 0, // se recalcula abajo a partir de los conceptos
            'status' => 'generado',
            'borrado' => false,
        ]);

        $concepts = [
            ['code' => '096', 'description' => 'TURNOS', 'quantity' => 24, 'remunerative_amount' => 1181184.50],
            ['code' => '135', 'description' => 'Adicional art. 68-71', 'quantity' => 1, 'remunerative_amount' => 161242.83],
            ['code' => '196', 'description' => 'NO REMUNERATIVO', 'quantity' => 1, 'non_remunerative_amount' => 238228.05],
            ['code' => '301', 'description' => 'JUBILACION 11%', 'quantity' => 0.11, 'deduction_amount' => 147667.01],
            ['code' => '302', 'description' => 'LEY 19032 3%', 'deduction_amount' => 40272.82],
            ['code' => '303', 'description' => 'OBRA SOCIAL Y ANSSAL', 'quantity' => 1, 'deduction_amount' => 47419.66],
            ['code' => '309', 'description' => 'Uso convenio', 'quantity' => 1, 'deduction_amount' => 23373.00],
        ];

        foreach ($concepts as $index => $concept) {
            $receipt->concepts()->create($concept + ['sort_order' => $index]);
        }

        $receipt->recalculateTotalsFromConcepts()->save();

        $this->command->info("Recibo de QA creado: ID {$receipt->id}, empleado {$employee->full_name} (legajo {$employee->id}).");
        $this->command->info("PDF: /rrhh/recibos/{$receipt->id}/pdf");
    }
}
