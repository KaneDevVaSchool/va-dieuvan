<?php

namespace Tests\Unit\TransportProgram;

use App\Models\TpProgram;
use App\Services\TransportProgram\TpProgramScheduleSlots;
use Tests\TestCase;

class TpProgramScheduleSlotsTest extends TestCase
{
    public function test_expands_morning_and_afternoon_from_settings(): void
    {
        $program = new TpProgram([
            'departure_time' => '06:00',
            'return_time' => '17:00',
            'settings' => [
                'morning' => ['enabled' => true, 'departure' => '06:00', 'arrival' => '07:15'],
                'afternoon' => ['enabled' => true, 'departure' => '17:00', 'arrival' => '18:00'],
            ],
        ]);

        $slots = (new TpProgramScheduleSlots)->slotsForProgram($program);
        $this->assertCount(2, $slots);
        $this->assertSame('morning', $slots[0]['shift']);
        $this->assertSame('06:00', $slots[0]['departure_time']);
        $this->assertSame('afternoon', $slots[1]['shift']);
        $this->assertSame('17:00', $slots[1]['departure_time']);
    }
}
