<?php

namespace App\Notifications;

use App\Models\DispatchRequest;
use App\Models\User as AppUser;
use App\Notifications\Concerns\AddsMailWhenValidEmail;
use App\Services\DispatchRequests\DispatchRequestMailPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeptApprovalReminderNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use AddsMailWhenValidEmail;
    use Queueable;

    public function __construct(public int $dispatchRequestId)
    {
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
        $dispatchRequest = $this->freshDispatchRequest();

        return (new MailMessage)
            ->subject('Nhắc duyệt phiếu — '.DispatchRequestMailPresenter::referenceCode($dispatchRequest))
            ->view('mail.dept-approval-reminder', $this->viewData($dispatchRequest, $notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $dispatchRequest = $this->freshDispatchRequest();
        $summary = trim(($dispatchRequest->origin ?? '').' → '.($dispatchRequest->destination ?? ''));
        $summaryLine = $summary !== '→' ? $summary : 'Yêu cầu #'.$dispatchRequest->id;

        return [
            'title' => 'Nhắc: phiếu chờ duyệt phòng ban',
            'body' => $summaryLine,
            'dispatch_request_id' => $dispatchRequest->id,
            'event' => 'dispatch_request.dept_approval_reminder',
            'url' => '/dept/requests/'.$dispatchRequest->id,
            'audience' => 'department_head',
        ];
    }

    private function freshDispatchRequest(): DispatchRequest
    {
        return DispatchRequest::query()
            ->with([
                'requester:id,name,email',
            ])
            ->findOrFail($this->dispatchRequestId);
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(DispatchRequest $dr, object $notifiable): array
    {
        $receiverDept = '';
        if ($notifiable instanceof AppUser) {
            $notifiable->loadMissing('department:id,name,code');
            $receiverDept = trim((string) ($notifiable->department?->name ?? $notifiable->department?->code ?? ''));
        }
        $privacyScopeFooter = $receiverDept !== ''
            ? sprintf(
                'Bạn nhận email nhắc duyệt vì được gán là Trưởng BP cho phiếu này (%s).',
                $receiverDept,
            )
            : 'Bạn nhận email nhắc duyệt vì được gán là Trưởng BP cho phiếu này.';

        return array_merge(
            [
                'deptHeadName' => trim((string) ($notifiable->name ?? '')) ?: $notifiable->email,
                'detailUrl' => DispatchRequestMailPresenter::detailUrlForRequest($dr, 'dept/requests'),
                'helpdesk' => DispatchRequestMailPresenter::helpdesk(),
                'privacyScopeFooter' => $privacyScopeFooter,
                'showProgress' => true,
                'progressCells' => [
                    ['label' => '✔ Tạo phiếu'],
                    ['label' => '✔ Fill giá'],
                    ['label' => '3 Trưởng BP', 'style' => 'color:#8B1A3A;font-weight:700;'],
                    ['label' => 'Điều xe'],
                    ['label' => 'Lưu trữ'],
                ],
                'closingNote' => 'Đây là email nhắc tự động — phiếu vẫn đang chờ xác nhận của bạn.',
            ],
            DispatchRequestMailPresenter::buildRequestSummary($dr),
            DispatchRequestMailPresenter::buildPriceSummary($dr),
            DispatchRequestMailPresenter::buildDeadlineNotice($dr),
        );
    }
}
