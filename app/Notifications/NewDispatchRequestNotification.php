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

class NewDispatchRequestNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use AddsMailWhenValidEmail;
    use Queueable;

    public function __construct(
        public int $dispatchRequestId,
        public string $summaryLine,
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
        return $this->channelsWithOptionalMail($notifiable, $this->isUrgent);
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
            ->subject('[GẤP] Yêu cầu điều xe — '.$ref.$routePart)
            ->view('mail.new-request-urgent', $this->viewData($dispatchRequest, $notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->isUrgent ? '[GẤP] Yêu cầu điều xe mới' : 'Yêu cầu điều xe mới',
            'body' => $this->summaryLine,
            'dispatch_request_id' => $this->dispatchRequestId,
            'event' => 'dispatch_request.created',
            'is_urgent' => $this->isUrgent,
            'url' => '/requests/'.$this->dispatchRequestId,
            'audience' => 'dispatcher',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(DispatchRequest $dr, object $notifiable): array
    {
        return array_merge(
            [
                'recipientName' => trim((string) ($notifiable->name ?? '')) ?: ($notifiable->email ?? 'bạn'),
                'detailUrl' => DispatchRequestMailPresenter::detailUrlForRequest($dr, 'requests'),
                'helpdesk' => DispatchRequestMailPresenter::helpdesk(),
                'privacyScopeFooter' => 'Bạn nhận email vì có quyền xử lý yêu cầu điều xe trên hệ thống Điều vận.',
                'showProgress' => false,
            ],
            DispatchRequestMailPresenter::buildRequestSummary($dr),
        );
    }
}
