<?php

namespace App\Http\Requests\Api\Costs;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class ListAllTripCostsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['trip.cost.view', 'trip.cost.reconcile']);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'max:64'],
            'trip_id' => ['nullable', 'integer', 'min:1'],
            'trip_type' => ['nullable', 'string', 'max:64'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'fleet_mode' => ['nullable', Rule::in(['internal', 'vendor_hire', 'taxi', 'unspecified'])],
            'standalone' => ['nullable', 'integer', Rule::in([0, 1])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
