<?php

namespace App\Services\TransportProgram;

use App\Models\Driver;
use App\Models\TpProgram;
use App\Models\TpProgramDay;

class TpShiftDriverSupport
{
    public function __construct(
        private readonly TpProgramScheduleSlots $scheduleSlots,
    ) {}

    public function programUsesPerShiftDrivers(TpProgram $program): bool
    {
        return count($this->scheduleSlots->slotsForProgram($program)) >= 2;
    }

    public function normalizeShift(?string $shift, TpProgram $program): ?string
    {
        if (! $this->programUsesPerShiftDrivers($program)) {
            return null;
        }
        $s = $shift === 'morning' || $shift === 'afternoon' ? $shift : null;
        abort_unless($s !== null, 422, 'Chương trình có ca sáng và chiều — cần chỉ rõ ca (morning hoặc afternoon).');

        return $s;
    }

    /**
     * @return array{driver_id: ?int, backup_driver_id: ?int}
     */
    public function overrideColumnIds(TpProgramDay $day, ?string $shift): array
    {
        if ($shift === 'morning') {
            return ['driver_id' => $day->morning_driver_id, 'backup_driver_id' => $day->morning_backup_driver_id];
        }
        if ($shift === 'afternoon') {
            return ['driver_id' => $day->afternoon_driver_id, 'backup_driver_id' => $day->afternoon_backup_driver_id];
        }

        return ['driver_id' => $day->driver_id, 'backup_driver_id' => $day->backup_driver_id];
    }

    public function effectiveMainDriverId(TpProgramDay $day, ?string $shift): ?int
    {
        $day->loadMissing('program');
        $program = $day->program;
        if ($program === null) {
            return null;
        }

        if ($shift === 'morning') {
            return $day->morning_driver_id
                ?? $this->settingsDriverId($program, 'morning', 'default_driver_id')
                ?? $day->driver_id
                ?? $program->default_driver_id;
        }
        if ($shift === 'afternoon') {
            return $day->afternoon_driver_id
                ?? $this->settingsDriverId($program, 'afternoon', 'default_driver_id')
                ?? $day->driver_id
                ?? $program->default_driver_id;
        }

        return $day->driver_id ?? $program->default_driver_id;
    }

    public function effectiveBackupDriverId(TpProgramDay $day, ?string $shift): ?int
    {
        $day->loadMissing('program');
        $program = $day->program;
        if ($program === null) {
            return null;
        }

        if ($shift === 'morning') {
            return $day->morning_backup_driver_id
                ?? $this->settingsDriverId($program, 'morning', 'backup_driver_id')
                ?? $day->backup_driver_id
                ?? $program->backup_driver_id;
        }
        if ($shift === 'afternoon') {
            return $day->afternoon_backup_driver_id
                ?? $this->settingsDriverId($program, 'afternoon', 'backup_driver_id')
                ?? $day->backup_driver_id
                ?? $program->backup_driver_id;
        }

        return $day->backup_driver_id ?? $program->backup_driver_id;
    }

    public function effectiveMainDriver(TpProgramDay $day, ?string $shift): ?Driver
    {
        return $this->loadActiveDriver($this->effectiveMainDriverId($day, $shift));
    }

    public function effectiveBackupDriver(TpProgramDay $day, ?string $shift): ?Driver
    {
        return $this->loadActiveDriver($this->effectiveBackupDriverId($day, $shift));
    }

    public function departureTimeForShift(TpProgram $program, string $shift): ?string
    {
        foreach ($this->scheduleSlots->slotsForProgram($program) as $slot) {
            if (($slot['shift'] ?? null) === $shift) {
                return $slot['departure_time'] ?? null;
            }
        }

        return $shift === 'morning'
            ? ($program->departure_time ? substr((string) $program->departure_time, 0, 5) : null)
            : ($program->return_time ? substr((string) $program->return_time, 0, 5) : null);
    }

    private function settingsDriverId(TpProgram $program, string $shift, string $key): ?int
    {
        $settings = is_array($program->settings) ? $program->settings : [];
        $block = is_array($settings[$shift] ?? null) ? $settings[$shift] : [];
        $id = $block[$key] ?? null;

        return $id !== null && $id !== '' ? (int) $id : null;
    }

    private function loadActiveDriver(?int $driverId): ?Driver
    {
        if (! $driverId) {
            return null;
        }
        $driver = Driver::query()->find($driverId);
        if (! $driver || $driver->trashed()) {
            return null;
        }
        if ($driver->employment_status === 'inactive') {
            return null;
        }

        return $driver;
    }
}
