<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;

class AddTripEventRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.event.create']);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:2000'],
            'data' => ['nullable', 'array'],
        ];
    }
}
