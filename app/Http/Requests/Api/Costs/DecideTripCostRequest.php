<?php

namespace App\Http\Requests\Api\Costs;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class DecideTripCostRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.cost.reconcile']);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['confirm', 'reject'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
