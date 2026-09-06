<?php

namespace App\Mail;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Fail only after real send exceptions. Rate-limit releases do not count.
     */
    public int $maxExceptions = 3;

    public function __construct(public User $user) {}

    /**
     * Rate-limit releases consume attempts, so retry by time instead of a try count.
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->plus(hours: 2);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.welcome.subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.welcome',
            with: [
                'userName' => $this->user->name,
                'browseUrl' => route('properties.index'),
                'createUrl' => route('properties.create'),
            ],
        );
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new RateLimited('resend'))->releaseAfter(2)];
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
