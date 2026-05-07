<?php

namespace App\Http\Requests\Api;

class UpdateUserProfileRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => 'nullable|string|max:255',
            'employee_code' => 'nullable|string|max:255',
            'gender' => 'nullable|integer|in:0,1',
            'birthdate' => 'nullable|date',
            'birth_place' => 'nullable|string|max:255',
            'national' => 'nullable|string|max:255',
            'religion' => 'nullable|string|max:255',
            'hometown' => 'nullable|string|max:255',
            'identity' => 'nullable|string|max:255',
            'identity_date' => 'nullable|date',
            'identity_place' => 'nullable|string|max:255',
            'tax_code' => 'nullable|string|max:255',
            'social_insurance_number' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'household' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:255',
            'bank' => 'nullable|string|max:255',
            'start_working_date' => 'nullable|date',
            'working_place' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'department_name' => 'nullable|string|max:255',
            'unit_name' => 'nullable|string|max:255',
            'headquarter_name' => 'nullable|string|max:255',
            'position_name' => 'nullable|string|max:255',
            'concurrent_position_name' => 'nullable|string|max:255',
            'department_id' => 'nullable|integer|min:0',
            'company_id' => 'nullable|integer|min:0',
            'health_insurance_code' => 'nullable|string|max:50',
            'unemployment_insurance_number' => 'nullable|string|max:50',
            'avatar' => ['sometimes', 'nullable', 'image', 'max:4096'],
        ];
    }
}
