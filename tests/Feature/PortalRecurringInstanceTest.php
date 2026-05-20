<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\DispatchRequestTemplate;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $dr = $this->recurringRequestFor($requester);

        $this->actingAs($requester);

        $this->patchJson("/api/portal/dispatch-requests/{$dr->id}/recurring-instance", [
            'student_count_actual' => 22,
            'origin' => 'Pickup X',
            'destination' => 'Dropoff Y',
        ])->assertOk()
            ->assertJsonPath('data.origin', 'Pickup X');

        $this->postJson("/api/portal/dispatch-requests/{$dr->id}/submit-recurring")
            ->assertOk();

        $dr->refresh();
        $this->assertSame(22, (int) $dr->student_count_actual);
        $this->assertNotNull($dr->student_count_submitted_at);
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
