<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpProgram;
use App\Services\TransportProgram\ProgramReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpReportAbsenceController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ProgramReportService $reports,
    ) {}

    public function index(Request $request, TpProgram $tpProgram): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_report.view')), 403);

        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        return $this->ok($this->reports->absencePivot($tpProgram, $data['from'], $data['to']));
    }
}
