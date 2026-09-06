<?php

namespace App\Jobs;

use DateTimeInterface;
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
     * Fail only after real send exceptions. Rate-limit releases do not count.
     */
    public int $maxExceptions = 3;

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
     * Rate-limit releases consume attempts, so retry by time instead of a try count.
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->plus(hours: 2);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new RateLimited('resend'))->releaseAfter(2)];
    }

    public function handle(): void
    {
        App::setLocale($this->locale);

        Mail::to($this->to)->send($this->mailable);
    }
}
