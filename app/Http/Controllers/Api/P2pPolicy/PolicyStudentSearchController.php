<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\SearchPolicyStudentsRequest;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class PolicyStudentSearchController extends Controller
{
    use ApiResponses;

    public function __invoke(SearchPolicyStudentsRequest $request): JsonResponse
    {
        $q = trim((string) $request->validated('q', ''));

        $query = Student::query()->where('is_active', true)->orderBy('full_name')->limit(20);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('full_name', 'like', '%'.$q.'%')
                    ->orWhere('student_code', 'like', '%'.$q.'%')
                    ->orWhere('grade', 'like', '%'.$q.'%');
            });
        }

        $items = $query->get(['id', 'full_name', 'student_code', 'grade'])->map(fn (Student $s) => [
            'id' => $s->id,
            'full_name' => $s->full_name,
            'student_code' => $s->student_code,
            'grade' => $s->grade,
            'class_name' => $s->grade,
        ])->values()->all();

        return $this->ok(['items' => $items]);
    }
}
