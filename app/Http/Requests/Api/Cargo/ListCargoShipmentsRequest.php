<?php

namespace App\Http\Requests\Api\Cargo;

use App\Http\Requests\Api\ApiFormRequest;

class ListCargoShipmentsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['cargo.manage', 'trip.view_all']);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
