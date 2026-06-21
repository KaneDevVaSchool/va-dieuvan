<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\User;
use App\Services\Trips\TripNamedPassengerSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripPassengerLegAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Trip, 1: DispatchRequest}
     */
    private function makeDoorToDoorTrip(): array
    {
        $dr = DispatchRequest::create([
            'requester_id' => User::factory()->create()->id,
            'trip_type' => 'door_to_door',
            'origin' => 'A',
            'destination' => 'D',
            'depart_at' => now()->addDay(),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [
                'passengerRows' => [
                    ['pickup' => 'A', 'dropoff' => 'B', 'person_in_charge' => 'Chặng 1', 'guests' => '1'],
                    ['pickup' => 'C', 'dropoff' => 'D', 'person_in_charge' => 'Chặng 2', 'guests' => '1'],
                ],
            ],
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'status' => 'approved',
            'depart_at' => now()->addDay(),
            'lock_version' => 0,
        ]);

        return [$trip, $dr];
    }

    public function test_persists_valid_leg_key_and_drops_invalid_one(): void
    {
        [$trip, $dr] = $this->makeDoorToDoorTrip();

        app(TripNamedPassengerSyncService::class)->replace(
            $trip,
            $dr,
            2,
            [
                ['name' => 'Hành khách 1', 'phone' => null, 'note' => null, 'leg_key' => 'passenger:0'],
                ['name' => 'Hành khách 2', 'phone' => null, 'note' => null, 'leg_key' => 'khong-ton-tai'],
            ],
            0,
        );

        $passengers = $trip->tripPassengers()->orderBy('id')->get();
        $this->assertCount(2, $passengers);
        $this->assertSame('passenger:0', $passengers[0]->leg_key);
        $this->assertNull($passengers[1]->leg_key, 'leg_key không hợp lệ phải bị bỏ.');
    }

    public function test_does_not_clobber_route_legs_when_replacing_passenger_list(): void
    {
        [$trip, $dr] = $this->makeDoorToDoorTrip();

        app(TripNamedPassengerSyncService::class)->replace(
            $trip,
            $dr,
            2,
            [
                ['name' => 'Hành khách 1', 'phone' => null, 'note' => null, 'leg_key' => 'passenger:1'],
                ['name' => 'Hành khách 2', 'phone' => null, 'note' => null, 'leg_key' => null],
            ],
            0,
        );

        $dr->refresh();
        $rows = $dr->wizard_snapshot['passengerRows'] ?? [];
        $this->assertCount(2, $rows, 'Các chặng có tuyến phải được giữ nguyên.');
        $this->assertSame('A', $rows[0]['pickup']);
        $this->assertSame('B', $rows[0]['dropoff']);
        $this->assertSame('C', $rows[1]['pickup']);
        $this->assertSame('D', $rows[1]['dropoff']);

        $this->assertSame('passenger:1', $trip->tripPassengers()->orderBy('id')->first()->leg_key);
    }
}
