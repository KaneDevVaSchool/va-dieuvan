<?php

namespace App\Services\RecurringDispatch;

use App\Models\DispatchPackage;
use App\Models\DispatchRequestTemplate;
use App\Models\Role;
use App\Models\Trip;
use App\Models\TripCost;
use App\Models\User;
use App\Notifications\RecurringBudgetExceededNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class RecurringBudgetAlertService
{
    /**
     * @return array<string, mixed>|null
     */
    public function computeMonthlyAlertForPackage(DispatchPackage $package, ?Carbon $month = null): ?array
    {
        $budget = $package->monthly_budget;
        if ($budget === null || (float) $budget <= 0) {
            return null;
        }

        $timezone = config('app.timezone') ?: 'UTC';
        $month = ($month ?? Carbon::now($timezone))->copy()->timezone($timezone);
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $templateIds = DispatchRequestTemplate::query()
            ->where('dispatch_package_id', $package->id)
            ->pluck('id');

        if ($templateIds->isEmpty()) {
            return null;
        }

        $monthlyCost = (float) TripCost::query()
            ->join('trips', 'trip_costs.trip_id', '=', 'trips.id')
            ->join('dispatch_requests', 'trips.dispatch_request_id', '=', 'dispatch_requests.id')
            ->whereIn('dispatch_requests.dispatch_request_template_id', $templateIds)
            ->whereBetween('dispatch_requests.depart_at', [$from, $to])
            ->where('trips.status', '!=', 'cancelled')
            ->sum('trip_costs.amount');

        $budgetFloat = (float) $budget;
        if ($monthlyCost <= $budgetFloat) {
            return null;
        }

        $label = (string) ($package->label !== null && $package->label !== '' ? $package->label : $package->trip_type);

        return [
            'severity' => 'exceeded',
            'package_label' => $label,
            'month' => (int) $month->month,
            'year' => (int) $month->year,
            'monthly_cost' => round($monthlyCost, 2),
            'monthly_budget' => round($budgetFloat, 2),
        ];
    }

    public function refreshAndNotifyForTrip(Trip $trip): void
    {
        $trip->loadMissing('dispatchRequest.dispatchRequestTemplate');
        $dr = $trip->dispatchRequest;
        if ($dr === null || $dr->dispatch_request_template_id === null) {
            return;
        }

        $template = $dr->dispatchRequestTemplate;
        if ($template === null || $template->dispatch_package_id === null) {
            return;
        }

        $pkg = DispatchPackage::query()->find((int) $template->dispatch_package_id);
        if ($pkg === null) {
            return;
        }

        $alert = $this->computeMonthlyAlertForPackage($pkg, $dr->depart_at ? Carbon::parse($dr->depart_at) : null);
        if ($alert === null) {
            return;
        }

        $lastNotified = $pkg->last_budget_alert_notified_at;
        if ($lastNotified !== null && Carbon::parse($lastNotified)->greaterThan(now()->subDay())) {
            return;
        }

        $recipients = collect();
        $dr->loadMissing('requester');
        if ($dr->requester) {
            $recipients->push($dr->requester);
        }

        if (Role::query()->where('name', 'dispatcher')->where('guard_name', 'web')->exists()) {
            $recipients = $recipients->merge(
                User::query()->role('dispatcher')->get()
            );
        }

        $recipients = $recipients->unique('id')->values();
        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new RecurringBudgetExceededNotification(
            dispatchPackageId: (int) $pkg->id,
            label: (string) $alert['package_label'],
            month: (int) $alert['month'],
            monthlyCost: (float) $alert['monthly_cost'],
            monthlyBudget: (float) $alert['monthly_budget'],
        ));

        $pkg->forceFill(['last_budget_alert_notified_at' => now()])->saveQuietly();
    }
}
