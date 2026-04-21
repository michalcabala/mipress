<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Cache;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Housekeeping
        $schedule->command('queue:prune-failed --hours=168')->daily();
        $schedule->command('queue:prune-batches --hours=48 --cancelled=72 --unfinished=72')->daily();

        // Scheduler health marker
        $schedule->call(fn () => Cache::put('scheduler:last-run', now()->toDateTimeString(), 3600))
            ->everyFiveMinutes();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
