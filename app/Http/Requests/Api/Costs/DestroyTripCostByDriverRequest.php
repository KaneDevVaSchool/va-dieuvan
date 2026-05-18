<?php

namespace App\Http\Requests\Api\Costs;

use App\Http\Requests\Api\ApiFormRequest;

class DestroyTripCostByDriverRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['trip.record.create', 'trip.cost.reconcile']);
    }

    public function rules(): array
    {
        return [];
    }
}
