<?php

namespace App\Http\Requests\Api\Payments;

use App\Http\Requests\Api\ApiFormRequest;

class CreateReconciliationPeriodRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['payment.reconcile']);
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}
