<?php

namespace App\Http\Requests\Api;

use App\Models\User;
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

    protected function allowAnyOf(array $permissions): bool
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        foreach ($permissions as $p) {
            if ($user->can($p)) {
                return true;
            }
        }

        return false;
    }

    protected function allowAllOf(array $permissions): bool
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        foreach ($permissions as $p) {
            if (! $user->can($p)) {
                return false;
            }
        }

        return true;
    }
}
