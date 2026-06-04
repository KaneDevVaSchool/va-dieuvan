<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class ListStudentPoliciesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['student_policy.manage', 'policy_trip.view']);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:active,inactive,suspended'],
            'time_slot' => ['nullable', 'in:morning,afternoon'],
            'semester' => ['nullable', 'integer', 'in:1,2'],
            'route_id' => ['nullable', 'integer', 'exists:routes,id'],
        ];
    }
}
