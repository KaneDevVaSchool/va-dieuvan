<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExtracurricularRecurringStaffListTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_requests_index_hides_extracurricular_recurring_until_portal_submit(): void
    {
        $this->seed(RbacSeeder::class);
        $tz = config('app.timezone') ?: 'UTC';
        Carbon::setTestNow(Carbon::parse('2026-05-20 10:00:00', $tz));

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');

        $this->actingAs($requester);

        $payload = [
            'trip_type' => 'point_to_point',
            'source_channel' => 'portal',
            'origin' => 'Đón A',
            'destination' => 'Trả B',
            'depart_at' => Carbon::parse('2026-05-23 07:00:00', $tz)->toIso8601String(),
            'start_date' => '2026-05-23',
            'return_time' => '17:00',
            'recurrence_rule' => [
                'freq' => 'weekly',
                'interval' => 1,
                'byweekday' => [4],
            ],
            'recurrence_end_date' => '2026-05-30',
            'plan_label' => 'CLB Hiển thị admin',
            'wizard_snapshot' => [
                'form' => [
                    'point_purpose_kind' => 'extracurricular',
                    'trip_type' => 'point_to_point',
                    'plan_name' => 'CLB Hiển thị admin',
                ],
            ],
        ];

        $create = $this->postJson('/api/portal/dispatch-request-templates', $payload);
        $create->assertCreated();

        $instanceId = (int) DispatchRequest::query()
            ->whereNotNull('dispatch_request_template_id')
            ->orderBy('id')
            ->value('id');
        $this->assertGreaterThan(0, $instanceId);

        $this->actingAs($dispatcher);

        $hidden = $this->getJson('/api/requests?per_page=50');
        $hidden->assertOk();
        $ids = collect($hidden->json('data.items'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertFalse($ids->contains($instanceId));

        $this->actingAs($requester);
        $this->patchJson("/api/portal/dispatch-requests/{$instanceId}/recurring-instance", [
            'student_count_actual' => 15,
        ])->assertOk();
        $this->postJson("/api/portal/dispatch-requests/{$instanceId}/submit-recurring")
            ->assertOk();

        $this->actingAs($dispatcher);

        $visible = $this->getJson('/api/requests?per_page=50');
        $visible->assertOk();
        $idsAfter = collect($visible->json('data.items'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertTrue($idsAfter->contains($instanceId));
    }
}
