<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\ApiFormRequest;

class ShowDispatchSettingRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['dispatch.settings.manage']);
    }

    public function rules(): array
    {
        return [];
    }
}
