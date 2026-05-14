<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;

class DeptHeadApprovalRequestedNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public int $dispatchRequestId,
        public string $summaryLine,
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
            'title' => 'Phiếu điều xe chờ duyệt phòng ban',
            'body' => $this->summaryLine,
            'dispatch_request_id' => $this->dispatchRequestId,
            'event' => 'dispatch_request.dept_approval_requested',
            'url' => '/requests/'.$this->dispatchRequestId,
        ];
    }
}
