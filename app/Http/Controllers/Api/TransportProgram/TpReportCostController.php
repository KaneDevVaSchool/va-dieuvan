<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpProgram;
use App\Services\TransportProgram\ProgramReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpReportCostController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ProgramReportService $reports,
    ) {}

    public function index(Request $request, TpProgram $tpProgram): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_report.view') || $user->can('tp_cost.manage')), 403);

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        return $this->ok($this->reports->costReport($tpProgram, $data['month']));
    }
}
