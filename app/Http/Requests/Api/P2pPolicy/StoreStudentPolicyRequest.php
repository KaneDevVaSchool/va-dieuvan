<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreStudentPolicyRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['student_policy.manage']);
    }

    public function rules(): array
    {
        return [
            // E1: 1 bản ghi / ca; chặn trùng (student, route, slot, semester, năm học) chưa xóa.
            'student_id' => [
                'required', 'integer', 'exists:students,id',
                Rule::unique('student_policies', 'student_id')
                    ->where('route_id', $this->input('route_id'))
                    ->where('time_slot', $this->input('time_slot'))
                    ->where('semester', $this->input('semester'))
                    ->where('school_year', $this->input('school_year'))
                    ->whereNull('deleted_at'),
            ],
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'school_year' => ['required', 'string', 'max:9'],
            'semester' => ['required', 'integer', 'in:1,2'],
            'time_slot' => ['required', 'in:morning,afternoon'],
            'pickup_point_id' => ['nullable', 'integer', 'exists:route_stops,id'],
            'dropoff_point_id' => ['nullable', 'integer', 'exists:route_stops,id'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['required', 'date', 'after_or_equal:effective_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.unique' => 'Học sinh đã có chính sách cho tuyến + ca + học kỳ này (E1).',
        ];
    }
}
