<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;

class ListDriversRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.driver.manage', 'trip.assign']);
    }

    /**
     * Query strings send booleans as "true"/"false" strings; Laravel's boolean rule
     * only accepts true/false/0/1/'0'/'1', so we normalize before validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('only_trashed')) {
            return;
        }

        $raw = $this->input('only_trashed');
        if ($raw === null || $raw === '') {
            return;
        }

        if (is_bool($raw)) {
            return;
        }

        $parsed = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($parsed !== null) {
            $this->merge(['only_trashed' => $parsed]);
        }
    }

    public function rules(): array
    {
        return [
            'employment_status' => ['nullable', 'string', 'in:active,on_leave,terminated'],
            'availability_status' => ['nullable', 'string', 'in:available,busy,offline'],
            'only_trashed' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
