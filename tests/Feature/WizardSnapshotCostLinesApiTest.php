<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\User;
use App\Services\Costs\WizardSnapshotCostEstimator;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WizardSnapshotCostLinesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_wizard_estimate_lines_for_point_to_point_passenger_row(): void
    {
        $this->seed(RbacSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-06-02 12:00:00'));

        $actor = User::factory()->create(['is_active' => true]);
        $actor->assignRole('dispatcher');

        $dr = DispatchRequest::create([
            'requester_id' => $actor->id,
            'trip_type' => 'point_to_point',
            'origin' => 'Tân Bình',
            'destination' => 'Hồ Bơi',
            'depart_at' => Carbon::parse('2026-06-29 07:00:00'),
            'status' => 'approved',
            'approved_by' => $actor->id,
            'source_channel' => 'portal',
            'paper_status' => 'pending',
            'wizard_snapshot' => [
                'form' => [],
                'passengerRows' => [
                    [
                        'unit_price' => '9000000',
                        'extra_fee' => '0',
                        'pickup' => 'Tân Bình',
                        'dropoff' => 'Hồ Bơi',
                    ],
                ],
            ],
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'dispatcher_id' => $actor->id,
            'status' => 'assigned',
            'depart_at' => $dr->depart_at,
            'lock_version' => 0,
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($actor);

        $res = $this->getJson('/api/trip-costs/wizard-estimate-lines?trip_id='.$trip->id);
        $res->assertOk();
        $items = $res->json('data.items');
        $this->assertCount(1, $items);
        $this->assertSame($trip->id, $items[0]['trip_id']);
        $this->assertSame('passenger-1', $items[0]['line_key']);
        $this->assertSame(9000000.0, (float) $items[0]['amount']);
    }

    public function test_wizard_estimate_lines_hidden_after_provision(): void
    {
        $this->seed(RbacSeeder::class);

        $actor = User::factory()->create(['is_active' => true]);
        $actor->assignRole('dispatcher');

        $dr = DispatchRequest::create([
            'requester_id' => $actor->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => Carbon::parse('2026-06-29 07:00:00'),
            'status' => 'approved',
            'approved_by' => $actor->id,
            'source_channel' => 'portal',
            'paper_status' => 'pending',
            'wizard_snapshot' => [
                'form' => [],
                'businessRows' => [
                    ['unit_price' => '1000000', 'extra_fee' => '0', 'pickup' => 'A', 'dropoff' => 'B'],
                ],
            ],
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'dispatcher_id' => $actor->id,
            'status' => 'completed',
            'depart_at' => $dr->depart_at,
            'lock_version' => 0,
            'payment_status' => 'unpaid',
        ]);

        $trip->costs()->create([
            'created_by' => $actor->id,
            'type' => WizardSnapshotCostEstimator::PROVISION_TYPE,
            'amount' => 1000000,
            'currency' => 'VND',
            'status' => 'submitted',
        ]);

        $this->actingAs($actor);

        $res = $this->getJson('/api/trip-costs/wizard-estimate-lines?trip_id='.$trip->id);
        $res->assertOk();
        $this->assertSame([], $res->json('data.items'));
    }
}
