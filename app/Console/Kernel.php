<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void {
        $schedule->command('backups:run-scheduled')->hourly();

        $schedule->command('model:prune')->daily();

        $schedule->command('files:clean-storage')->daily();
        $schedule->command('clean-database')->daily();
        $schedule->command('auth:clear-resets')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void {
        $this->load(__DIR__ . '/Commands');
    }
}
