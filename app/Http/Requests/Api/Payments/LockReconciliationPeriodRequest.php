<?php

namespace App\Http\Requests\Api\Payments;

use App\Http\Requests\Api\ApiFormRequest;

class LockReconciliationPeriodRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['payment.reconcile']);
    }

    public function rules(): array
    {
        return [];
    }
}
