<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropertyListingStatusChange extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  'marked_as_taken'|'deleted'  $action
     * @param  'automatically'|'manually'  $method
     */
    public function __construct(
        public string $recipientName,
        public string $propertyAddress,
        public string $action,
        public string $method,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->action === 'deleted'
            ? __('mail.listing_status_change.subject_deleted')
            : __('mail.listing_status_change.subject_marked_as_taken');

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.property.listing-status-change',
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
