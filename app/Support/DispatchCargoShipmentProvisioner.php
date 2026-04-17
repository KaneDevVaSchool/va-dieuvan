<?php

namespace App\Support;

use App\Models\CargoShipment;
use App\Models\DispatchRequest;
use App\Models\Trip;

class DispatchCargoShipmentProvisioner
{
    public static function provision(DispatchRequest $dispatchRequest, Trip $trip): void
    {
        if (($dispatchRequest->trip_type ?? '') !== 'cargo') {
            return;
        }

        $pickup = self::trimOrNull($dispatchRequest->origin);
        $delivery = self::trimOrNull($dispatchRequest->destination);
        $senderName = null;
        $receiverName = null;
        $quantity = null;

        $snap = $dispatchRequest->wizard_snapshot;
        if (is_array($snap)) {
            $form = $snap['form'] ?? [];
            $form = is_array($form) ? $form : [];
            $senderName = self::trimOrNull($form['requester_name'] ?? null) ?? $senderName;

            $cargoRows = $snap['cargoRows'] ?? [];
            $cargoRows = is_array($cargoRows) ? $cargoRows : [];

            foreach ($cargoRows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $nameFilled = self::trimOrNull($row['name'] ?? null) !== null;
                $pickFilled = self::trimOrNull($row['pickup_place'] ?? null) !== null;
                $delFilled = self::trimOrNull($row['delivery_place'] ?? null) !== null;
                if (! $nameFilled && ! $pickFilled && ! $delFilled) {
                    continue;
                }
                $pickup = self::trimOrNull($row['pickup_place'] ?? null) ?? $pickup;
                $delivery = self::trimOrNull($row['delivery_place'] ?? null) ?? $delivery;
                $senderName = self::trimOrNull($row['pickup_contact'] ?? null) ?? $senderName;
                $receiverName = self::trimOrNull($row['delivery_contact'] ?? null) ?? $receiverName;
                break;
            }

            foreach ($cargoRows as $row) {
                if (! is_array($row) || ! isset($row['qty']) || ! is_numeric($row['qty'])) {
                    continue;
                }
                $quantity = (int) ($quantity ?? 0) + (int) $row['qty'];
            }
            if ($quantity === 0) {
                $quantity = null;
            }
        }

        $slaDueAt = $dispatchRequest->arrive_by
            ?? ($dispatchRequest->depart_at ? $dispatchRequest->depart_at->copy()->addHours(3) : null);

        $shipment = CargoShipment::query()->updateOrCreate(
            ['dispatch_request_id' => $dispatchRequest->id],
            [
                'trip_id' => $trip->id,
                'pickup_address' => $pickup,
                'delivery_address' => $delivery,
                'sender_name' => $senderName,
                'receiver_name' => $receiverName,
                'quantity' => $quantity,
                'sla_due_at' => $slaDueAt,
            ],
        );

        if ($shipment->tracking_code === null || $shipment->tracking_code === '') {
            $shipment->update([
                'tracking_code' => 'CGO-'.str_pad((string) $shipment->id, 8, '0', STR_PAD_LEFT),
            ]);
        }
    }

    private static function trimOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }
}
