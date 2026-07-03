<?php

namespace App\Http\Requests\Api\Cargo;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class BulkDeleteCargoShipmentsRequest extends ApiFormRequest
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
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => [
                'integer',
                'distinct',
                Rule::exists('cargo_shipments', 'id'),
            ],
        ];
    }
}
