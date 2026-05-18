<?php

namespace App\Console\Commands;

use App\Models\CargoShipment;
use App\Models\User;
use App\Notifications\CargoSlaBreachedNotification;
use App\Services\Auditing\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CargoSlaCheckCommand extends Command
{
    protected $signature = 'cargo:sla-check {--notify=1 : Send notifications}';

    protected $description = 'Check cargo shipments SLA breach and notify dispatchers/admin.';

    public function handle(): int
    {
        $notify = (bool) ((int) $this->option('notify'));

        $shipments = CargoShipment::query()
            ->visibleOnStaffCargoIndex()
            ->openSlaBreached()
            ->get();

        if ($shipments->isEmpty()) {
            $this->info('No SLA breaches.');
            return self::SUCCESS;
        }

        $recipients = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['dispatcher', 'admin']))
            ->get();

        foreach ($shipments as $s) {
            app(AuditLogger::class)->log(
                actorId: null,
                event: 'cargo.sla_breached',
                auditable: $s,
                before: null,
                after: ['shipment_id' => $s->id, 'status' => $s->status, 'sla_due_at' => $s->sla_due_at],
            );

            if ($notify && $recipients->isNotEmpty()) {
                Notification::send($recipients, new CargoSlaBreachedNotification($s));
            }
        }

        $this->info('SLA breaches: ' . $shipments->count());
        return self::SUCCESS;
    }
}

