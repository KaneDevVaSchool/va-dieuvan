<?php

namespace App\Http\Requests\Api\TransportProgram;

use App\Http\Requests\Api\ApiFormRequest;

class AssignDayDriverRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_driver_assign.manage']);
    }

    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'shift' => ['nullable', 'in:morning,afternoon'],
        ];
    }
}
