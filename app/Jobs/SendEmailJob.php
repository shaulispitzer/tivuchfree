<?php

namespace App\Jobs;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Generous retry window so rate-limited releases don't exhaust attempts.
     */
    public int $tries = 20;

    private readonly string $locale;

    public function __construct(
        public readonly string $to,
        public readonly Mailable $mailable,
    ) {
        // Capture the locale at dispatch time so the queue worker renders
        // the email in the correct language regardless of its default locale.
        $this->locale = App::getLocale();
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('resend')];
    }

    public function handle(): void
    {
        App::setLocale($this->locale);

        Mail::to($this->to)->send($this->mailable);
    }
}
