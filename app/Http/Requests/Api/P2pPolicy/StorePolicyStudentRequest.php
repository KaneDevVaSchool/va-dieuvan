<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class StorePolicyStudentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'policy_route_id' => ['required', 'integer', 'exists:policy_routes,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'student_code' => ['required_without:student_id', 'string', 'max:20'],
            'student_name' => ['required_without:student_id', 'string', 'max:200'],
            'class_name' => ['nullable', 'string', 'max:20'],
            'direction' => ['nullable', 'in:one_way,two_way'],
            'policy_type' => ['required', 'string', 'max:100'],
            'policy_note' => ['nullable', 'string'],
            'contract_number' => ['nullable', 'string', 'max:50'],
            'sbs_contract' => ['nullable', 'string', 'max:50'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
            'active_weekdays_mask' => ['nullable', 'integer', 'min:1', 'max:127'],
        ];
    }
}
