<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpProgram;
use App\Services\TransportProgram\ProgramLifecycleService;
use App\Services\TransportProgram\TpProgramPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpProgramLifecycleController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ProgramLifecycleService $lifecycle,
        private readonly TpProgramPresenter $presenter,
    ) {}

    public function activate(Request $request, TpProgram $tpProgram): JsonResponse
    {
        $this->authorizeManage($request);

        return $this->ok($this->presenter->programSummary($this->lifecycle->activate($tpProgram, $request->user()?->id)));
    }

    public function pause(Request $request, TpProgram $tpProgram): JsonResponse
    {
        $this->authorizeManage($request);

        return $this->ok($this->presenter->programSummary($this->lifecycle->pause($tpProgram, $request->user()?->id)));
    }

    public function cancel(Request $request, TpProgram $tpProgram): JsonResponse
    {
        $this->authorizeManage($request);
        $reason = $request->input('reason');

        return $this->ok($this->presenter->programSummary($this->lifecycle->cancel($tpProgram, $reason, $request->user()?->id)));
    }

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_program.manage')), 403);
    }
}
