<?php

namespace App\Http\Requests\Api\Costs;

use App\Http\Requests\Api\ApiFormRequest;

class DestroyTripCostByDriverRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.record.create']);
    }

    public function rules(): array
    {
        return [];
    }
}
