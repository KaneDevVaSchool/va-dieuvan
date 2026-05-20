<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\DispatchRequestTemplate;
use App\Models\User;
use App\Services\RecurringDispatch\DispatchRecurringMaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PortalRecurringSyncInstancesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_cancels_pending_outside_new_range_and_keeps_submitted(): void
    {
        $tz = config('app.timezone') ?: 'UTC';
        Carbon::setTestNow(Carbon::parse('2026-05-20 08:00:00', $tz));

        $requester = User::factory()->create(['is_active' => true]);

        $template = DispatchRequestTemplate::create([
            'requester_id' => $requester->id,
            'is_active' => true,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'recurrence_rule' => ['freq' => 'weekly', 'interval' => 1, 'byweekday' => [1, 2, 3, 4, 5, 6]],
            'recurrence_time' => '08:00:00',
            'start_date' => '2026-05-21',
            'recurrence_end_date' => '2026-05-26',
            'return_time' => '17:00:00',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        $service = app(DispatchRecurringMaintenanceService::class);
        $service->materializeForTemplate($template);

        $submitted = DispatchRequest::query()
            ->where('dispatch_request_template_id', $template->id)
            ->orderBy('depart_at')
            ->first();
        $submitted->update([
            'student_count_actual' => 20,
            'student_count_submitted_at' => now(),
        ]);

        $template->update(['recurrence_end_date' => '2026-05-23']);
        $sync = $service->syncInstancesForTemplate($template->fresh());

        $this->assertGreaterThanOrEqual(1, $sync['cancelled']);

        $this->assertNotNull(
            DispatchRequest::query()->whereKey($submitted->id)->whereNotNull('student_count_submitted_at')->first(),
        );

        $active = DispatchRequest::query()
            ->where('dispatch_request_template_id', $template->id)
            ->where('status', 'pending')
            ->count();

        $this->assertLessThanOrEqual(3, $active);
    }
}
