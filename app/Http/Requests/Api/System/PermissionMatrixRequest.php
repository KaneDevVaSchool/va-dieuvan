<?php

namespace App\Http\Requests\Api\System;

use App\Http\Requests\Api\ApiFormRequest;

class PermissionMatrixRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['system.permissions.manage']);
    }

    public function rules(): array
    {
        return [];
    }
}
