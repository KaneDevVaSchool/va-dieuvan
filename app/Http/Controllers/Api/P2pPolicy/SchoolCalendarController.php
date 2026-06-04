<?php

namespace App\Http\Controllers\Api\P2pPolicy;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\P2pPolicy\BulkSchoolCalendarRequest;
use App\Http\Requests\Api\P2pPolicy\ListSchoolCalendarsRequest;
use App\Http\Requests\Api\P2pPolicy\GenerateSchoolCalendarMonthRequest;
use App\Http\Requests\Api\P2pPolicy\UpdateSchoolCalendarRequest;
use App\Models\SchoolCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class SchoolCalendarController extends Controller
{
    use ApiResponses;

    public function index(ListSchoolCalendarsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $year = (int) $data['year'];
        $month = (int) $data['month'];

        $items = SchoolCalendar::query()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get()
            ->map(fn (SchoolCalendar $row) => [
                'id' => $row->id,
                'date' => $row->date->format('Y-m-d'),
                'day_type' => $row->day_type,
                'semester' => $row->semester,
                'school_year' => $row->school_year,
                'note' => $row->note,
            ])
            ->values()
            ->all();

        return $this->ok(['items' => $items]);
    }

    public function update(UpdateSchoolCalendarRequest $request, string $date): JsonResponse
    {
        $row = SchoolCalendar::query()->whereDate('date', $date)->firstOrFail();
        $data = $request->validated();

        if (isset($data['semester'])) {
            $data['semester'] = $data['semester'] !== null ? (int) $data['semester'] : null;
        }

        $row->update($data);

        return $this->ok([
            'id' => $row->id,
            'date' => $row->date->format('Y-m-d'),
            'day_type' => $row->day_type,
            'semester' => $row->semester,
            'school_year' => $row->school_year,
            'note' => $row->note,
        ]);
    }

    public function bulkImport(BulkSchoolCalendarRequest $request): JsonResponse
    {
        $data = $request->validated();
        $userId = $request->user()?->id;
        $imported = 0;

        foreach ($data['entries'] as $entry) {
            $dayType = $entry['day_type'];
            $semester = $entry['semester'] ?? null;

            if (in_array($dayType, ['school_day', 'makeup_day'], true) && $semester === null) {
                abort(422, 'Bắt buộc điền semester cho school_day và makeup_day (L1).');
            }

            SchoolCalendar::updateOrCreate(
                ['date' => $entry['date']],
                [
                    'school_year' => $data['school_year'],
                    'day_type' => $dayType,
                    'semester' => $semester,
                    'note' => $entry['note'] ?? null,
                    'created_by' => $userId,
                ],
            );
            $imported++;
        }

        return $this->ok(['imported' => $imported]);
    }

    public function generateMonth(GenerateSchoolCalendarMonthRequest $request): JsonResponse
    {
        $data = $request->validated();
        $year = (int) $data['year'];
        $month = (int) $data['month'];
        $schoolYear = $data['school_year'];
        $semester = (int) $data['semester'];
        $userId = $request->user()?->id;

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $imported = 0;

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dow = (int) $d->dayOfWeek;
            $dayType = ($dow === 0 || $dow === 6) ? 'weekend' : 'school_day';

            SchoolCalendar::updateOrCreate(
                ['date' => $d->toDateString()],
                [
                    'school_year' => $schoolYear,
                    'day_type' => $dayType,
                    'semester' => $dayType === 'school_day' ? $semester : null,
                    'created_by' => $userId,
                ],
            );
            $imported++;
        }

        return $this->ok(['imported' => $imported]);
    }
}
