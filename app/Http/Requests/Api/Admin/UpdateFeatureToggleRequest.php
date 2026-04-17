<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateFeatureToggleRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['system.feature_toggles.manage']);
    }

    public function rules(): array
    {
        return [
            'key' => ['sometimes', 'required', 'string', 'max:120', 'regex:/^[a-z0-9_.]+$/'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_enabled' => ['sometimes', 'boolean'],
            'maintenance_mode' => ['sometimes', 'boolean'],
            'upgrade_notice' => ['sometimes', 'boolean'],
            'module' => ['nullable', 'string', 'max:120'],
        ];
    }
}
