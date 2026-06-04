<?php

namespace App\Http\Requests\Api\System;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\MenuItem;

class StoreMenuItemRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['system.feature_toggles.manage']);
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'type' => ['required', 'in:'.MenuItem::TYPE_GROUP.','.MenuItem::TYPE_ITEM],
            'label' => ['required', 'string', 'max:120'],
            'label_key' => ['nullable', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:60'],
            'route_name' => ['nullable', 'string', 'max:120'],
            'url' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'in:_self,_blank'],
            'badge_key' => ['nullable', 'string', 'max:60'],
            'permission_id' => ['nullable', 'integer', 'exists:permissions,id'],
            'feature_key' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer'],
            'is_visible' => ['nullable', 'boolean'],
        ];
    }
}
