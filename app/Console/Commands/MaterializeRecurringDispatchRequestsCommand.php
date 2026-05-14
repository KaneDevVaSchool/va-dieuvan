<?php

namespace App\Console\Commands;

use App\Models\DispatchRequestTemplate;
use App\Services\RecurringDispatch\DispatchRecurringMaintenanceService;
use Illuminate\Console\Command;

class MaterializeRecurringDispatchRequestsCommand extends Command
{
    protected $signature = 'dispatch:materialize-recurring-requests {--template=* : Restrict to template id(s)}';

    protected $description = 'Materialize recurring dispatch_requests instances from templates (scheduler).';

    public function handle(DispatchRecurringMaintenanceService $service): int
    {
        /** @var array<int, mixed> */
        $templateIdsRaw = $this->option('template');
        $templateIds = array_values(array_unique(array_filter(array_map('intval', (array) $templateIdsRaw))));

        $q = DispatchRequestTemplate::query()->where('is_active', true);
        if ($templateIds !== []) {
            $q->whereKey($templateIds);
        }

        $totalCreated = 0;
        foreach ($q->cursor() as $template) {
            $created = $service->materializeForTemplate($template);
            $totalCreated += $created;

            if ($created > 0) {
                $this->info("Template #{$template->getKey()}: +{$created} instance(s)");
            }
        }

        $this->info('Total recurring instances created: '.$totalCreated);

        return self::SUCCESS;
    }
}
