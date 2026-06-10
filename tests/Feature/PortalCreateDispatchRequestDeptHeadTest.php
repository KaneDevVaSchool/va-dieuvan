<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalCreateDispatchRequestDeptHeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_create_requires_dept_head_for_non_d2d_when_role_exists(): void
    {
        $this->seed(RbacSeeder::class);

        User::factory()->create(['is_active' => true])->assignRole('department_head');

        $requester = User::factory()->create(['is_active' => true]);

        $this->actingAs($requester);

        $this->postJson('/api/portal/dispatch-requests', [
            'trip_type' => 'business',
            'depart_at' => now()->addDays(3)->toIso8601String(),
            'wizard_snapshot' => ['form' => ['purpose' => 'Test']],
        ], ['Idempotency-Key' => 'portal-dept-head-missing'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dept_head_user_id']);
    }

    public function test_portal_create_persists_assigned_dept_head(): void
    {
        $this->seed(RbacSeeder::class);

        $head = User::factory()->create(['is_active' => true, 'email' => 'head.portal@example.test']);
        $head->assignRole('department_head');

        $requester = User::factory()->create(['is_active' => true]);

        $this->actingAs($requester);

        $depart = now()->addDays(30);

        $response = $this->postJson('/api/portal/dispatch-requests', [
            'trip_type' => 'business',
            'depart_at' => $depart->toIso8601String(),
            'dept_head_user_id' => $head->id,
            'wizard_snapshot' => ['form' => ['purpose' => 'Họp']],
        ], ['Idempotency-Key' => 'portal-dept-head-ok']);

        $response->assertCreated();
        $id = (int) $response->json('data.id');
        $fresh = DispatchRequest::query()->findOrFail($id);
        $this->assertSame($head->id, $fresh->assigned_dept_head_id);
        $this->assertSame('pending', $fresh->status);
    }

    public function test_portal_extracurricular_recurring_template_does_not_require_dept_head(): void
    {
        $this->seed(RbacSeeder::class);

        User::factory()->create(['is_active' => true])->assignRole('department_head');

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $this->actingAs($requester);

        $tz = config('app.timezone') ?: 'UTC';
        $depart = now($tz)->addDays(7)->setTime(7, 0);

        $this->postJson('/api/portal/dispatch-request-templates', [
            'trip_type' => 'point_to_point',
            'source_channel' => 'portal',
            'origin' => 'Đón A',
            'destination' => 'Trả B',
            'depart_at' => $depart->toIso8601String(),
            'start_date' => $depart->toDateString(),
            'return_time' => '17:00',
            'recurrence_rule' => [
                'freq' => 'weekly',
                'interval' => 1,
                'byweekday' => [1, 2, 3, 4, 5],
            ],
            'recurrence_end_date' => $depart->copy()->addDays(6)->toDateString(),
            'plan_label' => 'CLB không cần trưởng BP lúc tạo',
            'wizard_snapshot' => [
                'form' => [
                    'point_purpose_kind' => 'extracurricular',
                    'trip_type' => 'point_to_point',
                    'plan_name' => 'CLB không cần trưởng BP lúc tạo',
                ],
            ],
        ], ['Idempotency-Key' => 'portal-extracurricular-no-dept-head'])
            ->assertCreated()
            ->assertJsonPath('data.dispatch_request.assigned_dept_head_id', null);
    }

    public function test_portal_dept_head_search_returns_active_heads(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true, 'name' => 'Nguyen Van A']);

        $head = User::factory()->create([
            'is_active' => true,
            'name' => 'Tran Truong BP',
            'email' => 'truong.bp@example.test',
        ]);
        $head->assignRole('department_head');

        $this->actingAs($requester);

        $this->getJson('/api/portal/users/dept-heads?q=Truong')
            ->assertOk()
            ->assertJsonPath('data.0.id', $head->id);
    }
}
