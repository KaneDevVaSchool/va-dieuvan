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

class SignedPaperUploadReminderNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
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
            ->subject('Nhắc cập nhật phiếu giấy — '.DispatchRequestMailPresenter::referenceCode($dispatchRequest))
            ->view('mail.signed-paper-upload-reminder', $this->viewData($dispatchRequest, $notifiable));
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
            'title' => 'Nhắc: tải bản scan phiếu đã ký',
            'body' => $summaryLine,
            'dispatch_request_id' => $dispatchRequest->id,
            'event' => 'dispatch_request.signed_paper_upload_reminder',
            'url' => '/portal/requests/'.$dispatchRequest->id.'?tab=docs',
            'audience' => 'dispatcher',
        ];
    }

    private function freshDispatchRequest(): DispatchRequest
    {
        return DispatchRequest::query()
            ->with([
                'requester:id,name,email',
                'approver:id,name,email',
            ])
            ->findOrFail($this->dispatchRequestId);
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(DispatchRequest $dr, object $notifiable): array
    {
        $requesterDisplayName = trim((string) ($notifiable->name ?? '')) ?: ($notifiable->email ?? 'bạn');

        return array_merge(
            [
                'requesterName' => $requesterDisplayName,
                'detailUrl' => DispatchRequestMailPresenter::detailUrlForRequest($dr, 'portal/requests').'?tab=docs',
                'helpdesk' => DispatchRequestMailPresenter::helpdesk(),
                'privacyScopeFooter' => 'Bạn nhận email vì đây là phiếu đề xuất của bạn và hệ thống chưa ghi nhận bản scan phiếu đã ký.',
                'showProgress' => true,
                'progressCells' => [
                    ['label' => '✔ Tạo phiếu'],
                    ['label' => '✔ Fill giá'],
                    ['label' => '✔ Trưởng BP'],
                    ['label' => 'Điều xe'],
                    ['label' => 'Lưu trữ', 'style' => 'color:#8B1A3A;font-weight:700;'],
                ],
                'closingNote' => 'Đây là email nhắc tự động (tối đa một lần mỗi ngày) — vui lòng tải bản scan trên tab Tài liệu để Điều vận lưu trữ và chạy OCR.',
            ],
            DispatchRequestMailPresenter::buildRequestSummary($dr),
            DispatchRequestMailPresenter::buildPriceSummary($dr),
        );
    }
}
