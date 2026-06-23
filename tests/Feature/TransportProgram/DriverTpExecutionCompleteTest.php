<?php

namespace Tests\Feature\TransportProgram;

use App\Actions\CreateTransportProgramAction;
use App\Models\Driver;
use App\Models\TpProgram;
use App\Models\TpStudent;
use App\Models\TpTripExecution;
use App\Models\User;
use App\Services\TransportProgram\ProgramEnrollmentService;
use App\Services\TransportProgram\StudentLogService;
use App\Services\TransportProgram\TripExecutionService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DriverTpExecutionCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_returns_422_when_students_still_pending(): void
    {
        $this->seed(RbacSeeder::class);
        $driver = $this->makeDriver('TX Complete 422');
        $execution = $this->startExecutionForDriver($driver);

        $this->actingAs($driver->user);

        $this->postJson("/api/driver/tp-executions/{$execution->id}/complete", [
            'confirm_pending_board' => true,
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Còn 2 học sinh chưa xử lý — bấm Lên xe, Xuống xe hoặc Vắng cho từng em trước khi hoàn thành chuyến.']);
    }

    public function test_complete_returns_200_when_all_students_processed(): void
    {
        $this->seed(RbacSeeder::class);
        $driver = $this->makeDriver('TX Complete OK');
        $execution = $this->startExecutionForDriver($driver);

        $logService = app(StudentLogService::class);
        foreach ($execution->studentLogs as $log) {
            $logService->markAbsent($log, 'no_notice', null, $driver->user_id);
        }

        $this->actingAs($driver->user);

        $this->postJson("/api/driver/tp-executions/{$execution->id}/complete", [
            'confirm_pending_board' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', TpTripExecution::STATUS_COMPLETED);

        $execution->refresh();
        $this->assertSame(TpTripExecution::STATUS_COMPLETED, $execution->status);
    }

    private function startExecutionForDriver(Driver $driver): TpTripExecution
    {
        $result = app(CreateTransportProgramAction::class)->execute([
            'name' => 'CT hoàn thành API',
            'departure_time' => '06:30',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'default_driver_id' => $driver->id,
        ], null);

        $program = TpProgram::findOrFail($result['program']['id']);
        $s1 = TpStudent::create(['code' => 'CP001', 'full_name' => 'HS A', 'status' => 'active']);
        $s2 = TpStudent::create(['code' => 'CP002', 'full_name' => 'HS B', 'status' => 'active']);
        app(ProgramEnrollmentService::class)->enrollBulk($program, [$s1->id, $s2->id], null);

        $day = $program->days()->orderBy('scheduled_date')->first();
        $day->forceFill([
            'confirmed_at' => now(),
            'confirmed_by_driver_id' => $driver->id,
        ])->save();

        return app(TripExecutionService::class)->start($day, $driver);
    }

    private function makeDriver(string $name): Driver
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('driver');

        return Driver::query()->create([
            'user_id' => $user->id,
            'full_name' => $name,
            'phone' => '0900'.random_int(100000, 999999),
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);
    }
}
