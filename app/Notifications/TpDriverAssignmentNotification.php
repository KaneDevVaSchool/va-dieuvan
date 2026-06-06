<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Thông báo tài xế khi phân công / gỡ chuyến đưa đón (theo ngày hoặc mặc định chương trình).
 */
class TpDriverAssignmentNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public string $changeType,
        public string $driverRole,
        public string $scope,
        public int $programId,
        public string $programName,
        public ?int $programDayId = null,
        public ?string $scheduledDate = null,
        public ?string $departureLabel = null,
    ) {
        $this->onQueue(config('dispatch.notifications_queue_default'));
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'message' => $this->body(),
            'program_id' => $this->programId,
            'program_day_id' => $this->programDayId,
            'scheduled_date' => $this->scheduledDate,
            'change_type' => $this->changeType,
            'driver_role' => $this->driverRole,
            'scope' => $this->scope,
            'event' => 'tp.driver.assignment_changed',
            'url' => $this->actionUrl(),
            'audience' => 'driver',
        ];
    }

    private function title(): string
    {
        $isBackup = $this->driverRole === 'backup';
        $isProgram = $this->scope === 'program';

        if ($this->changeType === 'assigned') {
            if ($isProgram) {
                return $isBackup
                    ? 'Bạn là tài xế sơ cua mặc định'
                    : 'Bạn là tài xế mặc định chương trình';
            }

            return $isBackup
                ? 'Bạn được gán sơ cua chuyến đưa đón'
                : 'Bạn được gán chuyến đưa đón';
        }

        if ($isProgram) {
            return $isBackup
                ? 'Bạn không còn là sơ cua mặc định'
                : 'Bạn không còn là tài xế mặc định';
        }

        return $isBackup
            ? 'Bạn không còn là sơ cua chuyến này'
            : 'Bạn không còn được gán chuyến này';
    }

    private function body(): string
    {
        $name = trim($this->programName);
        $bits = [$name !== '' ? $name : 'Chương trình đưa đón'];

        if ($this->scheduledDate !== null && $this->scheduledDate !== '') {
            $bits[] = $this->formatDateVi($this->scheduledDate);
        }

        if ($this->departureLabel !== null && trim($this->departureLabel) !== '') {
            $bits[] = trim($this->departureLabel);
        }

        return implode(' · ', $bits);
    }

    private function actionUrl(): string
    {
        if ($this->programDayId !== null && $this->programDayId > 0) {
            return '/driver/tp-days/'.$this->programDayId;
        }

        return '/driver/tp-days';
    }

    private function formatDateVi(string $date): string
    {
        try {
            return Carbon::parse($date)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y');
        } catch (\Throwable) {
            return $date;
        }
    }
}
