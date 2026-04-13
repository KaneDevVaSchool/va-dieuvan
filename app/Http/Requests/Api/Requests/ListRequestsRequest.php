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
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'cancelled'])],
            'trip_type' => ['nullable', Rule::in(['door_to_door', 'point_to_point', 'business', 'cargo'])],
            'source_channel' => ['nullable', Rule::in(['portal', 'zalo', 'paper'])],
            'paper_status' => ['nullable', Rule::in(['pending', 'received', 'digitally_signed'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
