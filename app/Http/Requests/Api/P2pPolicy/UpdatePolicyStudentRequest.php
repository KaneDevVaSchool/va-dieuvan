<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class UpdatePolicyStudentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'class_name' => ['nullable', 'string', 'max:20'],
            'direction' => ['nullable', 'in:one_way,two_way'],
            'policy_type' => ['sometimes', 'string', 'max:100'],
            'policy_note' => ['nullable', 'string'],
            'contract_number' => ['nullable', 'string', 'max:50'],
            'sbs_contract' => ['nullable', 'string', 'max:50'],
            'effective_from' => ['sometimes', 'date'],
            'effective_to' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'active_weekdays_mask' => ['nullable', 'integer', 'min:1', 'max:127'],
        ];
    }
}
