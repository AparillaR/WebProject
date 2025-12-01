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
        $schedule->call(function () {
            $now = Carbon::now();
            $overdueCount = ReservationModel::where('status', 0)
                ->where('time_limit', '<', $now)
                ->update(['status' => 4]);
                
            \Log::info("Scheduler: Auto-cancelled {$overdueCount} overdue reservations");
        })->everyMinute();
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
