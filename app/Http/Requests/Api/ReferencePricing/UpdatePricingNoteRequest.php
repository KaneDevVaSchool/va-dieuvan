<?php

namespace App\Http\Requests\Api\ReferencePricing;

use App\Http\Requests\Api\ApiFormRequest;

class UpdatePricingNoteRequest extends ApiFormRequest
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
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
