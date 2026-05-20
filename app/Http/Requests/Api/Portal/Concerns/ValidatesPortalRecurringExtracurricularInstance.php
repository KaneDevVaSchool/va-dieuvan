<?php

namespace App\Http\Requests\Api\Portal\Concerns;

use App\Models\DispatchRequest;
use App\Support\Messages;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

trait ValidatesPortalRecurringExtracurricularInstance
{
    protected function isExtracurricularRecurringInstance(DispatchRequest $dr): bool
    {
        if ($dr->dispatch_request_template_id === null) {
            return false;
        }

        $snap = $dr->wizard_snapshot;
        $purposeKind = is_array($snap)
            ? (data_get($snap, 'form.point_purpose_kind') ?? data_get($snap, 'point_purpose_kind'))
            : null;

        return $purposeKind === 'extracurricular';
    }

    protected function validatePortalRecurringInstanceBase(Validator $v, DispatchRequest $dr): void
    {
        if ($dr->trashed()) {
            $v->errors()->add('dispatchRequest', 'Phiếu không tồn tại.');
        }

        if ($dr->dispatch_request_template_id === null) {
            $v->errors()->add('dispatch_request_template_id', Messages::REQUEST_NOT_RECURRING_INSTANCE);
        }

        if (! $this->isExtracurricularRecurringInstance($dr)) {
            $v->errors()->add('wizard_snapshot', Messages::REQUEST_RECURRING_TEMPLATE_EXTRACURRICULAR_ONLY);
        }

    }

    protected function validatePortalRecurringWithin24hOrSubmitted(Validator $v, DispatchRequest $dr, string $field = 'depart_at'): void
    {
        if ($dr->student_count_submitted_at !== null) {
            $v->errors()->add('student_count_submitted_at', Messages::REQUEST_RECURRING_STUDENT_COUNT_ALREADY_SUBMITTED);

            return;
        }

        if ($dr->depart_at === null) {
            return;
        }

        try {
            $departAt = Carbon::parse($dr->depart_at);
        } catch (\Throwable) {
            return;
        }

        if (! $departAt->greaterThan(now()->copy()->addHours(24))) {
            if ($dr->locked_at === null) {
                $dr->forceFill(['locked_at' => now()])->saveQuietly();
            }
            $v->errors()->add($field, Messages::REQUEST_RECURRING_PASSENGER_COUNT_LOCKED);
        }
    }
}
