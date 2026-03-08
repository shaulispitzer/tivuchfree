<?php

namespace App\Providers;

use App\Models\Property;
use App\Notifications\JobFailedNotification;
use App\Observers\PropertyObserver;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiters();
        $this->configureQueueEvents();

        Property::observe(PropertyObserver::class);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(5)
                ->letters()
                ->numbers()
                ->uncompromised()
            : null
        );
    }

    protected function configureRateLimiters(): void
    {
        RateLimiter::for('resend', fn (object $job): Limit => Limit::perSecond(1)->by('resend-global'));
    }

    protected function configureQueueEvents(): void
    {
        Queue::failing(function (\Illuminate\Queue\Events\JobFailed $event): void {
            try {
                Notification::route('mail', 'shaulispitzer@gmail.com')
                    ->notify(new JobFailedNotification($event));
            } catch (\Throwable) {
                // Prevent notification failures from cascading.
            }
        });
    }
}
