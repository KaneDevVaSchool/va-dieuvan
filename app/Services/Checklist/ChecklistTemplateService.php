<?php

namespace App\Services\Checklist;

use App\Models\ChecklistReminderSlot;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Cấu hình checklist: template, mục kiểm tra, khung giờ nhắc.
 * Sửa mục → tăng version (phiếu cũ giữ snapshot nên lịch sử không đổi).
 */
class ChecklistTemplateService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForSettings(): array
    {
        return ChecklistTemplate::query()
            ->with(['items', 'reminderSlots' => fn ($q) => $q->orderBy('remind_at')])
            ->orderBy('frequency')
            ->orderBy('id')
            ->get()
            ->map(fn (ChecklistTemplate $t) => $this->presentTemplate($t))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTemplate(ChecklistTemplate $template, User $actor, array $data): ChecklistTemplate
    {
        return DB::transaction(function () use ($template, $actor, $data) {
            $before = $template->only(['name', 'is_active', 'form_meta']);
            $template->fill(array_intersect_key($data, array_flip(['name', 'is_active'])));
            if (array_key_exists('form_meta', $data) && is_array($data['form_meta'])) {
                $template->form_meta = array_merge($template->form_meta ?? [], $data['form_meta']);
            }
            $template->save();

            $this->audit->log($actor->id, 'checklist_template.updated', $template, $before, $template->only(['name', 'is_active', 'form_meta']));

            return $template->fresh(['items', 'reminderSlots']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveItem(ChecklistTemplate $template, User $actor, array $data, ?ChecklistTemplateItem $item = null): ChecklistTemplateItem
    {
        return DB::transaction(function () use ($template, $actor, $data, $item) {
            $before = $item?->toArray();
            $fields = array_intersect_key($data, array_flip([
                'group_name', 'title', 'requirement', 'severity', 'ng_action', 'allow_na', 'requires_photo_on_ng', 'auto_source', 'is_active', 'sort_order',
            ]));

            if ($item === null) {
                $fields['sort_order'] ??= ((int) $template->items()->max('sort_order')) + 10;
                $item = $template->items()->create($fields);
                $event = 'checklist_template.item_created';
            } else {
                abort_unless((int) $item->template_id === (int) $template->id, 404);
                $item->fill($fields)->save();
                $event = 'checklist_template.item_updated';
            }

            $template->increment('version');
            $this->audit->log($actor->id, $event, $template, $before, $item->toArray(), ['item_id' => $item->id]);

            return $item->fresh();
        });
    }

    public function deleteItem(ChecklistTemplate $template, User $actor, ChecklistTemplateItem $item): void
    {
        abort_unless((int) $item->template_id === (int) $template->id, 404);

        DB::transaction(function () use ($template, $actor, $item) {
            $before = $item->toArray();
            $item->delete();
            $template->increment('version');
            $this->audit->log($actor->id, 'checklist_template.item_deleted', $template, $before, null, ['item_id' => $before['id']]);
        });
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorderItems(ChecklistTemplate $template, User $actor, array $orderedIds): void
    {
        DB::transaction(function () use ($template, $actor, $orderedIds) {
            $items = $template->items()->whereIn('id', $orderedIds)->get()->keyBy('id');
            foreach (array_values($orderedIds) as $i => $id) {
                $item = $items->get($id);
                if ($item) {
                    $item->update(['sort_order' => ($i + 1) * 10]);
                }
            }
            $template->increment('version');
            $this->audit->log($actor->id, 'checklist_template.items_reordered', $template, null, null, ['order' => array_values($orderedIds)]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveSlot(User $actor, array $data, ?ChecklistReminderSlot $slot = null): ChecklistReminderSlot
    {
        return DB::transaction(function () use ($actor, $data, $slot) {
            $before = $slot?->toArray();
            $fields = array_intersect_key($data, array_flip(['template_id', 'remind_at', 'weekdays', 'escalate_after_minutes', 'is_active']));
            if (isset($fields['weekdays'])) {
                $fields['weekdays'] = array_values(array_unique(array_map('intval', $fields['weekdays'])));
                sort($fields['weekdays']);
            }

            if ($slot === null) {
                $slot = ChecklistReminderSlot::query()->create([...$fields, 'created_by' => $actor->id]);
                $event = 'checklist_reminder_slot.created';
            } else {
                $slot->fill($fields)->save();
                $event = 'checklist_reminder_slot.updated';
            }

            $this->audit->log($actor->id, $event, $slot, $before, $slot->toArray());

            return $slot->fresh();
        });
    }

    public function deleteSlot(User $actor, ChecklistReminderSlot $slot): void
    {
        DB::transaction(function () use ($actor, $slot) {
            $before = $slot->toArray();
            $slot->delete();
            $this->audit->log($actor->id, 'checklist_reminder_slot.deleted', null, $before);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function presentTemplate(ChecklistTemplate $t): array
    {
        return [
            'id' => $t->id,
            'code' => $t->code,
            'name' => $t->name,
            'frequency' => $t->frequency,
            'version' => $t->version,
            'is_active' => $t->is_active,
            'form_meta' => $t->form_meta ?? [],
            'items' => $t->relationLoaded('items') ? $t->items->map(fn (ChecklistTemplateItem $i) => $i->only([
                'id', 'group_name', 'sort_order', 'title', 'requirement', 'severity', 'ng_action', 'allow_na', 'requires_photo_on_ng', 'auto_source', 'is_active',
            ]))->values()->all() : [],
            'reminder_slots' => $t->relationLoaded('reminderSlots') ? $t->reminderSlots->map(fn (ChecklistReminderSlot $s) => $s->only([
                'id', 'template_id', 'remind_at', 'weekdays', 'escalate_after_minutes', 'is_active',
            ]))->values()->all() : [],
        ];
    }
}
