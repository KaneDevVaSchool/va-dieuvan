<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDispatchRequestNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
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
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->isUrgent ? '[GẤP] Yêu cầu điều xe mới' : 'Yêu cầu điều xe mới',
            'body' => $this->summaryLine,
            'dispatch_request_id' => $this->dispatchRequestId,
            'event' => 'dispatch_request.created',
            'is_urgent' => $this->isUrgent,
            'url' => '/requests/'.$this->dispatchRequestId,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->isUrgent
            ? '[GẤP] Yêu cầu điều xe mới #'.$this->dispatchRequestId
            : 'Yêu cầu điều xe mới #'.$this->dispatchRequestId;

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Xin chào '.($notifiable->name ?? 'Dispatcher').',')
            ->line($this->summaryLine)
            ->action('Xem yêu cầu', url('/requests/'.$this->dispatchRequestId))
            ->line('Vui lòng xử lý trong thời gian sớm nhất.')
            ->salutation('Trân trọng, VA Điều Vận');
    }
}
