<?php

namespace App\Console\Commands;

use App\Models\Trip;
use App\Services\Costs\TripWizardCostProvisioner;
use App\Services\Costs\WizardSnapshotCostEstimator;
use Illuminate\Console\Command;

class BackfillTripWizardCostsCommand extends Command
{
    protected $signature = 'costs:backfill-wizard-estimates {--dry-run : Chỉ in số lượng, không ghi DB}';

    protected $description = 'Đồng bộ chi phí dự toán phiếu cho chuyến đã hoàn thành (tạo mới hoặc xác nhận nếu trưởng đơn vị đã duyệt phiếu)';

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

        $confirmBase = Trip::query()
            ->where('status', 'completed')
            ->whereHas('dispatchRequest')
            ->whereHas(
                'costs',
                fn ($q) => $q->where('type', WizardSnapshotCostEstimator::PROVISION_TYPE)->where('status', 'submitted'),
            );

        $confirmed = 0;
        $confirmBase->with(['dispatchRequest', 'costs'])->orderBy('id')->chunkById(100, function ($trips) use ($provisioner, &$confirmed) {
            foreach ($trips as $trip) {
                $dr = $trip->dispatchRequest;
                if ($dr === null) {
                    continue;
                }
                $cost = $trip->costs->first(
                    fn ($c) => $c->type === WizardSnapshotCostEstimator::PROVISION_TYPE && $c->status === 'submitted',
                );
                if ($cost === null) {
                    continue;
                }
                if ($provisioner->confirmWizardEstimateIfDeptApproved($cost, $dr)) {
                    $confirmed++;
                }
            }
        });

        if ($confirmed > 0) {
            $this->info("Đã tự xác nhận {$confirmed} dòng dự toán (trưởng đơn vị đã duyệt phiếu).");
        }

        return self::SUCCESS;
    }
}
