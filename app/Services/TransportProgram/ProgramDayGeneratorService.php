<?php

namespace App\Services\TransportProgram;

use App\Models\TpProgram;
use App\Models\TpProgramDay;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class ProgramDayGeneratorService
{
    private const DOW_MAP = [
        'mon' => Carbon::MONDAY,
        'tue' => Carbon::TUESDAY,
        'wed' => Carbon::WEDNESDAY,
        'thu' => Carbon::THURSDAY,
        'fri' => Carbon::FRIDAY,
        'sat' => Carbon::SATURDAY,
        'sun' => Carbon::SUNDAY,
    ];

    public function __construct(
        private readonly bool $skipSchoolHolidays = true,
    ) {}

    /** @return Collection<int, string> Y-m-d dates */
    public function expandDateRange(TpProgram $program): Collection
    {
        $runsOn = collect($program->runs_on ?? ['mon', 'tue', 'wed', 'thu', 'fri'])
            ->map(fn ($d) => strtolower((string) $d))
            ->filter(fn ($d) => isset(self::DOW_MAP[$d]))
            ->values()
            ->all();

        $excluded = collect($program->excluded_dates ?? [])
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        $extra = collect($program->extra_dates ?? [])
            ->map(fn ($d) => Carbon::parse($d)->toDateString());

        $allowedDow = array_map(fn ($k) => self::DOW_MAP[$k], $runsOn);

        $dates = collect();
        $period = CarbonPeriod::create($program->start_date, $program->end_date);

        foreach ($period as $day) {
            $ds = $day->toDateString();
            if ($excluded->has($ds)) {
                continue;
            }
            if (in_array($day->dayOfWeek, $allowedDow, true)) {
                if ($this->skipSchoolHolidays && $this->isSchoolHoliday($ds) && ! $extra->contains($ds)) {
                    continue;
                }
                $dates->push($ds);
            }
        }

        foreach ($extra as $ds) {
            if ($excluded->has($ds)) {
                continue;
            }
            if ($ds >= $program->start_date->toDateString() && $ds <= $program->end_date->toDateString()) {
                $dates->push($ds);
            }
        }

        return $dates->unique()->sort()->values();
    }

    public function generate(TpProgram $program): int
    {
        $dates = $this->expandDateRange($program);
        $enrolledCount = $program->enrollments()->whereNull('unenrolled_at')->count();

        return $this->upsertDays($program, $dates, $enrolledCount);
    }

    public function regenerate(TpProgram $program, Collection $oldDates, Collection $newDates): void
    {
        $oldSet = $oldDates->map(fn ($d) => Carbon::parse($d)->toDateString())->unique();
        $newSet = $newDates->map(fn ($d) => Carbon::parse($d)->toDateString())->unique();

        $toAdd = $newSet->diff($oldSet);
        $toRemove = $oldSet->diff($newSet);
        $enrolledCount = $program->enrollments()->whereNull('unenrolled_at')->count();

        if ($toAdd->isNotEmpty()) {
            $this->upsertDays($program, $toAdd->values(), $enrolledCount);
        }

        if ($toRemove->isEmpty()) {
            return;
        }

        $days = TpProgramDay::query()
            ->where('program_id', $program->id)
            ->whereIn('scheduled_date', $toRemove->all())
            ->get();

        foreach ($days as $day) {
            $hasData = $day->absences()->exists() || $day->execution()->exists();
            if ($hasData) {
                $day->update([
                    'day_type' => TpProgramDay::DAY_CANCELLED,
                    'cancel_reason' => $day->cancel_reason ?? 'Date range shortened',
                ]);
            } else {
                $day->delete();
            }
        }
    }

    /** @param  Collection<int, string>  $dates */
    private function upsertDays(TpProgram $program, Collection $dates, int $expectedCount): int
    {
        $now = now();
        $rows = $dates->map(fn (string $date) => [
            'program_id' => $program->id,
            'scheduled_date' => $date,
            'day_type' => TpProgramDay::DAY_OPERATING,
            'expected_count' => $expectedCount,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        $count = 0;
        foreach (array_chunk($rows, 100) as $chunk) {
            TpProgramDay::query()->upsert(
                $chunk,
                ['program_id', 'scheduled_date'],
                ['day_type', 'expected_count', 'updated_at']
            );
            $count += count($chunk);
        }

        return $count;
    }

    private function isSchoolHoliday(string $date): bool
    {
        return false;
    }
}
