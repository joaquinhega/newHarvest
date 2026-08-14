<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'period' => $this->period,
            'gross_amount' => (float) $this->gross_amount,
            'deductions_amount' => (float) $this->deductions_amount,
            'net_amount' => (float) $this->net_amount,
            'status' => $this->status,
            'file_url' => $this->file_path ? asset('storage/' . $this->file_path) : null,
            'employer_signed' => (bool) $this->employer_signed_at,
            'employer_signed_at' => $this->employer_signed_at?->toIso8601String(),
            'employee_signed' => (bool) $this->employee_signed_at,
            'employee_signed_at' => $this->employee_signed_at?->toIso8601String(),
            'employee_signature_url' => $this->employee_signature_path ? asset('storage/' . $this->employee_signature_path) : null,
            'legal_accepted' => $this->legal_accepted,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}