<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyTrip;
use App\Models\PolicyTripAudit;
use App\Models\PolicyTripStudent;
use App\Models\StudentPolicy;

/**
 * Nghiệp vụ dùng chung quanh chuyến policy: đếm lại số HS, ghi audit, kiểm tra
 * xung đột tài xế (E2), đồng bộ danh sách HS khi policy đổi trạng thái (§4.4).
 */
class PolicyTripService
{
    /**
     * Đếm lại expected/boarded/absent từ bảng con và ghi vào chuyến (U4).
     * `expected_count` = số HS expected=true (gồm cả HS vắng có phép — §7.1).
     */
    public function refreshCounts(PolicyTrip $trip): void
    {
        $expected = $trip->students()->where('expected', true);

        $trip->forceFill([
            'expected_count' => (clone $expected)->count(),
            'boarded_count' => (clone $expected)->whereNotNull('boarded_at')->count(),
            'absent_count' => (clone $expected)->whereNotNull('absence_reason')->count(),
        ])->save();
    }

    public function recordAudit(
        PolicyTrip $trip,
        ?int $userId,
        string $action,
        ?array $oldValue = null,
        ?array $newValue = null,
    ): void {
        PolicyTripAudit::create([
            'policy_trip_id' => $trip->id,
            'changed_by' => $userId,
            'changed_at' => now(),
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }

    /**
     * Chuyến khác (không bị hủy) mà tài xế đã được giao trong cùng ngày + ca (E2).
     * Trả về chuyến xung đột để nêu tên trong thông báo 409, hoặc null.
     */
    public function conflictingTrip(int $driverId, string $tripDate, string $timeSlot, ?int $excludeTripId = null): ?PolicyTrip
    {
        return PolicyTrip::query()
            ->with('route')
            ->where('driver_id', $driverId)
            ->whereDate('trip_date', $tripDate)
            ->where('time_slot', $timeSlot)
            ->where('status', '!=', PolicyTrip::STATUS_CANCELLED)
            ->when($excludeTripId, fn ($q) => $q->where('id', '!=', $excludeTripId))
            ->first();
    }

    public function driverHasConflict(int $driverId, string $tripDate, string $timeSlot, ?int $excludeTripId = null): bool
    {
        return $this->conflictingTrip($driverId, $tripDate, $timeSlot, $excludeTripId) !== null;
    }

    /**
     * Số chuyến chưa khởi hành (tương lai) sẽ bị ảnh hưởng nếu policy này ngừng
     * phục vụ — dùng để cảnh báo điều vận trước khi xác nhận (§4.4, §8.3).
     */
    public function countAffectedUpcomingTrips(StudentPolicy $policy): int
    {
        return PolicyTripStudent::query()
            ->where('student_policy_id', $policy->id)
            ->where('expected', true)
            ->whereHas('policyTrip', fn ($q) => $q->upcomingPending())
            ->count();
    }

    /**
     * Đồng bộ khi policy chuyển sang inactive/suspended (§4.4, CRITICAL FIX L3):
     * loại HS khỏi các chuyến tương lai chưa khởi hành (expected=false) và đếm lại.
     *
     * @return int số bản ghi HS đã cập nhật
     */
    public function syncStudentsOnPolicyStopService(StudentPolicy $policy, ?int $changedByUserId): int
    {
        $entries = PolicyTripStudent::query()
            ->where('student_policy_id', $policy->id)
            ->where('expected', true)
            ->whereHas('policyTrip', fn ($q) => $q->upcomingPending())
            ->with('policyTrip')
            ->get();

        $affectedTripIds = [];

        foreach ($entries as $entry) {
            $entry->update([
                'expected' => false,
                'absence_reason' => PolicyTripStudent::ABSENCE_REPORTED,
                'reported_by' => $changedByUserId,
            ]);
            if ($entry->policyTrip) {
                $affectedTripIds[$entry->policyTrip->id] = $entry->policyTrip;
            }
        }

        foreach ($affectedTripIds as $trip) {
            $this->refreshCounts($trip);
            $this->recordAudit($trip, $changedByUserId, 'student_marked_absent', null, [
                'reason' => 'policy_stopped_service',
                'student_policy_id' => $policy->id,
            ]);
        }

        return $entries->count();
    }

    /** Tất cả HS dự kiến của chuyến đều đã báo vắng (§7.2). */
    public function allExpectedAbsent(PolicyTrip $trip): bool
    {
        $expected = (int) $trip->expected_count;

        return $expected > 0 && (int) $trip->absent_count >= $expected;
    }
}
