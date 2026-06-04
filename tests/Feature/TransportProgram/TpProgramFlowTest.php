<?php

namespace Tests\Feature\TransportProgram;

use App\Actions\CreateTransportProgramAction;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use App\Models\TpStudent;
use App\Services\TransportProgram\ProgramEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 10 — luồng cốt lõi: tạo chương trình sinh ngày + đăng ký cập nhật sĩ số.
 */
class TpProgramFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeProgram(): TpProgram
    {
        $action = app(CreateTransportProgramAction::class);
        $start = Carbon::today();
        $end = Carbon::today()->addDays(6);

        $result = $action->execute([
            'name' => 'Đưa đón sáng khối 1',
            'departure_time' => '06:30',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
        ], null);

        return TpProgram::findOrFail($result['program']['id']);
    }

    public function test_creating_program_generates_operating_days(): void
    {
        $program = $this->makeProgram();

        // 7 ngày liên tiếp với runs_on đủ 7 ngày => 7 ngày vận hành.
        $this->assertSame(7, $program->days()->count());
        $this->assertSame(7, $program->days()->where('day_type', TpProgramDay::DAY_OPERATING)->count());
    }

    public function test_enrolling_students_increments_future_expected_count(): void
    {
        $program = $this->makeProgram();

        $s1 = TpStudent::create(['code' => 'TPS001', 'full_name' => 'Nguyễn Văn A', 'status' => 'active']);
        $s2 = TpStudent::create(['code' => 'TPS002', 'full_name' => 'Trần Thị B', 'status' => 'active']);

        app(ProgramEnrollmentService::class)->enrollBulk($program, [$s1->id, $s2->id], null);

        $this->assertSame(2, $program->enrollments()->whereNull('unenrolled_at')->count());

        $futureDay = $program->days()
            ->whereDate('scheduled_date', '>=', Carbon::today()->toDateString())
            ->first();

        $this->assertSame(2, (int) $futureDay->expected_count);
    }

    public function test_unenroll_decrements_expected_count(): void
    {
        $program = $this->makeProgram();
        $s1 = TpStudent::create(['code' => 'TPS010', 'full_name' => 'HS C', 'status' => 'active']);
        $service = app(ProgramEnrollmentService::class);
        $service->enrollBulk($program, [$s1->id], null);
        $service->unenroll($program, $s1->id, 'thôi học', null);

        $day = $program->days()->whereDate('scheduled_date', '>=', Carbon::today()->toDateString())->first();
        $this->assertSame(0, (int) $day->expected_count);
    }
}
