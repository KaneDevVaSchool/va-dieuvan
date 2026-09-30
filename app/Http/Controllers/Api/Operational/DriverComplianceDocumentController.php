<?php

namespace App\Http\Controllers\Api\Operational;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operational\RenewComplianceDocumentRequest;
use App\Http\Requests\Api\Operational\StoreDriverComplianceDocumentRequest;
use App\Http\Requests\Api\Operational\UpdateDriverComplianceDocumentRequest;
use App\Models\AuditLog;
use App\Models\Driver;
use App\Models\DriverComplianceDocument;
use App\Services\Resources\ComplianceDocumentService;

class DriverComplianceDocumentController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly ComplianceDocumentService $documents) {}

    /** Bản đang hiệu lực, mỗi bản kèm `history` các bản đã được gia hạn thay thế. */
    public function index(Driver $driver)
    {
        $this->authorizeDriverManage();

        return $this->ok(['items' => $this->documents->listActiveWithHistory($driver)]);
    }

    public function auditLogs(Driver $driver)
    {
        $this->authorizeDriverManage();

        $items = AuditLog::query()
            ->with(['actor:id,name,email,employee_code'])
            ->where('event', 'like', 'driver_compliance_document.%')
            ->where('metadata->driver_id', $driver->id)
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        return $this->ok(['items' => $items]);
    }

    public function store(StoreDriverComplianceDocumentRequest $request, Driver $driver)
    {
        $document = $this->documents->create(
            $driver,
            $request->validated(),
            $request->file('file'),
            $request->user()?->id,
        );

        return $this->created($this->documents->serialize($document));
    }

    public function update(UpdateDriverComplianceDocumentRequest $request, Driver $driver, DriverComplianceDocument $complianceDocument)
    {
        $this->assertDocumentBelongsToDriver($driver, $complianceDocument);

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
    public function renew(RenewComplianceDocumentRequest $request, Driver $driver, DriverComplianceDocument $complianceDocument)
    {
        $this->assertDocumentBelongsToDriver($driver, $complianceDocument);

        $document = $this->documents->renew(
            $complianceDocument,
            $request->validated(),
            $request->file('file'),
            $request->user()?->id,
        );

        return $this->created($this->documents->serialize($document));
    }

    public function destroy(Driver $driver, DriverComplianceDocument $complianceDocument)
    {
        $this->assertDocumentBelongsToDriver($driver, $complianceDocument);

        $this->documents->delete($complianceDocument, request()->user()?->id);

        return $this->ok(['deleted' => true]);
    }

    private function authorizeDriverManage(): void
    {
        if (! request()->user()?->can('resource.driver.manage')) {
            abort(403);
        }
    }

    private function assertDocumentBelongsToDriver(Driver $driver, DriverComplianceDocument $document): void
    {
        $this->authorizeDriverManage();
        if ((int) $document->driver_id !== (int) $driver->id) {
            abort(404);
        }
    }
}
