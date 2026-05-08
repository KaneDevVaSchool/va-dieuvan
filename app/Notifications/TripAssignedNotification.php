<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TripAssignedNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
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
        $bits = [];

        $tt = $this->tripTypeLabel();
        if ($tt !== '') {
            $bits[] = $tt;
        }

        try {
            if ($this->departAt !== '') {
                $c = Carbon::parse($this->departAt);
                $bits[] = 'Đón '.$c->locale('vi')->translatedFormat('d MMM, H:mm');
            }
        } catch (\Throwable) {
            //
        }

        $route = $this->routeSummary();
        $prefix = $bits !== [] ? implode(' · ', $bits).' — ' : '';

        return Str::limit($prefix.$route, 180, '…');
    }

    /**
     * Nhãn ngắn (tiếng Việt — đồng bộ với copy thông báo server).
     */
    private function tripTypeLabel(): string
    {
        return match ($this->tripType) {
            'door_to_door' => 'Cửa–cửa',
            'point_to_point' => 'Điểm–điểm',
            'business' => 'Công tác',
            'cargo' => 'Hàng hóa',
            default => '',
        };
    }

    private function routeSummary(): string
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
