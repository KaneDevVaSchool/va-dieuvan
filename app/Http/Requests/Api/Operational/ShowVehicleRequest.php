<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;

class ShowVehicleRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.vehicle.manage', 'trip.assign']);
    }

    public function rules(): array
    {
        return [];
    }
}
