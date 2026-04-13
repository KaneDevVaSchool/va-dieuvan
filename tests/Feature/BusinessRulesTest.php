<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName): User
    {
        $this->seed(RbacSeeder::class);

        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user->refresh();
    }

    public function test_br001_request_must_be_created_at_least_two_hours_before_departure(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 08:00:00'));

        $user = $this->makeUserWithRole('internal_user');
        $this->actingAs($user);

        $res = $this->postJson('/api/dispatch-requests', [
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => '2026-04-02 09:30:00', // < 2h
        ]);

        $res->assertStatus(422);
    }

    public function test_optimistic_lock_conflict_returns_409(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 08:00:00'));

        $dispatcher = $this->makeUserWithRole('dispatcher');
        $this->actingAs($dispatcher);

        $req = DispatchRequest::create([
            'requester_id' => $dispatcher->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => Carbon::parse('2026-04-02 12:00:00'),
            'status' => 'approved',
            'approved_by' => $dispatcher->id,
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $req->id,
            'dispatcher_id' => $dispatcher->id,
            'status' => 'approved',
            'depart_at' => $req->depart_at,
            'arrive_by' => Carbon::parse('2026-04-02 14:00:00'),
            'lock_version' => 2,
        ]);

        $res = $this->postJson("/api/trips/{$trip->id}/assign", [
            'lock_version' => 1, // stale
        ]);

        $res->assertStatus(409);
    }
}
