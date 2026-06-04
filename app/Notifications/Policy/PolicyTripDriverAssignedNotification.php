<?php

namespace App\Notifications\Policy;

use App\Notifications\Concerns\AddsMailWhenValidEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Báo tài xế vừa được phân công chuyến policy (§6.3, §11). In-app + email.
 */
class PolicyTripDriverAssignedNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use AddsMailWhenValidEmail;
    use Queueable;

    public function __construct(
        public int $tripId,
        public string $routeName,
        public string $timeSlotLabel,
        public string $tripDateLabel,
        public ?string $plannedDeparture = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $this->channelsWithOptionalMail($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $depart = $this->plannedDeparture ? ' lúc '.$this->plannedDeparture : '';

        return (new MailMessage)
            ->subject('Chuyến đưa đón mới — '.$this->routeName.' ('.$this->timeSlotLabel.')')
            ->greeting('Xin chào '.(trim((string) ($notifiable->name ?? '')) ?: 'bạn'))
            ->line('Bạn vừa được phân công chuyến đưa đón học sinh chính sách:')
            ->line('• Tuyến: '.$this->routeName)
            ->line('• Ca: '.$this->timeSlotLabel.' · Ngày '.$this->tripDateLabel.$depart)
            ->action('Mở chuyến', rtrim((string) config('app.url'), '/').'/driver/policy-trips/'.$this->tripId)
            ->line('Vui lòng kiểm tra danh sách học sinh trước giờ khởi hành.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Chuyến đưa đón mới được phân công',
            'body' => $this->routeName.' · '.$this->timeSlotLabel.' · '.$this->tripDateLabel,
            'event' => 'policy_trip.assigned',
            'policy_trip_id' => $this->tripId,
            'url' => '/driver/policy-trips/'.$this->tripId,
            'audience' => 'driver',
        ];
    }
}
