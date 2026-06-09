<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\User;
use App\Support\Messages;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DispatchRequestApprovalConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_second_approve_after_trip_exists_returns_409(): void
    {
        $this->seed(RbacSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-06-29 08:00:00'));

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'door_to_door',
            'depart_at' => now()->addHours(3),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
        ]);

        $this->actingAs($dispatcher);

        $first = $this->postJson("/api/dispatch-requests/{$dr->id}/decision", [
            'decision' => 'approve',
        ]);
        $first->assertSuccessful();

        $second = $this->postJson("/api/dispatch-requests/{$dr->id}/decision", [
            'decision' => 'approve',
        ]);
        $second->assertStatus(409);
        $second->assertJsonFragment(['message' => Messages::TRIP_ALREADY_EXISTS_FOR_REQUEST]);

        $this->assertSame(1, Trip::query()->where('dispatch_request_id', $dr->id)->count());
    }

    public function test_trip_status_transition_rejects_invalid_jump(): void
    {
        $this->seed(RbacSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('dispatcher');

        $dr = DispatchRequest::create([
            'requester_id' => $user->id,
            'trip_type' => 'point_to_point',
            'depart_at' => now()->addDay(),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'status' => 'assigned',
            'depart_at' => $dr->depart_at,
            'lock_version' => 0,
        ]);

        $this->actingAs($user);

        $res = $this->postJson("/api/trips/{$trip->id}/status", [
            'status' => 'completed',
            'lock_version' => 0,
        ]);
        $res->assertStatus(422);
    }
}
