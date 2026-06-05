<?php

namespace App\Services\TransportProgram;

use App\Models\TpProgram;

/**
 * Ca sáng / chiều trong một ngày vận hành chương trình đưa đón.
 */
class TpProgramScheduleSlots
{
    /**
     * @return list<array{shift: string, departure_time: ?string, arrival_time: ?string}>
     */
    public function slotsForProgram(TpProgram $program): array
    {
        $settings = is_array($program->settings) ? $program->settings : [];
        $morning = is_array($settings['morning'] ?? null) ? $settings['morning'] : [];
        $afternoon = is_array($settings['afternoon'] ?? null) ? $settings['afternoon'] : [];

        $slots = [];

        $morningEnabled = (bool) ($morning['enabled'] ?? false);
        $afternoonEnabled = (bool) ($afternoon['enabled'] ?? false);

        if ($morningEnabled) {
            $slots[] = [
                'shift' => 'morning',
                'departure_time' => $this->normalizeTime($morning['departure'] ?? $program->departure_time),
                'arrival_time' => $this->normalizeTime($morning['arrival'] ?? null),
            ];
        }

        if ($afternoonEnabled) {
            $slots[] = [
                'shift' => 'afternoon',
                'departure_time' => $this->normalizeTime($afternoon['departure'] ?? $program->return_time),
                'arrival_time' => $this->normalizeTime($afternoon['arrival'] ?? null),
            ];
        }

        if ($slots !== []) {
            return $slots;
        }

        if ($program->departure_time) {
            $slots[] = [
                'shift' => 'morning',
                'departure_time' => $this->normalizeTime($program->departure_time),
                'arrival_time' => null,
            ];
        }

        if ($program->return_time) {
            $slots[] = [
                'shift' => 'afternoon',
                'departure_time' => $this->normalizeTime($program->return_time),
                'arrival_time' => null,
            ];
        }

        return $slots;
    }

    private function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $s = trim((string) $value);

        return strlen($s) >= 5 ? substr($s, 0, 5) : ($s !== '' ? $s : null);
    }
}
