<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;

class ListTransportProvidersRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.provider.manage', 'trip.assign']);
    }

    /**
     * Query strings send booleans as "true"/"false" strings; Laravel's boolean rule
     * only accepts true/false/0/1/'0'/'1', so we normalize before validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['only_trashed', 'is_active'] as $key) {
            if (! $this->has($key)) {
                continue;
            }

            $raw = $this->input($key);
            if ($raw === null || $raw === '') {
                continue;
            }

            if (is_bool($raw)) {
                continue;
            }

            $parsed = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($parsed !== null) {
                $this->merge([$key => $parsed]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'is_active' => ['nullable', 'boolean'],
            'only_trashed' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
