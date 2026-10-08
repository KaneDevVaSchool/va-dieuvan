<?php

namespace App\Notifications;

use App\Notifications\Concerns\AddsMailWhenValidEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Báo điều phối khi tài xế nộp checklist có mục Critical = NG (không đủ điều kiện khởi hành).
 */
class ChecklistCriticalFailedNotification extends Notification implements ShouldQueue
{
    use AddsMailWhenValidEmail;
    use Queueable;

    /**
     * @param  list<string>  $ngTitles
     */
    public function __construct(
        public int $submissionId,
        public string $code,
        public string $driverName,
        public string $licensePlate,
        public array $ngTitles,
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
        return (new MailMessage)
            ->subject('Checklist không đạt — '.$this->licensePlate)
            ->line($this->summary())
            ->line('Mục Critical không đạt: '.implode(', ', $this->ngTitles))
            ->action('Xem phiếu', rtrim((string) config('app.url'), '/').'/mng/checklists/'.$this->submissionId);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Checklist không đạt: '.$this->licensePlate,
            'body' => $this->summary().' · '.implode(', ', array_slice($this->ngTitles, 0, 4)),
            'submission_id' => $this->submissionId,
            'event' => 'checklist.critical_ng',
            'url' => '/mng/checklists/'.$this->submissionId,
            'audience' => 'dispatcher',
        ];
    }

    private function summary(): string
    {
        return sprintf('%s · %s · %d mục Critical NG', $this->code, $this->driverName, count($this->ngTitles));
    }
}
