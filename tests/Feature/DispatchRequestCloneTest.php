<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchRequestCloneTest extends TestCase
{
    use RefreshDatabase;

    public function test_clone_creates_pending_copy_and_clears_workflow_fields(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $head = User::factory()->create(['is_active' => true]);
        $head->assignRole('department_head');

        $source = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(10),
            'status' => 'rejected',
            'rejection_reason' => 'Test reject',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'received',
            'paper_reference' => 'BM-OLD',
            'service_price' => 500000,
            'assigned_dept_head_id' => $head->id,
            'signing_workflow_status' => 'completed',
            'verification_status' => 'verified',
            'signature_detected' => true,
            'signature_verified' => true,
            'wizard_snapshot' => ['form' => ['purpose' => 'test']],
        ]);

        $this->actingAs($requester);

        $response = $this->postJson("/api/dispatch-requests/{$source->id}/clone", [], [
            'Idempotency-Key' => 'clone-test-'.uniqid('', true),
        ]);

        $response->assertCreated();
        $newId = (int) $response->json('data.id');
        $this->assertNotSame($source->id, $newId);

        $clone = DispatchRequest::query()->findOrFail($newId);
        $this->assertSame('pending', $clone->status);
        $this->assertSame($source->id, $clone->cloned_from_id);
        $this->assertNull($clone->service_price);
        $this->assertNull($clone->rejection_reason);
        $this->assertNull($clone->assigned_dept_head_id);
        $this->assertSame('pending', $clone->paper_status);
        $this->assertNull($clone->paper_reference);
        $this->assertNull($clone->signing_workflow_status);
        $this->assertNull($clone->verification_status);
        $this->assertNull($clone->signature_detected);
        $this->assertNull($clone->signature_verified);
        $this->assertSame('test', data_get($clone->wizard_snapshot, 'form.purpose'));
    }

    public function test_clone_rejected_when_source_not_terminal_status(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $source = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(3),
            'status' => 'price_filled',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
        ]);

        $this->actingAs($requester);

        $this->postJson("/api/dispatch-requests/{$source->id}/clone", [], [
            'Idempotency-Key' => 'clone-invalid-'.uniqid('', true),
        ])->assertUnprocessable();
    }
}
