<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;

class ListVehiclesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.vehicle.manage', 'trip.assign']);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:ready,in_use,maintenance,broken'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
