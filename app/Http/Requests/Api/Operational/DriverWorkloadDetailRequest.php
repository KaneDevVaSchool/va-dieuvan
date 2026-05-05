<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;

class DriverWorkloadDetailRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.driver.manage', 'trip.assign']);
    }

    public function rules(): array
    {
        return [];
    }
}
