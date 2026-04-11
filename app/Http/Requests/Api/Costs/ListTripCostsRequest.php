<?php

namespace App\Http\Requests\Api\Costs;

use App\Http\Requests\Api\ApiFormRequest;

class ListTripCostsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['trip.cost.view', 'trip.cost.reconcile', 'trip.record.create', 'trip.update_status']);
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
