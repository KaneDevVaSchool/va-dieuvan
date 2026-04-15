<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class CreateDispatchRequestRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['request.create']);
    }

    public function rules(): array
    {
        return [
            'trip_type' => ['required', Rule::in(['door_to_door', 'point_to_point', 'business', 'cargo'])],
            'origin' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'depart_at' => ['required', 'date'],
            'arrive_by' => ['nullable', 'date', 'after_or_equal:depart_at'],
            'passenger_count' => ['nullable', 'integer', 'min:1', 'max:999'],
            'notes' => ['nullable', 'string'],
            'source_channel' => ['nullable', Rule::in(['portal', 'zalo', 'paper'])],
            'is_urgent' => ['nullable', 'boolean'],
            'requester_id' => ['nullable', 'integer', 'min:1'],
            'wizard_snapshot' => ['nullable', 'array'],
        ];
    }
}
