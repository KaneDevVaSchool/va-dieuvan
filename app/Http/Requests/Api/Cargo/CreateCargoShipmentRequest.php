<?php

namespace App\Http\Requests\Api\Cargo;

use App\Http\Requests\Api\ApiFormRequest;

class CreateCargoShipmentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['cargo.manage']);
    }

    public function rules(): array
    {
        return [
            'sender_name' => ['nullable', 'string', 'max:255'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'pickup_address' => ['required', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'weight_grams' => ['nullable', 'integer', 'min:0'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'sla_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
        ];
    }
}
