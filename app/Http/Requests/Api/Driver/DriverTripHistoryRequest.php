<?php

namespace App\Http\Requests\Api\Driver;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class DriverTripHistoryRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $u = $this->user();

        return $u && $u->canAccessDriverWebApp();
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('status') && $this->input('status') === '') {
            $merge['status'] = null;
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string', Rule::in(['completed', 'cancelled', 'pending', 'in_progress'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:15'],
        ];
    }
}
