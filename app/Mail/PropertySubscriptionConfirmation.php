<?php

namespace App\Mail;

use App\Models\PropertySubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropertySubscriptionConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PropertySubscription $subscription,
        public string $unsubscribeUrl,
        public string $updateFiltersUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You are subscribed to property updates',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.subscription.confirmation',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
