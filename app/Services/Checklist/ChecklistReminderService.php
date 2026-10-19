<?php

namespace App\Services\Checklist;

use App\Models\ChecklistReminderSlot;
use App\Models\ChecklistSubmission;
use App\Models\ChecklistTemplate;
use App\Models\Driver;
use App\Notifications\ChecklistOverdueDigestNotification;
use App\Notifications\DriverChecklistReminderNotification;
use App\Services\FeatureToggleService;
use App\Services\Notifications\DispatchStaffNotificationRecipients;
use App\Support\ChecklistPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Nhắc mỗi phút, bắt kịp các slot đã đến hạn trong ngày.
 * Báo quá hạn theo kỳ của slot gốc, kể cả khi hạn rơi qua nửa đêm.
 * Chống trùng theo slot + ngày gốc + người nhận, giữ khóa hai ngày.
 */
class ChecklistReminderService
{
    /**
     * @return array{reminded: int, escalated: int}
     */
    public function run(?Carbon $at = null): array
    {
        $now = ChecklistPeriod::local($at);
        $reminded = 0;
        $escalated = 0;
        if (! app(FeatureToggleService::class)->isEnabled('module.driver_checklist')) {
            return ['reminded' => 0, 'escalated' => 0];
        }

        $slots = ChecklistReminderSlot::query()
            ->where('is_active', true)
            ->whereHas('template', fn ($q) => $q->where('is_active', true))
            ->with('template')
            ->get();

        foreach ($slots as $slot) {
            $weekdays = array_map('intval', $slot->weekdays ?? []);
            foreach ([$now->copy()->subDays(2), $now->copy()->subDay(), $now] as $day) {
                if (! in_array($day->isoWeekday(), $weekdays, true)) {
                    continue;
                }
                $slotAt = $this->slotInstant($slot, $day);
                if ($slotAt === null) {
                    continue;
                }
                // Bắt kịp slot trong ngày khi cron gián đoạn; không gửi nhắc của ngày cũ.
                if ($day->isSameDay($now) && $now->gte($slotAt)) {
                    $reminded += $this->remindDrivers($slot, $slot->template, $slotAt);
                }
                // Escalation dùng kỳ/ngày gốc, kể cả khi hạn rơi sang ngày kế tiếp.
                if ($slot->escalate_after_minutes !== null) {
                    $due = $slotAt->copy()->addMinutes($slot->escalate_after_minutes);
                    if ($now->gte($due) && $now->lt($due->copy()->addDay())) {
                        $escalated += $this->escalate($slot, $slot->template, $slotAt);
                    }
                }
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
            if (! Cache::add($key, 1, 172800)) {
                continue;
            }
            try {
                $user->notify(new DriverChecklistReminderNotification($template->id, $template->name, $template->frequency, $label));
            } catch (\Throwable $e) {
                Cache::forget($key);
                throw $e;
            }
            $sent++;
        }

        return $sent;
    }

    private function escalate(ChecklistReminderSlot $slot, ChecklistTemplate $template, Carbon $now): int
    {
        $key = sprintf('checklist-escalate:%d:%s', $slot->id, $now->toDateString());
        $periodKey = ChecklistPeriod::key($template->frequency, $now);
        $names = $this->pendingDrivers($template, $periodKey)->pluck('full_name')->filter()->values()->all();
        if ($names === []) {
            return 0;
        }

        $recipients = DispatchStaffNotificationRecipients::users();
        if ($recipients->isEmpty()) {
            return 0;
        }

        if (! Cache::add($key, 1, 172800)) {
            return 0;
        }

        try {
            Notification::send($recipients, new ChecklistOverdueDigestNotification(
                $template->name,
                ChecklistPeriod::label($template->frequency, $periodKey),
                $names,
            ));
        } catch (\Throwable $e) {
            Cache::forget($key);
            throw $e;
        }

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
}
