<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Actions\CreateTransportProgramAction;
use App\Actions\UpdateProgramDateRangeAction;
use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TransportProgram\BulkDeleteTpProgramsRequest;
use App\Http\Requests\Api\TransportProgram\ListTpProgramsRequest;
use App\Http\Requests\Api\TransportProgram\PurgeAllTpProgramsRequest;
use App\Http\Requests\Api\TransportProgram\StoreTpProgramRequest;
use App\Http\Requests\Api\TransportProgram\UpdateTpProgramRequest;
use App\Models\TpProgram;
use App\Services\TransportProgram\TpDriverAssignmentNotifyService;
use App\Services\TransportProgram\TpProgramListFilter;
use App\Services\TransportProgram\TpProgramPresenter;
use App\Services\TransportProgram\TpProgramPurgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TpProgramController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly TpProgramPresenter $presenter,
        private readonly CreateTransportProgramAction $createAction,
        private readonly UpdateProgramDateRangeAction $updateDateRangeAction,
        private readonly TpDriverAssignmentNotifyService $driverAssignmentNotify,
        private readonly TpProgramListFilter $listFilter,
        private readonly TpProgramPurgeService $purgeService,
    ) {}

    public function index(ListTpProgramsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $items = $this->listFilter
            ->apply(
                TpProgram::query()->with('responsibleUser:id,name'),
                $data,
            )
            ->orderByDesc('id')
            ->paginate($data['per_page'] ?? 20);

        return $this->ok([
            'items' => collect($items->items())->map(fn (TpProgram $p) => $this->presenter->programSummary($p))->all(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
            'summary' => $this->presenter->listSummary(),
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
        $previousDefaultDriverId = $tpProgram->default_driver_id;
        $previousBackupDriverId = $tpProgram->backup_driver_id;

        $dateKeys = ['start_date', 'end_date', 'runs_on', 'excluded_dates', 'extra_dates'];
        $touchesDateRange = (bool) array_intersect(array_keys($data), $dateKeys);

        if ($touchesDateRange) {
            $tpProgram = $this->updateDateRangeAction->execute($tpProgram, $data, $request->user()?->id);
        } else {
            $tpProgram->update($data);
        }

        $tpProgram = $tpProgram->fresh();

        if (array_key_exists('default_driver_id', $data)
            && (int) ($previousDefaultDriverId ?? 0) !== (int) ($tpProgram->default_driver_id ?? 0)) {
            $this->driverAssignmentNotify->notifyProgramDefaultDriverChange(
                $tpProgram,
                $previousDefaultDriverId,
                $tpProgram->default_driver_id,
                backup: false,
            );
        }

        if (array_key_exists('backup_driver_id', $data)
            && (int) ($previousBackupDriverId ?? 0) !== (int) ($tpProgram->backup_driver_id ?? 0)) {
            $this->driverAssignmentNotify->notifyProgramDefaultDriverChange(
                $tpProgram,
                $previousBackupDriverId,
                $tpProgram->backup_driver_id,
                backup: true,
            );
        }

        return $this->ok($this->presenter->programSummary($tpProgram));
    }

    public function destroy(Request $request, TpProgram $tpProgram): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('tp_program.manage'), 403);

        $tpProgram->delete();

        return $this->ok(['deleted' => true]);
    }

    public function bulkDestroy(BulkDeleteTpProgramsRequest $request): JsonResponse
    {
        $ids = collect($request->validated('ids'))->unique()->values()->all();
        $deleted = 0;

        DB::transaction(function () use ($ids, &$deleted) {
            $programs = TpProgram::query()->whereIn('id', $ids)->get();
            foreach ($programs as $program) {
                $program->delete();
                $deleted++;
            }
        });

        return $this->ok(['deleted_count' => $deleted]);
    }

    public function purgeAll(PurgeAllTpProgramsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $permanent = (bool) $validated['permanent'];
        $expected = (int) $validated['expected_count'];

        $filterData = collect($validated)
            ->except(['permanent', 'confirm_phrase', 'expected_count', 'per_page', 'page'])
            ->all();

        $total = (int) $this->listFilter->filteredQuery($filterData)->count();

        if ($total !== $expected) {
            abort(422, 'Số lượng chương trình đã thay đổi ('.$total.' ≠ '.$expected.'). Làm mới trang rồi thử lại.');
        }

        if ($total === 0) {
            return $this->ok(['deleted_count' => 0, 'permanent' => $permanent]);
        }

        $deleted = $this->purgeService->purgeAll($request->user(), $filterData, $permanent);

        return $this->ok([
            'deleted_count' => $deleted,
            'permanent' => $permanent,
        ]);
    }
}
