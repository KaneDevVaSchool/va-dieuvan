<?php

namespace App\Http\Controllers\Api\Attachments;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Attachments\DestroyAttachmentRequest;
use App\Http\Requests\Api\Attachments\RunAttachmentOcrRequest;
use App\Http\Requests\Api\Attachments\UploadAttachmentRequest;
use App\Models\Attachment;
use App\Models\CargoShipment;
use App\Models\DispatchRequest;
use App\Models\DriverComplianceDocument;
use App\Models\Trip;
use App\Models\TripCost;
use App\Models\VehicleComplianceDocument;
use App\Services\Auditing\AuditLogger;
use App\Jobs\ProcessAttachmentOcrJob;
use App\Http\Resources\SignedDocumentVersionResource;
use App\Services\Ocr\PaperOcrStubService;
use App\Services\SignedDocuments\SignedDocumentUploadService;
use App\Support\FinancialDataLock;
use App\Support\TripCostAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    use ApiResponses;

    public function upload(UploadAttachmentRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();

        [$attachable, $folder] = match ($data['attachable_type']) {
            'trip' => [Trip::findOrFail($data['attachable_id']), 'trips'],
            'cargo_shipment' => [CargoShipment::findOrFail($data['attachable_id']), 'cargo'],
            'trip_cost' => [TripCost::findOrFail($data['attachable_id']), 'costs'],
            'dispatch_request' => [DispatchRequest::findOrFail($data['attachable_id']), 'dispatch_requests'],
            'driver_compliance_document' => [DriverComplianceDocument::findOrFail($data['attachable_id']), 'driver_compliance_documents'],
            'vehicle_compliance_document' => [VehicleComplianceDocument::findOrFail($data['attachable_id']), 'vehicle_compliance_documents'],
        };

        if ($attachable instanceof DriverComplianceDocument) {
            if (! $request->user()?->can('resource.driver.manage')) {
                abort(403);
            }
        }

        if ($attachable instanceof VehicleComplianceDocument) {
            if (! $request->user()?->can('resource.vehicle.manage')) {
                abort(403);
            }
        }

        if ($attachable instanceof TripCost) {
            FinancialDataLock::assertTripCostAllowsNewAttachment($attachable);
        }

        /** @var UploadedFile $file */
        $file = $request->file('file');

        $kind = $data['kind'] ?? null;
        if (
            $attachable instanceof DispatchRequest
            && $kind === 'signed_paper'
        ) {
            $result = app(SignedDocumentUploadService::class)->upload(
                $attachable,
                $file,
                $user,
                'staff',
            );

            return $this->created([
                'version' => (new SignedDocumentVersionResource($result['version']))->resolve(),
                'attachment' => array_merge($result['attachment']->toArray(), [
                    'url' => Storage::url($result['attachment']->path),
                ]),
            ]);
        }

        $disk = 'public';

        $path = Storage::putFileAs(
            "attachments/{$folder}/{$attachable->getKey()}",
            $file,
            $file->hashName(),
            ['disk' => $disk],
        );

        $attachment = Attachment::create([
            'uploaded_by' => $user?->id,
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'kind' => $data['kind'] ?? null,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'file_binary' => Attachment::bytesFromUpload($file),
        ]);

        app(AuditLogger::class)->log(
            actorId: $user?->id,
            event: 'attachment.upload',
            auditable: $attachment,
            before: null,
            after: $attachment->toArray(),
            metadata: [
                'attachable_type' => $data['attachable_type'],
                'attachable_id' => $data['attachable_id'],
            ],
        );

        return $this->created([
            ...$attachment->toArray(),
            'url' => Storage::url($path),
        ]);
    }

    public function destroy(DestroyAttachmentRequest $request, Attachment $attachment)
    {
        $this->authorizeAttachmentDownload($request, $attachment);

        $user = $request->user();
        $before = $attachment->toArray();
        $attachmentId = $attachment->getKey();

        $disk = $attachment->disk ?: 'public';
        if ($attachment->path) {
            Storage::disk($disk)->delete($attachment->path);
        }
        $attachment->delete();

        app(AuditLogger::class)->log(
            actorId: $user?->id,
            event: 'attachment.delete',
            auditable: null,
            before: $before,
            after: null,
            metadata: ['attachment_id' => $attachmentId],
        );

        return $this->ok(['deleted' => true]);
    }

    /**
     * Tải file gốc qua API (Sanctum) — tránh phụ thuộc URL /storage công khai hoặc SPA/nginx trả HTML.
     */
    public function download(Request $request, Attachment $attachment)
    {
        $this->authorizeAttachmentDownload($request, $attachment);

        $name = $attachment->original_name ?: 'download';

        $binary = $attachment->file_binary;
        if ($binary !== null && $binary !== '') {
            $expectedBytes = $attachment->size_bytes;
            if ($expectedBytes !== null && strlen($binary) !== (int) $expectedBytes) {
                $binary = null;
            }
        }
        if ($binary !== null && $binary !== '') {
            return response($binary, 200, [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="'.$this->asciiFilenameForContentDisposition($name).'"',
            ]);
        }

        $disk = $attachment->disk ?: 'public';
        $rawPath = $attachment->path;
        if ($rawPath === null || trim((string) $rawPath) === '') {
            abort(404, 'Bản ghi đính kèm không có đường dẫn tệp (path).');
        }

        $resolved = $this->resolveForAttachmentDownload($disk, (string) $rawPath);
        if (! $resolved) {
            abort(404, 'Không tìm thấy tệp trên máy chủ (storage). Kiểm tra `php artisan storage:link`, quyền thư mục, hoặc tải lại chứng từ.');
        }

        $name = $attachment->original_name ?: basename($resolved['path']);

        if ($resolved['kind'] === 'absolute') {
            return response()->download($resolved['path'], $name);
        }

        return Storage::disk($resolved['disk'])->download($resolved['path'], $name);
    }

    /**
     * @return array{kind: 'absolute', path: string}|array{kind: 'relative', disk: string, path: string}|null
     */
    private function resolveForAttachmentDownload(string $disk, string $rawPath): ?array
    {
        $trim = trim($rawPath);
        if ($trim === '') {
            return null;
        }

        if ($this->isAbsoluteFilesystemPath($trim) && is_file($trim)) {
            return ['kind' => 'absolute', 'path' => $trim];
        }

        foreach ($this->candidateDisks($disk) as $tryDisk) {
            foreach ($this->candidateStoragePaths($rawPath) as $rel) {
                $useRel = $tryDisk === 'public'
                    ? $this->normalizeRelativeToPublicDisk($rel)
                    : ltrim(str_replace('\\', '/', $rel), '/');
                $abs = Storage::disk($tryDisk)->path($useRel);
                if (is_file($abs) && is_readable($abs)) {
                    return ['kind' => 'relative', 'disk' => $tryDisk, 'path' => $useRel];
                }
            }
        }

        // File có thể chỉ khớp qua public/storage (symlink / hosting).
        foreach ($this->candidateStoragePaths($rawPath) as $rel) {
            $normalized = $this->normalizeRelativeToPublicDisk($rel);
            $underPublic = public_path('storage/'.$normalized);
            if (is_file($underPublic) && is_readable($underPublic)) {
                return ['kind' => 'absolute', 'path' => $underPublic];
            }
        }

        return null;
    }

    /**
     * Trên disk `public`, path đúng là `attachments/...` dưới `storage/app/public/`.
     * Nếu DB lưu nhầm `storage/attachments/...` thì Flysystem thành `.../public/storage/attachments/...` (sai).
     */
    private function normalizeRelativeToPublicDisk(string $rel): string
    {
        $rel = ltrim(str_replace('\\', '/', $rel), '/');
        if (str_starts_with($rel, 'storage/') && ! str_starts_with($rel, 'storage/app/')) {
            return substr($rel, strlen('storage/'));
        }

        return $rel;
    }

    /**
     * @return list<string>
     */
    private function candidateDisks(string $disk): array
    {
        $disk = $disk ?: 'public';
        if ($disk === 'public') {
            return ['public'];
        }

        return [$disk, 'public'];
    }

    private function isAbsoluteFilesystemPath(string $path): bool
    {
        if (str_starts_with($path, '/')) {
            return true;
        }

        return (bool) preg_match('/^[a-zA-Z]:[\\\\\\/]/', $path);
    }

    /**
     * @return list<string>
     */
    private function candidateStoragePaths(string $rawPath): array
    {
        $p = trim($rawPath);
        if ($p === '') {
            return [];
        }

        if (preg_match('#^https?://#i', $p)) {
            $pathPart = parse_url($p, PHP_URL_PATH);
            if (is_string($pathPart) && $pathPart !== '') {
                $p = $pathPart;
            }
        }

        $p = ltrim($p, '/\\');
        $p = str_replace('\\', '/', $p);

        $out = [$p];

        if (preg_match('#/(?:storage|public/storage)/(.+)$#', $p, $m)) {
            $out[] = $m[1];
        }

        if (str_starts_with($p, 'storage/')) {
            $after = substr($p, strlen('storage/'));
            $out[] = $after;
            if (str_starts_with($after, 'app/public/')) {
                $out[] = substr($after, strlen('app/public/'));
            }
        }

        if (str_starts_with($p, 'app/public/')) {
            $out[] = substr($p, strlen('app/public/'));
        }

        if (str_starts_with($p, 'public/')) {
            $out[] = substr($p, 7);
        }

        if (str_starts_with($p, 'public/storage/')) {
            $out[] = substr($p, strlen('public/storage/'));
        }

        if (preg_match('#storage/app/public/(.+)$#', $p, $m)) {
            $out[] = $m[1];
        }

        return array_values(array_unique(array_filter($out)));
    }

    /**
     * morphTo đôi khi không resolve (attachable_type cũ / khác class). Thử map thủ công.
     */
    private function resolveAttachableParent(Attachment $attachment): ?Model
    {
        $attachment->loadMissing('attachable');
        if ($attachment->attachable instanceof Model) {
            return $attachment->attachable;
        }

        $type = $attachment->attachable_type;
        $id = $attachment->attachable_id;
        if (! $type || ! $id) {
            return null;
        }

        $type = (string) $type;

        $aliases = [
            'vehicle_compliance_document' => VehicleComplianceDocument::class,
            'driver_compliance_document' => DriverComplianceDocument::class,
            'trip' => Trip::class,
            'dispatch_request' => DispatchRequest::class,
            'cargo_shipment' => CargoShipment::class,
            'trip_cost' => TripCost::class,
        ];

        if (isset($aliases[$type])) {
            return $aliases[$type]::query()->find($id);
        }

        return null;
    }

    private function authorizeAttachmentDownload(Request $request, Attachment $attachment): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $parent = $this->resolveAttachableParent($attachment);
        if (! $parent) {
            abort(404, 'Không tìm thấy chứng từ gắn với tệp (orphan hoặc attachable_type/attachable_id không hợp lệ).');
        }

        if ($parent instanceof VehicleComplianceDocument) {
            if ($user->can('resource.vehicle.manage') || $user->can('trip.assign')) {
                return;
            }
            abort(403);
        }

        if ($parent instanceof DriverComplianceDocument) {
            if ($user->can('resource.driver.manage')) {
                return;
            }
            abort(403);
        }

        if ($parent instanceof Trip) {
            if ($user->can('trip.view_all') || $user->can('trip.assign')) {
                return;
            }
            abort(403);
        }

        if ($parent instanceof DispatchRequest) {
            if ($user->can('request.paper.manage') || $user->can('request.approve') || $user->can('request.create')) {
                return;
            }
            if ($user->can('view', $parent)) {
                return;
            }
            abort(403);
        }

        if ($parent instanceof CargoShipment) {
            if ($user->can('cargo.manage')) {
                return;
            }
            abort(403);
        }

        if ($parent instanceof TripCost) {
            if ($user->can('trip.cost.view') || $user->can('trip.cost.reconcile')) {
                return;
            }
            if (TripCostAccess::userCanView($user, $parent)) {
                return;
            }
            abort(403);
        }

        abort(403);
    }

    public function runOcr(RunAttachmentOcrRequest $request, Attachment $attachment, PaperOcrStubService $ocr)
    {
        if ($attachment->kind !== 'paper_scan') {
            abort(422, 'Chỉ hỗ trợ OCR cho file loại paper_scan.');
        }

        if (config('dispatch.ocr_use_queue', false)) {
            ProcessAttachmentOcrJob::dispatch($attachment->id);

            app(AuditLogger::class)->log(
                actorId: $request->user()?->id,
                event: 'attachment.ocr_queued',
                auditable: $attachment,
                before: null,
                after: $attachment->fresh()->toArray(),
            );

            $payload = $attachment->fresh()->toArray();
            $payload['ocr_status'] = 'queued';

            return $this->ok($payload);
        }

        $ocr->process($attachment->fresh());

        app(AuditLogger::class)->log(
            actorId: $request->user()?->id,
            event: 'attachment.ocr_stub',
            auditable: $attachment,
            before: null,
            after: $attachment->fresh()->toArray(),
        );

        return $this->ok(array_merge($attachment->fresh()->toArray(), ['ocr_status' => 'completed']));
    }

    private function asciiFilenameForContentDisposition(string $name): string
    {
        $trim = trim($name) !== '' ? trim($name) : 'download';
        $ascii = Str::ascii($trim);
        if ($ascii === '') {
            return 'download';
        }

        return str_replace(['"', "\r", "\n"], '_', $ascii);
    }
}
