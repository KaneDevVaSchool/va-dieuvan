<?php

namespace App\Http\Requests\Api\Costs;

class SubmitStandaloneTripCostRequest extends SubmitTripCostRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'trip_id' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
