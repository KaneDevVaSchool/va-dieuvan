<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnTpExecutions;
use App\Http\Controllers\Controller;
use App\Models\TpTripExecution;
use App\Services\TransportProgram\OfflineSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverTripSyncController extends Controller
{
    use ActsOnTpExecutions;
    use ApiResponses;

    public function __construct(
        private readonly OfflineSyncService $syncService,
    ) {}

    public function __invoke(Request $request, TpTripExecution $tpTripExecution): JsonResponse
    {
        $this->assertCanActOnExecution($request->user(), $tpTripExecution);

        $data = $request->validate([
            'actions' => ['required', 'array'],
            'actions.*.action' => ['required', 'in:board,alight,absent'],
            'actions.*.student_id' => ['required', 'integer'],
            'actions.*.client_timestamp' => ['nullable', 'date'],
            'actions.*.absence_type' => ['nullable', 'in:parent_notified,no_notice,late_cancel'],
            'actions.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->syncService->processQueue($tpTripExecution, $data['actions'], $request->user()?->id);

        return $this->ok($result);
    }
}
