<?php

namespace App\Services\Checklist;

use App\Models\Attachment;
use App\Models\ChecklistSubmission;
use App\Models\ChecklistSubmissionItem;
use App\Support\ChecklistPeriod;
use App\Support\TripScheduleInstant;

/**
 * Chuẩn hóa phiếu checklist thành mảng JSON cho API.
 */
class ChecklistPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function summary(ChecklistSubmission $s): array
    {
        return [
            'id' => $s->id,
            'code' => $s->code,
            'status' => $s->status,
            'result' => $s->result,
            'period_type' => $s->period_type,
            'period_key' => $s->period_key,
            'period_label' => ChecklistPeriod::label($s->period_type, $s->period_key),
            'critical_ng_count' => (int) $s->critical_ng_count,
            'non_critical_ng_count' => (int) $s->non_critical_ng_count,
            'started_at' => $this->iso($s->started_at),
            'submitted_at' => $this->iso($s->submitted_at),
            'reviewed_at' => $this->iso($s->reviewed_at),
            'lock_version' => (int) $s->lock_version,
            'template' => $s->relationLoaded('template') && $s->template ? [
                'id' => $s->template->id,
                'code' => $s->template->code,
                'name' => $s->template->name,
                'frequency' => $s->template->frequency,
            ] : null,
            'driver' => $s->relationLoaded('driver') && $s->driver ? [
                'id' => $s->driver->id,
                'full_name' => $s->driver->full_name,
                'phone' => $s->driver->phone,
            ] : null,
            'vehicle' => $s->relationLoaded('vehicle') && $s->vehicle ? [
                'id' => $s->vehicle->id,
                'license_plate' => $s->vehicle->license_plate,
                'type' => $s->vehicle->type,
            ] : null,
            'progress' => match (true) {
                $s->items_total !== null => ['total' => (int) $s->items_total, 'done' => (int) $s->items_done],
                $s->relationLoaded('items') => ['total' => $s->items->count(), 'done' => $s->items->whereNotNull('result')->count()],
                default => null,
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(ChecklistSubmission $s): array
    {
        $s->loadMissing(['template', 'driver', 'vehicle', 'reviewer:id,name', 'items.attachments' => fn ($q) => $q->select([
            'id', 'attachable_type', 'attachable_id', 'kind', 'disk', 'path', 'original_name', 'size_bytes', 'mime_type', 'created_at',
        ])]);

        return [
            ...$this->summary($s),
            'trip_id' => $s->trip_id,
            'tp_program_day_id' => $s->tp_program_day_id,
            'trip_info' => $s->trip_info ?? [],
            'note' => $s->note,
            'inspector_name' => $s->inspector_name,
            'location_text' => $s->location_text,
            'lat' => $s->lat,
            'lng' => $s->lng,
            'client_submitted_at' => $this->iso($s->client_submitted_at),
            'review_note' => $s->review_note,
            'reviewer' => $s->reviewer ? ['id' => $s->reviewer->id, 'name' => $s->reviewer->name] : null,
            'form_meta' => $s->template?->form_meta ?? [],
            'items' => $s->items->map(fn (ChecklistSubmissionItem $i) => [
                'id' => $i->id,
                'template_item_id' => $i->template_item_id,
                'group_name' => $i->group_name,
                'title' => $i->title,
                'requirement' => $i->requirement,
                'severity' => $i->severity,
                'ng_action' => $i->ng_action,
                'allow_na' => (bool) $i->allow_na,
                'requires_photo_on_ng' => (bool) $i->requires_photo_on_ng,
                'result' => $i->result,
                'note' => $i->note,
                'checked_at' => $this->iso($i->checked_at),
                'prefill_hint' => $i->prefill_hint,
                'attachments' => $i->attachments->map(fn (Attachment $a) => [
                    'id' => $a->id,
                    'original_name' => $a->original_name,
                    'mime_type' => $a->mime_type,
                    'size_bytes' => $a->size_bytes,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    private function iso(mixed $value): ?string
    {
        return $value ? $value->copy()->setTimezone(TripScheduleInstant::DISPLAY_TZ)->toIso8601String() : null;
    }
}
