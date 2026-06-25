<?php

namespace Tests\Feature;

use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use App\Services\Dispatching\TripScheduleLegService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * POST /api/trips/{id}/status — tài xế, chuyến đa lịch (schedule_key) và approved vs leg status.
 */
class TripDriverLegStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function makeDriverUser(): array
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('driver');
        $driver = Driver::query()->create([
            'user_id' => $user->id,
            'full_name' => 'TX Test',
            'phone' => '0900123456',
            'status' => 'active',
        ]);

        return [$user, $driver];
    }

    /**
     * @return array{0: Trip, 1: string}
     */
    private function makeMultiLegBusinessTrip(Driver $driver, string $tripStatus, array $legOverrides = []): array
    {
        $day = '2026-06-25';
        $snap = [
            'businessRows' => [
                [
                    'depart_at' => "{$day}T08:00:00+07:00",
                    'pickup' => '806 Âu Cơ',
                    'return_at' => "{$day}T10:00:00+07:00",
                    'dropoff' => 'Lạc Long Quân',
                    'guests' => '2',
                ],
                [
                    'depart_at' => "{$day}T14:00:00+07:00",
                    'pickup' => 'Lạc Long Quân',
                    'return_at' => "{$day}T16:00:00+07:00",
                    'dropoff' => '806 Âu Cơ',
                    'guests' => '2',
                ],
            ],
        ];

        $dr = DispatchRequest::create([
            'requester_id' => User::factory()->create()->id,
            'trip_type' => 'business',
            'origin' => '806 Âu Cơ',
            'destination' => 'Lạc Long Quân',
            'depart_at' => Carbon::parse("{$day} 08:00:00"),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => $snap,
        ]);

        $leg0 = array_merge([
            'key' => 'business:0',
            'driver_id' => $driver->id,
            'vehicle_id' => null,
            'status' => 'driver_confirmed',
        ], $legOverrides['business:0'] ?? []);

        $leg1 = array_merge([
            'key' => 'business:1',
            'driver_id' => $driver->id,
            'vehicle_id' => null,
            'status' => 'assigned',
        ], $legOverrides['business:1'] ?? []);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'status' => $tripStatus,
            'depart_at' => Carbon::parse("{$day} 08:00:00"),
            'driver_id' => $driver->id,
            'lock_version' => 0,
            'schedule_assignments' => [$leg0, $leg1],
        ]);

        return [$trip, 'business:0'];
    }

    public function test_resolve_leg_status_for_transition_uses_leg_not_trip(): void
    {
        [, $driver] = $this->makeDriverUser();
        [$trip, $legKey] = $this->makeMultiLegBusinessTrip($driver, 'approved');

        $legStatus = app(TripScheduleLegService::class)->resolveLegStatusForTransition($trip, $legKey);

        $this->assertSame('driver_confirmed', $legStatus);
    }

    public function test_driver_can_start_leg_when_trip_status_is_approved(): void
    {
        [$user, $driver] = $this->makeDriverUser();
        [$trip, $legKey] = $this->makeMultiLegBusinessTrip($driver, 'approved');

        $res = $this->actingAs($user, 'sanctum')->postJson("/api/trips/{$trip->id}/status", [
            'status' => 'in_progress',
            'schedule_key' => $legKey,
            'lock_version' => 0,
        ]);

        $res->assertOk();
        $trip->refresh();
        $this->assertSame('in_progress', $trip->status);
        $legs = app(TripScheduleLegService::class)->resolveScheduleLegsForTrip($trip);
        $this->assertSame('in_progress', $legs[0]['status']);
    }

    public function test_driver_multileg_start_without_schedule_key_returns_422(): void
    {
        [$user, $driver] = $this->makeDriverUser();
        [$trip] = $this->makeMultiLegBusinessTrip($driver, 'driver_confirmed');

        $res = $this->actingAs($user, 'sanctum')->postJson("/api/trips/{$trip->id}/status", [
            'status' => 'in_progress',
            'lock_version' => 0,
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('schedule_key', (string) $res->json('message'));
    }

    public function test_driver_single_leg_approved_can_start_without_schedule_key(): void
    {
        [$user, $driver] = $this->makeDriverUser();

        $dr = DispatchRequest::create([
            'requester_id' => User::factory()->create()->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDay(),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [
                'passengerRows' => [
                    ['pickup' => 'A', 'dropoff' => 'B', 'guests' => '1'],
                ],
            ],
        ]);

        $trip = Trip::create([
            'dispatch_request_id' => $dr->id,
            'status' => 'approved',
            'depart_at' => now()->addDay(),
            'driver_id' => $driver->id,
            'lock_version' => 0,
        ]);

        $res = $this->actingAs($user, 'sanctum')->postJson("/api/trips/{$trip->id}/status", [
            'status' => 'in_progress',
            'lock_version' => 0,
        ]);

        $res->assertOk();
        $trip->refresh();
        $this->assertSame('in_progress', $trip->status);
    }
}
