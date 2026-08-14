<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'type' => $this->type,
            'days_count' => $this->days_count,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'diagnosis' => $this->diagnosis,
            'attachment_url' => $this->attachment_path ? asset('storage/' . $this->attachment_path) : null,
            'status' => $this->status,
            'reviewed_by' => $this->whenLoaded('reviewer', fn() => $this->reviewer?->username),
            'action_at' => $this->action_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}