<?php

namespace App\Notifications;

use App\Models\DispatchRequest;
use App\Notifications\Concerns\AddsMailWhenValidEmail;
use App\Services\DispatchRequests\DispatchRequestMailPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecurringStudentCountSubmittedNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use AddsMailWhenValidEmail;
    use Queueable;

    public function __construct(
        public int $dispatchRequestId,
        public int $studentCountActual,
    ) {
        $this->onQueue(config('dispatch.notifications_queue_default'));
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channelsWithOptionalMail($notifiable, false);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dispatchRequest = DispatchRequest::query()
            ->with(['requester:id,name,email'])
            ->findOrFail($this->dispatchRequestId);

        $ref = DispatchRequestMailPresenter::referenceCode($dispatchRequest);
        $route = trim(($dispatchRequest->origin ?? '').' → '.($dispatchRequest->destination ?? ''));
        $routePart = $route !== '→' ? ' — '.$route : '';

        return (new MailMessage)
            ->subject('Chốt số HS ngoại khóa — '.$ref.$routePart)
            ->line('Người đề xuất đã gửi số học sinh thực tế cho chuyến định kỳ.')
            ->line('Số HS thực tế: '.$this->studentCountActual)
            ->action('Mở phiếu', DispatchRequestMailPresenter::detailUrlForRequest($dispatchRequest, 'requests'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $dispatchRequest = DispatchRequest::query()->find($this->dispatchRequestId);
        $route = $dispatchRequest
            ? trim(($dispatchRequest->origin ?? '').' → '.($dispatchRequest->destination ?? ''))
            : '';
        $routePart = $route !== '' && $route !== '→'
            ? $route
            : 'Yêu cầu #'.$this->dispatchRequestId;
        $summaryLine = $routePart.' · '.$this->studentCountActual.' HS';

        return [
            'title' => 'Chốt số HS ngoại khóa',
            'body' => $summaryLine,
            'dispatch_request_id' => $this->dispatchRequestId,
            'event' => 'dispatch_request.student_count_submitted',
            'student_count_actual' => $this->studentCountActual,
            'url' => '/requests/'.$this->dispatchRequestId,
            'audience' => 'dispatcher',
        ];
    }
}
