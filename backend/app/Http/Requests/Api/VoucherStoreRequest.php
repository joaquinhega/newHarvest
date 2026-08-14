<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoucherStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $legacyCompanyId = $this->input('id_empresa');

        $merge = [
            'remito_code' => $this->input('remito_code', $this->input('id_remito_v', $this->input('id'))),
            'company_name' => $this->input('company_name', $this->input('Empresa')),
            'passenger_name' => $this->input('passenger_name', $this->input('nombre_pasajero')),
            'origin' => $this->input('origin', $this->input('Origen')),
            'pickup_time' => $this->input('pickup_time', $this->input('hora_origen')),
            'destination' => $this->input('destination', $this->input('Destino')),
            'dropoff_time' => $this->input('dropoff_time', $this->input('hora_destino')),
            'wait_time' => $this->input('wait_time', $this->input('tiempo_espera')),
            'date' => $this->input('date', $this->input('Fecha')),
            'observation' => $this->input('observation', $this->input('observaciones')),
            'signature_base64' => $this->input('signature_base64', $this->input('firma')),
        ];

        if ($this->missing('company_id') && $legacyCompanyId !== null && $legacyCompanyId !== '') {
            $merge['company_id'] = $legacyCompanyId;
        }

        $this->merge($merge);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'company_name' => ['nullable', 'string', 'max:150', 'required_without:company_id'],
            'remito_code' => ['nullable', 'string', 'max:50', Rule::unique('vouchers', 'remito_code')],
            'passenger_name' => ['required', 'string', 'max:150'],
            'origin' => ['required', 'string', 'max:255'],
            'pickup_time' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'destination' => ['required', 'string', 'max:255'],
            'dropoff_time' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'wait_time' => ['nullable', 'string', 'max:20'],
            'date' => ['required', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'observation' => ['nullable', 'string'],
            'signature_base64' => ['nullable', 'string'],
        ];
    }
}