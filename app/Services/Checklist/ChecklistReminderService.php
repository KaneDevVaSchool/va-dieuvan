<?php

namespace App\Services\Checklist;

use App\Models\ChecklistReminderSlot;
use App\Models\ChecklistSubmission;
use App\Models\ChecklistTemplate;
use App\Models\Driver;
use App\Notifications\ChecklistOverdueDigestNotification;
use App\Notifications\DriverChecklistReminderNotification;
use App\Services\Notifications\DispatchStaffNotificationRecipients;
use App\Support\ChecklistPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Gửi nhắc checklist theo khung giờ cấu hình. Chạy mỗi 5 phút (scheduler):
 * slot rơi vào cửa sổ [remind_at, remind_at + 5') của lần chạy hiện tại → nhắc tài xế chưa nộp kỳ;
 * có escalate_after_minutes → sau thời hạn gửi 1 bản tổng hợp cho điều phối.
 * Chống gửi trùng bằng cache key theo slot + ngày + người nhận.
 */
class ChecklistReminderService
{
    public const WINDOW_MINUTES = 5;

    /**
     * @return array{reminded: int, escalated: int}
     */
    public function run(?Carbon $at = null): array
    {
        $now = ChecklistPeriod::local($at);
        $reminded = 0;
        $escalated = 0;

        $slots = ChecklistReminderSlot::query()
            ->where('is_active', true)
            ->whereHas('template', fn ($q) => $q->where('is_active', true))
            ->with('template')
            ->get();

        foreach ($slots as $slot) {
            $weekdays = array_map('intval', $slot->weekdays ?? []);
            if (! in_array($now->isoWeekday(), $weekdays, true)) {
                continue;
            }

            $slotAt = $this->slotInstant($slot, $now);
            if ($slotAt === null) {
                continue;
            }

            if ($this->inWindow($now, $slotAt)) {
                $reminded += $this->remindDrivers($slot, $slot->template, $now);
            }

            if ($slot->escalate_after_minutes !== null && $this->inWindow($now, $slotAt->copy()->addMinutes($slot->escalate_after_minutes))) {
                $escalated += $this->escalate($slot, $slot->template, $now);
            }
        }

        return ['reminded' => $reminded, 'escalated' => $escalated];
    }

    private function remindDrivers(ChecklistReminderSlot $slot, ChecklistTemplate $template, Carbon $now): int
    {
        $periodKey = ChecklistPeriod::key($template->frequency, $now);
        $label = ChecklistPeriod::label($template->frequency, $periodKey);
        $sent = 0;

        foreach ($this->pendingDrivers($template, $periodKey) as $driver) {
            $user = $driver->user;
            if ($user === null || ! (bool) ($user->is_active ?? true)) {
                continue;
            }
            $key = sprintf('checklist-remind:%d:%s:%d', $slot->id, $now->toDateString(), $user->id);
            if (! Cache::add($key, 1, now()->addDay())) {
                continue;
            }
            $user->notify(new DriverChecklistReminderNotification($template->id, $template->name, $template->frequency, $label));
            $sent++;
        }

        return $sent;
    }

    private function escalate(ChecklistReminderSlot $slot, ChecklistTemplate $template, Carbon $now): int
    {
        $key = sprintf('checklist-escalate:%d:%s', $slot->id, $now->toDateString());
        if (! Cache::add($key, 1, now()->addDay())) {
            return 0;
        }

        $periodKey = ChecklistPeriod::key($template->frequency, $now);
        $names = $this->pendingDrivers($template, $periodKey)->pluck('full_name')->filter()->values()->all();
        if ($names === []) {
            return 0;
        }

        $recipients = DispatchStaffNotificationRecipients::users();
        if ($recipients->isEmpty()) {
            return 0;
        }

        Notification::send($recipients, new ChecklistOverdueDigestNotification(
            $template->name,
            ChecklistPeriod::label($template->frequency, $periodKey),
            $names,
        ));

        return 1;
    }

    /**
     * Tài xế đang làm việc, có tài khoản, chưa nộp phiếu nào của template trong kỳ.
     *
     * @return Collection<int, Driver>
     */
    public function pendingDrivers(ChecklistTemplate $template, string $periodKey): Collection
    {
        $doneDriverIds = ChecklistSubmission::query()
            ->where('template_id', $template->id)
            ->where('period_key', $periodKey)
            ->where('status', '!=', ChecklistSubmission::STATUS_DRAFT)
            ->distinct()
            ->pluck('driver_id');

        return Driver::query()
            ->where('employment_status', 'active')
            ->whereNotNull('user_id')
            ->whereNotIn('id', $doneDriverIds)
            ->with('user')
            ->orderBy('full_name')
            ->get();
    }

    private function slotInstant(ChecklistReminderSlot $slot, Carbon $now): ?Carbon
    {
        if (! preg_match('/^(\d{2}):(\d{2})$/', (string) $slot->remind_at, $m)) {
            return null;
        }

        return $now->copy()->setTime((int) $m[1], (int) $m[2]);
    }

    private function inWindow(Carbon $now, Carbon $target): bool
    {
        return $now->gte($target) && $now->lt($target->copy()->addMinutes(self::WINDOW_MINUTES));
    }
}
