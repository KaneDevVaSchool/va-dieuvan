<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class UpdatePolicyRouteRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:200'],
            'origin_campus_id' => ['sometimes', 'integer', 'exists:campuses,id'],
            'dest_campus_id' => ['sometimes', 'integer', 'exists:campuses,id'],
            'morning_start' => ['nullable', 'date_format:H:i'],
            'morning_end' => ['nullable', 'date_format:H:i'],
            'afternoon_start' => ['nullable', 'date_format:H:i'],
            'afternoon_end' => ['nullable', 'date_format:H:i'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
