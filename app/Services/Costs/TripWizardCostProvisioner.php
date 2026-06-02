<?php

namespace App\Services\Costs;

use App\Models\Trip;
use App\Models\TripCost;
use App\Services\Auditing\AuditLogger;
use App\Support\FinancialDataLock;

class TripWizardCostProvisioner
{
    public function __construct(
        private readonly WizardSnapshotCostEstimator $estimator,
    ) {}

    /**
     * Ghi nhận dự toán phiếu vào trip_costs khi chuyến hoàn thành (chờ điều vận/kế toán duyệt).
     */
    public function provision(Trip $trip, ?int $actorId): void
    {
        FinancialDataLock::assertTripNotPaid($trip);

        if ($trip->costs()->where('type', WizardSnapshotCostEstimator::PROVISION_TYPE)->exists()) {
            return;
        }

        $trip->loadMissing('dispatchRequest');
        $dr = $trip->dispatchRequest;
        if ($dr === null) {
            return;
        }

        $dr->makeVisible(['wizard_snapshot']);
        $amount = $this->estimator->estimatedTotalVnd($dr);
        if ($amount <= 0) {
            return;
        }

        $createdBy = $actorId ?? $trip->dispatcher_id;

        $cost = TripCost::create([
            'trip_id' => $trip->id,
            'created_by' => $createdBy,
            'type' => WizardSnapshotCostEstimator::PROVISION_TYPE,
            'amount' => $amount,
            'currency' => 'VND',
            'description' => 'Chi phí theo dự toán phiếu điều xe',
            'status' => 'submitted',
        ]);

        if ($actorId !== null) {
            app(AuditLogger::class)->log(
                actorId: $actorId,
                event: 'cost.submit',
                auditable: $cost,
                before: null,
                after: $cost->toArray(),
                metadata: ['auto_provisioned' => true, 'source' => 'wizard_snapshot'],
            );
        }
    }
}
