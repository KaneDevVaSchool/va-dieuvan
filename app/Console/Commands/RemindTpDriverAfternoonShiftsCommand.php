<?php

namespace App\Console\Commands;

use App\Models\TpProgramDay;
use App\Notifications\TpDriverAfternoonReminderNotification;
use App\Services\TransportProgram\TpProgramScheduleSlots;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RemindTpDriverAfternoonShiftsCommand extends Command
{
    protected $signature = 'tp:remind-driver-afternoon-shifts {--date= : Y-m-d (mặc định hôm nay)}';

    protected $description = 'Nhắc tài xế ca chiều chương trình đưa đón — xác nhận trên app';

    public function handle(TpProgramScheduleSlots $slots): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->toDateString()
            : Carbon::today('Asia/Ho_Chi_Minh')->toDateString();

        $days = TpProgramDay::query()
            ->with(['program.defaultDriver.user', 'driver.user'])
            ->where('day_type', TpProgramDay::DAY_OPERATING)
            ->whereDate('scheduled_date', $date)
            ->get();

        $sent = 0;

        foreach ($days as $day) {
            $program = $day->program;
            if ($program === null || $program->status !== 'active') {
                continue;
            }

            $afternoonSlot = collect($slots->slotsForProgram($program))
                ->firstWhere('shift', 'afternoon');
            if ($afternoonSlot === null || empty($afternoonSlot['departure_time'])) {
                continue;
            }

            if ($day->slotConfirmedAt('afternoon') !== null) {
                continue;
            }

            $driver = $day->effectiveDriver();
            if ($driver === null) {
                continue;
            }

            $user = $driver->user;
            if ($user === null) {
                continue;
            }

            if ($day->execution()->exists()) {
                continue;
            }

            $user->notify(new TpDriverAfternoonReminderNotification(
                programDayId: (int) $day->id,
                programName: (string) $program->name,
                scheduledDate: $day->scheduled_date->toDateString(),
                departureTime: (string) $afternoonSlot['departure_time'],
                expectedCount: (int) $day->expected_count,
            ));
            $sent++;
        }

        $this->info("Đã xếp {$sent} thông báo ca chiều cho ngày {$date}.");

        return self::SUCCESS;
    }
}
