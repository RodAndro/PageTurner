<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Backup tasks
        // Daily backup at 2:00 AM
        $schedule->command('backup:run')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Backup failed at ' . now()->toDateTimeString());
            })
            ->onSuccess(function () {
                Log::info('Backup completed successfully at ' . now()->toDateTimeString());
            });

        // Clean old backups at 3:00 AM
        $schedule->command('backup:clean')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Backup cleanup failed at ' . now()->toDateTimeString());
            });

        // Maintenance tasks
        // Cleanup pending orders every hour
        $schedule->command('order:cleanup-pending')
            ->hourly()
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Order cleanup failed at ' . now()->toDateTimeString());
            });

        // Clear expired sessions daily at 4:00 AM
        $schedule->command('session:cleanup')
            ->dailyAt('04:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Session cleanup failed at ' . now()->toDateTimeString());
            });

        // Rotate logs weekly (Mondays at 5:00 AM)
        $schedule->command('log:rotate')
            ->weeklyOn(1, '05:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Log rotation failed at ' . now()->toDateTimeString());
            });

        // Generate daily sales report at 6:00 AM
        $schedule->command('report:generate-daily')
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Daily report generation failed at ' . now()->toDateTimeString());
            })
            ->onSuccess(function () {
                Log::info('Daily report generated successfully at ' . now()->toDateTimeString());
            });

        // Prune old notifications weekly (Sundays at 7:00 AM)
        $schedule->command('notification:prune')
            ->weeklyOn(0, '07:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Notification pruning failed at ' . now()->toDateTimeString());
            });

        // Archive old audit logs monthly (First day of month at 1:00 AM)
        $schedule->command('audit:archive')
            ->monthlyOn(1, '01:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Audit archiving failed at ' . now()->toDateTimeString());
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
