<?php

namespace App\Notifications;

use App\Notifications\Concerns\AddsMailWhenValidEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Gửi cho điều phối/Admin khi tài xế báo bận một ca đưa đón định kì,
 * để phân tài xế khác cho ca đó.
 */
class TpDriverReportedBusyNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use AddsMailWhenValidEmail;
    use Queueable;

    public function __construct(
        public int $programDayId,
        public int $programId,
        public string $programName,
        public string $dateLabel,
        public ?string $shift,
        public string $driverName,
        public string $reason,
    ) {
        $this->onQueue(config('dispatch.notifications_queue_urgent'));
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
        $recipientName = trim((string) ($notifiable->name ?? '')) ?: ($notifiable->email ?? 'bạn');
        $detailUrl = rtrim((string) config('app.url'), '/').'/transport-programs/'.$this->programId;

        $mail = (new MailMessage)
            ->subject('Tài xế báo bận — '.$this->programName.($this->dateLabel !== '' ? ' · '.$this->dateLabel : ''))
            ->greeting('Chào '.$recipientName.',')
            ->line($this->summaryBody())
            ->line('Vui lòng phân tài xế khác cho ca này.');

        if ($this->reason !== '') {
            $mail->line('Lý do: '.$this->reason);
        }

        return $mail
            ->action('Mở chương trình', $detailUrl)
            ->line('Bạn nhận email vì có quyền điều phối đưa đón trên hệ thống Điều vận.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Tài xế báo bận chuyến đưa đón',
            'body' => $this->summaryBody(),
            'program_id' => $this->programId,
            'program_day_id' => $this->programDayId,
            'shift' => $this->shift,
            'reason' => $this->reason !== '' ? $this->reason : null,
            'event' => 'tp.driver.reported_busy',
            'url' => '/transport-programs/'.$this->programId,
            'audience' => 'dispatcher',
        ];
    }

    private function shiftLabel(): string
    {
        return match ($this->shift) {
            'morning' => 'ca sáng',
            'afternoon' => 'ca chiều',
            default => '',
        };
    }

    private function summaryBody(): string
    {
        $bits = array_filter([
            $this->driverName !== '' ? $this->driverName : null,
            $this->programName !== '' ? $this->programName : null,
            $this->dateLabel !== '' ? $this->dateLabel : null,
            $this->shiftLabel() !== '' ? $this->shiftLabel() : null,
        ]);

        return $bits !== [] ? implode(' · ', $bits) : 'Tài xế báo bận';
    }
}
