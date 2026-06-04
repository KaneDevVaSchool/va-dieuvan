<?php

namespace App\Http\Requests\Api\System;

use App\Http\Requests\Api\ApiFormRequest;

class ListMenuItemsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['system.feature_toggles.manage', 'system.roles.manage']);
    }

    public function rules(): array
    {
        return [];
    }
}
