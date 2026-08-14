<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoucherUpdateRequest extends FormRequest
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
        $voucherId = $this->route('voucher')?->id;

        return [
            'company_id' => ['sometimes', 'nullable', 'integer', 'exists:companies,id'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'remito_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('vouchers', 'remito_code')->ignore($voucherId),
            ],
            'passenger_name' => ['sometimes', 'required', 'string', 'max:150'],
            'origin' => ['sometimes', 'required', 'string', 'max:255'],
            'pickup_time' => ['sometimes', 'required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'destination' => ['sometimes', 'required', 'string', 'max:255'],
            'dropoff_time' => ['sometimes', 'required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'wait_time' => ['sometimes', 'nullable', 'string', 'max:20'],
            'date' => ['sometimes', 'required', 'date'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'observation' => ['sometimes', 'nullable', 'string'],
            'signature_base64' => ['sometimes', 'nullable', 'string'],
        ];
    }
}