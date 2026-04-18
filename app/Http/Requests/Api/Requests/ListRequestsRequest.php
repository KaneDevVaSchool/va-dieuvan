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

    /**
     * Query strings send "true"/"false" as strings; Laravel's boolean rule only accepts true/false/0/1/'0'/'1'.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['is_urgent', 'sla_risk_only', 'only_trashed'] as $key) {
            if (! $this->has($key)) {
                continue;
            }
            $v = $this->input($key);
            if ($v === 'true' || $v === '1' || $v === 1 || $v === true) {
                $merge[$key] = true;
            } elseif ($v === 'false' || $v === '0' || $v === 0 || $v === false) {
                $merge[$key] = false;
            }
        }

        foreach (['q', 'status', 'trip_type', 'source_channel', 'paper_status', 'from', 'to', 'trip_status'] as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $merge[$key] = null;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $toRules = ['nullable', 'date'];
        if ($this->filled('from')) {
            $toRules[] = 'after_or_equal:from';
        }

        return [
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['draft', 'pending', 'approved', 'rejected', 'cancelled'])],
            'trip_status' => ['nullable', Rule::in([
                'pending', 'approved', 'assigned', 'driver_confirmed', 'in_progress', 'completed', 'cancelled', 'incident',
            ])],
            'trip_type' => ['nullable', Rule::in(['door_to_door', 'point_to_point', 'business', 'cargo'])],
            'source_channel' => ['nullable', Rule::in(['portal', 'zalo', 'paper'])],
            'paper_status' => ['nullable', Rule::in(['pending', 'received', 'digitally_signed'])],
            'is_urgent' => ['nullable', 'boolean'],
            'sla_risk_only' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'to' => $toRules,
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'only_trashed' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', Rule::in([
                'created_desc',
                'created_asc',
                'depart_desc',
                'depart_asc',
                'id_desc',
            ])],
        ];
    }
}
