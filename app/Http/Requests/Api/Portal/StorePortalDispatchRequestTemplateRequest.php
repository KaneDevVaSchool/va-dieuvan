<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\Requests\StoreDispatchRequestTemplateRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * Portal — người đề xuất không có dispatch.web / permission request.create.
 */
class StorePortalDispatchRequestTemplateRequest extends StoreDispatchRequestTemplateRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return false;
        }

        if (! $user->canAccessDispatchWebApp() && ! $user->canAccessDriverWebApp()) {
            return true;
        }

        return $user->can('request.update_own') || $user->can('request.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['passenger_count'] = ['nullable', 'integer', 'min:1', 'max:999'];
        $rules['recurrence_end_date'] = ['required', 'date', 'after_or_equal:start_date'];
        $rules['repeat_count'] = ['nullable', 'prohibited'];
        $rules['recurrence_rule.freq'] = ['required', 'string', Rule::in(['weekly'])];
        $rules['recurrence_rule.byweekday'] = ['required', 'array', 'min:1'];

        return $rules;
    }
}
