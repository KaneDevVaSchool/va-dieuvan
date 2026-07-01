<?php

namespace App\Http\Requests\Api\TpStudent;

use App\Http\Requests\Api\ApiFormRequest;

class ListTpStudentsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_student.view', 'tp_student.manage']);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:32'],
            'grade' => ['nullable', 'string', 'max:64'],
            'class_name' => ['nullable', 'string', 'max:64'],
            'campus_id' => ['nullable', 'integer'],
            'gender' => ['nullable', 'string', 'max:16'],
            'pickup_point' => ['nullable', 'string', 'max:255'],
            'address_contains' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:32'],
            'program_id' => ['nullable', 'integer'],
            'transport_status' => ['nullable', 'string', 'in:transporting,pending,paused,unregistered'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
