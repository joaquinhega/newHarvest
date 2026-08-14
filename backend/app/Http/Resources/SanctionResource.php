<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SanctionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'sanction_number' => $this->sanction_number,
            'type' => $this->type,
            'days_count' => $this->days_count,
            'date' => $this->date?->format('Y-m-d'),
            'reason' => $this->reason,
            'status' => $this->status,
            'read_at' => $this->read_at?->toIso8601String(),
            'signed_at' => $this->signed_at?->toIso8601String(),
            'signature_url' => $this->signature_path ? asset('storage/' . $this->signature_path) : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}