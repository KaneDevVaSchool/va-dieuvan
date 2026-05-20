<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Driver;
use Illuminate\Validation\Rule;

class UpdateDriverRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.driver.manage']);
    }

    protected function prepareForValidation(): void
    {
        foreach (['phone', 'email', 'national_id', 'license_class', 'license_expires_at'] as $k) {
            if ($this->has($k) && $this->input($k) === '') {
                $this->merge([$k => null]);
            }
        }
    }

    public function rules(): array
    {
        /** @var Driver $driver */
        $driver = $this->route('driver');

        $emailRules = ['nullable', 'string', 'email', 'max:255'];
        if ($driver->user_id) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($driver->user_id);
        } else {
            $emailRules[] = Rule::unique('drivers', 'email')->ignore($driver->id);
        }

        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => $emailRules,
            'national_id' => ['nullable', 'string', 'max:32'],
            'license_class' => ['nullable', 'string', 'max:32'],
            'license_expires_at' => ['nullable', 'date'],
            'employment_status' => ['sometimes', Rule::in(['active', 'on_leave', 'terminated'])],
            'availability_status' => ['sometimes', Rule::in(['available', 'busy', 'offline'])],
            'odometer_km' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
