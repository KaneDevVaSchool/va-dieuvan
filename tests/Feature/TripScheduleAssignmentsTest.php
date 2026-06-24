<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\TripAssignedToRequesterNotification;
use App\Services\Dispatching\DispatchingService;
use App\Services\Dispatching\TripScheduleLegService;
use App\Support\TripVisibility;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
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
            'status' => 'ready',
        ]);
        // Xe riêng (rảnh) cho chuyến đích — cô lập kịch bản "tài xế không bị chặn".
        $vehicleFree = Vehicle::query()->create([
            'license_plate' => '51A-FREE',
            'type' => 'bus',
            'seat_count' => 16,
            'status' => 'ready',
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
                    'vehicle_id' => $vehicleFree->id,
                ],
            ],
        ]);

        $this->assertSame('assigned', $updated->status);
        $this->assertSame($driverMorning->id, (int) $updated->driver_id);
    }

    public function test_assign_uses_trip_depart_when_snapshot_leg_has_no_times(): void
    {
        $this->seed(RbacSeeder::class);
        $vehicle = Vehicle::query()->create([
            'license_plate' => '51A-NOTIME',
            'type' => 'bus',
            'seat_count' => 16,
            'status' => 'ready',
        ]);
        $driver = Driver::query()->create([
            'user_id' => User::factory()->create()->id,
            'full_name' => 'Free Driver',
            'phone' => '0900000099',
            'status' => 'active',
        ]);

        $day = '2026-08-15';
        $tripDepart = Carbon::parse("{$day} 14:00:00");
        $tripArrive = Carbon::parse("{$day} 16:00:00");

        $dr = DispatchRequest::create([
            'requester_id' => User::factory()->create()->id,
            'trip_type' => 'business',
            'origin' => 'X',
            'destination' => 'Y',
            'depart_at' => $tripDepart,
            'arrive_by' => $tripArrive,
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
            'status' => 'approved',
            'depart_at' => $tripDepart,
            'arrive_by' => $tripArrive,
            'lock_version' => 0,
        ]);

        $dispatching = app(DispatchingService::class);
        $updated = $dispatching->assignResources($trip, [
            'lock_version' => 0,
            'schedule_assignments' => [
                [
                    'key' => 'business:0',
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                ],
            ],
        ]);

        $this->assertSame('assigned', $updated->status);
        $this->assertSame($driver->id, (int) $updated->driver_id);
    }

    public function test_assign_notifies_requester_with_vehicle_and_driver(): void
    {
        Notification::fake();
        $this->seed(RbacSeeder::class);

        $vehicle = Vehicle::query()->create([
            'license_plate' => '51B-51301',
            'type' => 'bus',
            'seat_count' => 16,
            'status' => 'ready',
        ]);
        $driver = Driver::query()->create([
            'user_id' => User::factory()->create()->id,
            'full_name' => 'Tài Xế A',
            'phone' => '0900000123',
            'status' => 'active',
        ]);

        $requester = User::factory()->create(['is_active' => true]);

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => '982/8 Quang Trung',
            'destination' => 'Cityland Center Hills',
            'depart_at' => now()->addDay(),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'service_price' => 1500000,
            'wizard_snapshot' => [
                'businessRows' => [
                    ['pickup' => '982/8 Quang Trung', 'dropoff' => 'Cityland Center Hills', 'guests' => '1'],
                ],
            ],
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'status' => 'approved',
            'depart_at' => now()->addDay(),
            'lock_version' => 0,
        ]);

        app(DispatchingService::class)->assignResources($trip, [
            'lock_version' => 0,
            'schedule_assignments' => [
                ['key' => 'business:0', 'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id],
            ],
        ]);

        Notification::assertSentTo(
            $requester,
            TripAssignedToRequesterNotification::class,
            function (TripAssignedToRequesterNotification $n) use ($dr) {
                return $n->dispatchRequestId === $dr->id
                    && $n->vehicleLabel === '51B-51301'
                    && $n->driverLabel === 'Tài Xế A'
                    && (float) $n->servicePrice === 1500000.0;
            },
        );
    }

    public function test_assign_does_not_notify_requester_when_actor_is_requester(): void
    {
        Notification::fake();
        $this->seed(RbacSeeder::class);

        $vehicle = Vehicle::query()->create([
            'license_plate' => '51B-99999',
            'type' => 'bus',
            'seat_count' => 16,
            'status' => 'ready',
        ]);
        $driver = Driver::query()->create([
            'user_id' => User::factory()->create()->id,
            'full_name' => 'Self Driver',
            'phone' => '0900000124',
            'status' => 'active',
        ]);

        $requester = User::factory()->create(['is_active' => true]);

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
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
            'status' => 'approved',
            'depart_at' => now()->addDay(),
            'lock_version' => 0,
        ]);

        app(DispatchingService::class)->assignResources($trip, [
            'lock_version' => 0,
            'actor_id' => $requester->id,
            'schedule_assignments' => [
                ['key' => 'business:0', 'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id],
            ],
        ]);

        Notification::assertNotSentTo($requester, TripAssignedToRequesterNotification::class);
    }

    public function test_afternoon_leg_driver_sees_trip_in_driver_history_api(): void
    {
        $this->seed(RbacSeeder::class);

        $userMorning = User::factory()->create(['is_active' => true]);
        $userMorning->assignRole('driver');
        $driverMorning = Driver::query()->create([
            'user_id' => $userMorning->id,
            'full_name' => 'Morning Driver',
            'phone' => '0900000101',
            'status' => 'active',
        ]);

        $userAfternoon = User::factory()->create(['is_active' => true]);
        $userAfternoon->assignRole('driver');
        $driverAfternoon = Driver::query()->create([
            'user_id' => $userAfternoon->id,
            'full_name' => 'Afternoon Driver',
            'phone' => '0900000102',
            'status' => 'active',
        ]);

        $vehicle = Vehicle::query()->create([
            'license_plate' => '51A-LEGS',
            'type' => 'bus',
            'seat_count' => 16,
            'status' => 'ready',
        ]);

        $day = '2026-09-10';
        $snap = [
            'businessRows' => [
                [
                    'depart_at' => "{$day}T08:00:00+07:00",
                    'pickup' => 'Điểm A',
                    'return_at' => "{$day}T10:00:00+07:00",
                    'dropoff' => 'Điểm B',
                    'guests' => '1',
                ],
                [
                    'depart_at' => "{$day}T14:00:00+07:00",
                    'pickup' => 'Điểm C',
                    'return_at' => "{$day}T16:00:00+07:00",
                    'dropoff' => 'Điểm D',
                    'guests' => '1',
                ],
            ],
        ];

        $dr = DispatchRequest::create([
            'requester_id' => User::factory()->create()->id,
            'trip_type' => 'business',
            'origin' => 'Điểm A',
            'destination' => 'Điểm D',
            'depart_at' => Carbon::parse("{$day} 08:00:00"),
            'arrive_by' => Carbon::parse("{$day} 16:00:00"),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => $snap,
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
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

        $this->assertTrue(TripVisibility::userCanViewTrip($userAfternoon, $trip));

        $otherDr = DispatchRequest::create([
            'requester_id' => User::factory()->create()->id,
            'trip_type' => 'point_to_point',
            'origin' => 'Solo',
            'destination' => 'Trip',
            'depart_at' => Carbon::parse("{$day} 09:00:00"),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => ['passengerRows' => [['pickup' => 'Solo', 'dropoff' => 'Trip', 'guests' => '1']]],
        ]);
        Trip::create([
            'dispatch_request_id' => $otherDr->id,
            'status' => 'assigned',
            'depart_at' => Carbon::parse("{$day} 09:00:00"),
            'driver_id' => $driverAfternoon->id,
            'vehicle_id' => $vehicle->id,
            'lock_version' => 0,
        ]);

        $response = $this->actingAs($userAfternoon, 'sanctum')
            ->getJson('/api/driver/trips?'.http_build_query([
                'date_from' => $day,
                'date_to' => $day,
            ]));

        $response->assertOk();
        $ids = collect($response->json('data.items'))->pluck('id')->all();
        $this->assertContains($trip->id, $ids);

        $row = collect($response->json('data.items'))->firstWhere('id', $trip->id);
        $this->assertSame('Điểm C', $row['pickup_location']);
        $this->assertSame('Điểm D', $row['dropoff_location']);
        $this->assertCount(1, $row['schedule_legs']);
        $this->assertSame('business:1', $row['schedule_legs'][0]['key']);
    }
}
