<?php

namespace App\Services\Resources;

use App\Models\Attachment;
use App\Models\Driver;
use App\Models\DriverComplianceDocument;
use App\Models\Vehicle;
use App\Models\VehicleComplianceDocument;
use App\Services\Auditing\AuditLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Chứng từ xe / tài xế (đăng kiểm, bảo hiểm, GPLX...): tạo, sửa, gia hạn, xóa.
 *
 * Gia hạn = tạo bản mới (status active, replaces_id → bản cũ) và chuyển bản cũ sang superseded,
 * giữ nguyên file cũ để tra cứu. Hạn trên bảng chủ (vehicles.*_expires_at, drivers.license_expires_at)
 * được đồng bộ theo hạn xa nhất trong các bản đang hiệu lực cùng loại.
 *
 * File lưu song song: disk + cột attachments.file_binary (download ưu tiên đọc từ DB).
 */
class ComplianceDocumentService
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUPERSEDED = 'superseded';

    public const EXPIRY_SOON_DAYS = 30;

    /**
     * Disk private (storage/app) — không truy cập trực tiếp qua URL; xem/tải qua GET /attachments/{id}/download.
     * Trước đây code truyền ['disk' => 'public'] vào Storage::putFileAs nhưng tùy chọn đó bị bỏ qua,
     * nên file thực tế vẫn nằm ở disk mặc định; nay ghi rõ để cột attachments.disk khớp thực tế.
     */
    public const FILE_DISK = 'local';

    /** Loại file cho phép với scan chứng từ (chặn .html/.svg/.php…). */
    public const FILE_MIMES_RULE = 'mimes:pdf,jpg,jpeg,png,webp,heic,heif,doc,docx,xls,xlsx';

    /** Cột attachments dùng cho danh sách — bỏ file_binary (LONGBLOB) để không kéo cả file vào bộ nhớ. */
    public const ATTACHMENT_LIST_COLUMNS = [
        'id', 'attachable_type', 'attachable_id', 'kind', 'disk', 'path',
        'original_name', 'size_bytes', 'mime_type', 'created_at',
    ];

    private const EDITABLE_FIELDS = ['doc_type', 'title', 'document_no', 'notes', 'issued_at', 'expires_at'];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Bản đang hiệu lực của một xe/tài xế, mỗi bản kèm `history` (các bản nó đã thay thế, mới → cũ).
     *
     * @return list<array<string, mixed>>
     */
    public function listActiveWithHistory(Vehicle|Driver $owner): array
    {
        $all = $owner->complianceDocuments()
            ->with(['attachments' => fn ($q) => $q->select(self::ATTACHMENT_LIST_COLUMNS)->orderBy('id')])
            ->orderByDesc('expires_at')
            ->orderBy('doc_type')
            ->orderByDesc('id')
            ->get();

        $byId = $all->keyBy('id');

        return $all
            ->filter(fn (Model $d) => $d->status !== self::STATUS_SUPERSEDED)
            ->map(function (Model $d) use ($byId) {
                $history = [];
                $seen = [$d->id => true];
                $prevId = $d->replaces_id;
                while ($prevId && isset($byId[$prevId]) && ! isset($seen[$prevId])) {
                    $seen[$prevId] = true;
                    $prev = $byId[$prevId];
                    $history[] = $this->serialize($prev);
                    $prevId = $prev->replaces_id;
                }

                return [...$this->serialize($d), 'history' => $history];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Vehicle|Driver $owner, array $data, ?UploadedFile $file, ?int $actorId): Model
    {
        $meta = $this->meta($owner);
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($owner, $data, $file, $actorId, $meta, &$storedFiles) {
                $doc = $owner->complianceDocuments()->create([
                    ...collect($data)->only(self::EDITABLE_FIELDS)->all(),
                    'status' => self::STATUS_ACTIVE,
                ]);

                if ($file) {
                    $this->storeFile($file, $doc, $actorId, $storedFiles);
                }

                $ownerSync = $this->syncOwnerExpiry($doc);
                $fresh = $doc->fresh(['attachments']);

                $this->audit->log(
                    actorId: $actorId,
                    event: $meta['event'].'.created',
                    auditable: $doc,
                    before: null,
                    after: $fresh->toArray(),
                    metadata: $this->auditMetadata($fresh, ['owner_expiry_sync' => $ownerSync]),
                );

                return $fresh;
            });
        } catch (Throwable $e) {
            $this->discardStoredFiles($storedFiles);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $doc, array $data, ?UploadedFile $file, bool $replaceFile, ?int $actorId): Model
    {
        $meta = $this->meta($doc);
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($doc, $data, $file, $replaceFile, $actorId, $meta, &$storedFiles) {
                $before = $doc->fresh(['attachments'])->toArray();

                $fill = collect($data)->only(self::EDITABLE_FIELDS)->all();
                if ($fill !== []) {
                    $doc->fill($fill)->save();
                }

                if ($file) {
                    if ($replaceFile) {
                        $this->deleteAttachments($doc);
                    }
                    $this->storeFile($file, $doc, $actorId, $storedFiles);
                }

                $ownerSync = $doc->status === self::STATUS_SUPERSEDED ? null : $this->syncOwnerExpiry($doc);
                $fresh = $doc->fresh(['attachments']);

                $this->audit->log(
                    actorId: $actorId,
                    event: $meta['event'].'.updated',
                    auditable: $doc,
                    before: $before,
                    after: $fresh->toArray(),
                    metadata: $this->auditMetadata($fresh, ['owner_expiry_sync' => $ownerSync]),
                );

                return $fresh;
            });
        } catch (Throwable $e) {
            $this->discardStoredFiles($storedFiles);
            throw $e;
        }
    }

    /**
     * Gia hạn: tạo bản mới thay thế `$doc`, giữ bản cũ (superseded) cùng file của nó.
     *
     * @param  array<string, mixed>  $data  expires_at (bắt buộc), issued_at, title, document_no, notes
     */
    public function renew(Model $doc, array $data, UploadedFile $file, ?int $actorId): Model
    {
        $meta = $this->meta($doc);
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($doc, $data, $file, $actorId, $meta, &$storedFiles) {
                /** @var Model $old */
                $old = $doc->newQuery()->lockForUpdate()->findOrFail($doc->getKey());

                if ($old->status === self::STATUS_SUPERSEDED) {
                    throw ValidationException::withMessages([
                        'document' => 'Chứng từ này đã được gia hạn. Hãy gia hạn từ bản đang hiệu lực.',
                    ]);
                }

                $newExpires = Carbon::parse($data['expires_at'])->startOfDay();
                if ($old->expires_at && $newExpires->lte($old->expires_at->copy()->startOfDay())) {
                    throw ValidationException::withMessages([
                        'expires_at' => 'Ngày hết hạn mới phải sau ngày hết hạn hiện tại ('.$old->expires_at->format('d/m/Y').').',
                    ]);
                }

                $before = $old->toArray();

                $new = $old->newQuery()->create([
                    $meta['owner_key'] => $old->{$meta['owner_key']},
                    'doc_type' => $old->doc_type,
                    'title' => ($data['title'] ?? null) ?: $old->title,
                    'document_no' => $data['document_no'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'issued_at' => $data['issued_at'] ?? null,
                    'expires_at' => $newExpires->toDateString(),
                    'status' => self::STATUS_ACTIVE,
                    'replaces_id' => $old->getKey(),
                ]);

                $old->update(['status' => self::STATUS_SUPERSEDED]);

                $this->storeFile($file, $new, $actorId, $storedFiles);

                $ownerSync = $this->syncOwnerExpiry($new);
                $fresh = $new->fresh(['attachments']);

                $this->audit->log(
                    actorId: $actorId,
                    event: $meta['event'].'.renewed',
                    auditable: $new,
                    before: $before,
                    after: $fresh->toArray(),
                    metadata: $this->auditMetadata($fresh, [
                        'replaces_id' => $old->getKey(),
                        'previous_expires_at' => $old->expires_at?->toDateString(),
                        'owner_expiry_sync' => $ownerSync,
                    ]),
                );

                return $fresh;
            });
        } catch (Throwable $e) {
            $this->discardStoredFiles($storedFiles);
            throw $e;
        }
    }

    /**
     * Xóa một chứng từ. Nếu xóa bản gia hạn đang hiệu lực, bản trước đó được khôi phục hiệu lực
     * (trường hợp gia hạn nhầm). Nếu xóa một bản trong lịch sử, chuỗi thay thế được nối lại.
     */
    public function delete(Model $doc, ?int $actorId): void
    {
        $meta = $this->meta($doc);

        DB::transaction(function () use ($doc, $actorId, $meta) {
            $before = $doc->fresh(['attachments'])?->toArray();
            $wasActive = $doc->status !== self::STATUS_SUPERSEDED;
            $predecessor = $doc->replaces_id ? $doc->newQuery()->lockForUpdate()->find($doc->replaces_id) : null;

            $doc->newQuery()->where('replaces_id', $doc->getKey())->update(['replaces_id' => $doc->replaces_id]);

            $restored = null;
            if ($wasActive && $predecessor && $predecessor->status === self::STATUS_SUPERSEDED) {
                $predecessor->update(['status' => self::STATUS_ACTIVE]);
                $restored = $predecessor;
            }

            $this->deleteAttachments($doc);
            $doc->delete();

            $ownerSync = $wasActive ? $this->syncOwnerExpiry($doc) : null;

            $this->audit->log(
                actorId: $actorId,
                event: $meta['event'].'.deleted',
                auditable: null,
                before: $before,
                after: null,
                metadata: $this->auditMetadata($doc, [
                    'compliance_document_id' => $doc->getKey(),
                    'restored_document_id' => $restored?->getKey(),
                    'owner_expiry_sync' => $ownerSync,
                ]),
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(Model $d): array
    {
        $meta = $this->meta($d);
        $d->loadMissing(['attachments' => fn ($q) => $q->select(self::ATTACHMENT_LIST_COLUMNS)->orderBy('id')]);

        $superseded = $d->status === self::STATUS_SUPERSEDED;

        return [
            'id' => $d->id,
            $meta['owner_key'] => $d->{$meta['owner_key']},
            'doc_type' => $d->doc_type,
            'title' => $d->title,
            'document_no' => $d->document_no,
            'notes' => $d->notes,
            'issued_at' => $d->issued_at?->format('Y-m-d'),
            'expires_at' => $d->expires_at?->format('Y-m-d'),
            'status' => $d->status ?? self::STATUS_ACTIVE,
            'replaces_id' => $d->replaces_id,
            'expiry' => $superseded
                ? ['state' => 'superseded', 'days' => null, 'until' => $d->expires_at?->format('Y-m-d')]
                : $this->expiryState($d->expires_at),
            'created_at' => $d->created_at?->toIso8601String(),
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

    /**
     * @return array{state: string, days: int|null, until: string|null}
     */
    public function expiryState(?Carbon $date): array
    {
        if ($date === null) {
            return ['state' => 'none', 'days' => null, 'until' => null];
        }
        $until = $date->format('Y-m-d');
        $diff = Carbon::now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);

        if ($diff < 0) {
            return ['state' => 'exp', 'days' => (int) abs($diff), 'until' => $until];
        }
        if ($diff <= self::EXPIRY_SOON_DAYS) {
            return ['state' => 'soon', 'days' => (int) $diff, 'until' => $until];
        }

        return ['state' => 'ok', 'days' => null, 'until' => $until];
    }

    /**
     * @param  list<array{disk: string, path: string}>  $storedFiles  file đã ghi (để dọn nếu transaction lỗi)
     */
    private function storeFile(UploadedFile $file, Model $doc, ?int $actorId, array &$storedFiles): Attachment
    {
        $meta = $this->meta($doc);
        $disk = self::FILE_DISK;
        $path = Storage::disk($disk)->putFileAs(
            "attachments/{$meta['folder']}/{$doc->getKey()}",
            $file,
            $file->hashName(),
        );
        if ($path === false) {
            throw new \RuntimeException('Không ghi được file chứng từ lên máy chủ.');
        }
        $storedFiles[] = ['disk' => $disk, 'path' => $path];

        $attachment = $doc->attachments()->create([
            'uploaded_by' => $actorId,
            'kind' => $meta['attachment_kind'],
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'sha256' => hash_file('sha256', (string) $file->getRealPath()) ?: null,
            'file_binary' => Attachment::bytesFromUpload($file),
        ]);

        $this->audit->log(
            actorId: $actorId,
            event: 'attachment.upload',
            auditable: $attachment,
            before: null,
            after: $attachment->toArray(),
            metadata: [
                'attachable_type' => $meta['event'],
                'attachable_id' => $doc->getKey(),
                $meta['owner_key'] => $doc->{$meta['owner_key']},
            ],
        );

        return $attachment;
    }

    /** Xóa bản ghi attachment ngay; file trên disk chỉ xóa sau khi transaction commit. */
    private function deleteAttachments(Model $doc): void
    {
        foreach ($doc->attachments()->get(['id', 'disk', 'path']) as $attachment) {
            // Bản ghi cũ có thể ghi disk 'public' dù file thực tế nằm ở disk mặc định → xóa ở cả hai.
            $disks = array_unique([$attachment->disk ?: 'public', self::FILE_DISK]);
            $path = $attachment->path;
            $attachment->delete();
            if ($path) {
                DB::afterCommit(function () use ($disks, $path) {
                    foreach ($disks as $disk) {
                        Storage::disk($disk)->delete($path);
                    }
                });
            }
        }
    }

    /**
     * Transaction lỗi sau khi đã ghi file lên disk → dọn file mồ côi.
     *
     * @param  list<array{disk: string, path: string}>  $storedFiles
     */
    private function discardStoredFiles(array $storedFiles): void
    {
        foreach ($storedFiles as $f) {
            Storage::disk($f['disk'])->delete($f['path']);
        }
    }

    /**
     * Đặt hạn trên bảng chủ = hạn xa nhất trong các bản đang hiệu lực cùng loại.
     * Không có bản nào có hạn → giữ nguyên giá trị hiện tại.
     *
     * @return array{column: string, from: string|null, to: string}|null
     */
    private function syncOwnerExpiry(Model $doc): ?array
    {
        $meta = $this->meta($doc);
        $column = $meta['expiry_columns'][$doc->doc_type] ?? null;
        if ($column === null) {
            return null;
        }

        /** @var Model|null $owner */
        $owner = $meta['owner_class']::query()->find($doc->{$meta['owner_key']});
        if (! $owner) {
            return null;
        }

        $max = $doc->newQuery()
            ->where($meta['owner_key'], $owner->getKey())
            ->where('doc_type', $doc->doc_type)
            ->where('status', self::STATUS_ACTIVE)
            ->max('expires_at');
        if ($max === null) {
            return null;
        }

        $to = Carbon::parse($max)->toDateString();
        $current = $owner->{$column};
        $from = $current ? Carbon::parse($current)->toDateString() : null;
        if ($from === $to) {
            return null;
        }

        $owner->forceFill([$column => $to])->save();

        return ['column' => $column, 'from' => $from, 'to' => $to];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function auditMetadata(Model $doc, array $extra = []): array
    {
        $meta = $this->meta($doc);

        return array_filter([
            $meta['owner_key'] => $doc->{$meta['owner_key']},
            'doc_type' => $doc->doc_type,
            ...$extra,
        ], fn ($v) => $v !== null);
    }

    /**
     * @return array{event: string, owner_key: string, owner_class: class-string<Model>, attachment_kind: string, folder: string, expiry_columns: array<string, string>}
     */
    private function meta(Model $model): array
    {
        return match (true) {
            $model instanceof Vehicle, $model instanceof VehicleComplianceDocument => [
                'event' => 'vehicle_compliance_document',
                'owner_key' => 'vehicle_id',
                'owner_class' => Vehicle::class,
                'attachment_kind' => 'vehicle_compliance',
                'folder' => 'vehicle_compliance_documents',
                'expiry_columns' => [
                    'inspection_certificate' => 'inspection_expires_at',
                    'insurance_certificate' => 'insurance_expires_at',
                ],
            ],
            $model instanceof Driver, $model instanceof DriverComplianceDocument => [
                'event' => 'driver_compliance_document',
                'owner_key' => 'driver_id',
                'owner_class' => Driver::class,
                'attachment_kind' => 'driver_compliance',
                'folder' => 'driver_compliance_documents',
                'expiry_columns' => [
                    'license' => 'license_expires_at',
                ],
            ],
            default => throw new \InvalidArgumentException('Không hỗ trợ loại chứng từ '.$model::class),
        };
    }
}
