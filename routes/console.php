<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:run')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onSuccess(fn () => Log::info('Scheduled backup completed successfully.'))
    ->onFailure(fn () => Log::error('Scheduled backup failed.'));

Schedule::command('backup:clean')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onSuccess(fn () => Log::info('Scheduled backup cleanup completed successfully.'))
    ->onFailure(fn () => Log::error('Scheduled backup cleanup failed.'));

Schedule::command('order:cleanup-pending')
    ->hourly()
    ->withoutOverlapping()
    ->onSuccess(fn () => Log::info('Pending order cleanup completed successfully.'))
    ->onFailure(fn () => Log::error('Pending order cleanup failed.'));

Schedule::command('session:cleanup')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->onSuccess(fn () => Log::info('Session cleanup completed successfully.'))
    ->onFailure(fn () => Log::error('Session cleanup failed.'));

Schedule::command('log:rotate')
    ->weeklyOn(0, '05:00')
    ->withoutOverlapping()
    ->onSuccess(fn () => Log::info('Log rotation completed successfully.'))
    ->onFailure(fn () => Log::error('Log rotation failed.'));

Schedule::command('report:generate-daily')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onSuccess(fn () => Log::info('Daily sales report completed successfully.'))
    ->onFailure(fn () => Log::error('Daily sales report failed.'));

Schedule::command('notification:prune')
    ->weeklyOn(0, '07:00')
    ->withoutOverlapping()
    ->onSuccess(fn () => Log::info('Notification pruning completed successfully.'))
    ->onFailure(fn () => Log::error('Notification pruning failed.'));

Schedule::command('audit:archive')
    ->monthlyOn(1, '01:00')
    ->withoutOverlapping()
    ->onSuccess(fn () => Log::info('Audit archive completed successfully.'))
    ->onFailure(fn () => Log::error('Audit archive failed.'));
