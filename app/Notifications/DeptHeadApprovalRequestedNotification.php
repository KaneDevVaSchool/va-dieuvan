<?php

namespace App\Notifications;

use App\Models\DispatchRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class DeptHeadApprovalRequestedNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
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

        return (new MailMessage)
            ->subject('Phiếu đề xuất chờ duyệt — '.$this->referenceCode($dispatchRequest))
            ->view('mail.dept-head-approval-requested', $this->viewData($dispatchRequest, $notifiable));
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
            'title' => 'Phiếu điều xe chờ duyệt phòng ban',
            'body' => $summaryLine,
            'dispatch_request_id' => $dispatchRequest->id,
            'event' => 'dispatch_request.dept_approval_requested',
            'url' => '/dept/requests/'.$dispatchRequest->id,
        ];
    }

    private function freshDispatchRequest(): DispatchRequest
    {
        return DispatchRequest::query()
            ->with([
                'requester:id,name,email,employee_code,department_id',
                'requester.department:id,name,code',
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

        $deadlineNotice = '';
        try {
            if ($dr->depart_at) {
                $base = $dr->depart_at instanceof Carbon ? $dr->depart_at->copy() : Carbon::parse($dr->depart_at);
                $deadline = $base->timezone($departTz)->copy()->subDay()->setTime(17, 0, 0);
                $deadlineNotice = sprintf(
                    'Trước 17:00 ngày %s.',
                    $deadline->format('d/m/Y')
                );
            }
        } catch (\Throwable) {
            $deadlineNotice = '';
        }

        $requesterUnit = trim((string) ($form['requester_unit'] ?? ''));
        $requesterName = trim((string) (($dr->requester?->name)));
        $requesterLine = $requesterUnit !== ''
            ? $requesterName.' — '.$requesterUnit
            : $requesterName;

        $coordinatorLine = trim((string) ($form['coordinator_name'] ?? ''));

        $extrasTotal = $this->sumExtraFeesFromSnapshot($snap);
        $grand = round((float) ($dr->service_price ?? 0) + $extrasTotal, 2);

        return [
            'deptHeadName' => trim((string) ($notifiable->name ?? '')) ?: $notifiable->email,
            'requestRefCode' => $this->referenceCode($dr),
            'tripTypeLabel' => $this->tripTypeLabelVi($dr->trip_type ?? ''),
            'requesterLine' => $requesterLine !== '' ? $requesterLine : '—',
            'purposeLine' => trim((string) ($form['purpose'] ?? '')) ?: '—',
            'leaderLine' => $coordinatorLine !== '' ? $coordinatorLine : '—',
            'routeLine' => trim(($dr->origin ?? '').' → '.($dr->destination ?? '')),
            'timeLineDepart' => $departAtVi !== '' ? $departAtVi : '—',
            'timeLineArrive' => $arriveAtVi !== '' ? $arriveAtVi : '—',
            'servicePriceFmt' => $this->moneyVnd($dr->service_price),
            'extraFeesFmt' => $this->moneyVnd($extrasTotal),
            'grandTotalFmt' => $this->moneyVnd($grand),
            'hasDeadlineNotice' => $deadlineNotice !== '',
            'deadlineNotice' => $deadlineNotice,
            'detailUrl' => rtrim(config('app.url'), '/').'/dept/requests/'.$dr->id,
            'helpdesk' => trim((string) config('dispatch.mail_helpdesk')),
            'departmentName' => (string) ($dr->requester?->department?->name ?? $dr->requester?->department?->code ?? 'phòng ban của Người đề xuất'),
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
        $total = 0.0;

        foreach (['businessRows', 'passengerRows', 'cargoRows'] as $key) {
            $rows = $snap[$key] ?? null;
            if (! is_array($rows)) {
                continue;
            }
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $v = $row['extra_fee'] ?? null;
                if ($v === null || $v === '') {
                    continue;
                }
                if (is_numeric($v)) {
                    $total += (float) $v;
                }
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
