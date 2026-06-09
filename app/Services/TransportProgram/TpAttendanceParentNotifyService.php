<?php

namespace App\Services\TransportProgram;

use App\Models\TpAuditLog;
use App\Models\TpProgramDay;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TpAttendanceParentNotifyService
{
    public function __construct(
        private readonly AttendanceService $attendance,
        private readonly TpAuditLogger $audit,
    ) {}

    /**
     * @param  array<int>  $studentIds
     * @return array{message: string, recipients: list<array<string, mixed>>, recipient_count: int, with_phone: int, without_phone: int}
     */
    public function preview(TpProgramDay $day, ?string $shift, string $scope, array $studentIds = []): array
    {
        $payload = $this->attendance->getAttendance($day, $shift);
        $targets = $this->resolveTargets($payload['items'], $scope, $studentIds);

        $message = $this->buildMessage($payload, $shift);

        return $this->buildPreviewResult($targets, $message);
    }

    /**
     * @param  array<int>  $studentIds
     * @return array{batch_id: string, queued: int, skipped_no_phone: int, failed: int, recipients: list<array<string, mixed>>}
     */
    public function notifyAbsentParents(
        TpProgramDay $day,
        ?int $actorId,
        ?string $shift,
        string $scope,
        array $studentIds = [],
        ?string $retryBatchId = null,
    ): array {
        $payload = $this->attendance->getAttendance($day, $shift);
        $targets = $this->resolveTargets($payload['items'], $scope, $studentIds);
        $message = $this->buildMessage($payload, $shift);
        $batchId = $retryBatchId ?: (string) Str::uuid();

        $queued = 0;
        $skipped = 0;
        $failed = 0;
        $recipientRows = [];

        foreach ($targets as $item) {
            $phone = trim((string) ($item['parent_phone'] ?? ''));
            $row = [
                'student_id' => $item['student_id'],
                'full_name' => $item['full_name'],
                'parent_phone' => $phone ?: null,
                'status' => 'queued',
            ];
            if ($phone === '') {
                $skipped++;
                $row['status'] = 'skipped_no_phone';
                $recipientRows[] = $row;

                continue;
            }
            try {
                Log::info('tp.attendance.parent_notify.queued', [
                    'batch_id' => $batchId,
                    'program_day_id' => $day->id,
                    'student_id' => $item['student_id'],
                    'phone' => $phone,
                ]);
                $queued++;
                $row['status'] = 'success';
            } catch (\Throwable) {
                $failed++;
                $row['status'] = 'failed';
            }
            $recipientRows[] = $row;
        }

        $this->audit->log($actorId, 'attendance.parent_notify', $day, $day->program, metadata: [
            'batch_id' => $batchId,
            'scope' => $scope,
            'shift' => $shift,
            'message_preview' => $message,
            'queued' => $queued,
            'skipped_no_phone' => $skipped,
            'failed' => $failed,
            'recipients' => $recipientRows,
        ]);

        return [
            'batch_id' => $batchId,
            'queued' => $queued,
            'skipped_no_phone' => $skipped,
            'failed' => $failed,
            'recipients' => $recipientRows,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listNotifyLogs(TpProgramDay $day, int $limit = 30): array
    {
        return TpAuditLog::query()
            ->where('entity_type', class_basename($day))
            ->where('entity_id', $day->id)
            ->where('action', 'attendance.parent_notify')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (TpAuditLog $log) => [
                'id' => $log->id,
                'actor_name' => $log->actor_name,
                'created_at' => $log->created_at?->toIso8601String(),
                'metadata' => $log->metadata,
            ])->all();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<int>  $studentIds
     * @return list<array<string, mixed>>
     */
    private function resolveTargets(array $items, string $scope, array $studentIds): array
    {
        $idSet = array_fill_keys(array_map('intval', $studentIds), true);

        return array_values(array_filter($items, function (array $item) use ($scope, $idSet) {
            return match ($scope) {
                'selected', 'filtered' => isset($idSet[(int) $item['student_id']]),
                'all_absent' => ($item['status'] ?? '') === 'absent',
                'not_marked' => ($item['status'] ?? '') === 'attending' && empty($item['boarded_at']),
                default => false,
            };
        }));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function buildMessage(array $payload, ?string $shift): string
    {
        $day = $payload['day'] ?? [];
        $date = $day['scheduled_date'] ?? '';
        $program = $day['program_name'] ?? 'Chương trình đưa đón';
        $shiftLabel = $shift === 'afternoon' ? 'Chiều' : ($shift === 'morning' ? 'Sáng' : '');

        $parts = ["Kính gửi phụ huynh,", "Con em vắng mặt chuyến xe {$program}"];
        if ($shiftLabel !== '') {
            $parts[] = "Ca {$shiftLabel}";
        }
        if ($date !== '') {
            $parts[] = "Ngày {$date}";
        }
        $parts[] = 'Vui lòng liên hệ nhà trường nếu cần hỗ trợ.';

        return implode("\n", $parts);
    }

    /**
     * @param  list<array<string, mixed>>  $targets
     * @return array{message: string, recipients: list<array<string, mixed>>, recipient_count: int, with_phone: int, without_phone: int}
     */
    private function buildPreviewResult(array $targets, string $message): array
    {
        $withPhone = 0;
        $withoutPhone = 0;
        $recipients = [];
        foreach ($targets as $item) {
            $phone = trim((string) ($item['parent_phone'] ?? ''));
            if ($phone !== '') {
                $withPhone++;
            } else {
                $withoutPhone++;
            }
            $recipients[] = [
                'student_id' => $item['student_id'],
                'full_name' => $item['full_name'],
                'parent_phone' => $phone ?: null,
                'display_status' => $item['display_status'] ?? null,
            ];
        }

        return [
            'message' => $message,
            'recipients' => $recipients,
            'recipient_count' => count($targets),
            'with_phone' => $withPhone,
            'without_phone' => $withoutPhone,
        ];
    }
}
