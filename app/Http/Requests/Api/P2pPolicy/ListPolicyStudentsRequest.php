<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class ListPolicyStudentsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.view', 'p2p_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'p2p_policy_term_id' => ['nullable', 'integer'],
            'policy_route_id' => ['nullable', 'integer'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'academic_term_id' => ['nullable', 'integer'],
            'campus_id' => ['nullable', 'integer'],
            'class_name' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable'],
            'policy_type' => ['nullable', 'string', 'max:100'],
            'weekday_iso' => ['nullable', 'integer', 'min:1', 'max:7'],
            'q' => ['nullable', 'string', 'max:120'],
        ];
    }
}
