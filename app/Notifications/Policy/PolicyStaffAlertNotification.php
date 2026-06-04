<?php

namespace App\Notifications\Policy;

use App\Notifications\Concerns\AddsMailWhenValidEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Cảnh báo điều vận/admin của module P2P (§11) — một class dùng chung cho nhiều
 * sự kiện (vắng không phép, toàn bộ vắng, chưa gán tài xế, job fail, thiếu
 * semester, policy đổi ảnh hưởng chuyến). In-app luôn bật; email tuỳ mức độ.
 */
class PolicyStaffAlertNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use AddsMailWhenValidEmail;
    use Queueable;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $event,
        public string $title,
        public string $body,
        public ?string $url = null,
        public bool $withMail = false,
        public array $meta = [],
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $this->channelsWithOptionalMail($notifiable, $this->withMail);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[P2P] '.$this->title)
            ->greeting('Xin chào '.(trim((string) ($notifiable->name ?? '')) ?: 'bạn'))
            ->line($this->body);

        if ($this->url) {
            $mail->action('Mở điều vận', rtrim((string) config('app.url'), '/').'/mng'.$this->url);
        }

        return $mail;
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return array_merge([
            'title' => $this->title,
            'body' => $this->body,
            'event' => $this->event,
            'url' => $this->url,
            'audience' => 'dispatch_staff',
        ], $this->meta);
    }
}
