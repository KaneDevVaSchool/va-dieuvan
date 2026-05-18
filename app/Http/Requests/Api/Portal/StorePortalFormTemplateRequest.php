<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * Lưu biểu mẫu portal để tái sử dụng — chỉ portal user (không phải staff/driver).
 */
class StorePortalFormTemplateRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && ! $user->canAccessDispatchWebApp()
            && ! $user->canAccessDriverWebApp();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'trip_type' => ['required', Rule::in(['door_to_door', 'point_to_point', 'business', 'cargo'])],
            'wizard_snapshot' => ['nullable', 'array'],
        ];
    }
}
