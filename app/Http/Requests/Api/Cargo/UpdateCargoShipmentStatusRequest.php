<?php

namespace App\Http\Requests\Api\Cargo;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateCargoShipmentStatusRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['cargo.manage']);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['picked_up', 'in_transit', 'delivered', 'failed', 'cancelled'])],
        ];
    }
}
