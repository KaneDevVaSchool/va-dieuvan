<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use App\Services\TransportProgram\TpProgramPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TpProgramDayController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly TpProgramPresenter $presenter,
    ) {}

    public function index(Request $request, TpProgram $tpProgram): JsonResponse
    {
        $month = $request->query('month');

        $days = $tpProgram->days()
            ->when($month, function ($q) use ($month) {
                [$y, $m] = explode('-', $month);
                $q->whereYear('scheduled_date', $y)->whereMonth('scheduled_date', $m);
            })
            ->orderBy('scheduled_date')
            ->get()
            ->map(fn (TpProgramDay $d) => $this->presenter->programDay($d))
            ->all();

        return $this->ok(['items' => $days]);
    }

    public function show(TpProgramDay $tpProgramDay): JsonResponse
    {
        return $this->ok($this->presenter->programDay($tpProgramDay));
    }

    public function update(Request $request, TpProgramDay $tpProgramDay): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_program.manage')), 403);

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
            'day_type' => ['nullable', 'in:operating,cancelled,makeup'],
            'cancel_reason' => ['nullable', 'string'],
        ]);

        $tpProgramDay->update($data);

        return $this->ok($this->presenter->programDay($tpProgramDay->fresh()));
    }
}
