<?php

namespace App\Http\Controllers\Api\D2D;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\D2D\CreateStudentRequest;
use App\Http\Requests\Api\D2D\ListStudentsRequest;
use App\Models\Student;
use App\Services\Auditing\AuditLogger;

class StudentController extends Controller
{
    use ApiResponses;

    public function index(ListStudentsRequest $request)
    {
        $data = $request->validated();

        $q = Student::query()->orderBy('full_name');

        $perPage = (int) ($data['per_page'] ?? 20);
        $results = $q->paginate($perPage);

        return $this->ok([
            'items' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    public function store(CreateStudentRequest $request)
    {
        $data = $request->validated();

        $student = Student::create([...$data, 'is_active' => true]);

        app(AuditLogger::class)->log(
            actorId: $request->user()->id,
            event: 'student.create',
            auditable: $student,
            before: null,
            after: $student->toArray(),
        );

        return $this->created($student);
    }
}
