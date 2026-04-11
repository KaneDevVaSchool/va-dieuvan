<?php

namespace App\Http\Requests\Api\D2D;

use App\Http\Requests\Api\ApiFormRequest;

class EnrollRouteStudentsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['student.manage']);
    }

    public function rules(): array
    {
        return [
            'students' => ['required', 'array', 'min:1'],
            'students.*.student_id' => ['required', 'integer', 'min:1'],
            'students.*.starts_on' => ['nullable', 'date'],
            // Note: Laravel can't easily reference sibling wildcard; keep soft validation here.
            'students.*.ends_on' => ['nullable', 'date'],
        ];
    }
}
