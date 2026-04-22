<?php

namespace App\Http\Requests\Api\Costs;

use App\Http\Requests\Api\ApiFormRequest;

class ShowTripCostRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['trip.cost.view', 'trip.record.create', 'trip.update_status']);
    }

    public function rules(): array
    {
        return [];
    }
}
