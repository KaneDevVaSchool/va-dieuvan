<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateP2pPolicyTermRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:200'],
            'operating_from' => ['sometimes', 'date'],
            'operating_to' => ['sometimes', 'date'],
            'default_morning_start' => ['nullable', 'date_format:H:i'],
            'default_morning_end' => ['nullable', 'date_format:H:i'],
            'default_afternoon_start' => ['nullable', 'date_format:H:i'],
            'default_afternoon_end' => ['nullable', 'date_format:H:i'],
            'weekdays_mask' => ['nullable', 'integer', 'min:1', 'max:127'],
            'exclude_fixed_holidays' => ['nullable', 'boolean'],
        ];
    }
}
