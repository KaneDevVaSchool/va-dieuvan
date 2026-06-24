<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\DriverTripHistoryRequest;
use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Services\Dispatching\TripScheduleLegService;
use App\Services\DispatchRequests\DispatchRequestMailPresenter;
use App\Support\DispatchWizardPassengerCount;
use App\Support\TripVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DriverTripController extends Controller
{
    use ApiResponses;

    /** @var list<string> */
    private const PENDING_STATUSES = ['pending', 'assigned', 'driver_confirmed', 'approved'];

    public function __construct(
        private readonly TripScheduleLegService $scheduleLegs,
    ) {}

    public function history(DriverTripHistoryRequest $request): JsonResponse
    {
        $user = $request->user();
        $driverId = Driver::query()->where('user_id', $user->id)->value('id');
        $driverId = $driverId !== null ? (int) $driverId : null;
        $data = $request->validated();
        $perPage = isset($data['per_page']) ? (int) $data['per_page'] : 15;
        $perPage = min(100, max(1, $perPage));

        $baseStats = TripVisibility::visibleTripsQuery($user)
            ->whereHas('dispatchRequest');

        $stats = $this->buildMonthlyStats(clone $baseStats);

        $q = TripVisibility::visibleTripsQuery($user)
            ->whereHas('dispatchRequest')
            ->with([
                'dispatchRequest:id,created_at,trip_type,origin,destination,passenger_count,student_count_actual,is_urgent,depart_at,arrive_by,notes,wizard_snapshot,requester_id',
                'dispatchRequest.requester:id,name,phone',
                'tripPassengers',
                'record:id,trip_id,distance_km',
                'vehicle:id,license_plate,type,seat_count',
            ]);

        $q->where('trips.depart_at', '>=', Carbon::parse($data['date_from'])->startOfDay())
            ->where('trips.depart_at', '<=', Carbon::parse($data['date_to'])->endOfDay());

        $status = isset($data['status']) ? (string) $data['status'] : null;
        if ($status !== null && $status !== '') {
            $this->applyStatusFilter($q, $status);
        }

        $q->orderByDesc('trips.depart_at')
            ->orderByDesc('trips.id');

        $results = $q->paginate($perPage);

        $items = collect($results->items())
            ->map(fn (Trip $trip) => $this->serializeTrip($trip, $driverId))
            ->values()
            ->all();

        return $this->ok([
            'items' => $items,
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * @param  Builder<Trip>  $q
     */
    private function applyStatusFilter(Builder $q, string $status): void
    {
        match ($status) {
            'pending' => $q->whereIn('trips.status', self::PENDING_STATUSES),
            'in_progress', 'completed', 'cancelled' => $q->where('trips.status', $status),
            default => null,
        };
    }

    /**
     * @param  Builder<Trip>  $base
     * @return array{completed_this_month: int, cancelled_this_month: int, completed_growth_pct: ?int, overdue_count: int}
     */
    private function buildMonthlyStats(Builder $base): array
    {
        $now = Carbon::now();
        $startOfToday = $now->copy()->startOfDay();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $prevStart = $now->copy()->subMonth()->startOfMonth();
        $prevEnd = $now->copy()->subMonth()->endOfMonth();

        $completedThisMonth = (int) (clone $base)
            ->where('trips.status', 'completed')
            ->whereBetween('trips.depart_at', [$monthStart, $monthEnd])
            ->count();

        $cancelledThisMonth = (int) (clone $base)
            ->where('trips.status', 'cancelled')
            ->whereBetween('trips.depart_at', [$monthStart, $monthEnd])
            ->count();

        $completedLastMonth = (int) (clone $base)
            ->where('trips.status', 'completed')
            ->whereBetween('trips.depart_at', [$prevStart, $prevEnd])
            ->count();

        $growthPct = null;
        if ($completedLastMonth > 0) {
            $growthPct = (int) round((($completedThisMonth - $completedLastMonth) / $completedLastMonth) * 100);
        } elseif ($completedThisMonth > 0 && $completedLastMonth === 0) {
            $growthPct = 100;
        }

        $overdueCount = (int) (clone $base)
            ->whereNotNull('trips.depart_at')
            ->where('trips.depart_at', '<', $startOfToday)
            ->whereNotIn('trips.status', ['completed', 'cancelled'])
            ->count();

        return [
            'completed_this_month' => $completedThisMonth,
            'cancelled_this_month' => $cancelledThisMonth,
            'completed_growth_pct' => $growthPct,
            'overdue_count' => $overdueCount,
        ];
    }

    private function serializeTrip(Trip $trip, ?int $driverId): array
    {
        $dr = $trip->dispatchRequest;
        $scheduleLegs = $this->legsForDriver($trip, $driverId);
        $depart = $trip->depart_at;
        $status = (string) $trip->status;
        $arriveBy = $dr?->arrive_by;
        $pickupLocation = $dr?->origin;
        $dropoffLocation = $dr?->destination;

        if (count($scheduleLegs) >= 1) {
            $primaryLeg = $scheduleLegs[0];
            if (! empty($primaryLeg['depart_at'])) {
                $depart = Carbon::parse((string) $primaryLeg['depart_at']);
            }
            if (! empty($primaryLeg['arrive_by'])) {
                $arriveBy = Carbon::parse((string) $primaryLeg['arrive_by']);
            }
            if (! empty($primaryLeg['pickup'])) {
                $pickupLocation = (string) $primaryLeg['pickup'];
            }
            if (! empty($primaryLeg['dropoff'])) {
                $dropoffLocation = (string) $primaryLeg['dropoff'];
            }
            if (! empty($primaryLeg['status'])) {
                $status = (string) $primaryLeg['status'];
            }
        }

        $durationMinutes = null;
        if ($trip->started_at && $trip->completed_at) {
            $seconds = abs($trip->started_at->diffInSeconds($trip->completed_at));
            $durationMinutes = (int) round($seconds / 60);
        }

        $passengerCount = DispatchWizardPassengerCount::displayFromDispatchRequest($dr);
        $tripType = (string) ($dr?->trip_type ?? '');
        if ($passengerCount <= 0 && $tripType !== 'cargo' && $trip->relationLoaded('tripPassengers')) {
            $passengerCount = $trip->tripPassengers->count();
        }

        $distanceKm = $trip->record?->distance_km !== null
            ? (float) $trip->record->distance_km
            : null;

        $requestCode = $dr instanceof DispatchRequest
            ? DispatchRequestMailPresenter::referenceCode($dr)
            : null;

        return [
            'id' => $trip->id,
            'lock_version' => (int) $trip->lock_version,
            'driver_id' => $trip->driver_id,
            'vehicle_id' => $trip->vehicle_id,
            'vehicle' => $trip->relationLoaded('vehicle') && $trip->vehicle
                ? [
                    'id' => $trip->vehicle->id,
                    'license_plate' => $trip->vehicle->license_plate,
                    'type' => $trip->vehicle->type,
                    'seat_count' => (int) ($trip->vehicle->seat_count ?? 0),
                ]
                : null,
            'request_code' => $requestCode,
            'trip_number' => $requestCode ?? ('#'.$trip->id),
            'type' => $this->tripTypeCode($dr?->trip_type),
            'trip_type_label' => $dr?->trip_type
                ? DispatchRequestMailPresenter::tripTypeLabelVi((string) $dr->trip_type)
                : null,
            'status' => $status,
            'depart_at' => $depart ? $depart->toIso8601String() : null,
            'depart_date' => $depart ? $depart->format('Y-m-d') : null,
            'pickup_time' => $depart ? $depart->format('H:i') : null,
            'pickup_date' => $depart ? $depart->format('d/m/Y') : null,
            'arrive_time' => $arriveBy ? $arriveBy->format('H:i') : null,
            'pickup_location' => $pickupLocation,
            'dropoff_location' => $dropoffLocation,
            'is_urgent' => (bool) ($dr?->is_urgent),
            'arrive_by' => $arriveBy ? $arriveBy->toIso8601String() : null,
            'passenger_count' => $passengerCount,
            'duration_minutes' => $durationMinutes,
            'distance_km' => $distanceKm,
            'notes_preview' => $this->notesPreview($dr?->notes),
            'requester_name' => $dr?->relationLoaded('requester') && $dr->requester
                ? (string) $dr->requester->name
                : null,
            'contact' => $this->resolveTripContact($dr),
            'dispatch_request' => $dr ? [
                'id' => $dr->id,
                'trip_type' => $dr->trip_type,
                'origin' => $dr->origin,
                'destination' => $dr->destination,
                'depart_at' => $dr->depart_at?->toIso8601String(),
                'arrive_by' => $dr->arrive_by?->toIso8601String(),
                'passenger_count' => $passengerCount,
                'student_count_actual' => $dr->student_count_actual,
                'wizard_snapshot' => $dr->wizard_snapshot,
                'is_urgent' => (bool) $dr->is_urgent,
                'notes' => $dr->notes,
                'requester' => $dr->relationLoaded('requester') && $dr->requester
                    ? ['name' => (string) $dr->requester->name, 'phone' => $dr->requester->phone]
                    : null,
            ] : null,
            'schedule_legs' => $scheduleLegs,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function legsForDriver(Trip $trip, ?int $driverId): array
    {
        $all = $this->scheduleLegs->resolveScheduleLegsForTrip($trip);
        if ($all === []) {
            return [];
        }

        $pool = $all;
        if ($driverId !== null) {
            $mine = array_values(array_filter(
                $all,
                fn (array $leg) => (int) ($leg['assignment']['driver_id'] ?? 0) === $driverId,
            ));
            if ($mine !== []) {
                $pool = $mine;
            } elseif (count($all) === 1 && (int) $trip->driver_id === $driverId) {
                $pool = $all;
            } else {
                return [];
            }
        }

        return array_map(fn (array $leg) => [
            'key' => $leg['key'],
            'label_seq' => $leg['label_seq'] ?? null,
            'depart_at' => $leg['depart_at'] ?? null,
            'arrive_by' => $leg['arrive_by'] ?? null,
            'pickup' => $leg['pickup'] ?? '',
            'dropoff' => $leg['dropoff'] ?? '',
            'status' => $leg['status'] ?? $trip->status,
        ], $pool);
    }

    /**
     * Liên hệ hiển thị cho tài xế: ưu tiên trưởng đoàn (chuyến Công tác) lấy từ
     * wizard_snapshot, sau đó người điều phối, cuối cùng fallback người yêu cầu.
     *
     * @return array{name: string, phone: ?string, role: string}|null
     */
    private function resolveTripContact(?DispatchRequest $dr): ?array
    {
        if (! $dr instanceof DispatchRequest) {
            return null;
        }

        $snap = is_array($dr->wizard_snapshot) ? $dr->wizard_snapshot : [];

        if ($dr->trip_type === 'business') {
            $form = is_array($snap['form'] ?? null) ? $snap['form'] : [];
            $coordName = trim((string) ($form['coordinator_name'] ?? ''));
            if ($coordName !== '') {
                return [
                    'name' => $coordName,
                    'phone' => $this->normalizePhone($form['coordinator_phone'] ?? null),
                    'role' => 'leader',
                ];
            }

            $rows = array_merge(
                is_array($snap['businessRows'] ?? null) ? $snap['businessRows'] : [],
                is_array($snap['passengerRows'] ?? null) ? $snap['passengerRows'] : [],
            );
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $name = trim((string) ($row['person_in_charge'] ?? ''));
                if ($name !== '' && ! $this->isAutoPassengerLabel($name)) {
                    return [
                        'name' => $name,
                        'phone' => $this->normalizePhone($row['phone'] ?? null),
                        'role' => 'leader',
                    ];
                }
            }
        }

        if ($dr->relationLoaded('requester') && $dr->requester) {
            $name = trim((string) $dr->requester->name);
            if ($name !== '') {
                return [
                    'name' => $name,
                    'phone' => $this->normalizePhone($dr->requester->phone),
                    'role' => 'requester',
                ];
            }
        }

        return null;
    }

    private function normalizePhone(mixed $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) ($raw ?? ''));

        return ($digits === '' || $digits === null) ? null : $digits;
    }

    private function isAutoPassengerLabel(string $name): bool
    {
        return (bool) preg_match(
            '/^(Khách|Hành khách|Guest|Passengers?|Đoàn công tác|Đoàn|Group)\s*[#№]?\s*\d+$/iu',
            trim($name),
        );
    }

    private function tripTypeCode(?string $tripType): string
    {
        return match ($tripType) {
            'door_to_door' => 'D2D',
            'point_to_point' => 'P2P',
            'business' => 'CT',
            'cargo' => 'Cargo',
            default => '—',
        };
    }

    private function notesPreview(?string $notes): ?string
    {
        if ($notes === null) {
            return null;
        }
        $trimmed = trim($notes);
        if ($trimmed === '') {
            return null;
        }

        return mb_strlen($trimmed) > 80 ? mb_substr($trimmed, 0, 78).'…' : $trimmed;
    }
}
