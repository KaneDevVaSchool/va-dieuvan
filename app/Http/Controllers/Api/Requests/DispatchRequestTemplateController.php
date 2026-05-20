<?php

namespace App\Http\Controllers\Api\Requests;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Concerns\PresentsDispatchRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Portal\StorePortalDispatchRequestTemplateRequest;
use App\Http\Requests\Api\Portal\UpdatePortalDispatchPlanLabelRequest;
use App\Http\Requests\Api\Portal\UpdatePortalDispatchRequestTemplateRequest;
use App\Http\Requests\Api\Requests\StoreDispatchRequestTemplateRequest;
use App\Models\DispatchPackage;
use App\Models\DispatchRequest;
use App\Models\DispatchRequestTemplate;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewDispatchRequestNotification;
use App\Services\Auditing\AuditLogger;
use App\Services\RecurringDispatch\DispatchRecurringMaintenanceService;
use App\Support\Messages;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DispatchRequestTemplateController extends Controller
{
    use ApiResponses;
    use AuthorizesRequests;
    use PresentsDispatchRequest;

    public function storePortal(StorePortalDispatchRequestTemplateRequest $request)
    {
        return $this->store($request);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function payloadIsExtracurricularRecurring(array $data): bool
    {
        if (($data['trip_type'] ?? '') !== 'point_to_point') {
            return false;
        }

        $snap = $data['wizard_snapshot'] ?? null;
        $purposeKind = is_array($snap)
            ? (data_get($snap, 'form.point_purpose_kind') ?? data_get($snap, 'point_purpose_kind'))
            : null;

        if ($purposeKind !== 'extracurricular') {
            return false;
        }

        return is_array($data['recurrence_rule'] ?? null)
            && ! empty($data['recurrence_end_date'] ?? null);
    }

    public function store(StoreDispatchRequestTemplateRequest $request)
    {
        $data = $request->validated();

        $departAt = Carbon::parse($data['depart_at']);
        $clientUrgent = (bool) ($data['is_urgent'] ?? false);
        $isExtracurricularRecurring = self::payloadIsExtracurricularRecurring($data);
        [$finalUrgent, $urgentTrigger] = DispatchRequest::resolveUrgentTrigger(
            $data['trip_type'],
            $departAt,
            $clientUrgent,
        );
        if ($isExtracurricularRecurring) {
            $finalUrgent = false;
            $urgentTrigger = null;
        }
        if (! $isExtracurricularRecurring && ! $finalUrgent && $departAt->lt(now()->addHours(2))) {
            abort(422, Messages::REQUEST_MUST_BE_2H_AHEAD);
        }

        $user = $request->user();

        $requesterId = $user->id;
        if (! empty($data['requester_id']) && ($user->hasRole('dispatcher') || $user->hasRole('admin'))) {
            $requesterId = (int) $data['requester_id'];
        }

        $urgentReasonTrim = isset($data['urgent_reason']) ? trim((string) $data['urgent_reason']) : '';

        $arriveOffset = null;
        $arriveBy = null;
        if (! empty($data['arrive_by'])) {
            $arriveBy = Carbon::parse($data['arrive_by']);
            $delta = $departAt->diffInMinutes($arriveBy, false);
            if ($delta < 0) {
                abort(422, 'Giờ đến phải sau hoặc cùng giờ khởi hành.');
            }
            $arriveOffset = (int) $delta;
        } elseif (! empty($data['return_time'])) {
            $departMinutes = (int) $departAt->format('H') * 60 + (int) $departAt->format('i');
            $returnParts = explode(':', (string) $data['return_time']);
            $returnMinutes = ((int) ($returnParts[0] ?? 0)) * 60 + ((int) ($returnParts[1] ?? 0));
            $delta = $returnMinutes - $departMinutes;
            if ($delta < 0) {
                $delta += 24 * 60;
            }
            $arriveOffset = $delta;
            $arriveBy = $departAt->copy()->addMinutes($delta);
        }

        $recurrenceTime = $departAt->format('H:i:s');
        $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date'])->toDateString() : $departAt->toDateString();
        $returnTime = isset($data['return_time']) ? (strlen((string) $data['return_time']) === 5
            ? $data['return_time'].':00'
            : $data['return_time']) : null;

        /** @var DispatchRequestTemplate $template */
        /** @var DispatchRequest $dispatchRequest */
        [$template, $dispatchRequest] = DB::transaction(function () use (
            $data,
            $requesterId,
            $departAt,
            $arriveBy,
            $arriveOffset,
            $recurrenceTime,
            $startDate,
            $returnTime,
            $finalUrgent,
            $urgentTrigger,
            $urgentReasonTrim,
            $user,
        ) {
            $template = DispatchRequestTemplate::create([
                'requester_id' => $requesterId,
                'dispatch_package_id' => $data['dispatch_package_id'] ?? null,
                'is_active' => true,
                'trip_type' => $data['trip_type'],
                'origin' => $data['origin'] ?? null,
                'destination' => $data['destination'] ?? null,
                'passenger_count' => $data['passenger_count'] ?? null,
                'notes' => $data['notes'] ?? null,
                'arrive_offset_minutes' => $arriveOffset,
                'recurrence_rule' => $data['recurrence_rule'],
                'recurrence_end_date' => $data['recurrence_end_date'] ?? null,
                'repeat_count' => $data['repeat_count'] ?? null,
                'recurrence_time' => $recurrenceTime,
                'start_date' => $startDate,
                'return_time' => $returnTime,
                'wizard_snapshot' => $data['wizard_snapshot'] ?? null,
            ]);

            $dispatchRequest = DispatchRequest::create([
                'requester_id' => $requesterId,
                'dispatch_request_template_id' => $template->id,
                'trip_type' => $data['trip_type'],
                'origin' => $data['origin'] ?? null,
                'destination' => $data['destination'] ?? null,
                'depart_at' => $departAt,
                'arrive_by' => $arriveBy,
                'passenger_count' => $data['passenger_count'] ?? null,
                'notes' => $data['notes'] ?? null,
                'wizard_snapshot' => $data['wizard_snapshot'] ?? null,
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

            return [$template, $dispatchRequest];
        });

        $template->refresh();

        $planLabel = trim((string) ($data['plan_label'] ?? data_get($data, 'wizard_snapshot.form.plan_name', '')));
        if ($planLabel !== '' && self::payloadIsExtracurricularRecurring($data)) {
            $sessionCount = max(1, app(DispatchRecurringMaintenanceService::class)->countTargetOccurrences($template));
            $pkg = DispatchPackage::create([
                'trip_type' => $template->trip_type,
                'label' => $planLabel,
                'total_sessions' => $sessionCount,
                'sessions_used' => 0,
            ]);
            $template->update(['dispatch_package_id' => $pkg->id]);
            $template->refresh();
        }

        app(DispatchRecurringMaintenanceService::class)->materializeForTemplate($template);

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

        return $this->created([
            'dispatch_request_template_id' => $template->id,
            'dispatch_request' => $this->presentDispatchRequest($dispatchRequest->fresh()),
        ]);
    }

    public function updatePortal(
        UpdatePortalDispatchRequestTemplateRequest $request,
        DispatchRequestTemplate $dispatchRequestTemplate,
    ) {
        return $this->applyTemplateUpdate($request, $dispatchRequestTemplate);
    }

    public function updatePortalPlanLabel(
        UpdatePortalDispatchPlanLabelRequest $request,
        DispatchRequestTemplate $dispatchRequestTemplate,
    ) {
        $label = trim((string) $request->validated()['plan_label']);
        $template = $dispatchRequestTemplate->load('dispatchPackage');
        $user = $request->user();
        $beforeLabel = $template->dispatchPackage?->label
            ?? data_get($template->wizard_snapshot, 'form.plan_name');

        DB::transaction(function () use ($template, $label) {
            if ($template->dispatch_package_id === null) {
                $sessionCount = max(1, app(DispatchRecurringMaintenanceService::class)->countTargetOccurrences($template));
                $pkg = DispatchPackage::create([
                    'trip_type' => $template->trip_type,
                    'label' => $label,
                    'total_sessions' => $sessionCount,
                    'sessions_used' => 0,
                ]);
                $template->update(['dispatch_package_id' => $pkg->id]);
                $template->refresh();
            } else {
                DispatchPackage::query()
                    ->whereKey((int) $template->dispatch_package_id)
                    ->update(['label' => $label]);
            }

            $snap = $template->wizard_snapshot;
            if (! is_array($snap)) {
                $snap = [];
            }
            data_set($snap, 'form.plan_name', $label);
            $template->update(['wizard_snapshot' => $snap]);

            DispatchRequest::query()
                ->where('dispatch_request_template_id', $template->id)
                ->orderBy('id')
                ->each(function (DispatchRequest $req) use ($label) {
                    $rs = $req->wizard_snapshot;
                    if (! is_array($rs)) {
                        $rs = [];
                    }
                    data_set($rs, 'form.plan_name', $label);
                    $req->update(['wizard_snapshot' => $rs]);
                });
        });

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'template.plan_label_updated',
            auditable: $template,
            before: ['label' => $beforeLabel],
            after: ['label' => $label],
        );

        return $this->ok([
            'dispatch_request_template_id' => $template->id,
            'plan_label' => $label,
        ]);
    }

    protected function applyTemplateUpdate(
        UpdatePortalDispatchRequestTemplateRequest|StoreDispatchRequestTemplateRequest $request,
        DispatchRequestTemplate $template,
    ) {
        $data = $request->validated();

        $departAt = Carbon::parse($data['depart_at']);
        $arriveOffset = null;
        if (! empty($data['arrive_by'])) {
            $arriveBy = Carbon::parse($data['arrive_by']);
            $delta = $departAt->diffInMinutes($arriveBy, false);
            if ($delta < 0) {
                abort(422, 'Giờ đến phải sau hoặc cùng giờ khởi hành.');
            }
            $arriveOffset = (int) $delta;
        } elseif (! empty($data['return_time'])) {
            $departMinutes = (int) $departAt->format('H') * 60 + (int) $departAt->format('i');
            $returnParts = explode(':', (string) $data['return_time']);
            $returnMinutes = ((int) ($returnParts[0] ?? 0)) * 60 + ((int) ($returnParts[1] ?? 0));
            $delta = $returnMinutes - $departMinutes;
            if ($delta < 0) {
                $delta += 24 * 60;
            }
            $arriveOffset = $delta;
        }

        $recurrenceTime = $departAt->format('H:i:s');
        $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date'])->toDateString() : $departAt->toDateString();
        $returnTime = isset($data['return_time']) ? (strlen((string) $data['return_time']) === 5
            ? $data['return_time'].':00'
            : $data['return_time']) : null;

        $user = $request->user();
        $before = $template->toArray();

        $template->update([
            'trip_type' => $data['trip_type'],
            'origin' => $data['origin'] ?? null,
            'destination' => $data['destination'] ?? null,
            'passenger_count' => $data['passenger_count'] ?? null,
            'notes' => $data['notes'] ?? null,
            'arrive_offset_minutes' => $arriveOffset,
            'recurrence_rule' => $data['recurrence_rule'],
            'recurrence_end_date' => $data['recurrence_end_date'] ?? null,
            'repeat_count' => $data['repeat_count'] ?? null,
            'recurrence_time' => $recurrenceTime,
            'start_date' => $startDate,
            'return_time' => $returnTime,
            'wizard_snapshot' => $data['wizard_snapshot'] ?? $template->wizard_snapshot,
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'template.updated',
            auditable: $template,
            before: $before,
            after: $template->fresh()->toArray(),
        );

        $sync = app(DispatchRecurringMaintenanceService::class)->syncInstancesForTemplate($template->fresh());

        return $this->ok([
            'dispatch_request_template_id' => $template->id,
            'sync' => $sync,
        ]);
    }
}
