<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Concerns\PresentsDispatchRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Portal\CreatePortalDispatchRequestRequest;
use App\Models\DispatchRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewDispatchRequestNotification;
use App\Services\Auditing\AuditLogger;
use App\Support\Messages;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class PortalDispatchRequestController extends Controller
{
    use ApiResponses;
    use PresentsDispatchRequest;

    public function store(CreatePortalDispatchRequestRequest $request)
    {
        $data = $request->validated();

        $departAt = Carbon::parse($data['depart_at']);
        $clientUrgent = (bool) ($data['is_urgent'] ?? false);
        [$finalUrgent, $urgentTrigger] = DispatchRequest::resolveUrgentTrigger(
            $data['trip_type'],
            $departAt,
            $clientUrgent,
        );
        if (! $finalUrgent && $departAt->lt(now()->addHours(2))) {
            abort(422, Messages::REQUEST_MUST_BE_2H_AHEAD);
        }

        $user = $request->user();

        $urgentReasonTrim = isset($data['urgent_reason']) ? trim((string) $data['urgent_reason']) : '';

        $dispatchRequest = DispatchRequest::create([
            ...$data,
            'requester_id' => $user->id,
            'status' => 'pending',
            'source_channel' => $data['source_channel'] ?? 'portal',
            'is_urgent' => $finalUrgent,
            'urgent_reason' => $finalUrgent ? ($urgentReasonTrim !== '' ? $urgentReasonTrim : null) : null,
            'urgent_trigger' => $urgentTrigger,
            'paper_status' => 'pending',
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'request.create',
            auditable: $dispatchRequest,
            before: null,
            after: $dispatchRequest->toArray(),
        );

        if ($dispatchRequest->is_urgent) {
            app(AuditLogger::class)->log(
                actorId: $user->id,
                event: 'request.urgent_marked',
                auditable: $dispatchRequest,
                before: null,
                after: null,
                metadata: [
                    'trigger' => $dispatchRequest->urgent_trigger,
                    'requester_id' => $dispatchRequest->requester_id,
                ],
            );
        }

        if (Role::query()->where('name', 'dispatcher')->where('guard_name', 'web')->exists()) {
            $recipients = User::query()
                ->role('dispatcher')
                ->get();
            if ($recipients->isNotEmpty()) {
                $summary = trim(($dispatchRequest->origin ?? '').' → '.($dispatchRequest->destination ?? ''));
                Notification::send(
                    $recipients,
                    new NewDispatchRequestNotification(
                        $dispatchRequest->id,
                        $summary !== '→' ? $summary : 'Yêu cầu #'.$dispatchRequest->id,
                        $dispatchRequest->is_urgent,
                    ),
                );
            }
        }

        return $this->created($this->presentDispatchRequest($dispatchRequest));
    }
}
