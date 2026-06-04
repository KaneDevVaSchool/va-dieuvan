<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyTrip;
use App\Models\StudentPolicy;
use App\Models\User;
use App\Notifications\Policy\PolicyStaffAlertNotification;
use App\Notifications\Policy\PolicyTripDriverAssignedNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

/**
 * Điều phối toàn bộ thông báo/cảnh báo của module P2P (§11). Kênh tin cậy là
 * in-app (database) đọc bởi chuông thông báo; email bật cho cảnh báo quan trọng.
 */
class PolicyNotificationService
{
    /** @var list<string> */
    private const STAFF_ROLES = ['dispatcher', 'admin', 'superadmin'];

    /** @var list<string> */
    private const ADMIN_ROLES = ['admin', 'superadmin'];

    /** §6.3: tài xế vừa được phân công chuyến. */
    public function tripDriverAssigned(PolicyTrip $trip): void
    {
        $trip->loadMissing(['driver', 'route']);
        $user = $trip->driver?->user;
        if (! $user) {
            return;
        }

        $user->notify(new PolicyTripDriverAssignedNotification(
            tripId: (int) $trip->id,
            routeName: $trip->route?->name ?? ('#'.$trip->id),
            timeSlotLabel: $this->timeSlotLabel($trip->time_slot),
            tripDateLabel: $this->dateLabel($trip->trip_date),
            plannedDeparture: $this->timeLabel($trip->planned_departure),
        ));
    }

    /** §11: tài xế báo HS vắng không phép → điều vận realtime. */
    public function studentAbsentNoNotice(PolicyTrip $trip, string $studentName): void
    {
        $this->notifyStaff(new PolicyStaffAlertNotification(
            event: 'policy_trip.absent_no_notice',
            title: 'Học sinh vắng không phép',
            body: $studentName.' vắng không phép — chuyến '.$this->tripLabel($trip),
            url: '/p2p-policy/trips?date='.$this->dateValue($trip->trip_date),
            withMail: true,
            meta: ['policy_trip_id' => (int) $trip->id],
        ));
    }

    /** §7.2: toàn bộ HS chuyến đã báo vắng → gợi ý hủy. */
    public function tripAllAbsent(PolicyTrip $trip): void
    {
        $this->notifyStaff(new PolicyStaffAlertNotification(
            event: 'policy_trip.all_absent',
            title: 'Toàn bộ học sinh đã báo vắng',
            body: 'Chuyến '.$this->tripLabel($trip).' có toàn bộ HS báo vắng. Cân nhắc hủy chuyến.',
            url: '/p2p-policy/trips?date='.$this->dateValue($trip->trip_date),
            withMail: false,
            meta: ['policy_trip_id' => (int) $trip->id],
        ));
    }

    /** §5.1/§11: hoàn thành chuyến khi còn HS đã lên nhưng chưa tích xuống. */
    public function tripBoardedNotAlighted(PolicyTrip $trip, int $count): void
    {
        $this->notifyStaff(new PolicyStaffAlertNotification(
            event: 'policy_trip.boarded_not_alighted',
            title: 'HS chưa được tích xuống xe',
            body: 'Chuyến '.$this->tripLabel($trip).' hoàn thành khi còn '.$count.' HS chưa tích xuống xe.',
            url: '/p2p-policy/trips?date='.$this->dateValue($trip->trip_date),
            withMail: true,
            meta: ['policy_trip_id' => (int) $trip->id],
        ));
    }

    /** §11: chuyến chưa gán tài xế khi gần giờ xe. */
    public function tripUnassigned(PolicyTrip $trip): void
    {
        $this->notifyStaff(new PolicyStaffAlertNotification(
            event: 'policy_trip.unassigned',
            title: 'Chuyến chưa gán tài xế',
            body: 'Chuyến '.$this->tripLabel($trip).' sắp đến giờ nhưng chưa có tài xế.',
            url: '/p2p-policy/trips?date='.$this->dateValue($trip->trip_date),
            withMail: true,
            meta: ['policy_trip_id' => (int) $trip->id],
        ));
    }

    /** §11: policy đổi trạng thái ảnh hưởng chuyến tương lai. */
    public function policyChangeAffectsTrips(StudentPolicy $policy, int $affected): void
    {
        if ($affected < 1) {
            return;
        }

        $policy->loadMissing('student');
        $this->notifyStaff(new PolicyStaffAlertNotification(
            event: 'policy.change_affects_trips',
            title: 'Thay đổi chính sách ảnh hưởng chuyến',
            body: 'Cập nhật chính sách của '.($policy->student?->full_name ?? ('HS #'.$policy->student_id))
                .' ảnh hưởng '.$affected.' chuyến chưa thực hiện.',
            url: '/p2p-policy/students',
            withMail: false,
            meta: ['student_policy_id' => (int) $policy->id, 'affected_trips' => $affected],
        ));
    }

    /** §11: job sinh chuyến fail cả 2 lần (22:00 + 05:00). */
    public function generationFailed(string $date, ?string $reason): void
    {
        $this->notifyAdmins(new PolicyStaffAlertNotification(
            event: 'policy_trip.generation_failed',
            title: 'Sinh chuyến P2P thất bại',
            body: 'Không thể sinh chuyến cho ngày '.$date.($reason ? ' — '.$reason : '').'. Vui lòng kiểm tra ngay.',
            url: '/p2p-policy/trips?date='.$date,
            withMail: true,
        ));
    }

    /** §11: lịch ngày học thiếu semester (L1). */
    public function calendarMissingSemester(string $date): void
    {
        $this->notifyAdmins(new PolicyStaffAlertNotification(
            event: 'policy_calendar.missing_semester',
            title: 'Lịch thiếu học kỳ (semester)',
            body: 'Ngày học '.$date.' chưa điền semester — job không sinh được chuyến (L1).',
            url: '/p2p-policy/calendar',
            withMail: true,
        ));
    }

    private function notifyStaff(PolicyStaffAlertNotification $notification): void
    {
        $this->send(self::STAFF_ROLES, $notification);
    }

    private function notifyAdmins(PolicyStaffAlertNotification $notification): void
    {
        $this->send(self::ADMIN_ROLES, $notification);
    }

    /**
     * @param list<string> $roles
     */
    private function send(array $roles, PolicyStaffAlertNotification $notification): void
    {
        if (! $this->rolesConfigured($roles)) {
            return;
        }

        $recipients = User::query()->role($roles)->get();
        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, $notification);
    }

    /**
     * @param list<string> $roles
     */
    private function rolesConfigured(array $roles): bool
    {
        $guard = config('permission.defaults.guard', 'web');

        return Role::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $roles)
            ->exists();
    }

    private function tripLabel(PolicyTrip $trip): string
    {
        $trip->loadMissing('route');

        return ($trip->route?->name ?? ('#'.$trip->id))
            .' · '.$this->timeSlotLabel($trip->time_slot)
            .' · '.$this->dateLabel($trip->trip_date);
    }

    private function timeSlotLabel(?string $slot): string
    {
        return $slot === PolicyTrip::TIME_SLOT_MORNING ? 'Ca sáng' : 'Ca chiều';
    }

    private function dateLabel(mixed $date): string
    {
        return $date instanceof \DateTimeInterface ? $date->format('d/m/Y') : (string) $date;
    }

    private function dateValue(mixed $date): string
    {
        return $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;
    }

    private function timeLabel(mixed $time): ?string
    {
        if ($time === null) {
            return null;
        }
        if (is_string($time)) {
            return substr($time, 0, 5);
        }

        return $time instanceof \DateTimeInterface ? $time->format('H:i') : (string) $time;
    }
}
