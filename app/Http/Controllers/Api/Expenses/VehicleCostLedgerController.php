<?php

namespace App\Http\Controllers\Api\Expenses;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Expenses\VehicleCostLedgerRequest;
use App\Models\Vehicle;
use App\Services\Expenses\ExpenseSettingsService;
use App\Services\Expenses\VehicleCostLedgerQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Sổ chi phí theo xe (định danh từng xe).
 */
class VehicleCostLedgerController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly VehicleCostLedgerQuery $ledger) {}

    public function summary(VehicleCostLedgerRequest $request): JsonResponse
    {
        $d = $request->validated();

        return $this->ok($this->ledger->summary($d['date_from'], $d['date_to']));
    }

    public function show(VehicleCostLedgerRequest $request, int $vehicle): JsonResponse
    {
        $d = $request->validated();

        return $this->ok($this->ledger->forVehicle(Vehicle::withTrashed()->findOrFail($vehicle), $d['date_from'], $d['date_to']));
    }

    public function export(VehicleCostLedgerRequest $request, int $vehicle, ExpenseSettingsService $settings): Response
    {
        $d = $request->validated();
        $model = Vehicle::withTrashed()->findOrFail($vehicle);
        $data = $this->ledger->forVehicle($model, $d['date_from'], $d['date_to']);
        $slug = preg_replace('/[^A-Za-z0-9]+/', '', (string) $model->license_plate) ?: 'xe';

        return response($settings->ledgerXlsx($data, $d['date_from'], $d['date_to']), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="so-chi-phi_'.$slug.'.xlsx"',
        ]);
    }
}
