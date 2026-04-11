<?php

namespace App\Http\Requests\Api\Costs;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class SubmitTripCostRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['trip.record.create', 'trip.update_status']);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['fuel', 'toll', 'parking', 'other'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
            'receipt_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
