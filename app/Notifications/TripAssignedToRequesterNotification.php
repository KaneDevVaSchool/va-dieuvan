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
use Illuminate\Support\Str;

/**
 * Gửi cho NGƯỜI ĐỀ XUẤT phiếu điều xe sau khi điều phối/Admin gán xong xe + tài xế,
 * để họ nắm được phương tiện và người lái được phân công.
 */
class TripAssignedToRequesterNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use AddsMailWhenValidEmail;
    use Queueable;

    public function __construct(
        public int $tripId,
        public int $dispatchRequestId,
        public string $tripType,
        public string $origin,
        public string $destination,
        public string $departAt,
        public ?string $driverLabel = null,
        public ?string $vehicleLabel = null,
        public ?string $servicePrice = null,
        public bool $isUrgent = false,
    ) {
        $this->onQueue(
            $this->isUrgent
                ? config('dispatch.notifications_queue_urgent')
                : config('dispatch.notifications_queue_default')
        );
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
        $prefix = $this->isUrgent ? '[GẤP] ' : '';
        $subject = $prefix.'Đã phân công xe — '.$label.($departLabel !== '' ? ' · '.$departLabel : '');

        $requesterName = trim((string) ($notifiable->name ?? '')) ?: ($notifiable->email ?? 'bạn');
        $detailUrl = rtrim((string) config('app.url'), '/').'/requests/'.$this->dispatchRequestId;

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Chào '.$requesterName.',')
            ->line('Phiếu điều xe của bạn đã được phân công phương tiện và tài xế.')
            ->line('Lộ trình: '.$this->routeLine())
            ->line('Giờ xuất phát: '.($departLabel !== '' ? $departLabel : '—'));

        if ($this->vehicleLabel) {
            $mail->line('Xe: '.$this->vehicleLabel);
        }
        if ($this->driverLabel) {
            $mail->line('Tài xế: '.$this->driverLabel);
        }
        if ($this->hasPrice()) {
            $mail->line('Giá để lại: '.DispatchRequestMailPresenter::moneyVnd($this->servicePrice));
        }

        return $mail
            ->action('Xem chi tiết phiếu', $detailUrl)
            ->line('Bạn nhận email này vì đây là phiếu đề xuất của bạn.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Đã phân công xe cho phiếu của bạn',
            'body' => $this->summaryBody(),
            'trip_id' => $this->tripId,
            'dispatch_request_id' => $this->dispatchRequestId,
            'trip_type' => $this->tripType,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'depart_at' => $this->departAt,
            'driver_label' => $this->driverLabel,
            'vehicle_label' => $this->vehicleLabel,
            'service_price' => $this->hasPrice() ? $this->servicePrice : null,
            'price_label' => $this->hasPrice() ? DispatchRequestMailPresenter::moneyVnd($this->servicePrice) : null,
            'event' => 'trip.assigned_to_requester',
            'is_urgent' => $this->isUrgent,
            'url' => '/requests/'.$this->dispatchRequestId,
            'audience' => 'requester',
        ];
    }

    private function routeLine(): string
    {
        $origin = trim($this->origin);
        $destination = trim($this->destination);
        if ($origin !== '' && $destination !== '') {
            return $origin.' → '.$destination;
        }

        return $origin ?: $destination ?: 'Chuyến #'.$this->tripId;
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
        $bits = [];
        $tt = match ($this->tripType) {
            'door_to_door' => 'Cửa–cửa',
            'point_to_point' => 'Điểm–điểm',
            'business' => 'Công tác',
            'cargo' => 'Hàng hóa',
            default => '',
        };
        if ($tt !== '') {
            $bits[] = $tt;
        }
        $resources = array_filter([$this->vehicleLabel, $this->driverLabel]);
        if ($resources !== []) {
            $bits[] = implode(' · ', $resources);
        }
        if ($this->hasPrice()) {
            $bits[] = DispatchRequestMailPresenter::moneyVnd($this->servicePrice);
        }
        $prefix = $bits !== [] ? implode(' · ', $bits).' — ' : '';

        return Str::limit($prefix.$this->routeLine(), 180, '…');
    }

    private function hasPrice(): bool
    {
        return $this->servicePrice !== null && (float) $this->servicePrice > 0;
    }
}
