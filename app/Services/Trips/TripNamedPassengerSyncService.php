<?php

namespace App\Services\Trips;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\TripPassenger;
use App\Support\Messages;
use App\Support\TripOptimisticLock;
use Illuminate\Support\Facades\DB;

class TripNamedPassengerSyncService
{
    /**
     * @param  array<int, array{name: string, phone?: ?string, note?: ?string}>  $passengers
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

            $now = now();
            $rows = [];
            foreach ($passengers as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $phoneTrim = isset($row['phone']) ? trim((string) $row['phone']) : '';
                $noteTrim = isset($row['note']) ? trim((string) $row['note']) : '';
                $rows[] = [
                    'trip_id' => $locked->id,
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

            $dispatchRequest->refresh();
            $dispatchRequest->passenger_count = $passengerCount;
            $snap = is_array($dispatchRequest->wizard_snapshot) ? $dispatchRequest->wizard_snapshot : [];
            $snap['passengerRows'] = $this->mirrorWizardPassengerRows($passengers);
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
