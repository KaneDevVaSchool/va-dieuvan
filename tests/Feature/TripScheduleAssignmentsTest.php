<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Dispatching\DispatchingService;
use App\Services\Dispatching\TripScheduleLegService;
use App\Support\TripVisibility;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_completing_one_leg_keeps_trip_in_progress_until_all_legs_done(): void
    {
        $service = app(TripScheduleLegService::class);
        $snap = [
            'cargoRows' => [
                ['name' => 'A', 'pickup_place' => 'P1', 'delivery_place' => 'D1'],
                ['name' => 'B', 'pickup_place' => 'P2', 'delivery_place' => 'D2'],
            ],
        ];
        $dr = DispatchRequest::create([
            'requester_id' => User::factory()->create()->id,
            'trip_type' => 'cargo',
            'origin' => 'P1',
            'destination' => 'D2',
            'depart_at' => now()->addDay(),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => $snap,
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'status' => 'assigned',
            'depart_at' => now()->addDay(),
            'lock_version' => 0,
            'schedule_assignments' => [
                ['key' => 'cargo:0', 'driver_id' => 1, 'vehicle_id' => 1],
                ['key' => 'cargo:1', 'driver_id' => 1, 'vehicle_id' => 1],
            ],
        ]);

        $applied = $service->applyStatusChange($trip, 'in_progress', 'cargo:0');
        $trip->update(array_merge($applied['trip'], [
            'schedule_assignments' => $applied['schedule_assignments'],
        ]));
        $trip->refresh();

        $this->assertSame('in_progress', $trip->status);
        $legs = $service->resolveScheduleLegsForTrip($trip);
        $this->assertSame('in_progress', $legs[0]['status']);
        $this->assertSame('driver_confirmed', $legs[1]['status']);

        $applied = $service->applyStatusChange($trip, 'completed', 'cargo:0');
        $trip->update(array_merge($applied['trip'], [
            'schedule_assignments' => $applied['schedule_assignments'],
        ]));
        $trip->refresh();

        $this->assertSame('in_progress', $trip->status);
        $this->assertNull($trip->completed_at);
        $legs = $service->resolveScheduleLegsForTrip($trip);
        $this->assertSame('completed', $legs[0]['status']);
        $this->assertSame('driver_confirmed', $legs[1]['status']);

        $applied = $service->applyStatusChange($trip, 'completed', 'cargo:1');
        $trip->update(array_merge($applied['trip'], [
            'schedule_assignments' => $applied['schedule_assignments'],
        ]));
        $trip->refresh();

        $this->assertSame('completed', $trip->status);
        $this->assertNotNull($trip->completed_at);
    }

    public function test_assign_driver_not_blocked_by_other_trip_primary_when_only_morning_leg(): void
    {
        $this->seed(RbacSeeder::class);
        $vehicle = Vehicle::query()->create([
            'license_plate' => '51A-TEST',
            'type' => 'bus',
            'seat_count' => 16,
            'status' => 'active',
        ]);

        $driverMorning = Driver::query()->create([
            'user_id' => User::factory()->create()->id,
            'full_name' => 'Morning Driver',
            'phone' => '0900000001',
            'status' => 'active',
        ]);
        $driverAfternoon = Driver::query()->create([
            'user_id' => User::factory()->create()->id,
            'full_name' => 'Afternoon Driver',
            'phone' => '0900000002',
            'status' => 'active',
        ]);

        $day = '2026-06-20';
        $snap = [
            'businessRows' => [
                [
                    'depart_at' => "{$day}T08:00:00+07:00",
                    'pickup' => 'A',
                    'return_at' => "{$day}T10:00:00+07:00",
                    'dropoff' => 'B',
                    'guests' => '1',
                ],
                [
                    'depart_at' => "{$day}T14:00:00+07:00",
                    'pickup' => 'C',
                    'return_at' => "{$day}T16:00:00+07:00",
                    'dropoff' => 'D',
                    'guests' => '1',
                ],
            ],
        ];

        $drExisting = DispatchRequest::create([
            'requester_id' => User::factory()->create()->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'D',
            'depart_at' => Carbon::parse("{$day} 08:00:00"),
            'arrive_by' => Carbon::parse("{$day} 16:00:00"),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => $snap,
        ]);

        Trip::create([
            'dispatch_request_id' => $drExisting->id,
            'status' => 'assigned',
            'depart_at' => Carbon::parse("{$day} 08:00:00"),
            'arrive_by' => Carbon::parse("{$day} 16:00:00"),
            'driver_id' => $driverMorning->id,
            'vehicle_id' => $vehicle->id,
            'lock_version' => 0,
            'schedule_assignments' => [
                [
                    'key' => 'business:0',
                    'driver_id' => $driverMorning->id,
                    'vehicle_id' => $vehicle->id,
                    'depart_at' => "{$day}T08:00:00+07:00",
                    'arrive_by' => "{$day}T10:00:00+07:00",
                ],
                [
                    'key' => 'business:1',
                    'driver_id' => $driverAfternoon->id,
                    'vehicle_id' => $vehicle->id,
                    'depart_at' => "{$day}T14:00:00+07:00",
                    'arrive_by' => "{$day}T16:00:00+07:00",
                ],
            ],
        ]);

        $drTarget = DispatchRequest::create([
            'requester_id' => User::factory()->create()->id,
            'trip_type' => 'business',
            'origin' => 'E',
            'destination' => 'F',
            'depart_at' => Carbon::parse("{$day} 14:30:00"),
            'arrive_by' => Carbon::parse("{$day} 15:30:00"),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [
                'businessRows' => [
                    [
                        'depart_at' => "{$day}T14:30:00+07:00",
                        'pickup' => 'E',
                        'return_at' => "{$day}T15:30:00+07:00",
                        'dropoff' => 'F',
                        'guests' => '1',
                    ],
                ],
            ],
        ]);

        $targetTrip = Trip::create([
            'dispatch_request_id' => $drTarget->id,
            'status' => 'approved',
            'depart_at' => Carbon::parse("{$day} 14:30:00"),
            'arrive_by' => Carbon::parse("{$day} 15:30:00"),
            'lock_version' => 0,
        ]);

        $dispatching = app(DispatchingService::class);
        $updated = $dispatching->assignResources($targetTrip, [
            'lock_version' => 0,
            'schedule_assignments' => [
                [
                    'key' => 'business:0',
                    'driver_id' => $driverMorning->id,
                    'vehicle_id' => $vehicle->id,
                ],
            ],
        ]);

        $this->assertSame('assigned', $updated->status);
        $this->assertSame($driverMorning->id, (int) $updated->driver_id);
    }
}
