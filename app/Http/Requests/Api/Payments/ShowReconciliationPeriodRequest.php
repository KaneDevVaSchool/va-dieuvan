<?php

namespace App\Http\Requests\Api\Payments;

use App\Http\Requests\Api\ApiFormRequest;

class ShowReconciliationPeriodRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['payment.reconcile', 'payment.execute', 'report.view']);
    }

    public function rules(): array
    {
        return [];
    }
}
