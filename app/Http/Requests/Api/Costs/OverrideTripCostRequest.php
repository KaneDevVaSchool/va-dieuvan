<?php

namespace App\Http\Requests\Api\Costs;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class OverrideTripCostRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['data.override_confirmed']);
    }

    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['fuel', 'toll', 'parking', 'other'])],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
