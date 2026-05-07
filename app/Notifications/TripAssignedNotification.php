<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TripAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $tripId,
        public string $tripType,
        public string $origin,
        public string $destination,
        public string $departAt,
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
        $body = $this->summaryBody();

        return [
            'title' => 'Chuyến mới được phân công',
            'body' => $body,
            'trip_id' => $this->tripId,
            'trip_type' => $this->tripType,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'depart_at' => $this->departAt,
            'event' => 'trip.assigned',
            'is_urgent' => $this->isUrgent,
            'url' => '/driver/trips/'.$this->tripId,
        ];
    }

    private function summaryBody(): string
    {
        $o = trim($this->origin);
        $d = trim($this->destination);
        if ($o !== '' && $d !== '') {
            return $o.' → '.$d;
        }
        if ($o !== '') {
            return $o;
        }
        if ($d !== '') {
            return $d;
        }

        return 'Chuyến #'.$this->tripId;
    }
}
