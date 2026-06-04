<?php

namespace App\Http\Controllers\Api\TpStudent;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpEnrollment;
use App\Models\TpStudent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpStudentProgramHistoryController extends Controller
{
    use ApiResponses;

    public function index(Request $request, TpStudent $tpStudent): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_student.view') || $user->can('tp_student.manage')), 403);

        $items = TpEnrollment::query()
            ->with('program')
            ->where('student_id', $tpStudent->id)
            ->orderByDesc('enrolled_at')
            ->get()
            ->map(fn (TpEnrollment $e) => [
                'program_id' => $e->program_id,
                'program_name' => $e->program?->name,
                'program_status' => $e->program?->status,
                'enrolled_at' => $e->enrolled_at?->toIso8601String(),
                'unenrolled_at' => $e->unenrolled_at?->toIso8601String(),
                'is_active' => $e->isActive(),
            ])->all();

        return $this->ok(['items' => $items]);
    }
}
