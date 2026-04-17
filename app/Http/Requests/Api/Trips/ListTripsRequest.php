<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class ListTripsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['trip.view_all', 'trip.view_own']);
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('is_urgent')) {
            $v = $this->input('is_urgent');
            if ($v === 'true' || $v === '1' || $v === 1 || $v === true) {
                $merge['is_urgent'] = true;
            } elseif ($v === 'false' || $v === '0' || $v === 0 || $v === false || $v === '') {
                $merge['is_urgent'] = null;
            }
        }
        foreach (['status', 'trip_type', 'source_channel', 'paper_status', 'fleet_mode'] as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $merge[$key] = null;
            }
        }
        if ($this->has('q')) {
            $qt = trim((string) $this->input('q'));
            $merge['q'] = $qt === '' ? null : $qt;
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'max:50'],
            'trip_type' => ['nullable', Rule::in(['door_to_door', 'point_to_point', 'business', 'cargo'])],
            'source_channel' => ['nullable', Rule::in(['portal', 'zalo', 'paper'])],
            'paper_status' => ['nullable', Rule::in(['pending', 'received', 'digitally_signed'])],
            'is_urgent' => ['nullable', 'boolean'],
            'fleet_mode' => ['nullable', Rule::in(['internal', 'vendor_hire', 'taxi', 'unspecified'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
