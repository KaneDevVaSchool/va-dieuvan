<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;

class RecurringBudgetExceededNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public int $dispatchPackageId,
        public string $label,
        public int $month,
        public float $monthlyCost,
        public float $monthlyBudget,
    ) {
        $this->onQueue(config('dispatch.notifications_queue_default'));
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $costFmt = number_format($this->monthlyCost, 0, ',', '.');
        $budgetFmt = number_format($this->monthlyBudget, 0, ',', '.');

        return [
            'title' => 'Cảnh báo vượt ngưỡng chi phí gói',
            'body' => sprintf(
                'Chi phí gói %s tháng %d đã vượt ngưỡng %s VND. Vui lòng tạo đề xuất bổ sung để phê duyệt thêm ngân sách.',
                $this->label !== '' ? $this->label : '#'.$this->dispatchPackageId,
                $this->month,
                $budgetFmt,
            ),
            'dispatch_package_id' => $this->dispatchPackageId,
            'month' => $this->month,
            'monthly_cost' => $this->monthlyCost,
            'monthly_budget' => $this->monthlyBudget,
            'monthly_cost_formatted' => $costFmt,
            'monthly_budget_formatted' => $budgetFmt,
            'event' => 'dispatch_package.monthly_budget_exceeded',
            'url' => '/requests',
        ];
    }
}
