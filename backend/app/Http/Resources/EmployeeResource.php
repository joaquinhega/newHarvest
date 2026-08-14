<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, // Legajo
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'cuil' => $this->cuil,
            'position' => $this->position,
            'hire_date' => $this->hire_date?->format('Y-m-d'),
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'phone' => $this->phone,
            'address' => $this->address,
            'status' => $this->status,
            'borrado' => $this->borrado,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id_usuario' => $this->user?->id_usuario,
                    'username' => $this->user?->username,
                    'email' => $this->user?->email,
                    'letter' => $this->user?->letter,
                ];
            }),
        ];
    }
}