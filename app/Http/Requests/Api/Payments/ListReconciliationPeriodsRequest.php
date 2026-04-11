<?php

namespace App\Http\Requests\Api\Payments;

use App\Http\Requests\Api\ApiFormRequest;

class ListReconciliationPeriodsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['payment.reconcile', 'payment.execute', 'report.view']);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
