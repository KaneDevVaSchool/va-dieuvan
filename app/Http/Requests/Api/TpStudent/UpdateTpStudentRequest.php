<?php

namespace App\Http\Requests\Api\TpStudent;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateTpStudentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_student.manage']);
    }

    public function rules(): array
    {
        $id = $this->route('tpStudent')?->id;

        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('tp_students', 'code')->ignore($id)],
            'full_name' => ['sometimes', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:20'],
            'class_name' => ['nullable', 'string', 'max:50'],
            'campus_id' => ['nullable', 'integer'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:active,inactive,transferred,graduated'],
            'metadata' => ['nullable', 'array'],
            'metadata.gender' => ['nullable', 'in:male,female,other'],
            'metadata.date_of_birth' => ['nullable', 'date'],
            'metadata.father_name' => ['nullable', 'string', 'max:255'],
            'metadata.father_phone' => ['nullable', 'string', 'max:20'],
            'metadata.mother_name' => ['nullable', 'string', 'max:255'],
            'metadata.mother_phone' => ['nullable', 'string', 'max:20'],
            'metadata.pickup_point' => ['nullable', 'string', 'max:255'],
            'metadata.note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
