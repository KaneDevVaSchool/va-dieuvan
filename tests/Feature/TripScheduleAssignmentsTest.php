<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Services\Dispatching\TripScheduleLegService;
use App\Support\TripVisibility;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripScheduleAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_legs_from_business_snapshot(): void
    {
        $service = app(TripScheduleLegService::class);
        $snap = [
            'businessRows' => [
                [
                    'depart_at' => '2026-06-01T08:00:00+07:00',
                    'pickup' => 'A',
                    'return_at' => '2026-06-01T10:00:00+07:00',
                    'dropoff' => 'B',
                    'guests' => '2',
                ],
                [
                    'depart_at' => '2026-06-01T14:00:00+07:00',
                    'pickup' => 'C',
                    'return_at' => '2026-06-01T16:00:00+07:00',
                    'dropoff' => 'D',
                    'guests' => '1',
                ],
            ],
        ];

        $legs = $service->buildLegDefinitionsFromSnapshot($snap, 'business');
        $this->assertCount(2, $legs);
        $this->assertSame('business:0', $legs[0]['key']);
        $this->assertSame('business:1', $legs[1]['key']);
    }

    public function test_business_trip_ignores_passenger_mirror_rows_in_snapshot(): void
    {
        $service = app(TripScheduleLegService::class);
        $snap = [
            'passengerRows' => [
                [
                    'depart_at' => '2026-06-01T08:00:00+07:00',
                    'pickup' => '',
                    'dropoff' => '',
                    'person_in_charge' => 'Nguyễn Văn A',
                    'guests' => '1',
                ],
            ],
            'businessRows' => [
                [
                    'depart_at' => '2026-06-01T08:00:00+07:00',
                    'pickup' => '806 Âu Cơ',
                    'return_at' => '2026-06-01T17:00:00+07:00',
                    'dropoff' => 'Cơ sở Vũng Tàu',
                    'guests' => '2',
                ],
            ],
        ];

        $legs = $service->buildLegDefinitionsFromSnapshot($snap, 'business');
        $this->assertCount(1, $legs);
        $this->assertSame('business:0', $legs[0]['key']);
    }

    public function test_driver_visibility_via_schedule_assignments_json(): void
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('driver');
        $driver = Driver::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Test Driver',
            'phone' => '0900000000',
            'status' => 'active',
        ]);

        $dr = DispatchRequest::create([
            'requester_id' => $user->id,
            'trip_type' => 'business',
            'origin' => 'X',
            'destination' => 'Y',
            'depart_at' => now()->addDay(),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [
                'businessRows' => [
                    ['pickup' => 'X', 'dropoff' => 'Y', 'guests' => '1'],
                ],
            ],
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'status' => 'assigned',
            'depart_at' => now()->addDay(),
            'lock_version' => 0,
            'schedule_assignments' => [
                [
                    'key' => 'business:0',
                    'driver_id' => $driver->id,
                    'vehicle_id' => null,
                ],
            ],
        ]);

        $this->assertTrue(TripVisibility::userCanViewTrip($user, $trip));
    }
}
