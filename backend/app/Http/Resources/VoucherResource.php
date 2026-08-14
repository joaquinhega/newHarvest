<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'remito_code' => $this->remito_code,
            'company_id' => $this->company_id,
            'company_name' => $this->company_name,
            'user_id' => $this->user_id,
            'passenger_name' => $this->passenger_name,
            'origin' => $this->origin,
            'pickup_time' => $this->pickup_time,
            'destination' => $this->destination,
            'dropoff_time' => $this->dropoff_time,
            'wait_time' => $this->wait_time,
            'signature_path' => $this->signature_path ? asset('storage/' . $this->signature_path) : null,
            'date' => $this->date?->format('Y-m-d'),
            'amount' => $this->amount,
            'observation' => $this->observation,
            'status' => $this->status,
            'borrado' => $this->borrado,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company?->id,
                    'name' => $this->company?->name,
                    'path' => $this->company?->path,
                    'borrado' => $this->company?->borrado,
                ];
            }),
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
            'id_remito_v' => $this->remito_code,
            'Empresa' => $this->company_name,
            'nombre_pasajero' => $this->passenger_name,
            'Origen' => $this->origin,
            'hora_origen' => $this->pickup_time,
            'Destino' => $this->destination,
            'hora_destino' => $this->dropoff_time,
            'Fecha' => $this->date?->format('Y-m-d'),
            'observaciones' => $this->observation,
            'tiempo_espera' => $this->wait_time,
        ];
    }
}