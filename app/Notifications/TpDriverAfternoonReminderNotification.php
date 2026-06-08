<?php

namespace App\Notifications;

use App\Notifications\Concerns\AddsMailWhenValidEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Nhắc tài xế ca chiều chương trình đưa đón (trước giờ chạy).
 */
class TpDriverAfternoonReminderNotification extends Notification implements ShouldQueue
{
    use AddsMailWhenValidEmail;
    use Queueable;

    public function __construct(
        public int $programDayId,
        public string $programName,
        public string $scheduledDate,
        public string $departureTime,
        public int $expectedCount,
    ) {
        $this->onQueue(config('dispatch.notifications_queue_default'));
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channelsWithOptionalMail($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = trim((string) ($notifiable->name ?? '')) ?: 'Anh/Chị';
        $line = trim($this->programName).' · '.$this->scheduledDate.' '.$this->departureTime;
        if ($this->expectedCount > 0) {
            $line .= ' · '.$this->expectedCount.' HS';
        }

        return (new MailMessage)
            ->subject('Ca chiều đưa đón — xác nhận trên app')
            ->greeting('Xin chào '.$name.',')
            ->line('Bạn có ca chương trình đưa đón chiều nay. Vui lòng mở app tài xế để xác nhận trước giờ chạy.')
            ->line($line)
            ->action('Mở app tài xế', rtrim(config('app.url'), '/').'/driver');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $body = trim($this->programName).' · '.$this->scheduledDate.' '.$this->departureTime;
        if ($this->expectedCount > 0) {
            $body .= ' · '.$this->expectedCount.' HS';
        }

        return [
            'title' => 'Ca chiều đưa đón hôm nay',
            'body' => $body,
            'program_day_id' => $this->programDayId,
            'scheduled_date' => $this->scheduledDate,
            'departure_time' => $this->departureTime,
            'shift' => 'afternoon',
            'event' => 'tp.driver.afternoon_reminder',
            'url' => '/driver',
            'audience' => 'driver',
        ];
    }
}
