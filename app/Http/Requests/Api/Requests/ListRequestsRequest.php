<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class ListRequestsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['draft', 'pending', 'approved', 'rejected', 'cancelled'])],
            'trip_status' => ['nullable', Rule::in([
                'pending', 'approved', 'assigned', 'driver_confirmed', 'in_progress', 'completed', 'cancelled', 'incident',
            ])],
            'trip_type' => ['nullable', Rule::in(['door_to_door', 'point_to_point', 'business', 'cargo'])],
            'source_channel' => ['nullable', Rule::in(['portal', 'zalo', 'paper'])],
            'paper_status' => ['nullable', Rule::in(['pending', 'received', 'digitally_signed'])],
            'sla_risk_only' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
