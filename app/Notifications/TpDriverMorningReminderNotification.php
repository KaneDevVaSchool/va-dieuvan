<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Nhắc tài xế ca sáng chương trình đưa đón (chạy lúc ~06:00).
 */
class TpDriverMorningReminderNotification extends Notification implements ShouldQueue
{
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
        return ['database'];
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
            'title' => 'Ca sáng đưa đón hôm nay',
            'body' => $body,
            'program_day_id' => $this->programDayId,
            'scheduled_date' => $this->scheduledDate,
            'departure_time' => $this->departureTime,
            'shift' => 'morning',
            'event' => 'tp.driver.morning_reminder',
            'url' => '/driver/tp-days/'.$this->programDayId,
            'audience' => 'driver',
        ];
    }
}
