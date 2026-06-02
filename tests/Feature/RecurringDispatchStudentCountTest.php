<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\DispatchRequestTemplate;
use App\Models\User;
use App\Notifications\RecurringStudentCountSubmittedNotification;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecurringDispatchStudentCountTest extends TestCase
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
            'passenger_count' => 30,
            'recurrence_rule' => ['freq' => 'weekly', 'interval' => 1, 'byweekday' => [1]],
            'recurrence_time' => '08:00:00',
            'start_date' => now()->toDateString(),
            'repeat_count' => 4,
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        return DispatchRequest::create([
            'requester_id' => $requester->id,
            'dispatch_request_template_id' => $template->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(3),
            'passenger_count' => 30,
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);
    }

    public function test_requester_can_patch_then_submit_student_count(): void
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

        $this->patchJson("/api/dispatch-requests/{$dr->id}/passenger-count", [
            'student_count_actual' => 25,
        ])->assertOk();

        $this->postJson("/api/dispatch-requests/{$dr->id}/submit-student-count")
            ->assertOk();

        $dr->refresh();
        $this->assertSame(25, (int) $dr->student_count_actual);
        $this->assertNotNull($dr->student_count_submitted_at);
        $this->assertSame((int) $requester->id, (int) $dr->student_count_submitted_by);

        Notification::assertSentTo(
            User::role('dispatcher')->get(),
            RecurringStudentCountSubmittedNotification::class,
        );
        Notification::assertSentTo($admin, RecurringStudentCountSubmittedNotification::class);

        $this->patchJson("/api/dispatch-requests/{$dr->id}/passenger-count", [
            'student_count_actual' => 20,
        ])->assertUnprocessable();
    }

    public function test_cannot_submit_without_saved_count(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $dr = $this->recurringRequestFor($requester);

        $this->actingAs($requester);

        $this->postJson("/api/dispatch-requests/{$dr->id}/submit-student-count")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_count_actual']);
    }

    public function test_dispatcher_cannot_patch_student_count(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');

        $dr = $this->recurringRequestFor($requester);

        $this->actingAs($dispatcher);

        $this->patchJson("/api/dispatch-requests/{$dr->id}/passenger-count", [
            'student_count_actual' => 18,
        ])->assertForbidden();
    }

    public function test_cannot_patch_within_24_hours_of_departure(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $template = DispatchRequestTemplate::create([
            'requester_id' => $requester->id,
            'is_active' => true,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'passenger_count' => 30,
            'recurrence_rule' => ['freq' => 'weekly', 'interval' => 1, 'byweekday' => [1]],
            'recurrence_time' => '08:00:00',
            'start_date' => now()->toDateString(),
            'repeat_count' => 4,
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'dispatch_request_template_id' => $template->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addHours(12),
            'passenger_count' => 30,
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        $this->actingAs($requester);

        $this->patchJson("/api/dispatch-requests/{$dr->id}/passenger-count", [
            'student_count_actual' => 25,
        ])->assertUnprocessable();
    }
}
