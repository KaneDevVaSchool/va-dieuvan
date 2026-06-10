<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\TripCost;
use App\Models\User;
use App\Services\Costs\WizardSnapshotCostEstimator;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TripWizardCostProvisionOnCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_trip_creates_confirmed_wizard_estimate_when_dept_head_approved_request(): void
    {
        $this->seed(RbacSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-06-29 08:00:00'));

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');
        $deptHead = User::factory()->create(['is_active' => true]);

        $dr = DispatchRequest::create([
            'requester_id' => $dispatcher->id,
            'trip_type' => 'business',
            'origin' => 'HQ',
            'destination' => 'Site',
            'depart_at' => Carbon::parse('2026-06-29 07:00:00'),
            'status' => 'approved',
            'approved_by' => $deptHead->id,
            'assigned_dept_head_id' => $deptHead->id,
            'source_channel' => 'portal',
            'paper_status' => 'pending',
            'wizard_snapshot' => [
                'form' => [],
                'businessRows' => [
                    [
                        'unit_price' => '1000500',
                        'extra_fee' => '0',
                        'pickup' => 'A',
                        'dropoff' => 'B',
                    ],
                ],
            ],
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'dispatcher_id' => $dispatcher->id,
            'status' => 'in_progress',
            'depart_at' => $dr->depart_at,
            'lock_version' => 0,
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($dispatcher);

        $this->postJson("/api/trips/{$trip->id}/status", ['status' => 'completed'])->assertOk();

        $cost = TripCost::query()->where('trip_id', $trip->id)->first();
        $this->assertNotNull($cost);
        $this->assertSame('confirmed', $cost->status);
        $this->assertSame($deptHead->id, (int) $cost->confirmed_by);
    }

    public function test_completing_trip_creates_submitted_wizard_estimate_cost(): void
    {
        $this->seed(RbacSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-06-29 08:00:00'));

        $actor = User::factory()->create(['is_active' => true]);
        $actor->assignRole('dispatcher');

        $dr = DispatchRequest::create([
            'requester_id' => $actor->id,
            'trip_type' => 'business',
            'origin' => 'HQ',
            'destination' => 'Site',
            'depart_at' => Carbon::parse('2026-06-29 07:00:00'),
            'status' => 'approved',
            'approved_by' => $actor->id,
            'source_channel' => 'portal',
            'paper_status' => 'pending',
            'wizard_snapshot' => [
                'form' => [],
                'businessRows' => [
                    [
                        'unit_price' => '9000000',
                        'extra_fee' => '0',
                        'pickup' => 'A',
                        'dropoff' => 'B',
                    ],
                ],
            ],
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'dispatcher_id' => $actor->id,
            'status' => 'in_progress',
            'depart_at' => $dr->depart_at,
            'lock_version' => 0,
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($actor);

        $res = $this->postJson("/api/trips/{$trip->id}/status", ['status' => 'completed']);
        $res->assertOk();

        $cost = TripCost::query()->where('trip_id', $trip->id)->first();
        $this->assertNotNull($cost);
        $this->assertSame(WizardSnapshotCostEstimator::PROVISION_TYPE, $cost->type);
        $this->assertSame('submitted', $cost->status);
        $this->assertSame('9000000.00', (string) $cost->amount);

        $this->postJson("/api/trips/{$trip->id}/status", ['status' => 'completed']);
        $this->assertSame(1, TripCost::query()->where('trip_id', $trip->id)->count());
    }
}
