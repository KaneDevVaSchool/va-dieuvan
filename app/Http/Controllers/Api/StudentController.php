<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'student_code' => ['nullable', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'grade' => ['nullable', 'string', 'max:50'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $student = Student::create([...$data, 'is_active' => true]);

        app(AuditLogger::class)->log(
            actorId: $request->user()->id,
            event: 'student.create',
            auditable: $student,
            before: null,
            after: $student->toArray(),
        );

        return response()->json(['data' => $student], 201);
    }
}

