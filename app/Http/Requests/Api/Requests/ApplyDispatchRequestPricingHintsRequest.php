<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;

class ApplyDispatchRequestPricingHintsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['request.fill_price', 'request.update']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estimated_distance_km' => ['nullable', 'numeric', 'min:0'],
            'reference_unit_price' => ['nullable', 'numeric', 'min:0'],
            'pricing_source' => ['nullable', 'string', 'max:64'],
            'pricing_row_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_hint' => ['nullable', 'string', 'max:128'],
        ];
    }
}
