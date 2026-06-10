<?php

namespace App\Services\Costs;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\TripCost;
use App\Services\Auditing\AuditLogger;
use App\Support\FinancialDataLock;
use Illuminate\Support\Facades\DB;

class TripWizardCostProvisioner
{
    public function __construct(
        private readonly WizardSnapshotCostEstimator $estimator,
    ) {}

    /**
     * Ghi nhận dự toán phiếu vào trip_costs khi chuyến hoàn thành.
     * Nếu trưởng đơn vị đã duyệt phiếu (assigned_dept_head) thì xác nhận luôn; ngược lại chờ điều vận/kế toán.
     */
    public function provision(Trip $trip, ?int $actorId): void
    {
        DB::transaction(function () use ($trip, $actorId) {
            /** @var Trip|null $locked */
            $locked = Trip::query()->whereKey($trip->id)->lockForUpdate()->first();
            if ($locked === null) {
                return;
            }

            FinancialDataLock::assertTripNotPaid($locked);

            $locked->loadMissing('dispatchRequest');
            $dr = $locked->dispatchRequest;
            if ($dr === null) {
                return;
            }

            $existing = $locked->costs()
                ->where('type', WizardSnapshotCostEstimator::PROVISION_TYPE)
                ->first();
            if ($existing !== null) {
                $this->confirmWizardEstimateIfDeptApproved($existing, $dr);

                return;
            }

            $dr->makeVisible(['wizard_snapshot']);
            $amount = $this->estimator->estimatedTotalVnd($dr);
            if ($amount <= 0) {
                return;
            }

            $createdBy = $actorId ?? $locked->dispatcher_id;
            $deptAlreadyApprovedEstimate = $this->estimateCoveredByDeptHeadApproval($dr);

            $cost = TripCost::create([
                'trip_id' => $locked->id,
                'created_by' => $createdBy,
                'type' => WizardSnapshotCostEstimator::PROVISION_TYPE,
                'amount' => $amount,
                'currency' => 'VND',
                'description' => 'Chi phí theo dự toán phiếu điều xe',
                'status' => $deptAlreadyApprovedEstimate ? 'confirmed' : 'submitted',
                'confirmed_by' => $deptAlreadyApprovedEstimate ? $dr->approved_by : null,
                'confirmed_at' => $deptAlreadyApprovedEstimate ? now() : null,
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

            if ($deptAlreadyApprovedEstimate) {
                $this->logAutoConfirmAudit($cost, $dr);
            }
        });
    }

    /** Chuyến đã có dòng dự toán chờ duyệt — nâng lên đã duyệt nếu trưởng đơn vị đã duyệt phiếu trước đó. */
    public function confirmWizardEstimateIfDeptApproved(TripCost $cost, DispatchRequest $dr): bool
    {
        if ($cost->type !== WizardSnapshotCostEstimator::PROVISION_TYPE) {
            return false;
        }
        if ($cost->status !== 'submitted') {
            return false;
        }
        if (! $this->estimateCoveredByDeptHeadApproval($dr)) {
            return false;
        }

        $before = $cost->toArray();
        $cost->update([
            'status' => 'confirmed',
            'confirmed_by' => $dr->approved_by,
            'confirmed_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->logAutoConfirmAudit($cost, $dr, $before);

        return true;
    }

    /**
     * @param  array<string, mixed>|null  $before
     */
    private function logAutoConfirmAudit(TripCost $cost, DispatchRequest $dr, ?array $before = null): void
    {
        if ($dr->approved_by === null) {
            return;
        }

        app(AuditLogger::class)->log(
            actorId: (int) $dr->approved_by,
            event: 'cost.confirm',
            auditable: $cost,
            before: $before,
            after: $cost->fresh()->toArray(),
            metadata: [
                'auto_confirmed' => true,
                'source' => 'dispatch_request_dept_approval',
            ],
        );
    }

    /** Dự toán trên phiếu đã được trưởng đơn vị được gán duyệt — không cần duyệt lại trên trip_costs. */
    private function estimateCoveredByDeptHeadApproval(DispatchRequest $dr): bool
    {
        if ($dr->assigned_dept_head_id === null || $dr->approved_by === null) {
            return false;
        }

        return (int) $dr->assigned_dept_head_id === (int) $dr->approved_by;
    }
}
