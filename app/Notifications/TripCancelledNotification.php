<?php

namespace App\Notifications;

use App\Notifications\Concerns\AddsMailWhenValidEmail;
use App\Services\DispatchRequests\DispatchRequestMailPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class TripCancelledNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use AddsMailWhenValidEmail;
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
        return $this->channelsWithOptionalMail($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = DispatchRequestMailPresenter::tripTypeLabelVi($this->tripType);
        $departLabel = $this->departAtLabel();
        $subject = 'Chuyến xe bị hủy — '.$label.($departLabel !== '' ? ' · '.$departLabel : '');

        return (new MailMessage)
            ->subject($subject)
            ->view('mail.trip-cancelled', $this->viewData($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Chuyến xe bị hủy',
            'body' => $this->summaryBody(),
            'trip_id' => $this->tripId,
            'trip_type' => $this->tripType,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'depart_at' => $this->departAt,
            'event' => 'trip.cancelled',
            'url' => '/driver/trips/'.$this->tripId,
            'audience' => 'driver',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(object $notifiable): array
    {
        $route = trim($this->origin).' → '.trim($this->destination);
        if (trim($this->origin) === '' && trim($this->destination) === '') {
            $route = 'Chuyến #'.$this->tripId;
        }

        return [
            'driverName' => trim((string) ($notifiable->name ?? '')) ?: ($notifiable->email ?? 'bạn'),
            'tripTypeLabel' => DispatchRequestMailPresenter::tripTypeLabelVi($this->tripType),
            'routeLine' => $route,
            'timeLineDepart' => $this->departAtLabel() ?: '—',
            'detailUrl' => rtrim(config('app.url'), '/').'/driver/trips/'.$this->tripId,
            'helpdesk' => DispatchRequestMailPresenter::helpdesk(),
            'privacyScopeFooter' => 'Bạn nhận email vì đã được phân công lái chuyến này trên Điều vận.',
            'showProgress' => false,
        ];
    }

    private function departAtLabel(): string
    {
        if ($this->departAt === '') {
            return '';
        }
        try {
            return Carbon::parse($this->departAt)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i');
        } catch (\Throwable) {
            return '';
        }
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
        $depart = $this->departAtLabel();
        if ($depart !== '') {
            $bits[] = 'Đón '.$depart;
        }
        $route = trim($this->origin);
        $dest = trim($this->destination);
        $routeSummary = ($route !== '' && $dest !== '') ? $route.' → '.$dest : ($route ?: $dest ?: 'Chuyến #'.$this->tripId);
        $prefix = $bits !== [] ? implode(' · ', $bits).' — ' : '';

        return \Illuminate\Support\Str::limit($prefix.$routeSummary, 180, '…');
    }
}
