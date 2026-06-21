<?php

namespace App\Services\Trips;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\TripPassenger;
use App\Services\Dispatching\TripScheduleLegService;
use App\Support\Messages;
use App\Support\TripOptimisticLock;
use Illuminate\Support\Facades\DB;

class TripNamedPassengerSyncService
{
    public function __construct(
        private readonly TripScheduleLegService $legService,
    ) {}

    /**
     * @param  array<int, array{name: string, phone?: ?string, note?: ?string, leg_key?: ?string}>  $passengers
     */
    public function replace(
        Trip $trip,
        DispatchRequest $dispatchRequest,
        int $passengerCount,
        array $passengers,
        int $expectedLockVersion,
    ): void {
        DB::transaction(function () use ($trip, $dispatchRequest, $passengerCount, $passengers, $expectedLockVersion) {
            /** @var Trip $locked */
            $locked = Trip::query()->whereKey($trip->id)->lockForUpdate()->firstOrFail();
            if ((int) $locked->lock_version !== $expectedLockVersion) {
                abort(409, Messages::OPTIMISTIC_LOCK_CONFLICT);
            }

            $locked->tripPassengers()->delete();

            $dispatchRequest->refresh();
            $snap = is_array($dispatchRequest->wizard_snapshot) ? $dispatchRequest->wizard_snapshot : [];

            // Chặng có tuyến (pickup/dropoff) cần được giữ nguyên; tập key hợp lệ
            // dùng để loại các leg_key trỏ tới chặng không còn tồn tại.
            $legDefs = $this->legService->buildLegDefinitionsFromSnapshot(
                $snap,
                (string) ($dispatchRequest->trip_type ?? ''),
            );
            $validLegKeys = [];
            $hasRouteLegs = false;
            foreach ($legDefs as $def) {
                $key = (string) ($def['key'] ?? '');
                if ($key === '') {
                    continue;
                }
                $validLegKeys[$key] = true;
                if (trim((string) ($def['pickup'] ?? '')) !== '' || trim((string) ($def['dropoff'] ?? '')) !== '') {
                    $hasRouteLegs = true;
                }
            }

            // Không có chặng tuyến → lộ trình sẽ bị mirror lại, mọi gán chặng vô nghĩa.
            if (! $hasRouteLegs) {
                $validLegKeys = [];
            }

            $now = now();
            $rows = [];
            foreach ($passengers as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $phoneTrim = isset($row['phone']) ? trim((string) $row['phone']) : '';
                $noteTrim = isset($row['note']) ? trim((string) $row['note']) : '';
                $legKey = trim((string) ($row['leg_key'] ?? ''));
                // Bỏ gán nếu chặng không còn tồn tại trong lộ trình.
                if ($legKey !== '' && ! isset($validLegKeys[$legKey])) {
                    $legKey = '';
                }
                $rows[] = [
                    'trip_id' => $locked->id,
                    'leg_key' => $legKey === '' ? null : mb_substr($legKey, 0, 64),
                    'name' => $name,
                    'phone' => $phoneTrim === '' ? null : mb_substr($phoneTrim, 0, 20),
                    'note' => $noteTrim === '' ? null : $noteTrim,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows !== []) {
                TripPassenger::insert($rows);
            }

            $dispatchRequest->passenger_count = $passengerCount;
            // Giữ nguyên các chặng có tuyến thật (đa chặng đưa đón); chỉ mirror khi
            // lộ trình chưa có chặng nào — tránh xóa chặng đã thiết lập.
            if (! $hasRouteLegs) {
                $snap['passengerRows'] = $this->mirrorWizardPassengerRows($passengers);
            }
            $dispatchRequest->wizard_snapshot = $snap;
            $dispatchRequest->save();

            TripOptimisticLock::update($locked, ['passenger_check_ins' => []], $expectedLockVersion);
        });
    }

    /**
     * Giữ wizard_snapshot tương thích phần ước tính chi phí / xuất cũ.
     *
     * @param  array<int, array{name: string, phone?: ?string, note?: ?string}>  $passengers
     * @return array<int, array<string, string>>
     */
    private function mirrorWizardPassengerRows(array $passengers): array
    {
        $out = [];
        foreach ($passengers as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $phone = trim((string) ($row['phone'] ?? ''));
            $note = trim((string) ($row['note'] ?? ''));
            $notes = $note;
            if ($phone !== '') {
                $notes = $notes === '' ? $phone : $phone."\n".$note;
            }

            $out[] = [
                'depart_at' => '',
                'pickup' => '',
                'return_at' => '',
                'dropoff' => '',
                'guests' => '1',
                'unit_price' => '',
                'extra_fee' => '',
                'person_in_charge' => $name,
                'notes' => mb_substr($notes, 0, 2000),
            ];
        }

        return $out;
    }
}
