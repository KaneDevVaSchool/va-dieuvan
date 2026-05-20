<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\DispatchRequestTemplate;
use App\Models\User;
use App\Services\RecurringDispatch\DispatchRecurringMaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecurringDailyMaterializeTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_date_range_materializes_all_inclusive_days(): void
    {
        $tz = config('app.timezone') ?: 'UTC';
        Carbon::setTestNow(Carbon::parse('2026-05-20 08:00:00', $tz));

        $requester = User::factory()->create(['is_active' => true]);

        $start = '2026-05-21';
        $end = '2026-05-26';

        $template = DispatchRequestTemplate::create([
            'requester_id' => $requester->id,
            'is_active' => true,
            'trip_type' => 'point_to_point',
            'origin' => 'Đón A',
            'destination' => 'Trả B',
            'passenger_count' => 25,
            'recurrence_rule' => ['freq' => 'daily', 'interval' => 1],
            'recurrence_time' => '08:00:00',
            'start_date' => $start,
            'recurrence_end_date' => $end,
            'return_time' => '17:00:00',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        DispatchRequest::create([
            'requester_id' => $requester->id,
            'dispatch_request_template_id' => $template->id,
            'trip_type' => 'point_to_point',
            'origin' => 'Đón A',
            'destination' => 'Trả B',
            'depart_at' => Carbon::parse("{$start} 08:00:00", $tz),
            'passenger_count' => 25,
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        $service = app(DispatchRecurringMaintenanceService::class);
        $service->materializeForTemplate($template->fresh());

        $count = DispatchRequest::query()
            ->where('dispatch_request_template_id', $template->id)
            ->count();

        $this->assertSame(6, $count);

        $days = DispatchRequest::query()
            ->where('dispatch_request_template_id', $template->id)
            ->orderBy('depart_at')
            ->get()
            ->map(fn (DispatchRequest $dr) => $dr->depart_at->timezone($tz)->format('Y-m-d'))
            ->all();

        $this->assertSame(
            ['2026-05-21', '2026-05-22', '2026-05-23', '2026-05-24', '2026-05-25', '2026-05-26'],
            $days,
        );
    }
}
