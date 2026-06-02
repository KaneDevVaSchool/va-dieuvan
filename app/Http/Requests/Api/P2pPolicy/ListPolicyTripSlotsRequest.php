<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;
use App\Support\P2pPolicy;

class ListPolicyTripSlotsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.view', 'p2p_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'p2p_policy_term_id' => ['nullable', 'integer'],
            'policy_route_id' => ['nullable', 'integer'],
            'run_date_from' => ['nullable', 'date'],
            'run_date_to' => ['nullable', 'date'],
            'leg' => ['nullable', 'string', 'in:'.P2pPolicy::LEG_MORNING.','.P2pPolicy::LEG_AFTERNOON],
            'trip_status' => ['nullable', 'string', 'max:32'],
            'has_trip' => ['nullable', 'string', 'in:yes,no'],
            'reminder_status' => ['nullable', 'string', 'in:sent,pending'],
            'q' => ['nullable', 'string', 'max:120'],
        ];
    }
}
