<?php

namespace App\Http\Requests\Api\D2D;

use App\Http\Requests\Api\ApiFormRequest;

class ListRoutesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['route.manage', 'student.manage', 'trip.view_all']);
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
