<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

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
        $channels = ['database'];
        if ($this->shouldDeliverMail()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Tránh lỗi SMTP khi production vô tình để MAIL_HOST=mailpit (chỉ có trong Docker dev).
     */
    protected function shouldDeliverMail(): bool
    {
        if (! config('dispatch.mail_for_new_dispatch_requests')) {
            return false;
        }

        if (config('mail.default') !== 'smtp') {
            return true;
        }

        $host = strtolower((string) config('mail.mailers.smtp.host', ''));
        if ($host === 'mailpit' && ! app()->environment(['local', 'testing'])) {
            Log::warning('Mail skipped for NewDispatchRequestNotification: MAIL_HOST=mailpit is for local dev. Configure production MAIL_* or set DISPATCH_MAIL_FOR_NEW_REQUESTS=false.');

            return false;
        }

        return true;
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
