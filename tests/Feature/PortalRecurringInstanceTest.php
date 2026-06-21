<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\DispatchRequestTemplate;
use App\Models\Role;
use App\Models\User;
use App\Notifications\RecurringStudentCountSubmittedNotification;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PortalRecurringInstanceTest extends TestCase
{
    use RefreshDatabase;

    private function recurringRequestFor(User $requester): DispatchRequest
    {
        $template = DispatchRequestTemplate::create([
            'requester_id' => $requester->id,
            'is_active' => true,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'recurrence_rule' => ['freq' => 'weekly', 'interval' => 1, 'byweekday' => [1, 2, 3, 4, 5, 6]],
            'recurrence_time' => '08:00:00',
            'start_date' => now()->toDateString(),
            'recurrence_end_date' => now()->addDays(5)->toDateString(),
            'return_time' => '17:00:00',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        return DispatchRequest::create([
            'requester_id' => $requester->id,
            'dispatch_request_template_id' => $template->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(3),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);
    }

    public function test_portal_patch_and_submit_recurring_instance(): void
    {
        $this->seed(RbacSeeder::class);
        Notification::fake();

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        User::factory()->create(['is_active' => true])->assignRole('dispatcher');
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $dr = $this->recurringRequestFor($requester);

        $this->actingAs($requester);

        $this->patchJson("/api/portal/dispatch-requests/{$dr->id}/recurring-instance", [
            'student_count_actual' => 22,
            'origin' => 'Pickup X',
            'destination' => 'Dropoff Y',
        ])->assertOk()
            ->assertJsonPath('data.origin', 'Pickup X');

        $dr->refresh();
        $this->assertSame(22, (int) $dr->passenger_count);

        // Chốt số học sinh yêu cầu phiếu point_to_point đã có trưởng đơn vị được gán.
        $head = User::factory()->create(['is_active' => true]);
        $head->assignRole('department_head');
        $dr->update(['assigned_dept_head_id' => $head->id]);

        $this->postJson("/api/portal/dispatch-requests/{$dr->id}/submit-recurring")
            ->assertOk();

        $dr->refresh();
        $this->assertSame(22, (int) $dr->student_count_actual);
        $this->assertNotNull($dr->student_count_submitted_at);

        Notification::assertSentTo(
            User::role('dispatcher')->get(),
            RecurringStudentCountSubmittedNotification::class,
        );
        Notification::assertSentTo($admin, RecurringStudentCountSubmittedNotification::class);
    }

    public function test_portal_patch_recurring_instance_persists_dept_head(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $head = User::factory()->create(['is_active' => true, 'email' => 'head.clb@example.test']);
        $head->assignRole('department_head');

        $dr = $this->recurringRequestFor($requester);

        $this->actingAs($requester);

        $this->patchJson("/api/portal/dispatch-requests/{$dr->id}/recurring-instance", [
            'dept_head_user_id' => $head->id,
        ])->assertOk()
            ->assertJsonPath('data.assigned_dept_head_id', $head->id);

        $dr->refresh();
        $this->assertSame($head->id, $dr->assigned_dept_head_id);
    }

    public function test_portal_patch_response_includes_wizard_snapshot_for_bm03(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $dr = $this->recurringRequestFor($requester);

        $this->actingAs($requester);

        $this->patchJson("/api/portal/dispatch-requests/{$dr->id}/recurring-instance", [
            'wizard_snapshot' => [
                'form' => [
                    'point_purpose_kind' => 'extracurricular',
                    'purpose' => 'CLB bóng đá',
                    'requester_name' => 'Nguyễn A',
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.wizard_snapshot.form.purpose', 'CLB bóng đá')
            ->assertJsonPath('data.wizard_snapshot.form.requester_name', 'Nguyễn A');
    }

    public function test_portal_only_user_without_update_own_permission_may_patch_student_count(): void
    {
        $this->seed(RbacSeeder::class);

        Role::query()->firstOrCreate(
            ['name' => 'portal_club_coordinator', 'guard_name' => 'web'],
            ['display_name' => 'Điều phối CLB (portal)'],
        );

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('portal_club_coordinator');
        $this->assertFalse($requester->canAccessDispatchWebApp());

        $dr = $this->recurringRequestFor($requester);

        $this->actingAs($requester);

        $this->patchJson("/api/portal/dispatch-requests/{$dr->id}/recurring-instance", [
            'student_count_actual' => 18,
        ])->assertOk()
            ->assertJsonPath('data.student_count_actual', 18);
    }

    public function test_portal_patch_rejected_within_24h_of_depart(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $dr = $this->recurringRequestFor($requester);
        $dr->update(['depart_at' => now()->addHours(12)]);

        $this->actingAs($requester);

        $this->patchJson("/api/portal/dispatch-requests/{$dr->id}/recurring-instance", [
            'student_count_actual' => 10,
        ])->assertUnprocessable();
    }
}
