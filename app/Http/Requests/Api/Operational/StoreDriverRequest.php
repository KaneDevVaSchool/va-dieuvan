<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreDriverRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.driver.manage', 'trip.assign']);
    }

    protected function prepareForValidation(): void
    {
        foreach (['phone', 'national_id', 'license_class', 'license_expires_at'] as $k) {
            if ($this->has($k) && $this->input($k) === '') {
                $this->merge([$k => null]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'national_id' => ['nullable', 'string', 'max:32'],
            'license_class' => ['nullable', 'string', 'max:32'],
            'license_expires_at' => ['nullable', 'date'],
            'employment_status' => ['sometimes', Rule::in(['active', 'on_leave', 'terminated'])],
            'availability_status' => ['sometimes', Rule::in(['available', 'busy', 'offline'])],
            'odometer_km' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
