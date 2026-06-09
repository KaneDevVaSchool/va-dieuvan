<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateTripStatusRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.update_status']);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['driver_confirmed', 'in_progress', 'completed', 'incident', 'cancelled'])],
            'schedule_key' => ['nullable', 'string', 'max:64'],
            'message' => ['nullable', 'string', 'max:1000'],
            'lock_version' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
