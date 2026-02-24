<?php

namespace App\Mail;

use App\Models\Property;
use App\Models\PropertySubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropertySubscriptionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Property $property,
        public PropertySubscription $subscription,
        public string $propertyUrl,
        public string $unsubscribeUrl,
        public string $updateFiltersUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New property matching your subscription',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.subscription.notification',
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
