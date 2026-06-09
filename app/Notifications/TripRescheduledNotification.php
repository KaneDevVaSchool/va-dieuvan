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

class TripRescheduledNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use AddsMailWhenValidEmail;
    use Queueable;

    public function __construct(
        public int $tripId,
        public string $tripType,
        public string $origin,
        public string $destination,
        public string $oldDepartAt,
        public string $newDepartAt,
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
        $newLabel = $this->formatDate($this->newDepartAt);
        $subject = 'Giờ xuất phát thay đổi — '.$label.($newLabel !== '' ? ' · '.$newLabel : '');

        return (new MailMessage)
            ->subject($subject)
            ->view('mail.trip-rescheduled', $this->viewData($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Giờ xuất phát chuyến đã thay đổi',
            'body' => $this->summaryBody(),
            'trip_id' => $this->tripId,
            'trip_type' => $this->tripType,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'old_depart_at' => $this->oldDepartAt,
            'depart_at' => $this->newDepartAt,
            'event' => 'trip.rescheduled',
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
            'oldDepartLabel' => $this->formatDate($this->oldDepartAt) ?: '—',
            'newDepartLabel' => $this->formatDate($this->newDepartAt) ?: '—',
            'detailUrl' => rtrim(config('app.url'), '/').'/driver/trips/'.$this->tripId,
            'helpdesk' => DispatchRequestMailPresenter::helpdesk(),
            'privacyScopeFooter' => 'Bạn nhận email vì được phân công lái chuyến này trên Điều vận.',
            'showProgress' => false,
        ];
    }

    private function formatDate(string $iso): string
    {
        if ($iso === '') {
            return '';
        }
        try {
            return Carbon::parse($iso)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i');
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
        $newLabel = $this->formatDate($this->newDepartAt);
        $bits = $tt !== '' ? [$tt] : [];
        if ($newLabel !== '') {
            $bits[] = 'Giờ mới: '.$newLabel;
        }
        $route = trim($this->origin);
        $dest = trim($this->destination);
        $routeSummary = ($route !== '' && $dest !== '') ? $route.' → '.$dest : ($route ?: $dest ?: 'Chuyến #'.$this->tripId);
        $prefix = $bits !== [] ? implode(' · ', $bits).' — ' : '';

        return \Illuminate\Support\Str::limit($prefix.$routeSummary, 180, '…');
    }
}
