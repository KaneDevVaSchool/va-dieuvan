<?php

namespace App\Services\Checklist;

use App\Models\Attachment;
use App\Models\ChecklistSubmission;
use App\Models\ChecklistSubmissionItem;
use App\Models\ChecklistTemplate;
use App\Models\Driver;
use App\Models\TpProgramDay;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\ChecklistCriticalFailedNotification;
use App\Services\Attachments\AttachmentStoreService;
use App\Services\Auditing\AuditLogger;
use App\Services\Notifications\DispatchStaffNotificationRecipients;
use App\Support\ChecklistAccess;
use App\Support\ChecklistPeriod;
use App\Support\DocumentCodeGenerator;
use App\Support\TripVisibility;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Vòng đời phiếu checklist: tạo (snapshot mục + điền sẵn) → lưu nháp → nộp (tính kết luận) → điều phối xem.
 * Mọi mốc thời gian nghiệp vụ (started_at, checked_at, submitted_at) lấy theo giờ server.
 */
class ChecklistSubmissionService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ChecklistPrefillService $prefill,
        private readonly AttachmentStoreService $files,
    ) {}

    /**
     * @param  array{template_id: int, vehicle_id: int, trip_id?: int|null, tp_program_day_id?: int|null, location_text?: string|null, lat?: float|null, lng?: float|null}  $data
     */
    public function start(User $actor, array $data): ChecklistSubmission
    {
        $driver = ChecklistAccess::driverFor($actor);
        if ($driver === null) {
            abort(422, 'Tài khoản chưa được gắn hồ sơ tài xế — liên hệ điều phối.');
        }

        $template = ChecklistTemplate::query()->active()->findOrFail($data['template_id']);
        $items = $template->activeItems()->get();
        if ($items->isEmpty()) {
            abort(422, 'Checklist này chưa có mục kiểm tra nào.');
        }

        $vehicle = Vehicle::query()->findOrFail($data['vehicle_id']);
        $trip = $this->resolveTrip($actor, $data['trip_id'] ?? null);
        $tpDay = $this->resolveTpDay($driver, $data['tp_program_day_id'] ?? null);

        $hints = $this->prefill->hints($items, $driver, $vehicle);
        $now = Carbon::now();

        $submission = DB::transaction(function () use ($actor, $data, $driver, $template, $items, $vehicle, $trip, $tpDay, $hints, $now) {
            $submission = ChecklistSubmission::query()->create([
                'code' => DocumentCodeGenerator::next(DocumentCodeGenerator::PREFIX_CHECKLIST, $now),
                'template_id' => $template->id,
                'template_version' => $template->version,
                'driver_id' => $driver->id,
                'user_id' => $actor->id,
                'vehicle_id' => $vehicle->id,
                'trip_id' => $trip?->id,
                'tp_program_day_id' => $tpDay?->id,
                'period_type' => $template->frequency,
                'period_key' => ChecklistPeriod::key($template->frequency, $now),
                'started_at' => $now,
                'location_text' => $data['location_text'] ?? null,
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'trip_info' => $this->prefill->tripInfo($driver, $vehicle, $trip, $tpDay),
                'inspector_name' => null,
                'status' => ChecklistSubmission::STATUS_DRAFT,
                'lock_version' => 0,
            ]);

            foreach ($items as $item) {
                ChecklistSubmissionItem::query()->create([
                    'submission_id' => $submission->id,
                    'template_item_id' => $item->id,
                    'group_name' => $item->group_name,
                    'title' => $item->title,
                    'requirement' => $item->requirement,
                    'severity' => $item->severity,
                    'ng_action' => $item->ng_action,
                    'allow_na' => $item->allow_na,
                    'requires_photo_on_ng' => $item->requires_photo_on_ng,
                    'sort_order' => $item->sort_order,
                    'prefill_hint' => $hints[$item->id] ?? null,
                ]);
            }

            $this->audit->log(
                actorId: $actor->id,
                event: 'checklist.started',
                auditable: $submission,
                after: $submission->toArray(),
                metadata: ['template_code' => $template->code, 'items' => $items->count()],
            );

            return $submission;
        });

        return $submission->fresh();
    }

    /**
     * Lưu nháp: kết quả từng mục + thông tin chung. Optimistic lock theo lock_version.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveDraft(ChecklistSubmission $submission, User $actor, array $data): ChecklistSubmission
    {
        return DB::transaction(function () use ($submission, $actor, $data) {
            $locked = $this->lockDraft($submission, $actor, (int) $data['lock_version']);
            $changed = $this->applyChanges($locked, $data);

            $locked->lock_version++;
            $locked->save();

            $this->audit->log(
                actorId: $actor->id,
                event: 'checklist.draft_saved',
                auditable: $locked,
                metadata: ['items_changed' => $changed, 'lock_version' => $locked->lock_version],
            );

            return $locked->fresh();
        });
    }

    /**
     * Nộp phiếu: kiểm tra đủ mục, NG có ghi chú / ảnh, tính kết luận, chốt giờ nộp server.
     *
     * @param  array<string, mixed>  $data
     */
    public function submit(ChecklistSubmission $submission, User $actor, array $data): ChecklistSubmission
    {
        $submitted = DB::transaction(function () use ($submission, $actor, $data) {
            $locked = $this->lockDraft($submission, $actor, (int) $data['lock_version']);
            $this->applyChanges($locked, $data);

            $items = $locked->items()->withCount('attachments')->get();
            $this->assertCompletable($items);

            $calc = ChecklistResultCalculator::calculate($items);
            $now = Carbon::now();

            $locked->fill([
                'result' => $calc['result'],
                'critical_ng_count' => $calc['critical_ng'],
                'non_critical_ng_count' => $calc['non_critical_ng'],
                'status' => ChecklistSubmission::STATUS_SUBMITTED,
                'submitted_at' => $now,
                'client_submitted_at' => isset($data['client_submitted_at']) ? Carbon::parse($data['client_submitted_at']) : null,
                'confirmed_statement_at' => $now,
            ]);
            $locked->lock_version++;
            $locked->save();

            $this->audit->log(
                actorId: $actor->id,
                event: 'checklist.submitted',
                auditable: $locked,
                after: $locked->only(['code', 'result', 'critical_ng_count', 'non_critical_ng_count', 'submitted_at', 'period_key']),
            );

            if ($calc['critical_ng'] > 0) {
                $this->audit->log(
                    actorId: $actor->id,
                    event: 'checklist.critical_ng',
                    auditable: $locked,
                    metadata: [
                        'items' => $items->filter(fn ($i) => $i->result === ChecklistSubmissionItem::RESULT_NG && $i->isCritical())
                            ->pluck('title')->values()->all(),
                    ],
                );
            }

            return $locked->fresh(['driver', 'vehicle', 'items']);
        });

        if ($submitted->critical_ng_count > 0) {
            $this->notifyCriticalFailure($submitted);
        }

        return $submitted;
    }

    public function review(ChecklistSubmission $submission, User $actor, ?string $note): ChecklistSubmission
    {
        return DB::transaction(function () use ($submission, $actor, $note) {
            $locked = ChecklistSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            if ($locked->status === ChecklistSubmission::STATUS_DRAFT) {
                abort(409, 'Phiếu chưa được nộp.');
            }

            $before = $locked->only(['status', 'reviewed_by', 'reviewed_at', 'review_note']);
            $locked->fill([
                'status' => ChecklistSubmission::STATUS_REVIEWED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => Carbon::now(),
                'review_note' => $note,
            ]);
            $locked->lock_version++;
            $locked->save();

            $this->audit->log(
                actorId: $actor->id,
                event: 'checklist.reviewed',
                auditable: $locked,
                before: $before,
                after: $locked->only(['status', 'reviewed_by', 'reviewed_at', 'review_note']),
            );

            return $locked->fresh();
        });
    }

    public function discardDraft(ChecklistSubmission $submission, User $actor): void
    {
        DB::transaction(function () use ($submission, $actor) {
            $locked = ChecklistSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            $this->assertOwnerDraft($locked, $actor);

            $before = $locked->toArray();
            $locked->delete();

            $this->audit->log(actorId: $actor->id, event: 'checklist.discarded', auditable: $locked, before: $before);
        });
    }

    public function addEvidence(ChecklistSubmissionItem $item, UploadedFile $file, User $actor): Attachment
    {
        return DB::transaction(function () use ($item, $file, $actor) {
            $submission = ChecklistSubmission::query()->lockForUpdate()->findOrFail($item->submission_id);
            $this->assertOwnerDraft($submission, $actor);

            $attachment = $this->files->store($item, $file, $actor->id, ChecklistSubmissionItem::ATTACHMENT_KIND, 'checklists');

            $this->audit->log(
                actorId: $actor->id,
                event: 'checklist.evidence_added',
                auditable: $submission,
                metadata: ['item_id' => $item->id, 'attachment_id' => $attachment->id],
            );

            return $attachment;
        });
    }

    public function removeEvidence(ChecklistSubmissionItem $item, Attachment $attachment, User $actor): void
    {
        if ($attachment->attachable_type !== $item->getMorphClass() || (int) $attachment->attachable_id !== (int) $item->id) {
            abort(404);
        }

        DB::transaction(function () use ($item, $attachment, $actor) {
            $submission = ChecklistSubmission::query()->lockForUpdate()->findOrFail($item->submission_id);
            $this->assertOwnerDraft($submission, $actor);

            $this->files->delete($attachment);

            $this->audit->log(
                actorId: $actor->id,
                event: 'checklist.evidence_removed',
                auditable: $submission,
                metadata: ['item_id' => $item->id, 'attachment_id' => $attachment->id],
            );
        });
    }

    private function lockDraft(ChecklistSubmission $submission, User $actor, int $lockVersion): ChecklistSubmission
    {
        $locked = ChecklistSubmission::query()->lockForUpdate()->findOrFail($submission->id);
        $this->assertOwnerDraft($locked, $actor);

        if ((int) $locked->lock_version !== $lockVersion) {
            abort(409, 'Phiếu đã được cập nhật ở nơi khác. Vui lòng tải lại.');
        }

        return $locked;
    }

    private function assertOwnerDraft(ChecklistSubmission $submission, User $actor): void
    {
        abort_unless(ChecklistAccess::userOwns($actor, $submission), 403);

        if (! $submission->isDraft()) {
            abort(409, 'Phiếu đã nộp — không thể chỉnh sửa.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyChanges(ChecklistSubmission $submission, array $data): int
    {
        foreach (['note', 'inspector_name', 'location_text', 'lat', 'lng'] as $field) {
            if (array_key_exists($field, $data)) {
                $submission->{$field} = $data[$field];
            }
        }

        if (array_key_exists('trip_info', $data) && is_array($data['trip_info'])) {
            $allowed = ['date', 'campus_route', 'license_plate', 'departure_time', 'driver_name', 'driver_phone', 'attendant'];
            $submission->trip_info = array_merge(
                $submission->trip_info ?? [],
                array_intersect_key($data['trip_info'], array_flip($allowed)),
            );
        }

        $payload = collect($data['items'] ?? [])->keyBy(fn ($row) => (int) $row['id']);
        if ($payload->isEmpty()) {
            return 0;
        }

        $items = $submission->items()->whereIn('id', $payload->keys())->get();
        if ($items->count() !== $payload->count()) {
            abort(422, 'Có mục không thuộc phiếu này.');
        }

        $now = Carbon::now();
        $changed = 0;
        foreach ($items as $item) {
            $row = $payload->get($item->id);

            if (array_key_exists('result', $row)) {
                $result = $row['result'];
                if ($result === ChecklistSubmissionItem::RESULT_NA && ! $item->allow_na) {
                    throw ValidationException::withMessages([
                        "items.{$item->id}.result" => "Mục \"{$item->title}\" không được chọn N/A.",
                    ]);
                }
                if ($result !== $item->result) {
                    $item->result = $result;
                    $item->checked_at = $result === null ? null : $now;
                }
            }
            if (array_key_exists('note', $row)) {
                $item->note = $row['note'];
            }

            if ($item->isDirty()) {
                $item->save();
                $changed++;
            }
        }

        return $changed;
    }

    /**
     * @param  iterable<ChecklistSubmissionItem>  $items
     */
    private function assertCompletable(iterable $items): void
    {
        $errors = [];
        foreach ($items as $item) {
            $key = "items.{$item->id}";
            if ($item->result === null) {
                $errors[$key.'.result'] = "Chưa kiểm tra mục \"{$item->title}\".";

                continue;
            }
            if ($item->result !== ChecklistSubmissionItem::RESULT_NG) {
                continue;
            }
            if (trim((string) $item->note) === '') {
                $errors[$key.'.note'] = "Mục \"{$item->title}\" không đạt — vui lòng ghi rõ lý do / phương án.";
            }
            if ($item->requires_photo_on_ng && (int) ($item->attachments_count ?? 0) === 0) {
                $errors[$key.'.photo'] = "Mục \"{$item->title}\" không đạt — cần chụp ảnh minh chứng.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function resolveTrip(User $actor, mixed $tripId): ?Trip
    {
        if (empty($tripId)) {
            return null;
        }
        $trip = Trip::query()->findOrFail((int) $tripId);
        abort_unless(TripVisibility::userCanViewTrip($actor, $trip), 403);

        return $trip;
    }

    private function resolveTpDay(Driver $driver, mixed $dayId): ?TpProgramDay
    {
        if (empty($dayId)) {
            return null;
        }
        $day = TpProgramDay::query()->findOrFail((int) $dayId);
        $driverIds = array_map('intval', array_filter([
            $day->driver_id, $day->backup_driver_id,
            $day->morning_driver_id, $day->morning_backup_driver_id,
            $day->afternoon_driver_id, $day->afternoon_backup_driver_id,
        ]));
        abort_unless(in_array((int) $driver->id, $driverIds, true), 403);

        return $day;
    }

    private function notifyCriticalFailure(ChecklistSubmission $submission): void
    {
        $recipients = DispatchStaffNotificationRecipients::users();
        if ($recipients->isEmpty()) {
            return;
        }

        $ngTitles = $submission->items
            ->filter(fn (ChecklistSubmissionItem $i) => $i->result === ChecklistSubmissionItem::RESULT_NG && $i->isCritical())
            ->pluck('title')
            ->values()
            ->all();

        Notification::send($recipients, new ChecklistCriticalFailedNotification(
            (int) $submission->id,
            (string) $submission->code,
            (string) ($submission->driver?->full_name ?? ''),
            (string) ($submission->vehicle?->license_plate ?? ''),
            $ngTitles,
        ));
    }
}
