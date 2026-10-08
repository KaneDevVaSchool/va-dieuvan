<?php

namespace App\Notifications;

use App\Notifications\Concerns\AddsMailWhenValidEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Nhắc tài xế làm checklist theo khung giờ cấu hình (database → Web Push qua listener).
 */
class DriverChecklistReminderNotification extends Notification implements ShouldQueue
{
    use AddsMailWhenValidEmail;
    use Queueable;

    public function __construct(
        public int $templateId,
        public string $templateName,
        public string $frequency,
        public string $periodLabel,
    ) {
        $this->onQueue(config('dispatch.notifications_queue_default'));
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channelsWithOptionalMail($notifiable, includeMail: false);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->frequency === 'weekly' ? 'Nhắc checklist hằng tuần' : 'Nhắc checklist hằng ngày',
            'body' => $this->templateName.' · '.$this->periodLabel.' — mở app để kiểm tra xe.',
            'template_id' => $this->templateId,
            'event' => 'checklist.reminder',
            'url' => '/driver/checklists?template='.$this->templateId,
            'audience' => 'driver',
        ];
    }
}
