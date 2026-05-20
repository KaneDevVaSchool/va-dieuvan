<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class StoreP2pPolicyTermRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'academic_term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'operating_from' => ['required', 'date'],
            'operating_to' => ['required', 'date', 'after_or_equal:operating_from'],
            'default_morning_start' => ['nullable', 'date_format:H:i'],
            'default_morning_end' => ['nullable', 'date_format:H:i'],
            'default_afternoon_start' => ['nullable', 'date_format:H:i'],
            'default_afternoon_end' => ['nullable', 'date_format:H:i'],
            'weekdays_mask' => ['nullable', 'integer', 'min:1', 'max:127'],
            'exclude_fixed_holidays' => ['nullable', 'boolean'],
        ];
    }
}
