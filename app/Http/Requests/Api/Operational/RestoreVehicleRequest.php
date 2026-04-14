<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;

class RestoreVehicleRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.vehicle.manage']);
    }

    public function rules(): array
    {
        return [];
    }
}
