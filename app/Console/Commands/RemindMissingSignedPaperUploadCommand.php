<?php

namespace App\Console\Commands;

use App\Models\DispatchRequest;
use App\Models\User;
use App\Notifications\SignedPaperUploadReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;

class RemindMissingSignedPaperUploadCommand extends Command
{
    protected $signature = 'dispatch:remind-signed-paper-upload';

    protected $description = 'Nhắc người đề xuất tải bản scan phiếu đã ký sau khi phiếu được duyệt (tối đa 1 lần/ngày)';

    public function handle(): int
    {
        $afterHours = max(1, (int) config('dispatch.signed_paper_reminder_after_hours', 24));
        $cutoff = now()->subHours($afterHours);
        $reminderCooldown = now()->subHours(24);

        $sent = 0;

        DispatchRequest::query()
            ->where('status', 'approved')
            ->whereNotNull('requester_id')
            ->where('updated_at', '<=', $cutoff)
            ->where(function ($q): void {
                $q->whereNull('current_signed_version_id')
                    ->whereDoesntHave('attachments', function ($a): void {
                        $a->where('kind', 'signed_paper');
                    });
            })
            ->with(['requester:id,name,email'])
            ->orderBy('id')
            ->chunkById(50, function ($requests) use (&$sent, $reminderCooldown): void {
                foreach ($requests as $dr) {
                    $requester = $dr->requester;
                    if (! $requester instanceof User) {
                        continue;
                    }

                    $alreadyReminded = DatabaseNotification::query()
                        ->where('notifiable_type', $requester->getMorphClass())
                        ->where('notifiable_id', $requester->getKey())
                        ->where('type', SignedPaperUploadReminderNotification::class)
                        ->where('created_at', '>=', $reminderCooldown)
                        ->where('data->dispatch_request_id', $dr->id)
                        ->exists();

                    if ($alreadyReminded) {
                        continue;
                    }

                    Notification::send($requester, new SignedPaperUploadReminderNotification($dr->id));
                    $sent++;
                }
            });

        $this->info("Đã gửi {$sent} nhắc tải phiếu scan đã ký.");

        return self::SUCCESS;
    }
}
