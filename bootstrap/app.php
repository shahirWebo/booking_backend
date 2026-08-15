<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequestCorrelationId;
use App\Support\ApiExceptionResponse;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->useCache(config('scheduler.lock_store'));

        $schedule->command('queue:prune-failed --hours='.config('scheduler.failed_jobs_retention_hours'))
            ->dailyAt(config('scheduler.failed_jobs_prune_time'))
            ->timezone('UTC')
            ->name('queue:prune-failed')
            ->description('Prune failed queue jobs beyond the approved retention window.')
            ->onOneServer()
            ->withoutOverlapping(config('scheduler.mutex_expiration_minutes'));
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(RequestCorrelationId::class);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiExceptionResponse::from($exception);
            }
        });
    })->create();
