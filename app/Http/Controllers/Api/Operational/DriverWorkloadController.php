<?php

namespace App\Http\Controllers\Api\Operational;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operational\DriverWorkloadDetailRequest;
use App\Http\Requests\Api\Operational\DriverWorkloadSummaryRequest;
use App\Models\Driver;
use App\Services\Operational\DriverWorkloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DriverWorkloadController extends Controller
{
    use ApiResponses;

    public function workload(DriverWorkloadSummaryRequest $request, DriverWorkloadService $service): JsonResponse
    {
        $data = $request->validated();
        $tripDateInput = $data['trip_date'] ?? null;
        $tripDate = $tripDateInput
            ? Carbon::parse($tripDateInput)->startOfDay()
            : now()->startOfDay();

        $from = isset($data['date_from'])
            ? Carbon::parse($data['date_from'])->startOfDay()
            : $tripDate->copy()->startOfWeek(Carbon::MONDAY);
        $to = isset($data['date_to'])
            ? Carbon::parse($data['date_to'])->endOfDay()
            : $tripDate->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        return $this->ok($service->buildWorkloadMap($from, $to, $tripDate));
    }

    public function workloadDetail(DriverWorkloadDetailRequest $request, Driver $driver, DriverWorkloadService $service): JsonResponse
    {
        return $this->ok($service->buildWorkloadDetail($driver));
    }
}
