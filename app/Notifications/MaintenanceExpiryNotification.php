<?php

namespace App\Notifications;

use App\Models\VehicleMaintenanceItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MaintenanceExpiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly VehicleMaintenanceItem $item,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $days = $this->item->days_remaining ?? 0;
        $name = $this->item->name;

        return [
            'title' => "Sắp hết hạn: {$name}",
            'body' => "Còn {$days} ngày · Hãy kiểm tra và gia hạn kịp thời.",
            'event' => 'maintenance.expiry_reminder',
            'maintenance_item_id' => $this->item->id,
            'days_remaining' => $days,
            'url' => '/driver/maintenance/'.$this->item->id,
        ];
    }
}
