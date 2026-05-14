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

    public function toArray(object $notifiable): array
    {
        $slaDue = optional($this->shipment->sla_due_at)->toIso8601String();

        return [
            'title' => 'SLA hàng hóa vi phạm',
            'body' => sprintf(
                'Lô hàng #%s (%s) đã quá SLA%s.',
                $this->shipment->id,
                $this->shipment->status,
                $slaDue ? ' lúc '.date('d/m H:i', strtotime($slaDue)) : '',
            ),
            'event' => 'cargo.sla_breached',
            'shipment_id' => $this->shipment->id,
            'status' => $this->shipment->status,
            'sla_due_at' => $slaDue,
            'url' => '/cargo/'.$this->shipment->id,
        ];
    }
}

