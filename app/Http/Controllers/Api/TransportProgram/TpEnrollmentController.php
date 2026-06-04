<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Actions\EnrollStudentsAction;
use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TransportProgram\BulkEnrollRequest;
use App\Models\TpEnrollment;
use App\Models\TpProgram;
use App\Services\TransportProgram\ProgramEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpEnrollmentController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly EnrollStudentsAction $enrollAction,
        private readonly ProgramEnrollmentService $enrollmentService,
    ) {}

    public function index(Request $request, TpProgram $tpProgram): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_program.view') || $user->can('tp_enrollment.manage')), 403);

        $items = TpEnrollment::query()
            ->with('student')
            ->where('program_id', $tpProgram->id)
            ->whereNull('unenrolled_at')
            ->get()
            ->map(fn (TpEnrollment $e) => [
                'student_id' => $e->student_id,
                'code' => $e->student->code,
                'full_name' => $e->student->full_name,
                'grade' => $e->student->grade,
                'class_name' => $e->student->class_name,
                'enrolled_at' => $e->enrolled_at?->toIso8601String(),
            ])->all();

        return $this->ok(['items' => $items]);
    }

    public function store(BulkEnrollRequest $request, TpProgram $tpProgram): JsonResponse
    {
        $result = $this->enrollAction->execute($tpProgram, $request->validated()['student_ids'], $request->user()?->id);

        return $this->created($result);
    }

    public function destroy(Request $request, TpProgram $tpProgram, int $student): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_enrollment.manage')), 403);

        $this->enrollmentService->unenroll($tpProgram, $student, $request->input('reason'), $request->user()?->id);

        return $this->ok(['unenrolled' => true]);
    }
}
