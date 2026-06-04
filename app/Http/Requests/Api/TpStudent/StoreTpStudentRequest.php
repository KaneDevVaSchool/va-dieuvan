<?php

namespace App\Http\Requests\Api\TpStudent;

use App\Http\Requests\Api\ApiFormRequest;

class StoreTpStudentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_student.manage']);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:tp_students,code'],
            'full_name' => ['required', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:20'],
            'class_name' => ['nullable', 'string', 'max:50'],
            'campus_id' => ['nullable', 'integer'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive,transferred,graduated'],
        ];
    }
}
