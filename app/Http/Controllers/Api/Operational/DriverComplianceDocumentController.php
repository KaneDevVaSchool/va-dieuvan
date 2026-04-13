<?php

namespace App\Http\Controllers\Api\Operational;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operational\StoreDriverComplianceDocumentRequest;
use App\Http\Requests\Api\Operational\UpdateDriverComplianceDocumentRequest;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Driver;
use App\Models\DriverComplianceDocument;
use App\Services\Auditing\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DriverComplianceDocumentController extends Controller
{
    use ApiResponses;

    public function index(Driver $driver)
    {
        $this->authorizeDriverManage();

        $items = $driver->complianceDocuments()
            ->with(['attachments' => fn ($q) => $q->orderBy('id')])
            ->orderByDesc('expires_at')
            ->orderBy('doc_type')
            ->get()
            ->map(fn (DriverComplianceDocument $d) => $this->serializeDocument($d));

        return $this->ok(['items' => $items]);
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
        $data = $request->validated();

        $document = DB::transaction(function () use ($data, $request, $driver) {
            $doc = $driver->complianceDocuments()->create([
                'doc_type' => $data['doc_type'],
                'title' => $data['title'] ?? null,
                'notes' => $data['notes'] ?? null,
                'issued_at' => $data['issued_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
            ]);

            if ($request->hasFile('file')) {
                $this->storeFileOnDocument($request->file('file'), $doc, $request->user()?->id);
            }

            $fresh = $doc->fresh(['attachments']);

            app(AuditLogger::class)->log(
                actorId: $request->user()?->id,
                event: 'driver_compliance_document.created',
                auditable: $doc,
                before: null,
                after: $fresh->toArray(),
                metadata: [
                    'driver_id' => $driver->id,
                    'doc_type' => $doc->doc_type,
                ],
            );

            return $fresh;
        });

        return $this->created($this->serializeDocument($document));
    }

    public function update(UpdateDriverComplianceDocumentRequest $request, Driver $driver, DriverComplianceDocument $complianceDocument)
    {
        $this->assertDocumentBelongsToDriver($driver, $complianceDocument);

        $data = $request->validated();
        $before = $complianceDocument->fresh(['attachments'])->toArray();

        $document = DB::transaction(function () use ($data, $request, $complianceDocument, $driver) {
            $fill = collect($data)->only(['doc_type', 'title', 'notes', 'issued_at', 'expires_at'])->filter(
                fn ($v, $k) => array_key_exists($k, $data)
            )->all();

            if ($fill !== []) {
                $complianceDocument->fill($fill);
                $complianceDocument->save();
            }

            $replace = (bool) ($data['replace_file'] ?? false);
            if ($request->hasFile('file')) {
                if ($replace) {
                    $this->deleteAllAttachments($complianceDocument);
                }
                $this->storeFileOnDocument($request->file('file'), $complianceDocument->fresh(), $request->user()?->id);
            }

            $fresh = $complianceDocument->fresh(['attachments']);

            app(AuditLogger::class)->log(
                actorId: $request->user()?->id,
                event: 'driver_compliance_document.updated',
                auditable: $complianceDocument,
                before: $before,
                after: $fresh->toArray(),
                metadata: [
                    'driver_id' => $driver->id,
                    'doc_type' => $fresh->doc_type,
                ],
            );

            return $fresh;
        });

        return $this->ok($this->serializeDocument($document));
    }

    public function destroy(Driver $driver, DriverComplianceDocument $complianceDocument)
    {
        $this->authorizeDriverManage();
        $this->assertDocumentBelongsToDriver($driver, $complianceDocument);

        $before = $complianceDocument->fresh(['attachments'])?->toArray();
        $docType = $complianceDocument->doc_type;
        $docId = $complianceDocument->id;

        DB::transaction(function () use ($complianceDocument) {
            $this->deleteAllAttachments($complianceDocument);
            $complianceDocument->delete();
        });

        app(AuditLogger::class)->log(
            actorId: request()->user()?->id,
            event: 'driver_compliance_document.deleted',
            auditable: null,
            before: $before,
            after: null,
            metadata: [
                'driver_id' => $driver->id,
                'doc_type' => $docType,
                'compliance_document_id' => $docId,
            ],
        );

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

    private function deleteAllAttachments(DriverComplianceDocument $document): void
    {
        foreach ($document->attachments as $attachment) {
            if ($attachment->path) {
                Storage::disk($attachment->disk ?: 'public')->delete($attachment->path);
            }
            $attachment->delete();
        }
    }

    private function storeFileOnDocument(UploadedFile $file, DriverComplianceDocument $document, ?int $actorId): Attachment
    {
        $disk = 'public';
        $path = Storage::putFileAs(
            "attachments/driver_compliance_documents/{$document->getKey()}",
            $file,
            $file->hashName(),
            ['disk' => $disk],
        );

        $attachment = $document->attachments()->create([
            'uploaded_by' => $actorId,
            'kind' => 'driver_compliance',
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
        ]);

        app(AuditLogger::class)->log(
            actorId: $actorId,
            event: 'attachment.upload',
            auditable: $attachment,
            before: null,
            after: $attachment->toArray(),
            metadata: [
                'attachable_type' => 'driver_compliance_document',
                'attachable_id' => $document->id,
                'driver_id' => $document->driver_id,
            ],
        );

        return $attachment;
    }

    private function serializeDocument(DriverComplianceDocument $d): array
    {
        $d->loadMissing('attachments');

        $expiry = $this->expiryState($d->expires_at);

        return [
            'id' => $d->id,
            'driver_id' => $d->driver_id,
            'doc_type' => $d->doc_type,
            'title' => $d->title,
            'notes' => $d->notes,
            'issued_at' => $d->issued_at?->format('Y-m-d'),
            'expires_at' => $d->expires_at?->format('Y-m-d'),
            'expiry' => $expiry,
            'attachments' => $d->attachments->map(fn (Attachment $a) => [
                'id' => $a->id,
                'kind' => $a->kind,
                'original_name' => $a->original_name,
                'url' => $a->url,
                'mime_type' => $a->mime_type,
                'size_bytes' => $a->size_bytes,
            ])->values()->all(),
        ];
    }

    private function expiryState(?Carbon $date): array
    {
        if ($date === null) {
            return ['state' => 'none', 'days' => null, 'until' => null];
        }
        $until = $date->format('Y-m-d');
        $end = $date->copy()->startOfDay();
        $now = Carbon::now()->startOfDay();
        $diff = $now->diffInDays($end, false);

        if ($diff < 0) {
            return ['state' => 'exp', 'days' => (int) abs($diff), 'until' => $until];
        }
        if ($diff <= 30) {
            return ['state' => 'soon', 'days' => (int) $diff, 'until' => $until];
        }

        return ['state' => 'ok', 'days' => null, 'until' => $until];
    }
}
