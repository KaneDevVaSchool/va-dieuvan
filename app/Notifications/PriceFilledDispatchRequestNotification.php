<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PriceFilledDispatchRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $dispatchRequestId,
        public string $summaryLine,
    ) {
        $this->onQueue(config('dispatch.notifications_queue_default'));
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
            'title' => 'Phiếu điều xe cần duyệt',
            'body' => $this->summaryLine,
            'dispatch_request_id' => $this->dispatchRequestId,
            'event' => 'dispatch_request.price_filled',
        ];
    }
}
