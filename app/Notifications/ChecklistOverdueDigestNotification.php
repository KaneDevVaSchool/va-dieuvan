<?php

namespace App\Notifications;

use App\Notifications\Concerns\AddsMailWhenValidEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Tổng hợp gửi điều phối: danh sách tài xế chưa làm checklist sau hạn nhắc.
 */
class ChecklistOverdueDigestNotification extends Notification implements ShouldQueue
{
    use AddsMailWhenValidEmail;
    use Queueable;

    /**
     * @param  list<string>  $driverNames
     */
    public function __construct(
        public string $templateName,
        public string $periodLabel,
        public array $driverNames,
    ) {
        $this->onQueue(config('dispatch.notifications_queue_default'));
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channelsWithOptionalMail($notifiable, includeMail: false);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $names = array_slice($this->driverNames, 0, 8);
        $more = count($this->driverNames) - count($names);

        return [
            'title' => count($this->driverNames).' tài xế chưa làm checklist',
            'body' => $this->templateName.' · '.$this->periodLabel.': '.implode(', ', $names).($more > 0 ? " và {$more} người khác" : ''),
            'event' => 'checklist.overdue_digest',
            'url' => '/mng/checklists',
            'audience' => 'dispatcher',
        ];
    }
}
