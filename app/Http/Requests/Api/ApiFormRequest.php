<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

abstract class ApiFormRequest extends FormRequest
{
    public function wantsJson(): bool
    {
        return true;
    }

    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * Check if authenticated user has required permission(s).
     * - anyOf: at least one permission must be granted
     * - allOf: all permissions must be granted
     */
    protected function allowAnyOf(array $permissions): bool
    {
        $user = $this->user();
        if (! $user || ! $permissions) {
            return false;
        }
        foreach ($permissions as $p) {
            if ($user->hasPermission($p)) {
                return true;
            }
        }

        return false;
    }

    protected function allowAllOf(array $permissions): bool
    {
        $user = $this->user();
        if (! $user || ! $permissions) {
            return false;
        }
        foreach ($permissions as $p) {
            if (! $user->hasPermission($p)) {
                return false;
            }
        }

        return true;
    }
}
