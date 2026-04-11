<?php

namespace App\Http\Requests\Api\Payments;

use App\Http\Requests\Api\ApiFormRequest;

class GeneratePaymentsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['payment.reconcile']);
    }

    public function rules(): array
    {
        return [
            'trip_ids' => ['required', 'array', 'min:1'],
            'trip_ids.*' => ['integer', 'min:1'],
        ];
    }
}
