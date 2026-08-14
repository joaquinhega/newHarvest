<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CombustibleStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'remito_code' => $this->input('remito_code', $this->input('id_remito_c', $this->input('id'))),
            'date' => $this->input('date', $this->input('fecha', $this->input('Fecha'))),
            'amount' => $this->input('amount', $this->input('monto', $this->input('Monto'))),
            'plate' => $this->input('plate', $this->input('patente')),
            'driver_name' => $this->input('driver_name', $this->input('nombre')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'remito_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('combustibles', 'remito_code'),
            ],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'plate' => ['required', 'string', 'max:20'],
            'driver_name' => ['nullable', 'string', 'max:150'],
        ];
    }
}