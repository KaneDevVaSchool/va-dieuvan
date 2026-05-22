<?php

namespace App\Http\Requests\Api\ReferencePricing;

use App\Http\Requests\Api\ApiFormRequest;

class SuggestReferencePricingRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'trip_type' => ['required', 'string', 'max:64'],
            'origin' => ['nullable', 'string', 'max:500'],
            'destination' => ['nullable', 'string', 'max:500'],
            'passenger_count' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }
}
