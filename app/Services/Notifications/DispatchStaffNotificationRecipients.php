<?php

namespace App\Services\Notifications;

use App\Models\DispatchRequest;
use App\Models\TpProgramDay;
use App\Models\User;
use App\Notifications\NewDispatchRequestNotification;
use App\Notifications\RecurringStudentCountSubmittedNotification;
use App\Notifications\TpDriverReportedBusyNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class DispatchStaffNotificationRecipients
{
    /** @var list<string> */
    private const STAFF_ROLES = ['dispatcher', 'admin', 'superadmin'];

    public static function staffRolesConfigured(): bool
    {
        $guard = config('permission.defaults.guard', 'web');

        return Role::query()
            ->where('guard_name', $guard)
            ->whereIn('name', self::STAFF_ROLES)
            ->exists();
    }

    /**
     * @return Collection<int, User>
     */
    public static function users(): Collection
    {
        if (! self::staffRolesConfigured()) {
            return new Collection;
        }

        return User::query()->role(self::STAFF_ROLES)->get();
    }

    public static function notifyNewDispatchRequest(DispatchRequest $dispatchRequest): void
    {
        $recipients = self::users();
        if ($recipients->isEmpty()) {
            return;
        }

        $summary = trim(($dispatchRequest->origin ?? '').' → '.($dispatchRequest->destination ?? ''));
        Notification::send(
            $recipients,
            new NewDispatchRequestNotification(
                (int) $dispatchRequest->id,
                $summary !== '→' ? $summary : 'Yêu cầu #'.$dispatchRequest->id,
                (bool) $dispatchRequest->is_urgent,
            ),
        );
    }

    public static function notifyTpDriverReportedBusy(
        TpProgramDay $day,
        ?string $shift,
        string $driverName,
        string $reason,
    ): void {
        $recipients = self::users();
        if ($recipients->isEmpty()) {
            return;
        }

        $program = $day->program;
        $dateLabel = $day->scheduled_date ? $day->scheduled_date->format('d/m/Y') : '';

        Notification::send(
            $recipients,
            new TpDriverReportedBusyNotification(
                (int) $day->id,
                (int) ($program?->id ?? 0),
                (string) ($program?->name ?? ''),
                $dateLabel,
                $shift,
                $driverName,
                $reason,
            ),
        );
    }

    public static function notifyRecurringStudentCountSubmitted(DispatchRequest $dispatchRequest): void
    {
        $recipients = self::users();
        if ($recipients->isEmpty()) {
            return;
        }

        $count = (int) ($dispatchRequest->student_count_actual ?? 0);
        if ($count < 1) {
            return;
        }

        Notification::send(
            $recipients,
            new RecurringStudentCountSubmittedNotification(
                (int) $dispatchRequest->id,
                $count,
            ),
        );
    }
}
