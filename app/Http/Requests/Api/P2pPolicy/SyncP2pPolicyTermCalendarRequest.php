<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class SyncP2pPolicyTermCalendarRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'holidays' => ['nullable', 'array'],
            'holidays.*.holiday_date' => ['required_with:holidays', 'date'],
            'holidays.*.label' => ['nullable', 'string', 'max:200'],
            'skip_dates' => ['nullable', 'array'],
            'skip_dates.*.skip_date' => ['required_with:skip_dates', 'date'],
            'skip_dates.*.reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
