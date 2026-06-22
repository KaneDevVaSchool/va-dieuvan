<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnTpExecutions;
use App\Http\Controllers\Controller;
use App\Models\TpProgramDay;
use App\Services\TransportProgram\TpProgramScheduleSlots;
use App\Services\TransportProgram\TpShiftDriverSupport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverTpDayListController extends Controller
{
    use ActsOnTpExecutions;
    use ApiResponses;

    public function __construct(
        private readonly TpProgramScheduleSlots $scheduleSlots,
        private readonly TpShiftDriverSupport $shiftSupport,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $driver = $this->actingDriver($request->user());
        abort_unless($driver, 403);

        $from = $request->query('date_from', Carbon::today()->toDateString());
        $to = $request->query('date_to', $from);
        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 60) {
            $to = Carbon::parse($from)->addDays(60)->toDateString();
        }

        $days = TpProgramDay::query()
            ->with(['program', 'executions'])
            ->driverScheduleVisible()
            ->whereDate('scheduled_date', '>=', $from)
            ->whereDate('scheduled_date', '<=', $to)
            ->get();

        $items = [];
        foreach ($days as $d) {
            $program = $d->program;
            if ($program === null) {
                continue;
            }
            $slots = $this->scheduleSlots->slotsForProgram($program);
            if ($slots === []) {
                $slots = [['shift' => 'morning', 'departure_time' => $program->departure_time, 'arrival_time' => null]];
            }

            $multiSlot = count($slots) > 1;
            foreach ($slots as $slot) {
                $shift = (string) ($slot['shift'] ?? 'morning');
                $slotDriver = $this->shiftSupport->effectiveMainDriver($d, $multiSlot ? $shift : null);
                if (! $slotDriver || (int) $slotDriver->id !== (int) $driver->id) {
                    continue;
                }
                // Khi có nhiều ca (tuyến con), trả về confirmed_at riêng của từng ca.
                // Khi chỉ có 1 ca, dùng confirmed_at chung (backward-compat).
                $confirmedAt = $multiSlot
                    ? $d->slotConfirmedAt($shift)?->toIso8601String()
                    : $d->confirmed_at?->toIso8601String();

                $items[] = [
                    'day_id' => $d->id,
                    'program_id' => $program->id,
                    'program_code' => $program->code,
                    'origin_name' => $program->origin_name,
                    'destination_name' => $program->destination_name,
                    'list_key' => $d->id.'-'.$shift,
                    'shift' => $shift,
                    'multi_slot' => $multiSlot,
                    'program_name' => $program->name,
                    'scheduled_date' => $d->scheduled_date->toDateString(),
                    'departure_time' => $slot['departure_time'] ?? $program->departure_time,
                    'arrival_time' => $slot['arrival_time'] ?? null,
                    'expected_count' => $d->expected_count,
                    'is_default_driver' => $d->driver_id === null,
                    'execution_status' => $d->executionForShift($shift)?->status,
                    'execution_id' => $d->executionForShift($shift)?->id,
                    'confirmed_at' => $confirmedAt,
                ];
            }
        }

        usort($items, function (array $a, array $b) {
            $cmp = strcmp($a['scheduled_date'], $b['scheduled_date']);
            if ($cmp !== 0) {
                return $cmp;
            }
            $ta = (string) ($a['departure_time'] ?? '');
            $tb = (string) ($b['departure_time'] ?? '');

            return strcmp($ta, $tb);
        });

        return $this->ok(['items' => array_values($items)]);
    }
}
