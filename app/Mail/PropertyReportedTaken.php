<?php

namespace App\Mail;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropertyReportedTaken extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Property $property) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.reported_taken.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.property.reported-taken',
            with: [
                'property' => $this->property,
                'editUrl' => route('properties.edit', $this->property),
                'daysToResolve' => 3,
            ],
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
