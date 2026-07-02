<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalDestroyPendingDispatchRequestTest extends TestCase
{
    use RefreshDatabase;

    private function portalPendingRequest(User $requester): DispatchRequest
    {
        return DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(2),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);
    }

    public function test_requester_can_soft_delete_pending_portal_request(): void
    {
        $requester = User::factory()->create(['is_active' => true]);
        $dr = $this->portalPendingRequest($requester);

        $this->actingAs($requester);

        $this->deleteJson('/api/portal/dispatch-requests/'.$dr->id)
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertSoftDeleted('dispatch_requests', ['id' => $dr->id]);

        $response = $this->getJson('/api/portal/dispatch-requests?per_page=50');
        $response->assertOk();
        $ids = collect($response->json('data.items'))->pluck('id')->all();
        $this->assertNotContains($dr->id, $ids);
    }

    public function test_requester_can_delete_price_filled_pending_approval(): void
    {
        $requester = User::factory()->create(['is_active' => true]);
        $dr = $this->portalPendingRequest($requester);
        $dr->update(['status' => 'price_filled', 'price_filled_at' => now()]);

        $this->actingAs($requester);

        $this->deleteJson('/api/portal/dispatch-requests/'.$dr->id)
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertSoftDeleted('dispatch_requests', ['id' => $dr->id]);
    }

    public function test_cannot_delete_approved_request_from_portal(): void
    {
        $requester = User::factory()->create(['is_active' => true]);
        $dr = $this->portalPendingRequest($requester);
        $dr->update(['status' => 'approved']);

        $this->actingAs($requester);

        $this->deleteJson('/api/portal/dispatch-requests/'.$dr->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('dispatch_requests', ['id' => $dr->id, 'deleted_at' => null]);
    }

    public function test_other_user_cannot_delete_portal_request(): void
    {
        $requester = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);
        $dr = $this->portalPendingRequest($requester);

        $this->actingAs($other);

        $this->deleteJson('/api/portal/dispatch-requests/'.$dr->id)
            ->assertForbidden();
    }

    public function test_dispatch_staff_cannot_use_portal_destroy_even_as_requester(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('dispatcher');

        $dr = $this->portalPendingRequest($requester);

        $this->actingAs($requester);

        $this->deleteJson('/api/portal/dispatch-requests/'.$dr->id)
            ->assertForbidden();
    }
}
