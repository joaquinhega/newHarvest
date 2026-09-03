<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SalaryReceiptStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'period' => ['required', 'string', 'max:50'],
            // Si viene "concepts", estos 3 se recalculan solos y pasan a ser opcionales.
            'gross_amount' => ['nullable', 'numeric', 'min:0'],
            'non_remunerative_amount' => ['nullable', 'numeric', 'min:0'],
            'deductions_amount' => ['nullable', 'numeric', 'min:0'],
            'net_amount' => ['required_without:concepts', 'nullable', 'numeric', 'min:0'],
            'file_path' => ['nullable', 'string', 'max:255'],

            // Conceptos dinámicos (tabla COD / CONCEPTO / CANTIDAD / REM / NO REM / DEDUCCIONES)
            'concepts' => ['nullable', 'array'],
            'concepts.*.code' => ['nullable', 'string', 'max:10'],
            'concepts.*.description' => ['required_with:concepts', 'string', 'max:150'],
            'concepts.*.quantity' => ['nullable', 'numeric'],
            'concepts.*.remunerative_amount' => ['nullable', 'numeric', 'min:0'],
            'concepts.*.non_remunerative_amount' => ['nullable', 'numeric', 'min:0'],
            'concepts.*.deduction_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}