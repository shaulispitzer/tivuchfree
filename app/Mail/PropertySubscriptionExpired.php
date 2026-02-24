<?php

namespace App\Mail;

use App\Models\PropertySubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropertySubscriptionExpired extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PropertySubscription $subscription,
        public string $propertiesUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your property subscription has expired',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.subscription.expired',
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
