<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;

class DispatchPackageSessionsLowBalanceNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public int $dispatchPackageId,
        public string $label,
        public int $sessionsRemaining,
        public int $totalSessions,
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
            'title' => 'Gói buổi điều xe sắp hết',
            'body' => sprintf(
                'Gói "%s": còn %d/%d buổi.',
                $this->label !== '' ? $this->label : '#'.$this->dispatchPackageId,
                $this->sessionsRemaining,
                $this->totalSessions
            ),
            'dispatch_package_id' => $this->dispatchPackageId,
            'sessions_remaining' => $this->sessionsRemaining,
            'total_sessions' => $this->totalSessions,
            'event' => 'dispatch_package.low_sessions_remaining',
            'url' => '/requests',
        ];
    }
}
