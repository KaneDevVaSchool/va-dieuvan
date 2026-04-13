<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateVehicleRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.vehicle.manage', 'trip.assign']);
    }

    public function rules(): array
    {
        return [
            'default_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
        ];
    }
}
