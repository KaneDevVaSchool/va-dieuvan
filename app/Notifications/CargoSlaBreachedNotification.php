<?php

namespace App\Notifications;

use App\Models\CargoShipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CargoSlaBreachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CargoShipment $shipment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'cargo_sla_breached',
            'shipment_id' => $this->shipment->id,
            'status' => $this->shipment->status,
            'sla_due_at' => optional($this->shipment->sla_due_at)->toIso8601String(),
        ];
    }
}

