<?php

namespace App\Notifications;

use App\Notifications\Concerns\AddsMailWhenValidEmail;
use App\Services\DispatchRequests\DispatchRequestMailPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class P2pPolicyTripDepartReminderNotification extends Notification implements ShouldQueue
{
    use AddsMailWhenValidEmail;
    use Queueable;

    public function __construct(
        public int $tripId,
        public string $origin,
        public string $destination,
        public string $departAt,
        public bool $driverAppLink = false,
        public string $legLabel = '',
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
        $departLabel = $this->departAtLabel();
        $subject = 'Nhắc chuyến trung chuyển P2P'.($departLabel !== '' ? ' · '.$departLabel : '');

        return (new MailMessage)
            ->subject($subject)
            ->view('mail.trip-assigned', $this->viewData($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $route = trim($this->origin).' → '.trim($this->destination);

        return [
            'title' => 'Nhắc chuyến trung chuyển P2P',
            'body' => $this->summaryBody(),
            'trip_id' => $this->tripId,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'depart_at' => $this->departAt,
            'event' => 'p2p_policy.trip.depart_reminder',
            'url' => $this->driverAppLink ? '/driver/trips/'.$this->tripId : '/trips/'.$this->tripId,
            'leg_label' => $this->legLabel,
            'route_line' => $route,
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

        $base = rtrim(config('app.url'), '/');
        $detailPath = $this->driverAppLink ? '/driver/trips/'.$this->tripId : '/trips/'.$this->tripId;

        return [
            'driverName' => trim((string) ($notifiable->name ?? '')) ?: ($notifiable->email ?? 'bạn'),
            'isUrgent' => false,
            'tripTypeLabel' => 'Trung chuyển nội bộ (P2P)',
            'routeLine' => $route,
            'timeLineDepart' => $this->departAtLabel() ?: '—',
            'detailUrl' => $base.$detailPath,
            'helpdesk' => DispatchRequestMailPresenter::helpdesk(),
            'privacyScopeFooter' => 'Bạn nhận email vì liên quan tới chuyến trung chuyển P2P sắp khởi hành.',
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
        $bits = ['P2P'];
        if ($this->legLabel !== '') {
            $bits[] = $this->legLabel;
        }
        $depart = $this->departAtLabel();
        if ($depart !== '') {
            $bits[] = 'Khởi hành '.$depart;
        }
        $route = trim($this->origin);
        $dest = trim($this->destination);
        $routeSummary = ($route !== '' && $dest !== '') ? $route.' → '.$dest : ($route ?: $dest ?: 'Chuyến #'.$this->tripId);

        return \Illuminate\Support\Str::limit(implode(' · ', $bits).' — '.$routeSummary, 200, '…');
    }
}
