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

class DeptHeadDecisionNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use AddsMailWhenValidEmail;
    use Queueable;

    public function __construct(
        public int $dispatchRequestId,
        public string $decision,
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
        $dispatchRequest = $this->freshDispatchRequest();
        $ref = DispatchRequestMailPresenter::referenceCode($dispatchRequest);
        $subject = $this->decision === 'reject'
            ? 'Phiếu đề xuất bị từ chối — '.$ref
            : 'Phiếu đề xuất đã được duyệt — '.$ref;

        return (new MailMessage)
            ->subject($subject)
            ->view('mail.dept-head-decision', $this->viewData($dispatchRequest, $notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $dispatchRequest = $this->freshDispatchRequest();
        $summary = trim(($dispatchRequest->origin ?? '').' → '.($dispatchRequest->destination ?? ''));
        $summaryLine = $summary !== '→' ? $summary : 'Yêu cầu #'.$dispatchRequest->id;

        $isReject = $this->decision === 'reject';

        return [
            'title' => $isReject
                ? 'Phiếu điều xe bị từ chối'
                : 'Phiếu điều xe đã được duyệt',
            'body' => $summaryLine,
            'dispatch_request_id' => $dispatchRequest->id,
            'event' => $isReject
                ? 'dispatch_request.dept_rejected'
                : 'dispatch_request.dept_approved',
            'url' => '/requests/'.$dispatchRequest->id,
        ];
    }

    private function freshDispatchRequest(): DispatchRequest
    {
        return DispatchRequest::query()
            ->with([
                'requester:id,name,email,employee_code,department_id',
                'requester.department:id,name,code',
                'approver:id,name,email',
            ])
            ->findOrFail($this->dispatchRequestId);
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(DispatchRequest $dr, object $notifiable): array
    {
        $deptHeadName = trim((string) ($dr->approver?->name ?? ''));
        if ($deptHeadName === '') {
            $deptHeadName = trim((string) ($dr->approver?->email ?? '')) ?: 'Trưởng bộ phận';
        }

        $requesterDisplayName = trim((string) ($notifiable->name ?? '')) ?: ($notifiable->email ?? 'bạn');
        $rejectionReason = trim((string) ($dr->rejection_reason ?? ''));
        $isReject = $this->decision === 'reject';

        $deptCell = $isReject
            ? ['label' => '✗ Trưởng BP', 'style' => 'color:#b91c1c;font-weight:700;']
            : ['label' => '✔ Trưởng BP', 'style' => 'color:#15803d;font-weight:700;'];

        return array_merge(
            [
                'decision' => $this->decision,
                'requesterName' => $requesterDisplayName,
                'deptHeadName' => $deptHeadName,
                'rejectionReason' => $rejectionReason,
                'hasRejectionReason' => $isReject && $rejectionReason !== '',
                'detailUrl' => DispatchRequestMailPresenter::detailUrlForRequest($dr, 'requests'),
                'helpdesk' => DispatchRequestMailPresenter::helpdesk(),
                'privacyScopeFooter' => 'Bạn nhận email vì đây là phiếu đề xuất của bạn.',
                'showProgress' => true,
                'progressCells' => [
                    ['label' => '✔ Tạo phiếu'],
                    ['label' => '✔ Fill giá'],
                    $deptCell,
                    ['label' => $isReject ? '—' : 'Điều xe'],
                    ['label' => 'Lưu trữ'],
                ],
                'closingNote' => $isReject
                    ? 'Bạn có thể chỉnh sửa và gửi lại quy trình theo hướng dẫn nội bộ của trường.'
                    : 'Sau khi duyệt, bạn có thể xuất PDF phiếu trên hệ thống. Điều vận tiến hành điều phối xe.',
            ],
            DispatchRequestMailPresenter::buildRequestSummary($dr),
            DispatchRequestMailPresenter::buildPriceSummary($dr),
        );
    }
}
