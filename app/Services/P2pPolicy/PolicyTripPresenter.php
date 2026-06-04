<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyTrip;
use App\Models\PolicyTripStudent;
use App\Models\StudentPolicy;

/**
 * Chuẩn hoá payload P2P trả về cho SPA (điều vận + tài xế). Tách khỏi service
 * nghiệp vụ để controller/SSE dùng chung một hình dạng dữ liệu.
 */
class PolicyTripPresenter
{
    /** Hàng chuyến trên dashboard / danh sách (§8.1). */
    public function tripRow(PolicyTrip $trip): array
    {
        $trip->loadMissing(['route', 'driver', 'vehicle']);

        return [
            'id' => $trip->id,
            'trip_date' => $trip->trip_date?->format('Y-m-d'),
            'time_slot' => $trip->time_slot,
            'route_id' => $trip->route_id,
            'route_name' => $trip->route?->name ?? ($trip->route_snapshot['name'] ?? null),
            'planned_departure' => $this->formatTime($trip->planned_departure),
            'actual_departure' => $trip->actual_departure?->toIso8601String(),
            'actual_arrival' => $trip->actual_arrival?->toIso8601String(),
            'driver_id' => $trip->driver_id,
            'driver_name' => $trip->driver?->full_name,
            'driver_phone' => $trip->driver?->phone,
            'vehicle_id' => $trip->vehicle_id,
            'vehicle_plate' => $trip->vehicle?->license_plate,
            'vehicle_seats' => $trip->vehicle?->seat_count,
            'expected_count' => (int) $trip->expected_count,
            'boarded_count' => (int) $trip->boarded_count,
            'absent_count' => (int) $trip->absent_count,
            'status' => $trip->status,
            'generated_by' => $trip->generated_by,
            'cancel_reason' => $trip->cancel_reason,
            'notes' => $trip->notes,
        ];
    }

    /** Chỉ các trường thay đổi realtime — dùng cho SSE (§8.1, U5). */
    public function tripLiveRow(PolicyTrip $trip): array
    {
        return [
            'id' => $trip->id,
            'boarded_count' => (int) $trip->boarded_count,
            'absent_count' => (int) $trip->absent_count,
            'expected_count' => (int) $trip->expected_count,
            'status' => $trip->status,
        ];
    }

    /** Một học sinh trong chuyến — chi tiết điểm danh (§8.2, §10.2). */
    public function studentRow(PolicyTripStudent $entry): array
    {
        $entry->loadMissing(['student', 'reporter']);

        return [
            'id' => $entry->id,
            'student_id' => $entry->student_id,
            'student_name' => $entry->student?->full_name,
            'student_code' => $entry->student?->student_code,
            'class_name' => $entry->student?->grade,
            'expected' => (bool) $entry->expected,
            'boarded_at' => $entry->boarded_at?->toIso8601String(),
            'alighted_at' => $entry->alighted_at?->toIso8601String(),
            'absence_reason' => $entry->absence_reason,
            'reported_by_name' => $entry->reporter?->name ?? $entry->reporter?->email,
            'status' => $this->studentStatus($entry),
        ];
    }

    /** Hàng chính sách HS trên master list (§8.3). */
    public function studentPolicyRow(StudentPolicy $sp): array
    {
        $sp->loadMissing(['student', 'route', 'pickupPoint', 'dropoffPoint']);

        return [
            'id' => $sp->id,
            'student_id' => $sp->student_id,
            'student_name' => $sp->student?->full_name,
            'student_code' => $sp->student?->student_code,
            'class_name' => $sp->student?->grade,
            'route_id' => $sp->route_id,
            'route_name' => $sp->route?->name,
            'school_year' => $sp->school_year,
            'semester' => $sp->semester,
            'time_slot' => $sp->time_slot,
            'pickup_point_id' => $sp->pickup_point_id,
            'dropoff_point_id' => $sp->dropoff_point_id,
            'pickup_point' => $sp->pickupPoint?->name ?? $sp->pickupPoint?->address,
            'dropoff_point' => $sp->dropoffPoint?->name ?? $sp->dropoffPoint?->address,
            'effective_from' => $sp->effective_from?->format('Y-m-d'),
            'effective_to' => $sp->effective_to?->format('Y-m-d'),
            'status' => $sp->status,
        ];
    }

    /** Trạng thái rút gọn của HS để render badge. */
    private function studentStatus(PolicyTripStudent $entry): string
    {
        if ($entry->absence_reason !== null) {
            return 'absent';
        }
        if ($entry->alighted_at !== null) {
            return 'alighted';
        }
        if ($entry->boarded_at !== null) {
            return 'boarded';
        }

        return 'expected';
    }

    private function formatTime(mixed $time): ?string
    {
        if ($time === null) {
            return null;
        }
        if (is_string($time)) {
            return substr($time, 0, 5);
        }
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        return (string) $time;
    }
}
