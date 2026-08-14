<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CombustibleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'remito_code' => $this->remito_code,
            'user_id' => $this->user_id,
            'driver_name' => $this->driver_name,
            'plate' => $this->plate,
            'date' => $this->date?->format('Y-m-d'),
            'amount' => $this->amount,
            'status' => $this->status,
            'borrado' => $this->borrado,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id_usuario' => $this->user?->id_usuario,
                    'username' => $this->user?->username,
                    'first_name' => $this->user?->first_name,
                    'last_name' => $this->user?->last_name,
                    'letter' => $this->user?->letter,
                ];
            }),
            // Legacy aliases consumed by mobile/legacy clients.
            'id_remito_c' => $this->remito_code,
            'Fecha' => $this->date?->format('Y-m-d'),
            'Monto' => $this->amount,
            'patente' => $this->plate,
            'nombre' => $this->driver_name,
        ];
    }
}