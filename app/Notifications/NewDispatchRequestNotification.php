<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewDispatchRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $dispatchRequestId,
        public string $summaryLine,
        public bool $isUrgent = false,
    ) {
        $this->onQueue(
            $this->isUrgent
                ? config('dispatch.notifications_queue_urgent')
                : config('dispatch.notifications_queue_default')
        );
    }

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
            'title' => 'Yêu cầu điều xe mới',
            'body' => $this->summaryLine,
            'dispatch_request_id' => $this->dispatchRequestId,
            'event' => 'dispatch_request.created',
            'is_urgent' => $this->isUrgent,
        ];
    }
}
