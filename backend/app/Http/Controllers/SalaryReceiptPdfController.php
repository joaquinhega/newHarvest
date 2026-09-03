<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\SalaryReceipt;
use Barryvdh\DomPDF\Facade\Pdf;

class SalaryReceiptPdfController extends Controller
{
    /**
     * Generar y descargar el PDF de un recibo de sueldo.
     *
     * El PDF se arma 100% a partir de los conceptos dinámicos cargados (tabla
     * salary_receipt_concepts), no de columnas fijas, para poder representar
     * cualquier combinación de haberes / no remunerativos / deducciones.
     */
    public function generate($id)
    {
        $receipt = SalaryReceipt::with(['employee', 'concepts'])
            ->where('borrado', false)
            ->findOrFail($id);

        $employee = $receipt->employee;
        $company = Company::where('name', 'New Harvest')->first();

        $data = [
            'numero_recibo' => $receipt->id,
            'empresa_nombre' => $company?->name ?: 'NEW HARVEST S.A.',
            'empresa_cuit' => $company?->cuit ?: config('newharvest.default_cuit', ''),
            'empresa_domicilio' => $company?->address ?: config('newharvest.default_address', ''),

            'legajo' => $employee?->id ?? '—',
            'apellido_nombre' => $employee
                ? strtoupper($employee->last_name) . ', ' . $employee->first_name
                : 'Personal desvinculado',
            'cuil' => $employee?->cuil ?: '—',
            'cargo' => $employee?->position ?: '—',
            'fecha_ingreso' => $employee?->hire_date ? $employee->hire_date->format('d/m/Y') : '—',
            'periodo' => $receipt->period,

            'conceptos' => $receipt->concepts->map(fn ($c) => [
                'code' => $c->code,
                'description' => $c->description,
                'quantity' => $c->quantity,
                'remunerative_amount' => (float) $c->remunerative_amount,
                'non_remunerative_amount' => (float) $c->non_remunerative_amount,
                'deduction_amount' => (float) $c->deduction_amount,
            ]),

            'total_remunerativo' => (float) $receipt->gross_amount,
            'total_no_remunerativo' => (float) $receipt->non_remunerative_amount,
            'total_deducciones' => (float) $receipt->deductions_amount,
            'importe_neto' => (float) $receipt->net_amount,

            // Estado de firmas: hasta el Lote 4/9 esto es un sello simple con fecha,
            // no todavía una firma criptográfica real sobre el archivo.
            'empleado_firmado' => (bool) $receipt->employee_signed_at,
            'empleado_firmado_fecha' => $receipt->employee_signed_at?->format('d/m/Y H:i'),
            'empleado_nombre' => $employee ? "{$employee->first_name} {$employee->last_name}" : '',
            'empleado_cuil' => $employee?->cuil ?: '',

            'empresa_firmado' => (bool) $receipt->employer_signed_at,
            'empresa_firmado_fecha' => $receipt->employer_signed_at?->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdfs.salary_receipt', $data);

        return $pdf->download("Recibo_{$receipt->id}_{$receipt->period}.pdf");
    }
}
