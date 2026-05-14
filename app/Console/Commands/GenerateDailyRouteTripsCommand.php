<?php

namespace App\Console\Commands;

use App\Models\RouteRun;
use App\Models\RouteSchedule;
use App\Models\RouteVersion;
use App\Models\Trip;
use App\Services\Auditing\AuditLogger;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateDailyRouteTripsCommand extends Command
{
    protected $signature = 'route:generate-daily-trips {--run-date= : YYYY-MM-DD (default: tomorrow in application timezone)}';

    protected $description = 'Generate pending trips from approved route_schedules for a calendar day (idempotent per route_version + date).';

    public function handle(): int
    {
        $opt = $this->option('run-date');
        $runCarbon = $opt ? Carbon::parse((string) $opt) : Carbon::tomorrow();
        $runDateString = $runCarbon->toDateString();
        $isoDow = (int) $runCarbon->isoWeekday(); // 1=Mon … 7=Sun

        $schedules = RouteSchedule::query()
            ->join('route_versions as rv', 'rv.id', '=', 'route_schedules.route_version_id')
            ->where('rv.status', 'approved')
            ->where('route_schedules.day_of_week', $isoDow)
            ->select(['route_schedules.*'])
            ->orderBy('route_schedules.route_version_id')
            ->orderBy('route_schedules.depart_time')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info("No schedules for {$runDateString} (ISO weekday {$isoDow}).");

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($schedules as $schedule) {
            /** @var RouteSchedule $schedule */
            $already = RouteRun::query()
                ->where('route_version_id', $schedule->route_version_id)
                ->where('run_date', $runDateString)
                ->exists();

            if ($already) {
                $skipped++;

                continue;
            }

            $routeVersion = RouteVersion::query()->findOrFail($schedule->route_version_id);

            DB::transaction(function () use (
                $schedule,
                $routeVersion,
                $runDateString,
            ): void {
                $departAt = $this->combineDateAndRunTime($runDateString, $schedule->depart_time);
                $arriveBy = null;
                if ($schedule->arrive_time !== null) {
                    $arriveBy = $this->combineDateAndRunTime($runDateString, $schedule->arrive_time);
                }

                $trip = Trip::create([
                    'dispatch_request_id' => null,
                    'dispatcher_id' => null,
                    'status' => 'pending',
                    'depart_at' => $departAt,
                    'arrive_by' => $arriveBy,
                    'lock_version' => 0,
                ]);

                $run = RouteRun::updateOrCreate(
                    ['route_version_id' => $routeVersion->id, 'run_date' => $runDateString],
                    ['trip_id' => $trip->id],
                );

                app(AuditLogger::class)->log(
                    actorId: null,
                    event: 'route.run.generate_trip',
                    auditable: $routeVersion,
                    before: null,
                    after: ['route_run_id' => $run->id, 'trip_id' => $trip->id, 'run_date' => $runDateString],
                );
            });

            $created++;
        }

        $this->info("run_date={$runDateString} created={$created} skipped(existing route_run)={$skipped}");

        return self::SUCCESS;
    }

    private function combineDateAndRunTime(string $runDate, mixed $time): Carbon
    {
        return Carbon::parse($runDate.' '.$this->normalizeTimeSql($time));
    }

    private function normalizeTimeSql(mixed $time): string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i:s');
        }

        $s = trim((string) $time);
        if ($s === '') {
            return '00:00:00';
        }

        if (preg_match('/^\d{2}:\d{2}$/', $s)) {
            return "{$s}:00";
        }

        return $s;
    }
}
