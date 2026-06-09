<?php

namespace App\Notifications;

use App\Services\DispatchRequests\DispatchRequestMailPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class TripDriverRemovedNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public int $tripId,
        public string $tripType,
        public string $origin,
        public string $destination,
        public string $departAt,
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
        return [
            'title' => 'Bạn đã được rút khỏi chuyến',
            'body' => $this->summaryBody(),
            'trip_id' => $this->tripId,
            'trip_type' => $this->tripType,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'depart_at' => $this->departAt,
            'event' => 'trip.driver_removed',
            'url' => '/driver/schedule',
            'audience' => 'driver',
        ];
    }

    private function summaryBody(): string
    {
        $tt = match ($this->tripType) {
            'door_to_door' => 'Cửa–cửa',
            'point_to_point' => 'Điểm–điểm',
            'business' => 'Công tác',
            'cargo' => 'Hàng hóa',
            default => '',
        };
        $bits = $tt !== '' ? [$tt] : [];
        if ($this->departAt !== '') {
            try {
                $bits[] = 'Đón '.Carbon::parse($this->departAt)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i');
            } catch (\Throwable) {
            }
        }
        $route = trim($this->origin);
        $dest = trim($this->destination);
        $routeSummary = ($route !== '' && $dest !== '') ? $route.' → '.$dest : ($route ?: $dest ?: 'Chuyến #'.$this->tripId);
        $prefix = $bits !== [] ? implode(' · ', $bits).' — ' : '';

        return \Illuminate\Support\Str::limit($prefix.$routeSummary, 180, '…');
    }
}
