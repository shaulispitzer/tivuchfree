<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->prefix('ivr')
                ->name('ivr.')
                ->group(base_path('routes/ivr.php'));

            // if (app()->isLocal()) {
            Route::middleware('web')
                ->prefix('dev')
                ->name('dev.')
                ->group(base_path('routes/dev.php'));
            // }
        },
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('properties:process-lifecycle')->dailyAt('13:00')->days([0, 1, 2, 3, 4, 5]);
        $schedule->job(new \App\Jobs\ProcessExpiredPropertySubscriptions)->dailyAt('13:00')->days([0, 1, 2, 3, 4, 5]);
        $schedule->job(new \App\Jobs\PurgeStaleTemporaryUploads)->weekly();
        $schedule->job(new \App\Jobs\PurgeStalePropertySubscriptionPendings)->weekly();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->validateCsrfTokens(except: [
            'ivr',
            'ivr/*',
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            return back()
                ->withErrors([
                    'images' => 'The total upload size is too large. Please upload fewer images or smaller files.',
                ])
                ->withInput();
        });
    })->create();
