<?php

namespace App\Services\TransportProgram;

use App\Models\TpProgram;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Ca sáng / chiều là hai phiên điểm danh độc lập khi chương trình bật cả hai.
 */
class TpAttendanceShiftResolver
{
    private ?bool $absenceShiftColumn = null;

    public function __construct(
        private readonly TpProgramScheduleSlots $scheduleSlots,
    ) {}

    public function absenceTableHasShiftColumn(): bool
    {
        if ($this->absenceShiftColumn === null) {
            $this->absenceShiftColumn = Schema::hasColumn('tp_day_absences', 'shift');
        }

        return $this->absenceShiftColumn;
    }

    public function isMultiSlot(TpProgram $program): bool
    {
        return count($this->scheduleSlots->slotsForProgram($program)) > 1;
    }

    /**
     * @return 'all'|'morning'|'afternoon'
     */
    public function resolveStorageShift(TpProgram $program, ?string $requested): string
    {
        if (! $this->absenceTableHasShiftColumn()) {
            return 'all';
        }

        if (! $this->isMultiSlot($program)) {
            return 'all';
        }

        $req = strtolower(trim((string) $requested));
        if (in_array($req, ['morning', 'afternoon'], true)) {
            return $req;
        }

        $slots = $this->scheduleSlots->slotsForProgram($program);

        return (string) ($slots[0]['shift'] ?? 'morning');
    }

    /**
     * @param  Builder<\App\Models\TpDayAbsence>  $query
     */
    public function applyAbsenceScope(Builder $query, string $storageShift): void
    {
        if (! $this->absenceTableHasShiftColumn()) {
            return;
        }

        if ($storageShift === 'all') {
            $query->where('shift', 'all');

            return;
        }

        $query->where(function (Builder $q) use ($storageShift) {
            $q->where('shift', $storageShift)->orWhere('shift', 'all');
        });
    }

    /**
     * @return array<string, int|string>
     */
    public function absenceIdentityAttributes(int $programDayId, int $studentId, string $storageShift): array
    {
        $attrs = [
            'program_day_id' => $programDayId,
            'student_id' => $studentId,
        ];

        if ($this->absenceTableHasShiftColumn()) {
            $attrs['shift'] = $storageShift;
        }

        return $attrs;
    }

    /**
     * @param  Builder<\App\Models\TpDayAbsence>  $query
     */
    public function applyExactShiftScope(Builder $query, string $storageShift): void
    {
        if (! $this->absenceTableHasShiftColumn()) {
            return;
        }

        $query->where('shift', $storageShift);
    }
}
