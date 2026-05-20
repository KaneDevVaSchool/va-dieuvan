<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class StorePolicyRouteRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'p2p_policy_term_id' => ['required', 'integer', 'exists:p2p_policy_terms,id'],
            'name' => ['required', 'string', 'max:200'],
            'origin_campus_id' => ['required', 'integer', 'exists:campuses,id'],
            'dest_campus_id' => ['required', 'integer', 'exists:campuses,id'],
            'morning_start' => ['nullable', 'date_format:H:i'],
            'morning_end' => ['nullable', 'date_format:H:i'],
            'afternoon_start' => ['nullable', 'date_format:H:i'],
            'afternoon_end' => ['nullable', 'date_format:H:i'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
