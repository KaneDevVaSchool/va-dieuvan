<?php

namespace App\Http\Requests\Api\D2D;

use App\Http\Requests\Api\ApiFormRequest;

class ShowRouteRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['route.manage', 'student.manage', 'trip.view_all']);
    }

    public function rules(): array
    {
        return [];
    }
}
