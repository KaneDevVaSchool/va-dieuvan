<?php

namespace App\Console\Commands;

use App\Models\DispatchRequest;
use App\Models\User;
use App\Notifications\DeptApprovalReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;

class RemindPendingDeptApprovalsCommand extends Command
{
    protected $signature = 'dispatch:remind-dept-approvals';

    protected $description = 'Nhắc Trưởng BP duyệt các phiếu price_filled đã chờ quá ngưỡng cấu hình';

    public function handle(): int
    {
        $afterHours = max(1, (int) config('dispatch.dept_approval_reminder_after_hours', 24));
        $cutoff = now()->subHours($afterHours);
        $reminderCooldown = now()->subHours(24);

        $sent = 0;

        DispatchRequest::query()
            ->where('status', 'price_filled')
            ->whereNotNull('assigned_dept_head_id')
            ->whereNotNull('price_filled_at')
            ->where('price_filled_at', '<=', $cutoff)
            ->with(['assignedDeptHead:id,name,email'])
            ->orderBy('id')
            ->chunkById(50, function ($requests) use (&$sent, $reminderCooldown): void {
                foreach ($requests as $dr) {
                    $head = $dr->assignedDeptHead;
                    if (! $head instanceof User) {
                        continue;
                    }

                    $alreadyReminded = DatabaseNotification::query()
                        ->where('notifiable_type', $head->getMorphClass())
                        ->where('notifiable_id', $head->getKey())
                        ->where('type', DeptApprovalReminderNotification::class)
                        ->where('created_at', '>=', $reminderCooldown)
                        ->where('data->dispatch_request_id', $dr->id)
                        ->exists();

                    if ($alreadyReminded) {
                        continue;
                    }

                    Notification::send($head, new DeptApprovalReminderNotification($dr->id));
                    $sent++;
                }
            });

        $this->info("Đã gửi {$sent} nhắc duyệt Trưởng BP.");

        return self::SUCCESS;
    }
}
