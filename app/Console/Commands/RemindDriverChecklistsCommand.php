<?php

namespace App\Console\Commands;

use App\Services\Checklist\ChecklistReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RemindDriverChecklistsCommand extends Command
{
    protected $signature = 'checklist:remind-drivers {--at= : Thời điểm giả lập (Y-m-d H:i, giờ Việt Nam)}';

    protected $description = 'Nhắc tài xế làm checklist Daily/Weekly theo khung giờ cấu hình; báo điều phối khi quá hạn';

    public function handle(ChecklistReminderService $reminders): int
    {
        $at = $this->option('at')
            ? Carbon::parse((string) $this->option('at'), 'Asia/Ho_Chi_Minh')
            : null;

        $result = $reminders->run($at);

        $this->info("Đã nhắc {$result['reminded']} tài xế, gửi {$result['escalated']} bản tổng hợp quá hạn.");

        return self::SUCCESS;
    }
}
