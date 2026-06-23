<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\User;
use App\Services\Reports\TripCostReportService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TripCostReportLegTest extends TestCase
{
    use RefreshDatabase;

    private function makeTwoLegTrip(User $actor): Trip
    {
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
                    ['unit_price' => '3000000', 'extra_fee' => '0', 'pickup' => 'Tân Bình', 'dropoff' => 'Hồ Bơi'],
                    ['unit_price' => '2000000', 'extra_fee' => '0', 'pickup' => 'Hồ Bơi', 'dropoff' => 'Tân Bình'],
                ],
            ],
        ]);

        return Trip::create([
            'dispatch_request_id' => $dr->id,
            'dispatcher_id' => $actor->id,
            'status' => 'assigned',
            'depart_at' => $dr->depart_at,
            'lock_version' => 0,
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_estimate_rows_are_tagged_per_leg(): void
    {
        $this->seed(RbacSeeder::class);
        $actor = User::factory()->create(['is_active' => true]);
        $actor->assignRole('dispatcher');

        $trip = $this->makeTwoLegTrip($actor);

        $rows = app(TripCostReportService::class)->rows($actor, ['trip_id' => $trip->id]);

        $estimates = array_values(array_filter($rows, fn ($r) => $r['source'] === 'estimate'));
        $this->assertCount(2, $estimates);
        $labels = array_map(fn ($r) => $r['leg_label'], $estimates);
        sort($labels);
        $this->assertSame(['Chặng 1', 'Chặng 2'], $labels);
    }

    public function test_recorded_cost_stores_and_reports_valid_leg_key(): void
    {
        $this->seed(RbacSeeder::class);
        $actor = User::factory()->create(['is_active' => true]);
        $actor->assignRole('dispatcher');

        $trip = $this->makeTwoLegTrip($actor);
        $this->actingAs($actor);

        // Valid leg key → stored.
        $res = $this->postJson("/api/trips/{$trip->id}/costs", [
            'type' => 'fuel',
            'amount' => 500000,
            'leg_key' => 'passenger:1',
        ]);
        $res->assertCreated();
        $this->assertSame('passenger:1', $res->json('data.leg_key'));

        // Invalid leg key → nulled out (whole-trip cost).
        $res2 = $this->postJson("/api/trips/{$trip->id}/costs", [
            'type' => 'toll',
            'amount' => 100000,
            'leg_key' => 'passenger:9',
        ]);
        $res2->assertCreated();
        $this->assertNull($res2->json('data.leg_key'));

        $rows = app(TripCostReportService::class)->rows($actor, ['trip_id' => $trip->id]);
        $recorded = array_values(array_filter($rows, fn ($r) => $r['source'] === 'recorded'));

        $fuel = collect($recorded)->firstWhere('amount', 500000.0);
        $this->assertSame('passenger:1', $fuel['leg_key']);
        $this->assertSame('Chặng 2', $fuel['leg_label']);
        $this->assertSame('Hồ Bơi → Tân Bình', $fuel['leg_route']);

        $toll = collect($recorded)->firstWhere('amount', 100000.0);
        $this->assertNull($toll['leg_key']);
        $this->assertNull($toll['leg_label']);
    }
}
