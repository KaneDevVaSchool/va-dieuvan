<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyTripSlot;
use App\Models\User;
use App\Notifications\P2pPolicyTripDepartReminderNotification;
use App\Support\P2pPolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class P2pPolicyDepartReminderService
{
    public function sendDueReminders(?Carbon $now = null): int
    {
        $now = ($now ?? now())->timezone('Asia/Ho_Chi_Minh');
        $leadMinutes = (int) config('dispatch.p2p_policy_depart_reminder_lead_minutes', 30);
        $graceMinutes = (int) config('dispatch.p2p_policy_depart_reminder_grace_minutes', 15);

        $sent = 0;

        PolicyTripSlot::query()
            ->whereNotNull('trip_id')
            ->whereNull('depart_reminder_sent_at')
            ->whereHas('trip')
            ->whereHas('dispatchRequest', fn ($q) => $q->where('source_channel', P2pPolicy::SOURCE_CHANNEL))
            ->with([
                'trip.driver.user',
                'trip.dispatchRequest',
                'policyRoute.originCampus',
                'policyRoute.destCampus',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($slots) use ($now, $leadMinutes, $graceMinutes, &$sent) {
                foreach ($slots as $slot) {
                    if ($this->processSlot($slot, $now, $leadMinutes, $graceMinutes)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    private function processSlot(
        PolicyTripSlot $slot,
        Carbon $now,
        int $leadMinutes,
        int $graceMinutes,
    ): bool {
        $trip = $slot->trip;
        if ($trip === null || $trip->depart_at === null) {
            return false;
        }

        $depart = $trip->depart_at instanceof Carbon
            ? $trip->depart_at->copy()->timezone('Asia/Ho_Chi_Minh')
            : Carbon::parse($trip->depart_at)->timezone('Asia/Ho_Chi_Minh');

        $windowStart = $depart->copy()->subMinutes($leadMinutes);
        $windowEnd = $depart->copy()->addMinutes($graceMinutes);

        if ($now->lt($windowStart) || $now->gte($windowEnd)) {
            return false;
        }

        $dr = $slot->dispatchRequest ?? $trip->dispatchRequest;
        $route = $slot->policyRoute;
        $route->loadMissing(['originCampus', 'destCampus']);

        $origin = $dr?->origin ?? $route?->originCampus?->name ?? '';
        $destination = $dr?->destination ?? $route?->destCampus?->name ?? '';
        $departIso = $depart->toIso8601String();
        $legLabel = $slot->leg === P2pPolicy::LEG_AFTERNOON ? 'Chiều' : 'Sáng';

        $driverUserId = (int) ($trip->driver?->user_id ?? 0);
        $recipientIds = [];

        if ($driverUserId > 0) {
            $recipientIds[$driverUserId] = true;
        }
        $activatorId = (int) ($dr?->requester_id ?? 0);
        if ($activatorId > 0) {
            $recipientIds[$activatorId] = true;
        }
        $dispatcherId = (int) ($trip->dispatcher_id ?? 0);
        if ($dispatcherId > 0) {
            $recipientIds[$dispatcherId] = true;
        }

        if ($recipientIds === []) {
            Log::warning('p2p_policy.depart_reminder_no_recipients', ['policy_trip_slot_id' => $slot->id]);

            return false;
        }

        $users = User::query()->whereIn('id', array_keys($recipientIds))->get();

        foreach ($users as $user) {
            $isDriver = $driverUserId > 0 && (int) $user->id === $driverUserId;
            $user->notify(new P2pPolicyTripDepartReminderNotification(
                tripId: (int) $trip->id,
                origin: (string) $origin,
                destination: (string) $destination,
                departAt: $departIso,
                driverAppLink: $isDriver,
                legLabel: $legLabel,
            ));
        }

        $slot->forceFill(['depart_reminder_sent_at' => now()])->save();

        return true;
    }
}
