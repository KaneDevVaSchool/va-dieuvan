<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class BulkDeleteVehiclesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.vehicle.manage']);
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => [
                'integer',
                'distinct',
                Rule::exists('vehicles', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
