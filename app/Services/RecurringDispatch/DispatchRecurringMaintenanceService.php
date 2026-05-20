<?php

namespace App\Services\RecurringDispatch;

use App\Models\DispatchPackage;
use App\Models\DispatchRequest;
use App\Models\DispatchRequestTemplate;
use App\Models\Trip;
use App\Notifications\DispatchPackageSessionsLowBalanceNotification;
use App\Services\Auditing\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DispatchRecurringMaintenanceService
{
    /**
     * Sinh các bản dispatch_requests con trong cửa sổ hiện tại → +horizon thỏa recurrence_rule.
     *
     * @return int số phiếu mới được tạo
     */
    public function materializeForTemplate(DispatchRequestTemplate $template): int
    {
        if (! $template->is_active) {
            return 0;
        }

        $timezone = config('app.timezone') ?: 'UTC';
        $horizonDays = max(1, (int) config('dispatch.recurring_materialization_horizon_days', 21));
        $now = Carbon::now($timezone)->startOfMinute();

        $cursorEnd = $now->copy()->addDays($horizonDays)->endOfDay();

        $ruleEndDay = $template->effectiveRecurrenceEndDay($timezone);
        // Chuỗi có ngày/kết thúc cố định (vd. lặp 4 tuần): sinh đủ tới hết chuỗi, không cắt bởi horizon 21 ngày.
        if ($ruleEndDay !== null) {
            $untilDay = $ruleEndDay;
        } else {
            $untilDay = $cursorEnd->copy();
        }

        /** @phpstan-ignore-next-line */
        $frequency = strtolower((string) data_get($template->recurrence_rule, 'freq', 'weekly'));
        /** @phpstan-ignore-next-line */
        $interval = max(1, (int) data_get($template->recurrence_rule, 'interval', 1));

        if ($template->start_date !== null) {
            $anchor = Carbon::parse((string) $template->start_date, $timezone)->startOfDay();
        } else {
            /** @phpstan-ignore-next-line */
            $anchor = ($template->created_at ?? $now)->copy()->timezone($timezone)->startOfDay();
        }

        $timeStrRaw = $template->getAttributes()['recurrence_time'] ?? '08:00:00';
        /** @phpstan-ignore-next-line */
        $timeStr = is_object($timeStrRaw) && method_exists($timeStrRaw, 'format')
            /** @phpstan-ignore-next-line */
            ? $timeStrRaw->format('H:i:s')
            /** @phpstan-ignore-next-line */
            : preg_replace('/\.\d+$/', '', (string) $timeStrRaw);

        /** @phpstan-ignore-next-line */
        if ($timeStr !== null && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $timeStr ?? '') !== 1) {
            /** @phpstan-ignore-next-line */
            $timeStr = '08:00:00';
        }

        $created = 0;

        $dayCursor = $anchor->copy();
        if ($dayCursor->lt($now->copy()->startOfDay())) {
            $dayCursor = $now->copy()->startOfDay();
        }
        /** @phpstan-ignore-next-line */
        for (; $dayCursor->lte($untilDay->copy()->startOfDay()); $dayCursor->addDay()) {
            if (! self::frequencyMatchesDay($frequency, $interval, $template->recurrence_rule, $anchor, $dayCursor, $timezone)) {
                continue;
            }

            $departCandidate = Carbon::parse(
                /** @phpstan-ignore-next-line */
                $dayCursor->format('Y-m-d').' '.($timeStr ?? '08:00:00'),
                $timezone,
            )->startOfMinute();

            if ($departCandidate->lte($now)) {
                continue;
            }

            if ($departCandidate->greaterThan($untilDay)) {
                continue;
            }

            if ($this->instanceExistsNearby($template, $departCandidate)) {
                continue;
            }

            if ($this->dispatchPackageReachedLimit($template)) {
                continue;
            }

            $arriveBy = null;
            if ($template->arrive_offset_minutes !== null) {
                /** @phpstan-ignore-next-line */
                $arriveBy = $departCandidate->copy()->addMinutes((int) $template->arrive_offset_minutes);
            }

            $instance = DispatchRequest::create([
                'requester_id' => $template->requester_id,
                'dispatch_request_template_id' => $template->id,
                'trip_type' => $template->trip_type,
                'origin' => $template->origin,
                'destination' => $template->destination,
                'depart_at' => $departCandidate->copy(),
                'arrive_by' => $arriveBy !== null ? $arriveBy->copy() : null,
                'passenger_count' => $template->passenger_count,
                'notes' => $template->notes,
                'wizard_snapshot' => $template->wizard_snapshot,
                'status' => 'pending',
                'source_channel' => 'portal',
                'is_urgent' => false,
                'urgent_reason' => null,
                'urgent_trigger' => null,
                'paper_status' => 'pending',
            ]);

            app(AuditLogger::class)->log(
                actorId: null,
                event: 'request.recurring_instance_created',
                auditable: $instance,
                before: null,
                after: $instance->fresh()->toArray(),
                metadata: ['dispatch_request_template_id' => $template->id],
            );

            $created++;
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>|null  $rule
     */
    protected static function frequencyMatchesDay(string $frequency, int $interval, ?array $rule, Carbon $anchor, Carbon $day, string $timezone): bool
    {
        /** @phpstan-ignore-next-line */
        if ($frequency === 'weekly') {
            /** @phpstan-ignore-next-line */
            $wanted = collect((array) data_get($rule, 'byweekday', []))
                ->filter(fn ($d): bool => is_numeric($d))
                /** @phpstan-ignore-next-line */
                ->map(fn ($d) => (int) $d)
                /** @phpstan-ignore-next-line */
                ->unique()->sort()->values()->all();

            if ($wanted === []) {
                $wanted = [(int) $day->isoWeekday()];
            }

            return in_array((int) $day->isoWeekday(), $wanted, true);
        }

        if ($frequency === 'daily') {
            $delta = $anchor->copy()->timezone($timezone)->startOfDay()
                /** @phpstan-ignore-next-line */
                ->diffInDays($day->copy()->timezone($timezone)->startOfDay(), false);

            return $delta >= 0 && $interval > 0 && ((int) $delta) % $interval === 0;
        }

        return false;
    }

    /** Tránh duplicate cùng gần mốc khởi hành. */
    protected function instanceExistsNearby(DispatchRequestTemplate $template, Carbon $localDepartMinute): bool
    {
        $windowStart = $localDepartMinute->copy()->subSeconds(59);
        $windowEnd = $localDepartMinute->copy()->addSeconds(59);

        return DispatchRequest::query()
            ->where('dispatch_request_template_id', $template->id)
            ->whereBetween('depart_at', [$windowStart, $windowEnd])
            /** @phpstan-ignore-next-line */
            ->exists();
    }

    protected function dispatchPackageReachedLimit(DispatchRequestTemplate $template): bool
    {
        /** @phpstan-ignore-next-line */
        if ($template->dispatch_package_id === null) {
            return false;
        }

        /** @phpstan-ignore-next-line */
        $pkg = DispatchPackage::query()->find((int) $template->dispatch_package_id);
        /** @phpstan-ignore-next-line */
        if ($pkg === null) {
            return false;
        }

        /** @phpstan-ignore-next-line */
        return (int) $pkg->sessions_used >= (int) $pkg->total_sessions;
    }

    /**
     * Tăng sessions_used của gói gắn template (theo một chuyến hoàn thành) và gửi notify nếu còn ít buổi.
     */
    public function consumePackageSessionAfterTripCompletion(Trip $trip): void
    {
        DB::transaction(function () use ($trip) {
            if ($trip->dispatch_request_id === null) {
                return;
            }

            /** @phpstan-ignore-next-line */
            $dr = DispatchRequest::query()->whereKey((int) $trip->dispatch_request_id)->first();
            /** @phpstan-ignore-next-line */
            if ($dr === null || $dr->dispatch_request_template_id === null) {
                return;
            }

            /** @phpstan-ignore-next-line */
            $tid = $dr->dispatch_request_template_id;
            $template = DispatchRequestTemplate::query()->with(['requester'])->find($tid);
            /** @phpstan-ignore-next-line */
            if ($template === null || $template->dispatch_package_id === null) {
                return;
            }

            /** @phpstan-ignore-next-line */
            $pkg = DispatchPackage::query()->whereKey((int) $template->dispatch_package_id)->lockForUpdate()->first();
            /** @phpstan-ignore-next-line */
            if ($pkg === null) {
                return;
            }

            /** @phpstan-ignore-next-line */
            $remainingBefore = $pkg->remainingSessions();

            /** @phpstan-ignore-next-line */
            if ($pkg->sessions_used >= $pkg->total_sessions) {
                return;
            }

            /** @phpstan-ignore-next-line */
            $pkg->sessions_used = (int) $pkg->sessions_used + 1;
            /** @phpstan-ignore-next-line */
            $pkg->save();

            /** @phpstan-ignore-next-line */
            $pkg->refresh();

            /** @phpstan-ignore-next-line */
            $remaining = $pkg->remainingSessions();
            /** @phpstan-ignore-next-line */
            $threshold = max(0, (int) $pkg->alert_when_remaining_sessions);

            if ($remaining > $threshold) {
                /** @phpstan-ignore-next-line */
                return;
            }

            /** @phpstan-ignore-next-line */
            if ($remainingBefore <= $threshold) {
                return;
            }

            /** @phpstan-ignore-next-line */
            $requesterUser = $template->requester;
            /** @phpstan-ignore-next-line */
            if (! $requesterUser) {
                return;
            }

            Notification::send($requesterUser, new DispatchPackageSessionsLowBalanceNotification(
                /** @phpstan-ignore-next-line */
                dispatchPackageId: (int) $pkg->id,
                /** @phpstan-ignore-next-line */
                label: (string) ($pkg->label ?? $pkg->trip_type),
                sessionsRemaining: $remaining,
                totalSessions: (int) $pkg->total_sessions,
            ));

            /** @phpstan-ignore-next-line */
            $pkg->last_low_sessions_notified_at = now();
            /** @phpstan-ignore-next-line */
            $pkg->saveQuietly();
        });
    }
}
