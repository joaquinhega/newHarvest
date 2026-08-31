<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
                Rule::unique('companies', 'name')->ignore($companyId),
            ],
            'logo_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'borrado' => ['sometimes', 'boolean'],
        ];
    }
}