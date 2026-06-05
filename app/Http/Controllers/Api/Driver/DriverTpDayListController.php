<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Driver\Concerns\ActsOnTpExecutions;
use App\Http\Controllers\Controller;
use App\Models\TpProgramDay;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverTpDayListController extends Controller
{
    use ActsOnTpExecutions;
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $driver = $this->actingDriver($request->user());
        abort_unless($driver, 403);

        $from = $request->query('date_from', Carbon::today()->toDateString());
        $to = $request->query('date_to', $from);
        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 7) {
            $to = Carbon::parse($from)->addDays(7)->toDateString();
        }

        $days = TpProgramDay::query()
            ->with('program', 'execution')
            ->where('day_type', TpProgramDay::DAY_OPERATING)
            ->whereBetween('scheduled_date', [$from, $to])
            ->get()
            ->filter(fn (TpProgramDay $d) => optional($d->effectiveDriver())->id === $driver->id)
            ->map(fn (TpProgramDay $d) => [
                'day_id' => $d->id,
                'program_name' => $d->program->name,
                'scheduled_date' => $d->scheduled_date->toDateString(),
                'departure_time' => $d->program->departure_time,
                'expected_count' => $d->expected_count,
                'is_default_driver' => $d->driver_id === null,
                'execution_status' => $d->execution?->status,
                'confirmed_at' => $d->confirmed_at?->toIso8601String(),
            ])->values()->all();

        return $this->ok(['items' => $days]);
    }
}
