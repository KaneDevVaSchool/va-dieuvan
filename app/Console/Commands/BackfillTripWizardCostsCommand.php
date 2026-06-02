<?php

namespace App\Console\Commands;

use App\Models\Trip;
use App\Services\Costs\TripWizardCostProvisioner;
use Illuminate\Console\Command;

class BackfillTripWizardCostsCommand extends Command
{
    protected $signature = 'costs:backfill-wizard-estimates {--dry-run : Chỉ in số lượng, không ghi DB}';

    protected $description = 'Tạo dòng chi phí dự toán (submitted) cho chuyến đã hoàn thành chưa có wizard_estimate';

    public function handle(TripWizardCostProvisioner $provisioner): int
    {
        $dry = (bool) $this->option('dry-run');

        $base = Trip::query()
            ->where('status', 'completed')
            ->whereDoesntHave('costs', fn ($q) => $q->where('type', 'wizard_estimate'));

        $count = (clone $base)->count();

        if ($dry) {
            $this->info("Sẽ đồng bộ tối đa: {$count} chuyến");

            return self::SUCCESS;
        }

        $n = 0;
        $base->orderBy('id')->chunkById(100, function ($trips) use ($provisioner, &$n) {
            foreach ($trips as $trip) {
                $before = $trip->costs()->count();
                $provisioner->provision($trip, null);
                if ($trip->costs()->count() > $before) {
                    $n++;
                }
            }
        });

        $this->info("Đã tạo chi phí dự toán cho {$n} chuyến.");

        return self::SUCCESS;
    }
}
