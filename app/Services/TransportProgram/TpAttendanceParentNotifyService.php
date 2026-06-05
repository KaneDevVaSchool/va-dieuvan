<?php

namespace App\Services\TransportProgram;

use App\Models\TpProgramDay;
use Illuminate\Support\Facades\Log;

class TpAttendanceParentNotifyService
{
    public function __construct(
        private readonly AttendanceService $attendance,
        private readonly TpAuditLogger $audit,
    ) {}

    /**
     * MVP: ghi audit + đếm học sinh vắng có SĐT phụ huynh (kênh SMS/Zalo sau ENH-01).
     *
     * @return array{queued: int, skipped_no_phone: int}
     */
    public function notifyAbsentParents(TpProgramDay $day, ?int $actorId): array
    {
        $payload = $this->attendance->getAttendance($day);
        $queued = 0;
        $skipped = 0;

        foreach ($payload['items'] as $item) {
            if ($item['status'] !== 'absent') {
                continue;
            }
            if (empty($item['parent_phone'])) {
                $skipped++;

                continue;
            }
            $queued++;
            Log::info('tp.attendance.parent_notify.queued', [
                'program_day_id' => $day->id,
                'student_id' => $item['student_id'],
                'phone' => $item['parent_phone'],
            ]);
        }

        $this->audit->log($actorId, 'attendance.parent_notify', $day, $day->program, metadata: [
            'queued' => $queued,
            'skipped_no_phone' => $skipped,
        ]);

        return ['queued' => $queued, 'skipped_no_phone' => $skipped];
    }
}
