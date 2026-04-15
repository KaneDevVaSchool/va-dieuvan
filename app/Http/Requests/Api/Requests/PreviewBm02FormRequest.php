<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;

class PreviewBm02FormRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['request.create']);
    }

    public function rules(): array
    {
        return [
            'wizard' => ['required', 'array'],
            'wizard.form' => ['sometimes', 'array'],
            'wizard.passengerRows' => ['sometimes', 'array'],
            'wizard.businessRows' => ['sometimes', 'array'],
            'wizard.cargoRows' => ['sometimes', 'array'],
        ];
    }
}
