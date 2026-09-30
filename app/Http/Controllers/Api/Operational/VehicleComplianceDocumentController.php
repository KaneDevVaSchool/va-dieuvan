<?php

namespace App\Http\Controllers\Api\Operational;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operational\RenewComplianceDocumentRequest;
use App\Http\Requests\Api\Operational\StoreVehicleComplianceDocumentRequest;
use App\Http\Requests\Api\Operational\UpdateVehicleComplianceDocumentRequest;
use App\Models\AuditLog;
use App\Models\Vehicle;
use App\Models\VehicleComplianceDocument;
use App\Services\Resources\ComplianceDocumentService;

class VehicleComplianceDocumentController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly ComplianceDocumentService $documents) {}

    /** Bản đang hiệu lực, mỗi bản kèm `history` các bản đã được gia hạn thay thế. */
    public function index(Vehicle $vehicle)
    {
        $this->authorizeVehicleComplianceRead();

        return $this->ok(['items' => $this->documents->listActiveWithHistory($vehicle)]);
    }

    public function auditLogs(Vehicle $vehicle)
    {
        $this->authorizeVehicleManage();

        $items = AuditLog::query()
            ->with(['actor:id,name,email,employee_code'])
            ->where('event', 'like', 'vehicle_compliance_document.%')
            ->where('metadata->vehicle_id', $vehicle->id)
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        return $this->ok(['items' => $items]);
    }

    public function store(StoreVehicleComplianceDocumentRequest $request, Vehicle $vehicle)
    {
        $document = $this->documents->create(
            $vehicle,
            $request->validated(),
            $request->file('file'),
            $request->user()?->id,
        );

        return $this->created($this->documents->serialize($document));
    }

    public function update(UpdateVehicleComplianceDocumentRequest $request, Vehicle $vehicle, VehicleComplianceDocument $complianceDocument)
    {
        $this->assertDocumentBelongsToVehicle($vehicle, $complianceDocument);

        $data = $request->validated();
        $document = $this->documents->update(
            $complianceDocument,
            $data,
            $request->file('file'),
            (bool) ($data['replace_file'] ?? false),
            $request->user()?->id,
        );

        return $this->ok($this->documents->serialize($document));
    }

    /** Gia hạn: tải scan mới + hạn mới, bản cũ được giữ lại trong lịch sử. */
    public function renew(RenewComplianceDocumentRequest $request, Vehicle $vehicle, VehicleComplianceDocument $complianceDocument)
    {
        $this->assertDocumentBelongsToVehicle($vehicle, $complianceDocument);

        $document = $this->documents->renew(
            $complianceDocument,
            $request->validated(),
            $request->file('file'),
            $request->user()?->id,
        );

        return $this->created($this->documents->serialize($document));
    }

    public function destroy(Vehicle $vehicle, VehicleComplianceDocument $complianceDocument)
    {
        $this->assertDocumentBelongsToVehicle($vehicle, $complianceDocument);

        $this->documents->delete($complianceDocument, request()->user()?->id);

        return $this->ok(['deleted' => true]);
    }

    private function authorizeVehicleComplianceRead(): void
    {
        $user = request()->user();
        if (! $user) {
            abort(403);
        }
        if ($user->can('resource.vehicle.manage') || $user->can('trip.assign')) {
            return;
        }
        abort(403);
    }

    private function authorizeVehicleManage(): void
    {
        if (! request()->user()?->can('resource.vehicle.manage')) {
            abort(403);
        }
    }

    private function assertDocumentBelongsToVehicle(Vehicle $vehicle, VehicleComplianceDocument $document): void
    {
        $this->authorizeVehicleManage();
        if ((int) $document->vehicle_id !== (int) $vehicle->id) {
            abort(404);
        }
    }
}
