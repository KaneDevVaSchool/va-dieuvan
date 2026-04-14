<?php

namespace App\Http\Requests\Api\ReferencePricing;

use App\Http\Requests\Api\ApiFormRequest;

class ListReferencePricingRevisionsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['reference_pricing.manage']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:passenger_fare_rate,cargo_fare_rate,pricing_note'],
            'id' => ['required', 'integer', 'min:1'],
        ];
    }
}
