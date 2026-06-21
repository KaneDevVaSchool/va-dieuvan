<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\DispatchRequestTemplate;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PortalRecurringBm03GroupSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_patch_bm03_syncs_shared_fields_to_siblings_but_not_per_trip_dates(): void
    {
        $this->seed(RbacSeeder::class);

        $tz = config('app.timezone') ?: 'UTC';
        Carbon::setTestNow(Carbon::parse('2026-05-20 08:00:00', $tz));

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

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
            'wizard_snapshot' => [
                'form' => [
                    'point_purpose_kind' => 'extracurricular',
                    'purpose' => 'Old purpose',
                ],
            ],
        ]);

        $depart1 = Carbon::parse('2026-05-25 08:00:00', $tz);
        $depart2 = Carbon::parse('2026-05-26 08:00:00', $tz);

        $a = DispatchRequest::create([
            'requester_id' => $requester->id,
            'dispatch_request_template_id' => $template->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => $depart1,
            'arrive_by' => $depart1->copy()->addHours(9),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [
                'form' => [
                    'point_purpose_kind' => 'extracurricular',
                    'purpose' => 'Old purpose',
                    'date_needed' => '2026-05-25',
                    'proposed_date' => '2026-05-01',
                ],
            ],
        ]);

        $b = DispatchRequest::create([
            'requester_id' => $requester->id,
            'dispatch_request_template_id' => $template->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => $depart2,
            'arrive_by' => $depart2->copy()->addHours(9),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [
                'form' => [
                    'point_purpose_kind' => 'extracurricular',
                    'purpose' => 'Old purpose',
                    'date_needed' => '2026-05-26',
                    'proposed_date' => '2026-05-02',
                ],
            ],
        ]);

        $this->actingAs($requester);

        $this->patchJson("/api/portal/dispatch-requests/{$a->id}/recurring-instance", [
            'origin' => 'Pickup shared',
            'destination' => 'Dropoff shared',
            'wizard_snapshot' => [
                'form' => [
                    'purpose' => 'Synced purpose',
                    'date_needed' => '2026-05-25',
                    'proposed_date' => '2026-05-99-should-not-sync',
                    'coordinator_name' => 'Coord A',
                ],
            ],
        ])->assertOk();

        $b->refresh();
        $this->assertSame('Synced purpose', data_get($b->wizard_snapshot, 'form.purpose'));
        $this->assertSame('Coord A', data_get($b->wizard_snapshot, 'form.coordinator_name'));
        $this->assertSame('2026-05-26', data_get($b->wizard_snapshot, 'form.date_needed'));
        $this->assertSame('2026-05-02', data_get($b->wizard_snapshot, 'form.proposed_date'));
        $this->assertSame('Pickup shared', $b->origin);
        $this->assertSame('Dropoff shared', $b->destination);
        $this->assertTrue($b->depart_at->equalTo($depart2));
        $this->assertTrue($b->arrive_by->equalTo($depart2->copy()->addHours(9)));
    }

    public function test_submit_stamps_proposed_date_to_today(): void
    {
        $this->seed(RbacSeeder::class);

        $tz = config('app.timezone') ?: 'UTC';
        Carbon::setTestNow(Carbon::parse('2026-05-20 08:00:00', $tz));

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $template = DispatchRequestTemplate::create([
            'requester_id' => $requester->id,
            'is_active' => true,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'recurrence_rule' => ['freq' => 'weekly', 'interval' => 1, 'byweekday' => [1]],
            'recurrence_time' => '08:00:00',
            'start_date' => '2026-05-21',
            'recurrence_end_date' => '2026-05-26',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'dispatch_request_template_id' => $template->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => Carbon::parse('2026-05-25 08:00:00', $tz),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'student_count_actual' => 15,
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        // Chốt số học sinh yêu cầu phiếu point_to_point đã có trưởng đơn vị được gán.
        $head = User::factory()->create(['is_active' => true]);
        $head->assignRole('department_head');
        $dr->update(['assigned_dept_head_id' => $head->id]);

        $this->actingAs($requester);

        $this->postJson("/api/portal/dispatch-requests/{$dr->id}/submit-recurring")
            ->assertOk();

        $dr->refresh();
        $this->assertSame('2026-05-20', data_get($dr->wizard_snapshot, 'form.proposed_date'));
    }
}
