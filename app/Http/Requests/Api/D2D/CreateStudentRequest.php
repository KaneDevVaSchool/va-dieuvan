<?php

namespace App\Http\Requests\Api\D2D;

use App\Http\Requests\Api\ApiFormRequest;

class CreateStudentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['student.manage']);
    }

    public function rules(): array
    {
        return [
            'student_code' => ['nullable', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'grade' => ['nullable', 'string', 'max:50'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
