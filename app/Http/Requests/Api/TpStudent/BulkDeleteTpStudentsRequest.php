<?php

namespace App\Http\Requests\Api\TpStudent;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class BulkDeleteTpStudentsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_student.manage']);
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => [
                'integer',
                'distinct',
                Rule::exists('tp_students', 'id'),
            ],
        ];
    }
}
