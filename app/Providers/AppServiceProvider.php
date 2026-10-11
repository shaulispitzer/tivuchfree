<?php

namespace App\Providers;

use App\Models\Property;
use App\Notifications\JobFailedNotification;
use App\Observers\PropertyObserver;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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
        $this->configureMailboxGate();

        // Must run after the route cache is loaded; registering during boot is
        // wiped when Laravel replaces the route collection from cache.
        $this->app->booted(function (): void {
            $this->ensureMailboxRoutesAreRegistered();
        });

        Property::observe(PropertyObserver::class);
    }

    /**
     * Allow the mailbox dashboard whenever MAIL_MAILER=mailbox so staging
     * reviewers (including guests) can inspect captured mail without admin access.
     */
    protected function configureMailboxGate(): void
    {
        Gate::define(config('mailbox.gate', 'viewMailbox'), static function ($user = null): bool {
            return config('mail.default') === 'mailbox';
        });
    }

    /**
     * Laravel Cloud may build the route cache before runtime env vars like
     * MAIL_MAILER=mailbox are available, so the package skips registering
     * /mailbox-test. Re-load those routes when the mailer is mailbox and the
     * named route is missing (e.g. stale route:cache).
     */
    protected function ensureMailboxRoutesAreRegistered(): void
    {
        if (config('mail.default') !== 'mailbox') {
            return;
        }

        $path = trim((string) config('mailbox.path', 'mailbox'), '/');

        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === $path) {
                return;
            }
        }

        $routes = base_path('vendor/redberry/mailbox-for-laravel/routes/mailbox.php');

        if (! is_file($routes)) {
            return;
        }

        config(['mailbox.enabled' => true]);

        require $routes;
    }

    /**
     * @internal Exposes route recovery for feature tests.
     */
    public function registerMailboxRoutesIfMissing(): void
    {
        $this->ensureMailboxRoutesAreRegistered();
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
