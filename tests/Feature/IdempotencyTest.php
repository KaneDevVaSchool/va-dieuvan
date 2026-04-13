<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_idempotency_replays_dispatch_approve_with_same_key(): void
    {
        $this->seed(RbacSeeder::class);

        $dispatcher = User::factory()->create();
        $dispatcher->assignRole('dispatcher');

        $requester = User::factory()->create();
        $requester->assignRole('internal_user');

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'point_to_point',
            'depart_at' => now()->addHours(3),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
        ]);

        $this->actingAs($dispatcher);

        $key = 'idem-key-12345678901';
        $headers = ['Idempotency-Key' => $key];
        $payload = ['decision' => 'approve'];

        $first = $this->postJson("/api/dispatch-requests/{$dr->id}/decision", $payload, $headers);
        $first->assertSuccessful();
        $this->assertFalse($first->headers->has('X-Idempotent-Replayed'));

        $second = $this->postJson("/api/dispatch-requests/{$dr->id}/decision", $payload, $headers);
        $second->assertSuccessful();
        $second->assertHeader('X-Idempotent-Replayed', 'true');
        $this->assertSame($first->json(), $second->json());
    }

    public function test_idempotency_replays_dispatch_store_with_same_key(): void
    {
        $this->seed(RbacSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('internal_user');

        $this->actingAs($user);

        $key = 'idem-store-key-12';
        $payload = [
            'trip_type' => 'point_to_point',
            'depart_at' => now()->addHours(3)->toIso8601String(),
            'source_channel' => 'portal',
            'is_urgent' => false,
        ];
        $headers = ['Idempotency-Key' => $key];

        $first = $this->postJson('/api/dispatch-requests', $payload, $headers);
        $first->assertCreated();
        $this->assertFalse($first->headers->has('X-Idempotent-Replayed'));

        $second = $this->postJson('/api/dispatch-requests', $payload, $headers);
        $second->assertSuccessful();
        $second->assertHeader('X-Idempotent-Replayed', 'true');
        $this->assertSame($first->json(), $second->json());
        $this->assertEquals(1, DispatchRequest::count());
    }
}
