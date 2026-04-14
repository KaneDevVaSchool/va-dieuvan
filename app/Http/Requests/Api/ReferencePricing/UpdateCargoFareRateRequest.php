<?php

namespace App\Http\Requests\Api\ReferencePricing;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateCargoFareRateRequest extends ApiFormRequest
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
            'route_label' => ['sometimes', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'one_crate_50_40_50' => ['sometimes', 'numeric', 'min:0'],
            'crates_2_to_5_50_40_50' => ['sometimes', 'numeric', 'min:0'],
            'van_500kg' => ['sometimes', 'numeric', 'min:0'],
            'van_1000kg' => ['sometimes', 'numeric', 'min:0'],
            'van_2000kg' => ['sometimes', 'numeric', 'min:0'],
            'loading_assist_per_point' => ['sometimes', 'numeric', 'min:0'],
            'waiting_fee_per_hour' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
