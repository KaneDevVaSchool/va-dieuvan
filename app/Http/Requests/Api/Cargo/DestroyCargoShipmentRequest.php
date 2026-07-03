<?php

namespace App\Http\Requests\Api\Cargo;

use App\Http\Requests\Api\ApiFormRequest;

class DestroyCargoShipmentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf([
            'cargo.manage',
            'trip.view_all',
            'request.approve',
        ]);
    }

    public function rules(): array
    {
        return [];
    }
}
