<?php

namespace App\Http\Requests\Api\Cargo;

use App\Http\Requests\Api\ApiFormRequest;

class ShowCargoShipmentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['cargo.manage', 'trip.view_all']);
    }

    public function rules(): array
    {
        return [];
    }
}
