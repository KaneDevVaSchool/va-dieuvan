<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateDispatchSettingRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['dispatch.settings.manage']);
    }

    public function rules(): array
    {
        return [
            'passenger_urgent_threshold_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'cargo_urgent_threshold_hours' => ['required', 'integer', 'min:1', 'max:8760'],
        ];
    }
}
