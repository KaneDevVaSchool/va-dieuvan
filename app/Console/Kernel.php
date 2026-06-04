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
        $schedule->command('route:generate-daily-trips')->dailyAt('22:00');
        $schedule->command('dispatch:remind-dept-approvals')->dailyAt('08:00');
        $schedule->command('dispatch:remind-signed-paper-upload')->dailyAt('08:15');
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
