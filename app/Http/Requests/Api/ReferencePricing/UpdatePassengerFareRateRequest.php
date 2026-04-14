<?php

namespace App\Http\Requests\Api\ReferencePricing;

use App\Http\Requests\Api\ApiFormRequest;

class UpdatePassengerFareRateRequest extends ApiFormRequest
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
            'package_label' => ['sometimes', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'seat_7' => ['nullable', 'numeric', 'min:0'],
            'seat_15' => ['nullable', 'numeric', 'min:0'],
            'seat_28' => ['nullable', 'numeric', 'min:0'],
            'seat_33' => ['nullable', 'numeric', 'min:0'],
            'seat_45' => ['nullable', 'numeric', 'min:0'],
            'limo_9' => ['nullable', 'numeric', 'min:0'],
            'limo_11' => ['nullable', 'numeric', 'min:0'],
            'driver_self_support' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
