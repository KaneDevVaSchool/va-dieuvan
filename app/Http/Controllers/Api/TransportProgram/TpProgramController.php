<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Actions\CreateTransportProgramAction;
use App\Actions\UpdateProgramDateRangeAction;
use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TransportProgram\ListTpProgramsRequest;
use App\Http\Requests\Api\TransportProgram\StoreTpProgramRequest;
use App\Http\Requests\Api\TransportProgram\UpdateTpProgramRequest;
use App\Models\TpProgram;
use App\Services\TransportProgram\TpProgramPresenter;
use Illuminate\Http\JsonResponse;

class TpProgramController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly TpProgramPresenter $presenter,
        private readonly CreateTransportProgramAction $createAction,
        private readonly UpdateProgramDateRangeAction $updateDateRangeAction,
    ) {}

    public function index(ListTpProgramsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $items = TpProgram::query()
            ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($data['responsible_user_id'] ?? null, fn ($q, $u) => $q->where('responsible_user_id', $u))
            ->when($data['search'] ?? null, fn ($q, $term) => $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%");
            }))
            ->orderByDesc('id')
            ->paginate($data['per_page'] ?? 20);

        return $this->ok([
            'items' => collect($items->items())->map(fn (TpProgram $p) => $this->presenter->programSummary($p))->all(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(StoreTpProgramRequest $request): JsonResponse
    {
        $result = $this->createAction->execute($request->validated(), $request->user()?->id);

        return $this->created($result);
    }

    public function show(TpProgram $tpProgram): JsonResponse
    {
        return $this->ok($this->presenter->programSummary($tpProgram));
    }

    public function update(UpdateTpProgramRequest $request, TpProgram $tpProgram): JsonResponse
    {
        $data = $request->validated();
        $dateKeys = ['start_date', 'end_date', 'runs_on', 'excluded_dates', 'extra_dates'];
        $touchesDateRange = (bool) array_intersect(array_keys($data), $dateKeys);

        if ($touchesDateRange) {
            $tpProgram = $this->updateDateRangeAction->execute($tpProgram, $data, $request->user()?->id);
        } else {
            $tpProgram->update($data);
        }

        return $this->ok($this->presenter->programSummary($tpProgram->fresh()));
    }

    public function destroy(TpProgram $tpProgram): JsonResponse
    {
        $tpProgram->delete();

        return $this->ok(['deleted' => true]);
    }
}
