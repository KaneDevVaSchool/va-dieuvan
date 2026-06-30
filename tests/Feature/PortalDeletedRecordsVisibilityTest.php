<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\DispatchRequestTemplate;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewDispatchRequestNotification;
use App\Services\RecurringDispatch\DispatchRecurringMaintenanceService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PortalDeletedRecordsVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_list_excludes_soft_deleted_and_cancelled_requests(): void
    {
        $this->seed(RbacSeeder::class);

        Role::query()->firstOrCreate(
            ['name' => 'portal_club_coordinator', 'guard_name' => 'web'],
            ['display_name' => 'Điều phối CLB (portal)'],
        );

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('portal_club_coordinator');

        $active = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDay(),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        $cancelled = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'point_to_point',
            'origin' => 'C',
            'destination' => 'D',
            'depart_at' => now()->addDays(2),
            'status' => 'cancelled',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        $trashed = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'point_to_point',
            'origin' => 'E',
            'destination' => 'F',
            'depart_at' => now()->addDays(3),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);
        $trashed->delete();

        $this->actingAs($requester);

        $response = $this->getJson('/api/portal/dispatch-requests?extracurricular_only=1&per_page=50');
        $response->assertOk();

        $ids = collect($response->json('data.items'))->pluck('id')->all();
        $this->assertContains($active->id, $ids);
        $this->assertNotContains($cancelled->id, $ids);
        $this->assertNotContains($trashed->id, $ids);
    }

    public function test_portal_notifications_exclude_removed_dispatch_requests(): void
    {
        $requester = User::factory()->create(['is_active' => true]);

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDay(),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
        ]);

        Notification::send($requester, new NewDispatchRequestNotification($dr->id, 'A → B', false));

        $this->actingAs($requester);
        $this->getJson('/api/portal/notifications?per_page=20')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);

        $dr->delete();

        $this->getJson('/api/portal/notifications?per_page=20')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 0);
    }

    public function test_materialize_does_not_recreate_soft_deleted_recurring_instance(): void
    {
        $tz = config('app.timezone') ?: 'UTC';
        Carbon::setTestNow(Carbon::parse('2026-05-20 08:00:00', $tz));

        $requester = User::factory()->create(['is_active' => true]);
        $start = '2026-05-21';

        $template = DispatchRequestTemplate::create([
            'requester_id' => $requester->id,
            'is_active' => true,
            'trip_type' => 'point_to_point',
            'origin' => 'Đón A',
            'destination' => 'Trả B',
            'recurrence_rule' => [
                'freq' => 'weekly',
                'interval' => 1,
                'byweekday' => [4],
            ],
            'recurrence_time' => '08:00:00',
            'start_date' => $start,
            'recurrence_end_date' => '2026-05-21',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        $instance = DispatchRequest::create([
            'requester_id' => $requester->id,
            'dispatch_request_template_id' => $template->id,
            'trip_type' => 'point_to_point',
            'origin' => 'Đón A',
            'destination' => 'Trả B',
            'depart_at' => Carbon::parse("{$start} 08:00:00", $tz),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        $instance->delete();

        $service = app(DispatchRecurringMaintenanceService::class);
        $created = $service->materializeForTemplate($template->fresh());

        $this->assertSame(0, $created);
        $this->assertSame(
            0,
            DispatchRequest::query()->where('dispatch_request_template_id', $template->id)->count(),
        );
    }
}
