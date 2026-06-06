<?php

namespace App\Services\TransportProgram;

use App\Models\Driver;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use App\Models\User;
use App\Notifications\TpDriverAssignmentNotification;
use Illuminate\Support\Facades\Log;

class TpDriverAssignmentNotifyService
{
    public function __construct(
        private readonly TpProgramScheduleSlots $scheduleSlots,
    ) {}

    public function notifyEffectiveMainChange(TpProgramDay $day, ?int $previousDriverId, ?int $newDriverId): void
    {
        $this->notifyPair($day, $previousDriverId, $newDriverId, 'main');
    }

    public function notifyEffectiveBackupChange(TpProgramDay $day, ?int $previousDriverId, ?int $newDriverId): void
    {
        $this->notifyPair($day, $previousDriverId, $newDriverId, 'backup');
    }

    public function notifyProgramDefaultDriverChange(
        TpProgram $program,
        ?int $previousDriverId,
        ?int $newDriverId,
        bool $backup,
    ): void {
        $role = $backup ? 'backup' : 'main';
        $this->dispatchChange(
            driverId: $previousDriverId,
            changeType: 'removed',
            driverRole: $role,
            scope: 'program',
            program: $program,
            day: null,
            skipIfDriverId: $newDriverId,
        );
        $this->dispatchChange(
            driverId: $newDriverId,
            changeType: 'assigned',
            driverRole: $role,
            scope: 'program',
            program: $program,
            day: null,
            skipIfDriverId: $previousDriverId,
        );
    }

    private function notifyPair(TpProgramDay $day, ?int $previousDriverId, ?int $newDriverId, string $role): void
    {
        $day->loadMissing('program');
        $program = $day->program;
        if ($program === null) {
            return;
        }

        $prev = $this->normalizeDriverId($previousDriverId);
        $next = $this->normalizeDriverId($newDriverId);
        if ($prev === $next) {
            return;
        }

        $this->dispatchChange(
            driverId: $prev,
            changeType: 'removed',
            driverRole: $role,
            scope: 'day',
            program: $program,
            day: $day,
            skipIfDriverId: $next,
        );
        $this->dispatchChange(
            driverId: $next,
            changeType: 'assigned',
            driverRole: $role,
            scope: 'day',
            program: $program,
            day: $day,
            skipIfDriverId: $prev,
        );
    }

    private function dispatchChange(
        ?int $driverId,
        string $changeType,
        string $driverRole,
        string $scope,
        TpProgram $program,
        ?TpProgramDay $day,
        ?int $skipIfDriverId,
    ): void {
        $id = $this->normalizeDriverId($driverId);
        if ($id === null) {
            return;
        }
        if ($skipIfDriverId !== null && $id === $this->normalizeDriverId($skipIfDriverId)) {
            return;
        }

        $user = $this->driverUser($id);
        if ($user === null) {
            Log::warning('tp.driver_assignment_notify_no_user', [
                'driver_id' => $id,
                'change_type' => $changeType,
                'program_id' => $program->id,
                'program_day_id' => $day?->id,
            ]);

            return;
        }

        $departureLabel = $day !== null ? $this->departureLabelForDay($day) : null;
        $scheduledDate = $day?->scheduled_date?->toDateString();

        $user->notify(new TpDriverAssignmentNotification(
            changeType: $changeType,
            driverRole: $driverRole,
            scope: $scope,
            programId: (int) $program->id,
            programName: (string) $program->name,
            programDayId: $day !== null ? (int) $day->id : null,
            scheduledDate: $scheduledDate,
            departureLabel: $departureLabel,
        ));
    }

    private function driverUser(int $driverId): ?User
    {
        $driver = Driver::query()->with('user')->find($driverId);
        $user = $driver?->user;
        if ($user === null || ! $user->is_active) {
            return null;
        }

        return $user;
    }

    private function departureLabelForDay(TpProgramDay $day): string
    {
        $program = $day->program;
        if ($program === null) {
            return '';
        }

        $slots = $this->scheduleSlots->slotsForProgram($program);
        $times = [];
        foreach ($slots as $slot) {
            $t = $slot['departure_time'] ?? null;
            if (is_string($t) && $t !== '') {
                $times[] = $t;
            }
        }

        if ($times !== []) {
            return implode(', ', array_values(array_unique($times)));
        }

        return (string) ($program->departure_time ?? '');
    }

    private function normalizeDriverId(?int $driverId): ?int
    {
        if ($driverId === null || $driverId < 1) {
            return null;
        }

        return $driverId;
    }
}
