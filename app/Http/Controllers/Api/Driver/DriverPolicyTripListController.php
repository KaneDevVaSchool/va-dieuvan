<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnPolicyTrips;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\DriverPolicyTripListRequest;
use App\Models\PolicyTrip;
use App\Services\P2pPolicy\PolicyTripPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * Danh sách chuyến policy của tài xế đang đăng nhập (§5.1, §10.2). Chỉ hiển thị
 * chuyến đã được gán (assigned) hoặc đang chạy (in_progress) trong ngày.
 */
class DriverPolicyTripListController extends Controller
{
    use ActsOnPolicyTrips;
    use ApiResponses;

    public function __construct(
        private readonly PolicyTripPresenter $presenter,
    ) {}

    public function index(DriverPolicyTripListRequest $request): JsonResponse
    {
        $driver = $this->actingDriver($request->user());
        if (! $driver) {
            return $this->ok(['items' => []]);
        }

        $dateInput = (string) ($request->validated('date') ?? 'today');
        $date = ($dateInput === '' || $dateInput === 'today')
            ? Carbon::today()->toDateString()
            : Carbon::parse($dateInput)->toDateString();

        $items = PolicyTrip::query()
            ->with(['route', 'vehicle'])
            ->where('driver_id', $driver->id)
            ->forDate($date)
            ->whereIn('status', [PolicyTrip::STATUS_ASSIGNED, PolicyTrip::STATUS_IN_PROGRESS])
            ->orderBy('time_slot')
            ->get()
            ->map(fn (PolicyTrip $t) => $this->presenter->tripRow($t))
            ->values()
            ->all();

        return $this->ok(['date' => $date, 'items' => $items]);
    }
}
