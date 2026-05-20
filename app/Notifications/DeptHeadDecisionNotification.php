<?php

namespace App\Notifications;

use App\Models\DispatchRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class DeptHeadDecisionNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
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
        $channels = ['database'];
        $email = trim((string) ($notifiable->email ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dispatchRequest = $this->freshDispatchRequest();
        $ref = $this->referenceCode($dispatchRequest);
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
        $form = [];
        $snap = $dr->wizard_snapshot ?? [];
        if (isset($snap['form']) && is_array($snap['form'])) {
            $form = $snap['form'];
        }

        $departTz = 'Asia/Ho_Chi_Minh';
        $departAtVi = '';
        try {
            if ($dr->depart_at) {
                $d = $dr->depart_at instanceof Carbon ? $dr->depart_at->copy() : Carbon::parse($dr->depart_at);
                $departAtVi = $d->timezone($departTz)->format('d/m/Y').' '.$d->timezone($departTz)->format('H:i');
            }
        } catch (\Throwable) {
            $departAtVi = '';
        }

        $arriveAtVi = '';
        try {
            if ($dr->arrive_by) {
                $a = $dr->arrive_by instanceof Carbon ? $dr->arrive_by->copy() : Carbon::parse($dr->arrive_by);
                $arriveAtVi = $a->timezone($departTz)->format('d/m/Y').' '.$a->timezone($departTz)->format('H:i');
            }
        } catch (\Throwable) {
            $arriveAtVi = '';
        }

        $requesterUnit = trim((string) ($form['requester_unit'] ?? ''));
        $requesterName = trim((string) (($dr->requester?->name)));
        $requesterLine = $requesterUnit !== ''
            ? $requesterName.' — '.$requesterUnit
            : $requesterName;

        $coordinatorLine = trim((string) ($form['coordinator_name'] ?? ''));

        $isCargo = ($dr->trip_type ?? '') === 'cargo';
        $unitTotal = $isCargo
            ? $this->sumRowMoneyField($snap, 'cargoRows', 'cost')
            : $this->sumRowMoneyField($snap, 'businessRows', 'unit_price')
                + $this->sumRowMoneyField($snap, 'passengerRows', 'unit_price');
        $extrasTotal = $isCargo ? 0.0 : $this->sumExtraFeesFromSnapshot($snap);
        $grand = round((float) ($dr->service_price ?? 0), 2);
        $showPriceBreakdown = ! $isCargo && ($unitTotal > 0 || $extrasTotal > 0);

        $deptHeadName = trim((string) ($dr->approver?->name ?? ''));
        if ($deptHeadName === '') {
            $deptHeadName = trim((string) ($dr->approver?->email ?? '')) ?: 'Trưởng bộ phận';
        }

        $requesterDisplayName = trim((string) ($notifiable->name ?? '')) ?: ($notifiable->email ?? 'bạn');

        $rejectionReason = trim((string) ($dr->rejection_reason ?? ''));

        return [
            'decision' => $this->decision,
            'requesterName' => $requesterDisplayName,
            'deptHeadName' => $deptHeadName,
            'rejectionReason' => $rejectionReason,
            'hasRejectionReason' => $this->decision === 'reject' && $rejectionReason !== '',
            'requestRefCode' => $this->referenceCode($dr),
            'tripTypeLabel' => $this->tripTypeLabelVi($dr->trip_type ?? ''),
            'requesterLine' => $requesterLine !== '' ? $requesterLine : '—',
            'purposeLine' => trim((string) ($form['purpose'] ?? '')) ?: '—',
            'leaderLine' => $coordinatorLine !== '' ? $coordinatorLine : '—',
            'routeLine' => trim(($dr->origin ?? '').' → '.($dr->destination ?? '')),
            'timeLineDepart' => $departAtVi !== '' ? $departAtVi : '—',
            'timeLineArrive' => $arriveAtVi !== '' ? $arriveAtVi : '—',
            'showPriceBreakdown' => $showPriceBreakdown,
            'showExtraLine' => $showPriceBreakdown && $extrasTotal > 0,
            'unitPriceTotalFmt' => $this->moneyVnd($unitTotal),
            'extraFeesFmt' => $this->moneyVnd($extrasTotal),
            'grandTotalFmt' => $this->moneyVnd($grand),
            'detailUrl' => rtrim(config('app.url'), '/').'/requests/'.$dr->id,
            'helpdesk' => trim((string) config('dispatch.mail_helpdesk')),
            'privacyScopeFooter' => 'Bạn nhận email vì đây là phiếu đề xuất của bạn.',
        ];
    }

    private function referenceCode(DispatchRequest $dr): string
    {
        $d = $dr->created_at ? ($dr->created_at instanceof Carbon ? $dr->created_at : Carbon::parse($dr->created_at)) : now();
        $d = $d->timezone('Asia/Ho_Chi_Minh');

        return sprintf('REQ-%s%s-%s', $d->format('Y'), $d->format('m'), str_pad((string) $dr->id, 3, '0', STR_PAD_LEFT));
    }

    private function tripTypeLabelVi(string $t): string
    {
        return match ($t) {
            'cargo' => 'Hàng hoá',
            'business' => 'Xe công tác',
            'point_to_point' => 'Điểm — điểm',
            'door_to_door' => 'Đưa đón (D2D)',
            default => $t !== '' ? $t : '—',
        };
    }

    private function sumExtraFeesFromSnapshot(array $snap): float
    {
        return round(
            $this->sumRowMoneyField($snap, 'businessRows', 'extra_fee')
                + $this->sumRowMoneyField($snap, 'passengerRows', 'extra_fee'),
            2,
        );
    }

    private function sumRowMoneyField(array $snap, string $rowsKey, string $field): float
    {
        $rows = $snap[$rowsKey] ?? null;
        if (! is_array($rows)) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $v = $row[$field] ?? null;
            if ($v === null || $v === '') {
                continue;
            }
            if (is_numeric($v)) {
                $total += (float) $v;
            }
        }

        return round($total, 2);
    }

    /** @param  mixed  $amount */
    private function moneyVnd($amount): string
    {
        if ($amount === null) {
            return '—';
        }
        try {
            $n = round((float) $amount);

            return number_format($n, 0, ',', '.').' đ';
        } catch (\Throwable) {
            return '—';
        }
    }
}
