<?php

namespace Tests\Unit;

use App\Support\DispatchWizardPassengerCount;
use App\Support\TripScheduleInstant;
use Tests\TestCase;

class TripScheduleInstantTest extends TestCase
{
    public function test_naive_datetime_is_vn_wall_clock(): void
    {
        $iso = TripScheduleInstant::toIso8601String('2026-06-29T15:00:00');
        $this->assertNotNull($iso);
        $this->assertStringContainsString('T15:00:00', $iso);
        $this->assertStringContainsString('+07:00', $iso);
    }

    public function test_explicit_offset_is_preserved(): void
    {
        $iso = TripScheduleInstant::toIso8601String('2026-06-29T08:00:00+07:00');
        $this->assertSame('2026-06-29T08:00:00+07:00', $iso);
    }

    public function test_guests_for_passenger_leg_key(): void
    {
        $snap = [
            'passengerRows' => [
                ['pickup' => 'A', 'dropoff' => 'B', 'guests' => '10'],
                ['pickup' => 'B', 'dropoff' => 'A', 'guests' => '1'],
            ],
        ];
        $this->assertSame(10, DispatchWizardPassengerCount::guestsForScheduleLegKey($snap, 'point_to_point', 'passenger:0'));
        $this->assertSame(1, DispatchWizardPassengerCount::guestsForScheduleLegKey($snap, 'point_to_point', 'passenger:1'));
    }
}
