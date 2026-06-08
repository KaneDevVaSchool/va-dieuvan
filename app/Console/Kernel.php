<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('cargo:sla-check')->everyFiveMinutes();
        $schedule->command('dispatch:materialize-recurring-requests')->hourly();
        $schedule->command('dispatch:remind-dept-approvals')->dailyAt('08:00');
        $schedule->command('dispatch:remind-signed-paper-upload')->dailyAt('08:15');
        $schedule->command('tp:remind-driver-morning-shifts')->dailyAt('06:00')->timezone('Asia/Ho_Chi_Minh');
        $schedule->command('tp:remind-driver-afternoon-shifts')->dailyAt('14:00')->timezone('Asia/Ho_Chi_Minh');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
